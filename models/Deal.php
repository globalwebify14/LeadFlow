<?php
require_once __DIR__ . '/../config/db.php';

class Deal {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAllDeals($orgId, $filters = [], $limit = 20, $offset = 0) {
        $sql = "SELECT d.*, l.name as lead_name, l.phone as lead_phone, u.name as agent_name, ps.name as stage_name, ps.color as stage_color
                FROM deals d
                LEFT JOIN leads l ON d.lead_id = l.id
                LEFT JOIN users u ON d.assigned_to = u.id
                LEFT JOIN pipeline_stages ps ON d.stage_id = ps.id
                WHERE d.organization_id = :org_id";
        $params = [':org_id' => $orgId];

        if (!empty($filters['search'])) {
            $sql .= " AND (d.name LIKE :search OR l.name LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }
        if (!empty($filters['status'])) {
            $sql .= " AND d.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['stage_id'])) {
            $sql .= " AND d.stage_id = :stage_id";
            $params[':stage_id'] = $filters['stage_id'];
        }
        if (!empty($filters['assigned_to'])) {
            $sql .= " AND d.assigned_to = :assigned_to";
            $params[':assigned_to'] = $filters['assigned_to'];
        }

        $sql .= " ORDER BY d.id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => &$val) { $stmt->bindParam($key, $val); }
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getTotalCount($orgId, $filters = []) {
        $sql = "SELECT COUNT(*) FROM deals d LEFT JOIN leads l ON d.lead_id = l.id WHERE d.organization_id = :org_id";
        $params = [':org_id' => $orgId];
        if (!empty($filters['search'])) {
            $sql .= " AND (d.name LIKE :search OR l.name LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }
        if (!empty($filters['status'])) {
            $sql .= " AND d.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['stage_id'])) {
            $sql .= " AND d.stage_id = :stage_id";
            $params[':stage_id'] = $filters['stage_id'];
        }
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => &$val) { $stmt->bindParam($key, $val); }
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    public function getDealById($id, $orgId = null) {
        $sql = "SELECT d.*, l.name as lead_name, l.phone as lead_phone, l.email as lead_email, u.name as agent_name, ps.name as stage_name, ps.color as stage_color
                FROM deals d
                LEFT JOIN leads l ON d.lead_id = l.id
                LEFT JOIN users u ON d.assigned_to = u.id
                LEFT JOIN pipeline_stages ps ON d.stage_id = ps.id
                WHERE d.id = :id";
        $params = ['id' => $id];
        if ($orgId) {
            $sql .= " AND d.organization_id = :org_id";
            $params['org_id'] = $orgId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function createDeal($data) {
        $stmt = $this->pdo->prepare("INSERT INTO deals (organization_id, lead_id, name, value, stage_id, assigned_to, expected_close_date, description) VALUES (:org_id, :lead_id, :name, :value, :stage_id, :assigned_to, :close_date, :description)");
        $stmt->execute([
            'org_id' => $data['organization_id'],
            'lead_id' => $data['lead_id'] ?: null,
            'name' => $data['name'],
            'value' => $data['value'] ?? 0,
            'stage_id' => $data['stage_id'] ?: null,
            'assigned_to' => $data['assigned_to'] ?: null,
            'close_date' => $data['expected_close_date'] ?: null,
            'description' => $data['description'] ?? null,
        ]);
        $dealId = $this->pdo->lastInsertId();

        // Log activity
        $this->logActivity($dealId, 'note', 'Deal created', $data['user_id'] ?? null);

        return $dealId;
    }

    public function updateDeal($id, $data) {
        $current = $this->getDealById($id);
        $stmt = $this->pdo->prepare("UPDATE deals SET name=:name, lead_id=:lead_id, value=:value, stage_id=:stage_id, assigned_to=:assigned_to, expected_close_date=:close_date, status=:status, description=:description WHERE id=:id");
        $stmt->execute([
            'id' => $id,
            'name' => $data['name'],
            'lead_id' => $data['lead_id'] ?: null,
            'value' => $data['value'] ?? 0,
            'stage_id' => $data['stage_id'] ?: null,
            'assigned_to' => $data['assigned_to'] ?: null,
            'close_date' => $data['expected_close_date'] ?: null,
            'status' => $data['status'] ?? 'open',
            'description' => $data['description'] ?? null,
        ]);

        if ($current && $current['stage_id'] != $data['stage_id']) {
            $this->logActivity($id, 'stage_change', 'Stage changed', $data['user_id'] ?? null, $current['stage_name'] ?? '', $data['stage_id']);
        }
        if ($current && $current['value'] != $data['value']) {
            $this->logActivity($id, 'value_change', 'Value changed from ' . $current['value'] . ' to ' . $data['value'], $data['user_id'] ?? null, $current['value'], $data['value']);
        }
        return true;
    }

    public function deleteDeal($id) {
        $stmt = $this->pdo->prepare("DELETE FROM deals WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function updateStage($dealId, $stageId, $userId = null) {
        $stmt = $this->pdo->prepare("UPDATE deals SET stage_id = :stage_id WHERE id = :id");
        $result = $stmt->execute(['stage_id' => $stageId, 'id' => $dealId]);
        $this->logActivity($dealId, 'stage_change', 'Deal stage updated', $userId);
        return $result;
    }

    public function logActivity($dealId, $type, $description, $userId = null, $oldValue = null, $newValue = null) {
        $stmt = $this->pdo->prepare("INSERT INTO deal_activities (deal_id, user_id, activity_type, description, old_value, new_value) VALUES (:deal_id, :user_id, :type, :desc, :old, :new)");
        return $stmt->execute([
            'deal_id' => $dealId,
            'user_id' => $userId,
            'type' => $type,
            'desc' => $description,
            'old' => $oldValue,
            'new' => $newValue,
        ]);
    }

    public function getActivities($dealId) {
        $stmt = $this->pdo->prepare("SELECT da.*, u.name as user_name FROM deal_activities da LEFT JOIN users u ON da.user_id = u.id WHERE da.deal_id = :deal_id ORDER BY da.created_at DESC");
        $stmt->execute(['deal_id' => $dealId]);
        return $stmt->fetchAll();
    }

    public function getDealsByStage($orgId) {
        $stmt = $this->pdo->prepare(
            "SELECT ps.id as stage_id, ps.name as stage_name, ps.color, ps.position, ps.is_won, ps.is_lost,
                    d.id, d.name, d.value, d.status, d.expected_close_date, d.lead_id,
                    l.name as lead_name, u.name as agent_name
             FROM pipeline_stages ps
             LEFT JOIN deals d ON d.stage_id = ps.id AND d.status = 'open'
             LEFT JOIN leads l ON d.lead_id = l.id
             LEFT JOIN users u ON d.assigned_to = u.id
             WHERE ps.organization_id = :org_id
             ORDER BY ps.position, d.updated_at DESC"
        );
        $stmt->execute(['org_id' => $orgId]);
        $rows = $stmt->fetchAll();

        $stages = [];
        foreach ($rows as $row) {
            $sid = $row['stage_id'];
            if (!isset($stages[$sid])) {
                $stages[$sid] = [
                    'id' => $sid,
                    'name' => $row['stage_name'],
                    'color' => $row['color'],
                    'position' => $row['position'],
                    'is_won' => $row['is_won'],
                    'is_lost' => $row['is_lost'],
                    'deals' => [],
                    'total_value' => 0,
                ];
            }
            if ($row['id']) {
                $stages[$sid]['deals'][] = [
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'value' => $row['value'],
                    'lead_name' => $row['lead_name'],
                    'agent_name' => $row['agent_name'],
                    'expected_close_date' => $row['expected_close_date'],
                ];
                $stages[$sid]['total_value'] += $row['value'];
            }
        }
        return array_values($stages);
    }

    public function getRevenueStats($orgId, $userId = null) {
        $stats = [];
        $params = ['org' => $orgId];
        $userFilter = "";
        
        if ($userId) {
            $userFilter = " AND assigned_to = :user_id";
            $params['user_id'] = $userId;
        }

        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(value), 0) FROM deals WHERE organization_id = :org AND status = 'won'" . $userFilter);
        $stmt->execute($params);
        $stats['won_revenue'] = $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(value), 0) FROM deals WHERE organization_id = :org AND status = 'open'" . $userFilter);
        $stmt->execute($params);
        $stats['pipeline_value'] = $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM deals WHERE organization_id = :org AND status = 'won'" . $userFilter);
        $stmt->execute($params);
        $won = $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM deals WHERE organization_id = :org AND status IN ('won','lost')" . $userFilter);
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        $stats['win_rate'] = $total > 0 ? round(($won / $total) * 100, 1) : 0;
        return $stats;
    }
}
?>
