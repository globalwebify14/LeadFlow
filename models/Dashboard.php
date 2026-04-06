<?php
require_once __DIR__ . '/../config/db.php';

class Dashboard {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get Super Admin Platform Statistics
     */
    public function getSuperAdminStats() {
        $stats = [
            'total_orgs'      => 0,
            'active_orgs'     => 0,
            'suspended_orgs'  => 0,
            'total_users'     => 0,
            'total_leads'     => 0,
            'total_deals'     => 0,
            'monthly_revenue' => 0,
        ];
        try {
            $stats['total_orgs']     = $this->pdo->query("SELECT COUNT(*) FROM organizations")->fetchColumn();
            $stats['active_orgs']    = $this->pdo->query("SELECT COUNT(*) FROM organizations WHERE status='active'")->fetchColumn();
            $stats['suspended_orgs'] = $this->pdo->query("SELECT COUNT(*) FROM organizations WHERE status='suspended'")->fetchColumn();
            $stats['total_users']    = $this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
            $stats['total_leads']    = $this->pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn();
            $stats['total_deals']    = $this->pdo->query("SELECT COUNT(*) FROM deals")->fetchColumn();
        } catch (Exception $e) { /* silently handle */ }

        try {
            $stats['monthly_revenue'] = $this->pdo->query(
                "SELECT COALESCE(SUM(amount), 0) FROM billing_history
                 WHERE MONTH(COALESCE(paid_at, created_at)) = MONTH(CURDATE())
                   AND YEAR(COALESCE(paid_at, created_at)) = YEAR(CURDATE())
                   AND status='paid'"
            )->fetchColumn();
        } catch (Exception $e) {
            $stats['monthly_revenue'] = 0;
        }

        return $stats;
    }

    public function getPlatformRecentActivity($limit = 8) {
        try {
            return $this->pdo->query("SELECT * FROM organizations ORDER BY created_at DESC LIMIT $limit")->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getPlatformOrgGrowth() {
        try {
            return $this->pdo->query(
                "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, DATE_FORMAT(created_at, '%b %Y') as label, COUNT(*) as count
                 FROM organizations WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
                 GROUP BY month, label ORDER BY month"
            )->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get Organization statistics (Owner, Admin, Team Lead, Agent)
     * @return array
     */
    public function getStatistics($orgId, $userId = null, $role = 'org_owner') {
        $stats = [
            'total_leads'        => 0,
            'new_leads'          => 0,
            'follow_up'          => 0,
            'converted'          => 0,
            'total_deals'        => 0,
            'deal_value'         => 0,
            'won_deals'          => 0,
            'lost_deals'         => 0,
            'pending_followups'  => 0,
            'upcoming_followups' => 0,
            'assigned_today'     => 0,
            'conversion_rate'    => 0,
            'missed_followups'   => 0,
            'team_members'       => 0,
            'contacted_leads'    => 0,
            'leads_by_status'    => [],
            'leads_by_stage'     => [],
            'deals_in_progress'  => 0,
        ];

        // Team members count
        try {
            $teamStmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE organization_id = :org_id");
            $teamStmt->execute(['org_id' => $orgId]);
            $stats['team_members'] = $teamStmt->fetchColumn();
        } catch (Exception $e) {}

        // Lead stats grouped by status
        try {
            $leadSql = "SELECT status, COUNT(*) as count FROM leads WHERE organization_id = :org_id";
            $leadParams = ['org_id' => $orgId];

            if ($role === 'agent' && $userId) {
                $leadSql .= " AND assigned_to = :user_id";
                $leadParams['user_id'] = $userId;
            }
            $leadSql .= " GROUP BY status";

            $stmt = $this->pdo->prepare($leadSql);
            $stmt->execute($leadParams);
            $rows = $stmt->fetchAll();

            foreach ($rows as $row) {
                $stats['total_leads'] += $row['count'];
                $stats['leads_by_status'][] = [
                    'status' => $row['status'],
                    'count'  => $row['count'],
                ];
                if ($row['status'] === 'New Lead') $stats['new_leads'] += $row['count'];
                if ($row['status'] === 'Follow Up') $stats['follow_up'] += $row['count'];
                if ($row['status'] !== 'New Lead') $stats['contacted_leads'] += $row['count'];
            }
        } catch (Exception $e) {}

        // Assigned today
        try {
            $leadParams = ['org_id' => $orgId];
            if ($role === 'agent' && $userId) {
                $leadParams['user_id'] = $userId;
            }

            $assignSql = "SELECT COUNT(*) FROM leads WHERE organization_id = :org_id AND DATE(created_at) = CURDATE()";
            if ($role === 'agent' && $userId) {
                $assignSql .= " AND assigned_to = :user_id";
            }
            $stmtAssign = $this->pdo->prepare($assignSql);
            $stmtAssign->execute($leadParams);
            $stats['assigned_today'] = $stmtAssign->fetchColumn();
        } catch (Exception $e) {}

        // Deal stats
        try {
            $leadParams = ['org_id' => $orgId];
            if ($role === 'agent' && $userId) {
                $leadParams['user_id'] = $userId;
            }

            $dealSql = "SELECT status, COUNT(*) as cnt, COALESCE(SUM(value), 0) as total_value FROM deals WHERE organization_id = :org_id";
            if ($role === 'agent' && $userId) {
                $dealSql .= " AND assigned_to = :user_id";
            }
            $dealSql .= " GROUP BY status";

            $stmtDeal = $this->pdo->prepare($dealSql);
            $stmtDeal->execute($leadParams);
            $dealRows = $stmtDeal->fetchAll();

            foreach ($dealRows as $d) {
                $stats['total_deals'] += $d['cnt'];
                $stats['deal_value']  += $d['total_value'];
                if ($d['status'] === 'won')  $stats['won_deals'] += $d['cnt'];
                if ($d['status'] === 'lost') $stats['lost_deals'] += $d['cnt'];
                if ($d['status'] === 'open') $stats['deals_in_progress'] += $d['cnt'];
            }
        } catch (Exception $e) {}

        // Conversion
        $stats['converted']        = $stats['won_deals'];
        $stats['conversion_rate']  = $stats['total_leads'] > 0
            ? min(100, round(($stats['converted'] / $stats['total_leads']) * 100))
            : 0;

        // Followup stats
        try {
            $fParams = ['org_id' => $orgId];
            if ($role === 'agent' && $userId) {
                $fParams['user_id'] = $userId;
            }

            // Pending (today + overdue)
            $fSql = "SELECT COUNT(*) FROM followups WHERE organization_id = :org_id AND status = 'pending' AND followup_date <= CURDATE()";
            if ($role === 'agent' && $userId) $fSql .= " AND user_id = :user_id";
            $stmtF = $this->pdo->prepare($fSql);
            $stmtF->execute($fParams);
            $stats['pending_followups'] = $stmtF->fetchColumn();

            // Missed
            $mSql = "SELECT COUNT(*) FROM followups WHERE organization_id = :org_id AND status = 'pending' AND followup_date < CURDATE()";
            if ($role === 'agent' && $userId) $mSql .= " AND user_id = :user_id";
            $stmtM = $this->pdo->prepare($mSql);
            $stmtM->execute($fParams);
            $stats['missed_followups'] = $stmtM->fetchColumn();

            // Upcoming
            $uSql = "SELECT COUNT(*) FROM followups WHERE organization_id = :org_id AND status = 'pending' AND followup_date > CURDATE()";
            if ($role === 'agent' && $userId) $uSql .= " AND user_id = :user_id";
            $stmtU = $this->pdo->prepare($uSql);
            $stmtU->execute($fParams);
            $stats['upcoming_followups'] = $stmtU->fetchColumn();

        } catch (Exception $e) {}

        // Leads by stage
        $stats['leads_by_stage'] = $this->getPipelineOverview($orgId, $userId, $role);

        return $stats;
    }

    public function getRecentLeads($orgId, $limit = 10, $userId = null, $role = 'org_owner') {
        try {
            $sql = "SELECT l.*, u.name as agent_name, ps.name as stage_name, ps.color as stage_color
                    FROM leads l
                    LEFT JOIN users u ON l.assigned_to = u.id
                    LEFT JOIN pipeline_stages ps ON l.pipeline_stage_id = ps.id
                    WHERE l.organization_id = :org_id";
            $params = ['org_id' => $orgId];
            if ($role === 'agent' && $userId) {
                $sql .= " AND l.assigned_to = :user_id";
                $params['user_id'] = $userId;
            }
            $sql .= " ORDER BY l.id DESC LIMIT " . (int)$limit;
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getTodayFollowups($orgId, $userId = null) {
        try {
            $sql = "SELECT f.*, l.name as lead_name, l.phone as lead_phone, u.name as agent_name
                    FROM followups f
                    LEFT JOIN leads l ON f.lead_id = l.id
                    LEFT JOIN users u ON f.user_id = u.id
                    WHERE f.organization_id = :org_id AND f.followup_date = CURDATE() AND f.status = 'pending'";
            $params = ['org_id' => $orgId];
            if ($userId) {
                $sql .= " AND f.user_id = :user_id";
                $params['user_id'] = $userId;
            }
            $sql .= " ORDER BY f.followup_time ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getRecentActivities($orgId, $limit = 15, $userId = null, $role = 'org_owner') {
        try {
            $sql = "SELECT la.*, l.name as lead_name, u.name as user_name
                 FROM lead_activities la
                 INNER JOIN leads l ON la.lead_id = l.id
                 LEFT JOIN users u ON la.user_id = u.id
                 WHERE l.organization_id = :org_id";
            $params = ['org_id' => $orgId];
            if ($role === 'agent' && $userId) {
                $sql .= " AND (la.user_id = :user_id OR l.assigned_to = :user_id)";
                $params['user_id'] = $userId;
            }
            $sql .= " ORDER BY la.created_at DESC LIMIT " . (int)$limit;
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getMonthlyLeadGrowth($orgId, $userId = null, $role = 'org_owner') {
        try {
            $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, DATE_FORMAT(created_at, '%b %Y') as label, COUNT(*) as count
                 FROM leads WHERE organization_id = :org_id
                 AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)";
            $params = ['org_id' => $orgId];
            if ($role === 'agent' && $userId) {
                $sql .= " AND assigned_to = :user_id";
                $params['user_id'] = $userId;
            }
            $sql .= " GROUP BY month, label ORDER BY month";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getPipelineOverview($orgId, $userId = null, $role = 'org_owner') {
        try {
            $sql = "SELECT ps.name, ps.color, COUNT(l.id) as count
                 FROM pipeline_stages ps
                 LEFT JOIN leads l ON l.pipeline_stage_id = ps.id";

            if ($role === 'agent' && $userId) {
                $sql .= " AND l.assigned_to = " . (int)$userId;
            }
            $sql .= " WHERE ps.organization_id = :org_id GROUP BY ps.id, ps.name, ps.color ORDER BY ps.position";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['org_id' => $orgId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getAgentPerformance($orgId) {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT u.name, COUNT(l.id) as total_leads,
                        SUM(CASE WHEN l.status IN ('Done','Closed Won') THEN 1 ELSE 0 END) as converted
                 FROM users u
                 LEFT JOIN leads l ON l.assigned_to = u.id AND l.organization_id = :org_id
                 WHERE u.organization_id = :org_id2 AND u.role IN ('agent','team_lead')
                 GROUP BY u.id, u.name
                 ORDER BY total_leads DESC"
            );
            $stmt->execute(['org_id' => $orgId, 'org_id2' => $orgId]);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
}
?>
