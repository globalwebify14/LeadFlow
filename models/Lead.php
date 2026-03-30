<?php
require_once __DIR__ . '/../config/db.php';

class Lead {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all leads with advanced filtering
     */
    public function getAllLeads($orgId, $filters = [], $limit = 10, $offset = 0) {
        $sql = "SELECT l.*, u.name as agent_name, ps.name as stage_name, ps.color as stage_color
                FROM leads l
                LEFT JOIN users u ON l.assigned_to = u.id
                LEFT JOIN pipeline_stages ps ON l.pipeline_stage_id = ps.id
                WHERE l.organization_id = :org_id";
        $params = [':org_id' => $orgId];

        // Search filter
        if (!empty($filters['search'])) {
            $sql .= " AND (l.name LIKE :search OR l.phone LIKE :search OR l.email LIKE :search OR l.company LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }

        // Status filter
        if (!empty($filters['status'])) {
            $sql .= " AND l.status = :status";
            $params[':status'] = $filters['status'];
        }

        // Priority filter
        if (!empty($filters['priority'])) {
            $sql .= " AND l.priority = :priority";
            $params[':priority'] = $filters['priority'];
        }

        // Source filter
        if (!empty($filters['source'])) {
            $sql .= " AND l.source = :source";
            $params[':source'] = $filters['source'];
        }

        // Assigned agent filter (enforced or optional)
        if (!empty($filters['enforce_assigned_to'])) {
            $sql .= " AND l.assigned_to = :assigned_to";
            $params[':assigned_to'] = $filters['enforce_assigned_to'];
        } elseif (!empty($filters['assigned_to'])) {
            $sql .= " AND l.assigned_to = :assigned_to";
            $params[':assigned_to'] = $filters['assigned_to'];
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(l.created_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(l.created_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        // Tag filter
        if (!empty($filters['tag_id'])) {
            $sql .= " AND l.id IN (SELECT lead_id FROM lead_tag_map WHERE tag_id = :tag_id)";
            $params[':tag_id'] = $filters['tag_id'];
        }

        // Facebook Page filter
        if (!empty($filters['facebook_page_id'])) {
            $sql .= " AND l.facebook_page_id = :fb_page_id";
            $params[':fb_page_id'] = $filters['facebook_page_id'];
        }

        $sql .= " ORDER BY l.created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Count leads with filters
     */
    public function getTotalLeadsCount($orgId, $filters = []) {
        $sql = "SELECT COUNT(*) as total FROM leads l WHERE l.organization_id = :org_id";
        $params = [':org_id' => $orgId];

        if (!empty($filters['search'])) {
            $sql .= " AND (l.name LIKE :search OR l.phone LIKE :search OR l.email LIKE :search OR l.company LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }
        if (!empty($filters['status'])) {
            $sql .= " AND l.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['priority'])) {
            $sql .= " AND l.priority = :priority";
            $params[':priority'] = $filters['priority'];
        }
        if (!empty($filters['source'])) {
            $sql .= " AND l.source = :source";
            $params[':source'] = $filters['source'];
        }
        if (!empty($filters['enforce_assigned_to'])) {
            $sql .= " AND l.assigned_to = :assigned_to";
            $params[':assigned_to'] = $filters['enforce_assigned_to'];
        } elseif (!empty($filters['assigned_to'])) {
            $sql .= " AND l.assigned_to = :assigned_to";
            $params[':assigned_to'] = $filters['assigned_to'];
        }
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(l.created_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(l.created_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        if (!empty($filters['tag_id'])) {
            $sql .= " AND l.id IN (SELECT lead_id FROM lead_tag_map WHERE tag_id = :tag_id)";
            $params[':tag_id'] = $filters['tag_id'];
        }
        if (!empty($filters['facebook_page_id'])) {
            $sql .= " AND l.facebook_page_id = :fb_page_id";
            $params[':fb_page_id'] = $filters['facebook_page_id'];
        }

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ? $row['total'] : 0;
    }

    /**
     * Get single lead by ID
     */
    public function getLeadById($id, $orgId = null) {
        $sql = "SELECT l.*, u.name as agent_name 
                FROM leads l 
                LEFT JOIN users u ON l.assigned_to = u.id 
                WHERE l.id = :id";
        $params = ['id' => $id];
        if ($orgId) {
            $sql .= " AND l.organization_id = :org_id";
            $params['org_id'] = $orgId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    /**
     * Determine agent assignment based on Org settings (Round Robin or Manual)
     */
    public function getAutoAssignAgentId($orgId) {
        // Check organization assignment mode
        $stmtOrg = $this->pdo->prepare("SELECT assignment_mode FROM organizations WHERE id = ?");
        $stmtOrg->execute([$orgId]);
        $mode = $stmtOrg->fetchColumn();

        if ($mode === 'auto') {
            // Fetch all active agents for this org, ordered by last assigned time (Round Robin logic)
            // Try with availability_status first
            try {
                $sql = "SELECT u.id 
                        FROM users u 
                        WHERE u.organization_id = :org_id 
                          AND u.role = 'agent' 
                          AND u.is_active = 1
                          AND u.availability_status = 'active'
                        ORDER BY (
                            SELECT COALESCE(MAX(id), 0) 
                            FROM leads 
                            WHERE assigned_to = u.id
                        ) ASC 
                        LIMIT 1";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(['org_id' => $orgId]);
                $agentId = $stmt->fetchColumn();
                return $agentId ?: null;
            } catch (PDOException $e) {
                // Fallback if availability_status column doesn't exist on live DB yet
                $sql = "SELECT u.id 
                        FROM users u 
                        WHERE u.organization_id = :org_id 
                          AND u.role = 'agent' 
                          AND u.is_active = 1
                        ORDER BY (
                            SELECT COALESCE(MAX(id), 0) 
                            FROM leads 
                            WHERE assigned_to = u.id
                        ) ASC 
                        LIMIT 1";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute(['org_id' => $orgId]);
                $agentId = $stmt->fetchColumn();
                return $agentId ?: null;
            }
        }

        return null;
    }

    /**
     * Add a new lead
     */
    public function addLead($data) {
        $startedTransaction = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $startedTransaction = true;
        }

        try {
            // Determine assignment
            $assignedTo = $data['assigned_to'] ?: null;

            // If not manually specified (e.g., from webhook), try auto-assign
            if (!$assignedTo && empty($data['ignore_auto_assign'])) {
                $assignedTo = $this->getAutoAssignAgentId($data['organization_id']);
            }

            $stmt = $this->pdo->prepare("INSERT INTO leads (organization_id, name, phone, email, company, source, status, priority, assigned_to, note, meta_campaign, meta_form_id, facebook_page_id, created_at) 
                VALUES (:org_id, :name, :phone, :email, :company, :source, :status, :priority, :assigned_to, :note, :meta_campaign, :meta_form_id, :facebook_page_id, :created_at)");
            $stmt->execute([
                'org_id'           => $data['organization_id'],
                'name'             => $data['name'],
                'phone'            => $data['phone'],
                'email'            => $data['email'] ?? null,
                'company'          => $data['company'] ?? null,
                'source'           => $data['source'] ?? null,
                'status'           => $data['status'] ?? 'New Lead',
                'priority'         => $data['priority'] ?? 'Warm',
                'assigned_to'      => $assignedTo,
                'note'             => $data['note'] ?? null,
                'meta_campaign'    => $data['meta_campaign'] ?? null,
                'meta_form_id'     => $data['meta_form_id'] ?? null,
                'facebook_page_id' => $data['facebook_page_id'] ?? null,
                'created_at'       => $data['created_at'] ?? date('Y-m-d H:i:s')
            ]);
            $leadId = $this->pdo->lastInsertId();

            // Sync pipeline stage based on status
            $this->syncPipelineStageWithStatus($leadId, $data['status'] ?? 'New Lead', $data['organization_id']);

            // Notify assigned agent directly inside generic addLead if auto-assigned or manually assigned to someone else
            if ($assignedTo && (!isset($data['user_id']) || $data['user_id'] != $assignedTo)) {
                require_once __DIR__ . '/Notification.php';
                $notifier = new Notification($this->pdo);
                $notifier->create(
                    $data['organization_id'], 
                    $assignedTo, 
                    'lead_assigned', 
                    'New Lead Assigned: ' . $data['name'], 
                    "You have been assigned a new lead from " . ($data['source'] ?? 'Direct Entry') . ".", 
                    BASE_URL . "modules/leads/view.php?id={$leadId}"
                );
            }

            // Log initial activity
            $this->logActivity($leadId, 'status_change', 'Lead created with status: ' . ($data['status'] ?? 'New Lead'), null, $data['status'] ?? 'New Lead', $data['user_id'] ?? null);

            if ($assignedTo) {
                 $this->logActivity($leadId, 'assignment', 'Lead assigned upon creation', null, $assignedTo, $data['user_id'] ?? null);
            }

            // Add initial note if present
            if (!empty($data['note'])) {
                $this->addNote($leadId, $data['note'], $data['user_id'] ?? null);
            }

            // Handle tags
            if (!empty($data['tags'])) {
                $this->syncTags($leadId, $data['tags']);
            }

            // Start Automation Sequence if active for org
            $stmtAuto = $this->pdo->prepare("SELECT id FROM automation_sequences WHERE organization_id = :org AND is_active = 1 LIMIT 1");
            $stmtAuto->execute(['org' => $data['organization_id']]);
            $sequenceId = $stmtAuto->fetchColumn();
            if ($sequenceId) {
                // Log activity
                $this->logActivity($leadId, 'status_change', 'Automation sequence started', null, null, $data['user_id'] ?? null);
                // Start tracking progress
                $stmtProg = $this->pdo->prepare("INSERT INTO lead_automation_progress (lead_id, sequence_id) VALUES (:lead, :seq)");
                $stmtProg->execute(['lead' => $leadId, 'seq' => $sequenceId]);
            }

            if ($startedTransaction) {
                $this->pdo->commit();
            }
            return $leadId;
        } catch (Exception $e) {
            error_log("CRITICAL CRM LEAD INSERT ERROR: " . $e->getMessage());
            // Attempt to aggressively write to the facebook_sync log file in case of webhook failure
            $logPath = __DIR__ . '/../modules/facebook_integration/logs/facebook_sync.log';
            if (is_writable(dirname($logPath))) {
                file_put_contents($logPath, date('Y-m-d H:i:s') . ' - [FATAL PIPELINE REJECTION] ' . $e->getMessage() . "\n", FILE_APPEND);
            }
            if ($startedTransaction) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    /**
     * Update a lead
     */
    public function updateLead($id, $data) {
        $currentLead = $this->getLeadById($id);
        
        $startedTransaction = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $startedTransaction = true;
        }

        try {
            $stmt = $this->pdo->prepare("UPDATE leads SET name=:name, phone=:phone, email=:email, company=:company, source=:source, status=:status, priority=:priority, assigned_to=:assigned_to, note=:note WHERE id=:id");
            $stmt->execute([
                'id'          => $id,
                'name'        => $data['name'],
                'phone'       => $data['phone'],
                'email'       => $data['email'] ?? null,
                'company'     => $data['company'] ?? null,
                'source'      => $data['source'] ?? null,
                'status'      => $data['status'] ?? 'New Lead',
                'priority'    => $data['priority'] ?? 'Warm',
                'assigned_to' => $data['assigned_to'] ?: null,
                'note'        => $data['note'] ?? null,
            ]);

            // Sync pipeline stage if status changed
            if ($currentLead && $currentLead['status'] !== $data['status']) {
                $this->syncPipelineStageWithStatus($id, $data['status'], $currentLead['organization_id']);
            }

            // Log status change
            if ($currentLead && $currentLead['status'] !== $data['status']) {
                $this->logActivity($id, 'status_change', 'Status changed from ' . $currentLead['status'] . ' to ' . $data['status'], $currentLead['status'], $data['status'], $data['user_id'] ?? null);
            }

            // Log assignment change
            if ($currentLead && $currentLead['assigned_to'] != ($data['assigned_to'] ?: null)) {
                $this->logActivity($id, 'assignment', 'Lead reassigned', $currentLead['assigned_to'], $data['assigned_to'] ?: null, $data['user_id'] ?? null);
            }

            // Sync tags
            if (isset($data['tags'])) {
                $this->syncTags($id, $data['tags']);
            }

            if ($startedTransaction) {
                $this->pdo->commit();
            }
            return true;
        } catch (Exception $e) {
            if ($startedTransaction) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    /**
     * Delete a lead
     */
    public function deleteLead($id) {
        $stmt = $this->pdo->prepare("DELETE FROM leads WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Update lead status
     */
    public function updateStatus($id, $status, $note = '', $userId = null) {
        $currentLead = $this->getLeadById($id);
        
        $startedTransaction = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $startedTransaction = true;
        }

        try {
            $stmt = $this->pdo->prepare("UPDATE leads SET status=:status WHERE id=:id");
            $stmt->execute(['status' => $status, 'id' => $id]);

            // Sync pipeline stage
            $this->syncPipelineStageWithStatus($id, $status, $currentLead['organization_id']);

            $desc = $note ?: 'Status changed from ' . ($currentLead['status'] ?? '') . ' to ' . $status;
            $this->logActivity($id, 'status_change', $desc, $currentLead['status'] ?? null, $status, $userId);

            if ($startedTransaction) {
                $this->pdo->commit();
            }
            return true;
        } catch (Exception $e) {
            if ($startedTransaction) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    /**
     * Helper to sync pipeline stage ID based on status name
     */
    private function syncPipelineStageWithStatus($leadId, $status, $orgId) {
        $mapping = [
            'Working'   => 'Contacted',
            'Follow Up' => 'Contacted',
            'Done'      => 'Closed Won',
            'Rejected'  => 'Closed Lost'
        ];

        $stageName = $status ?: 'New Lead';
        $mappedName = $mapping[$status] ?? null;

        // 1. Try to find stage matching status name directly
        $stmt = $this->pdo->prepare("SELECT id FROM pipeline_stages WHERE organization_id = :org AND TRIM(LOWER(name)) = LOWER(:name) LIMIT 1");
        $stmt->execute(['org' => $orgId, 'name' => trim($stageName)]);
        $stageId = $stmt->fetchColumn();

        // 2. If not found, try the mapping name
        if (!$stageId && $mappedName) {
            $stmt->execute(['org' => $orgId, 'name' => trim($mappedName)]);
            $stageId = $stmt->fetchColumn();
        }

        // 3. FALLBACK: Use the first stage if still not found
        if (!$stageId) {
            $firstStmt = $this->pdo->prepare("SELECT id FROM pipeline_stages WHERE organization_id = :org ORDER BY position ASC LIMIT 1");
            $firstStmt->execute(['org' => $orgId]);
            $stageId = $firstStmt->fetchColumn();
        }

        if ($stageId) {
            $update = $this->pdo->prepare("UPDATE leads SET pipeline_stage_id = :stage WHERE id = :id");
            $update->execute(['stage' => $stageId, 'id' => $leadId]);
        }
    }

    /**
     * Finds leads with missing pipeline stage IDs and repairs them.
     */
    public function repairOrphanedLeads($orgId) {
        // Limit to 50 at a time to prevent heavy loads on large orgs
        $stmt = $this->pdo->prepare("SELECT id, status FROM leads WHERE organization_id = :org AND (pipeline_stage_id IS NULL OR pipeline_stage_id = 0) LIMIT 50");
        $stmt->execute(['org' => $orgId]);
        $orphans = $stmt->fetchAll();

        foreach ($orphans as $orphan) {
            $this->syncPipelineStageWithStatus($orphan['id'], $orphan['status'], $orgId);
        }
    }

    /**
     * Gets pipeline stages for an organization, or initializes defaults if none exist.
     */
    public function getOrInitializeStages($orgId) {
        $stmt = $this->pdo->prepare("SELECT * FROM pipeline_stages WHERE organization_id = :org ORDER BY position");
        $stmt->execute(['org' => $orgId]);
        $stages = $stmt->fetchAll();

        if (empty($stages)) {
            // Define default stages
            $defaults = [
                ['name' => 'New Lead', 'color' => '#3b82f6', 'pos' => 1],
                ['name' => 'Contacted', 'color' => '#6366f1', 'pos' => 2],
                ['name' => 'Qualified', 'color' => '#8b5cf6', 'pos' => 3],
                ['name' => 'Negotiation', 'color' => '#f59e0b', 'pos' => 4],
                ['name' => 'Closed Won', 'color' => '#10b981', 'pos' => 5],
                ['name' => 'Closed Lost', 'color' => '#ef4444', 'pos' => 6]
            ];

            foreach ($defaults as $d) {
                $ins = $this->pdo->prepare("INSERT INTO pipeline_stages (organization_id, name, color, position) VALUES (:org, :name, :color, :pos)");
                $ins->execute([
                    'org'   => $orgId,
                    'name'  => $d['name'],
                    'color' => $d['color'],
                    'pos'   => $d['pos']
                ]);
            }

            // Fetch again after insertion
            $stmt->execute(['org' => $orgId]);
            $stages = $stmt->fetchAll();
        }

        // Proactive Repair: Ensure leads are mapped to stages
        $this->repairOrphanedLeads($orgId);

        return $stages;
    }

    /**
     * Log an activity for a lead
     */
    public function logActivity($leadId, $type, $description, $oldValue = null, $newValue = null, $userId = null) {
        $stmt = $this->pdo->prepare("INSERT INTO lead_activities (lead_id, user_id, activity_type, description, old_value, new_value) VALUES (:lead_id, :user_id, :type, :desc, :old, :new)");
        return $stmt->execute([
            'lead_id' => $leadId,
            'user_id' => $userId,
            'type'    => $type,
            'desc'    => $description,
            'old'     => $oldValue,
            'new'     => $newValue,
        ]);
    }

    /**
     * Add a note to a lead
     */
    public function addNote($leadId, $note, $userId = null) {
        $stmt = $this->pdo->prepare("INSERT INTO lead_notes (lead_id, user_id, note) VALUES (:lead_id, :user_id, :note)");
        $result = $stmt->execute([
            'lead_id' => $leadId,
            'user_id' => $userId,
            'note'    => $note,
        ]);
        // Also log as activity
        $this->logActivity($leadId, 'note', $note, null, null, $userId);
        return $result;
    }

    /**
     * Get activities for a lead
     */
    public function getActivities($leadId) {
        $stmt = $this->pdo->prepare("SELECT la.*, u.name as user_name FROM lead_activities la LEFT JOIN users u ON la.user_id = u.id WHERE la.lead_id = :lead_id ORDER BY la.created_at DESC");
        $stmt->execute(['lead_id' => $leadId]);
        return $stmt->fetchAll();
    }

    /**
     * Get notes for a lead
     */
    public function getNotes($leadId) {
        $stmt = $this->pdo->prepare("SELECT ln.*, u.name as user_name FROM lead_notes ln LEFT JOIN users u ON ln.user_id = u.id WHERE ln.lead_id = :lead_id ORDER BY ln.created_at DESC");
        $stmt->execute(['lead_id' => $leadId]);
        return $stmt->fetchAll();
    }

    /**
     * Get tags for a lead
     */
    public function getTags($leadId) {
        $stmt = $this->pdo->prepare("SELECT t.* FROM lead_tags t INNER JOIN lead_tag_map ltm ON t.id = ltm.tag_id WHERE ltm.lead_id = :lead_id");
        $stmt->execute(['lead_id' => $leadId]);
        return $stmt->fetchAll();
    }

    /**
     * Sync tags for a lead
     */
    public function syncTags($leadId, $tagIds) {
        // Remove all existing
        $this->pdo->prepare("DELETE FROM lead_tag_map WHERE lead_id = ?")->execute([$leadId]);
        // Add new ones
        if (!empty($tagIds)) {
            $stmt = $this->pdo->prepare("INSERT INTO lead_tag_map (lead_id, tag_id) VALUES (?, ?)");
            foreach ($tagIds as $tagId) {
                $stmt->execute([$leadId, $tagId]);
            }
        }
    }

    /**
     * Get all tags for an organization
     */
    public function getOrgTags($orgId) {
        $stmt = $this->pdo->prepare("SELECT * FROM lead_tags WHERE organization_id = :org_id ORDER BY name");
        $stmt->execute(['org_id' => $orgId]);
        return $stmt->fetchAll();
    }

    /**
     * Find duplicates by phone or email
     */
    public function findDuplicates($orgId, $phone, $email = null, $excludeId = null) {
        $sql = "SELECT * FROM leads WHERE organization_id = :org_id AND (phone = :phone";
        $params = ['org_id' => $orgId, 'phone' => $phone];
        
        if (!empty($email)) {
            $sql .= " OR email = :email";
            $params['email'] = $email;
        }
        $sql .= ")";
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Bulk update status
     */
    public function bulkUpdateStatus($ids, $status, $userId = null) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("UPDATE leads SET status = ? WHERE id IN ($placeholders)");
        $params = array_merge([$status], $ids);
        $result = $stmt->execute($params);
        
        // Log activities and Sync pipeline stages
        foreach ($ids as $id) {
            $this->logActivity($id, 'status_change', "Bulk status change to $status", null, $status, $userId);
            
            // Get lead to know its org_id for sync (or we could assume all leads in $ids belong to same org if coming from list view, but safer to check)
            $lead = $this->getLeadById($id);
            if ($lead) {
                $this->syncPipelineStageWithStatus($id, $status, $lead['organization_id']);
            }
        }
        return $result;
    }

    /**
     * Bulk delete leads
     */
    public function bulkDelete($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("DELETE FROM leads WHERE id IN ($placeholders)");
        return $stmt->execute($ids);
    }

    /**
     * Bulk assign leads to agent
     */
    public function bulkAssign($ids, $agentId, $userId = null) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare("UPDATE leads SET assigned_to = ? WHERE id IN ($placeholders)");
        $params = array_merge([$agentId], $ids);
        $result = $stmt->execute($params);

        foreach ($ids as $id) {
            $this->logActivity($id, 'assignment', 'Lead assigned via bulk action', null, $agentId, $userId);
        }
        return $result;
    }

    /**
     * Get distinct sources for filter dropdown
     */
    public function getSources($orgId) {
        $stmt = $this->pdo->prepare("SELECT DISTINCT source FROM leads WHERE organization_id = :org_id AND source IS NOT NULL AND source != '' ORDER BY source");
        $stmt->execute(['org_id' => $orgId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Get leads count by status for dashboard
     */
    public function getCountByStatus($orgId) {
        $stmt = $this->pdo->prepare("SELECT status, COUNT(*) as count FROM leads WHERE organization_id = :org_id GROUP BY status");
        $stmt->execute(['org_id' => $orgId]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Get leads count by priority
     */
    public function getCountByPriority($orgId) {
        $stmt = $this->pdo->prepare("SELECT priority, COUNT(*) as count FROM leads WHERE organization_id = :org_id GROUP BY priority");
        $stmt->execute(['org_id' => $orgId]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Get monthly lead growth (last 6 months)
     */
    public function getMonthlyGrowth($orgId) {
        $stmt = $this->pdo->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
            FROM leads WHERE organization_id = :org_id AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
            GROUP BY month ORDER BY month");
        $stmt->execute(['org_id' => $orgId]);
        return $stmt->fetchAll();
    }

    /**
     * Get leads for pipeline view
     */
    public function getLeadsByStage($orgId, $stageId, $userId = null, $dateFrom = null, $dateTo = null) {
        $sql = "SELECT l.*, u.name as agent_name FROM leads l LEFT JOIN users u ON l.assigned_to = u.id WHERE l.organization_id = :org_id AND l.pipeline_stage_id = :stage_id";
        $params = ['org_id' => $orgId, 'stage_id' => $stageId];
        if ($userId) {
            $sql .= " AND l.assigned_to = :user_id";
            $params['user_id'] = $userId;
        }
        if ($dateFrom) {
            $sql .= " AND DATE(l.created_at) >= :date_from";
            $params['date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND DATE(l.created_at) <= :date_to";
            $params['date_to'] = $dateTo;
        }
        $sql .= " ORDER BY l.updated_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function updatePipelineStage($leadId, $stageId, $userId = null) {
        $startedTransaction = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $startedTransaction = true;
        }

        try {
            // Get stage name to update status string
            $stmtStage = $this->pdo->prepare("SELECT name FROM pipeline_stages WHERE id = ?");
            $stmtStage->execute([$stageId]);
            $stageName = $stmtStage->fetchColumn();

            if ($stageName) {
                $stmt = $this->pdo->prepare("UPDATE leads SET pipeline_stage_id = :stage_id, status = :status WHERE id = :id");
                $result = $stmt->execute(['stage_id' => $stageId, 'status' => $stageName, 'id' => $leadId]);
                $this->logActivity($leadId, 'status_change', "Moved to pipeline stage: $stageName", null, $stageName, $userId);
            } else {
                $stmt = $this->pdo->prepare("UPDATE leads SET pipeline_stage_id = :stage_id WHERE id = :id");
                $result = $stmt->execute(['stage_id' => $stageId, 'id' => $leadId]);
                $this->logActivity($leadId, 'status_change', 'Pipeline stage updated', null, $stageId, $userId);
            }

            if ($startedTransaction) {
                $this->pdo->commit();
            }
            return $result;
        } catch (Exception $e) {
            if ($startedTransaction) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    /**
     * Get filtered leads for export without pagination
     */
    public function getFilteredLeadsForExport($orgId, $filters = []) {
        $sql = "SELECT l.*, u.name as agent_name, ps.name as stage_name
                FROM leads l
                LEFT JOIN users u ON l.assigned_to = u.id
                LEFT JOIN pipeline_stages ps ON l.pipeline_stage_id = ps.id
                WHERE l.organization_id = :org_id";
        $params = [':org_id' => $orgId];

        // Search filter
        if (!empty($filters['search'])) {
            $sql .= " AND (l.name LIKE :search OR l.phone LIKE :search OR l.email LIKE :search OR l.company LIKE :search)";
            $params[':search'] = "%" . $filters['search'] . "%";
        }

        // Status filter
        if (!empty($filters['status'])) {
            $sql .= " AND l.status = :status";
            $params[':status'] = $filters['status'];
        }

        // Priority filter
        if (!empty($filters['priority'])) {
            $sql .= " AND l.priority = :priority";
            $params[':priority'] = $filters['priority'];
        }

        // Source filter
        if (!empty($filters['source'])) {
            $sql .= " AND l.source = :source";
            $params[':source'] = $filters['source'];
        }

        // Assigned agent filter
        if (!empty($filters['enforce_assigned_to'])) {
            $sql .= " AND l.assigned_to = :assigned_to";
            $params[':assigned_to'] = $filters['enforce_assigned_to'];
        } elseif (!empty($filters['assigned_to'])) {
            $sql .= " AND l.assigned_to = :assigned_to";
            $params[':assigned_to'] = $filters['assigned_to'];
        }

        // Date range filter
        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(l.created_at) >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(l.created_at) <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }

        // Tag filter
        if (!empty($filters['tag_id'])) {
            $sql .= " AND l.id IN (SELECT lead_id FROM lead_tag_map WHERE tag_id = :tag_id)";
            $params[':tag_id'] = $filters['tag_id'];
        }
        if (!empty($filters['facebook_page_id'])) {
            $sql .= " AND l.facebook_page_id = :fb_page_id";
            $params[':fb_page_id'] = $filters['facebook_page_id'];
        }

        $sql .= " ORDER BY l.created_at DESC";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        $stmt->execute();
        return $stmt->fetchAll();
    }
    public function getFacebookPages($orgId) {
        $stmt = $this->pdo->prepare("SELECT DISTINCT page_id, page_name FROM facebook_pages WHERE organization_id = :org_id ORDER BY page_name");
        $stmt->execute([':org_id' => $orgId]);
        return $stmt->fetchAll();
    }
}
?>
