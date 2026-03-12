<?php
/**
 * AssignmentRule Model — Manages lead distribution rules
 */
class AssignmentRule {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all assignment rules for an organization
     */
    public function getAll($orgId) {
        $stmt = $this->pdo->prepare("SELECT * FROM assignment_rules WHERE organization_id = :org ORDER BY created_at DESC");
        $stmt->execute(['org' => $orgId]);
        return $stmt->fetchAll();
    }

    /**
     * Get a single rule by ID
     */
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM assignment_rules WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Create a new assignment rule
     */
    public function create($data) {
        $stmt = $this->pdo->prepare("INSERT INTO assignment_rules (organization_id, name, type, source_filter, agent_ids, is_active) VALUES (:org, :name, :type, :source, :agents, :active)");
        return $stmt->execute([
            'org'    => $data['organization_id'],
            'name'   => $data['name'],
            'type'   => $data['type'],
            'source' => $data['source_filter'] ?: null,
            'agents' => $data['agent_ids'],
            'active' => $data['is_active'] ?? 1,
        ]);
    }

    /**
     * Update an assignment rule
     */
    public function update($id, $data) {
        $stmt = $this->pdo->prepare("UPDATE assignment_rules SET name = :name, type = :type, source_filter = :source, agent_ids = :agents, is_active = :active WHERE id = :id");
        return $stmt->execute([
            'id'     => $id,
            'name'   => $data['name'],
            'type'   => $data['type'],
            'source' => $data['source_filter'] ?: null,
            'agents' => $data['agent_ids'],
            'active' => $data['is_active'] ?? 1,
        ]);
    }

    /**
     * Delete an assignment rule
     */
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM assignment_rules WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Toggle rule active/inactive
     */
    public function toggleActive($id) {
        $stmt = $this->pdo->prepare("UPDATE assignment_rules SET is_active = NOT is_active WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Auto-assign a lead using active rules
     * Returns the assigned user ID or null
     */
    public function autoAssignLead($orgId, $leadSource = null) {
        // First try source-based rules
        if ($leadSource) {
            $stmt = $this->pdo->prepare("SELECT * FROM assignment_rules WHERE organization_id = :org AND is_active = 1 AND type = 'source_based' AND source_filter = :source LIMIT 1");
            $stmt->execute(['org' => $orgId, 'source' => $leadSource]);
            $rule = $stmt->fetch();
            if ($rule) {
                return $this->assignFromRule($rule);
            }
        }

        // Fallback to round-robin
        $stmt = $this->pdo->prepare("SELECT * FROM assignment_rules WHERE organization_id = :org AND is_active = 1 AND type = 'round_robin' ORDER BY id ASC LIMIT 1");
        $stmt->execute(['org' => $orgId]);
        $rule = $stmt->fetch();
        if ($rule) {
            return $this->assignFromRule($rule);
        }

        return null;
    }

    /**
     * Assign from a specific rule (round-robin logic)
     */
    private function assignFromRule($rule) {
        $agentIds = json_decode($rule['agent_ids'], true);
        if (empty($agentIds)) return null;

        // Round-robin: use last_assigned_index
        $nextIndex = ($rule['last_assigned_index'] + 1) % count($agentIds);
        $assignedUserId = $agentIds[$nextIndex];

        // Update the index
        $stmt = $this->pdo->prepare("UPDATE assignment_rules SET last_assigned_index = :idx WHERE id = :id");
        $stmt->execute(['idx' => $nextIndex, 'id' => $rule['id']]);

        return $assignedUserId;
    }

    /**
     * Bulk reassign leads from one agent to another
     */
    public function reassignLeads($orgId, $fromUserId, $toUserId) {
        $stmt = $this->pdo->prepare("UPDATE leads SET assigned_to = :to_user, updated_at = NOW() WHERE organization_id = :org AND assigned_to = :from_user");
        $stmt->execute(['org' => $orgId, 'to_user' => $toUserId, 'from_user' => $fromUserId]);
        return $stmt->rowCount();
    }

    /**
     * Get lead count per agent
     */
    public function getLeadCountPerAgent($orgId) {
        $stmt = $this->pdo->prepare("SELECT u.id, u.name, COUNT(l.id) as lead_count FROM users u LEFT JOIN leads l ON l.assigned_to = u.id AND l.organization_id = :org WHERE u.organization_id = :org2 AND u.is_active = 1 AND u.role IN ('agent','manager') GROUP BY u.id, u.name ORDER BY lead_count DESC");
        $stmt->execute(['org' => $orgId, 'org2' => $orgId]);
        return $stmt->fetchAll();
    }

    /**
     * Get unassigned lead count
     */
    public function getUnassignedCount($orgId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM leads WHERE organization_id = :org AND assigned_to IS NULL");
        $stmt->execute(['org' => $orgId]);
        return $stmt->fetchColumn();
    }
}
?>
