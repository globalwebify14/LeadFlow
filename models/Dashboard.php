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
            'total_orgs' => $this->pdo->query("SELECT COUNT(*) FROM organizations")->fetchColumn(),
            'active_orgs' => $this->pdo->query("SELECT COUNT(*) FROM organizations WHERE status='active'")->fetchColumn(),
            'suspended_orgs' => $this->pdo->query("SELECT COUNT(*) FROM organizations WHERE status='suspended'")->fetchColumn(),
            'total_users' => $this->pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
            'total_leads' => $this->pdo->query("SELECT COUNT(*) FROM leads")->fetchColumn(),
            'total_deals' => $this->pdo->query("SELECT COUNT(*) FROM deals")->fetchColumn(),
            'monthly_revenue' => (function($pdo) {
                try {
                    return $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM billing_history WHERE MONTH(COALESCE(paid_at, created_at)) = MONTH(CURDATE()) AND YEAR(COALESCE(paid_at, created_at)) = YEAR(CURDATE()) AND status='paid'")->fetchColumn();
                } catch (Exception $e) { return 0; }
            })($this->pdo)
        ];
        return $stats;
    }

    public function getPlatformRecentActivity($limit = 8) {
        return $this->pdo->query("SELECT * FROM organizations ORDER BY created_at DESC LIMIT $limit")->fetchAll();
    }

    public function getPlatformOrgGrowth() {
        return $this->pdo->query("SELECT DATE_FORMAT(created_at, '%Y-%m') as month, DATE_FORMAT(created_at, '%b %Y') as label, COUNT(*) as count FROM organizations WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) GROUP BY month, label ORDER BY month")->fetchAll();
    }

    /**
     * Get Organization statistics (Owner, Admin, Team Lead, Agent)
     */
    public function getStatistics($orgId, $userId = null, $role = 'org_owner') {
        $stats = [
            'total_leads' => 0,
            'new_leads' => 0,
            'follow_up' => 0,
            'converted' => 0,
            'total_deals' => 0,
            'deal_value' => 0,
            'won_deals' => 0,
            'lost_deals' => 0,
            'pending_followups' => 0,
            'assigned_today' => 0,
            'conversion_rate' => 0,
            'missed_followups' => 0,
            'team_members' => 0,
            'contacted_leads' => 0,
            'leads_by_status' => [],
            'leads_by_stage' => [],
            'deals_in_progress' => 0
        ];

        // Team members count
        $teamStmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE organization_id = :org_id");
        $teamStmt->execute(['org_id' => $orgId]);
        $stats['team_members'] = $teamStmt->fetchColumn();

        // Base lead query
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
                'count' => $row['count']
            ];
            if ($row['status'] === 'New Lead') $stats['new_leads'] += $row['count'];
            if ($row['status'] === 'Follow Up') $stats['follow_up'] += $row['count'];
            if ($row['status'] !== 'New Lead') $stats['contacted_leads'] += $row['count'];
            if (in_array($row['status'], ['Done', 'Closed Won'])) $stats['converted'] += $row['count'];
        }

        $stats['conversion_rate'] = $stats['total_leads'] > 0 ? round(($stats['converted'] / $stats['total_leads']) * 100) : 0;

        // Assigned today
        $assignSql = "SELECT COUNT(*) FROM leads WHERE organization_id = :org_id AND DATE(created_at) = CURDATE()";
        if ($role === 'agent' && $userId) {
            $assignSql .= " AND assigned_to = :user_id";
        }
        $stmtAssign = $this->pdo->prepare($assignSql);
        $stmtAssign->execute($leadParams);
        $stats['assigned_today'] = $stmtAssign->fetchColumn();

        // Deal stats
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
            $stats['deal_value'] += $d['total_value'];
            if ($d['status'] === 'won') $stats['won_deals'] += $d['cnt'];
            if ($d['status'] === 'lost') $stats['lost_deals'] += $d['cnt'];
            if ($d['status'] === 'open') $stats['deals_in_progress'] += $d['cnt'];
        }

        // Pending follow-ups
        $fSql = "SELECT COUNT(*) FROM followups WHERE organization_id = :org_id AND status = 'pending' AND followup_date = CURDATE()";
        if ($role === 'agent' && $userId) {
            $fSql .= " AND user_id = :user_id";
        }
        $stmtF = $this->pdo->prepare($fSql);
        $stmtF->execute($leadParams);
        $stats['pending_followups'] = $stmtF->fetchColumn();

        // Missed follow-ups
        $mSql = "SELECT COUNT(*) FROM followups WHERE organization_id = :org_id AND status = 'pending' AND followup_date < CURDATE()";
        if ($role === 'agent' && $userId) {
            $mSql .= " AND user_id = :user_id";
        }
        $stmtM = $this->pdo->prepare($mSql);
        $stmtM->execute($leadParams);
        $stats['missed_followups'] = $stmtM->fetchColumn();

        // Leads by Stage
        $stats['leads_by_stage'] = $this->getPipelineOverview($orgId, $userId, $role);

        return $stats;
    }

    public function getRecentLeads($orgId, $limit = 10, $userId = null, $role = 'org_owner') {
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
    }

    public function getTodayFollowups($orgId, $userId = null) {
        $sql = "SELECT f.*, l.name as lead_name, l.phone as lead_phone 
                FROM followups f 
                LEFT JOIN leads l ON f.lead_id = l.id 
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
    }

    public function getRecentActivities($orgId, $limit = 15, $userId = null, $role = 'org_owner') {
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
    }

    public function getMonthlyLeadGrowth($orgId, $userId = null, $role = 'org_owner') {
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
    }

    public function getPipelineOverview($orgId, $userId = null, $role = 'org_owner') {
        $sql = "SELECT ps.name, ps.color, COUNT(l.id) as count 
             FROM pipeline_stages ps 
             LEFT JOIN leads l ON l.pipeline_stage_id = ps.id ";
        
        if ($role === 'agent' && $userId) {
            $sql .= " AND l.assigned_to = " . (int)$userId;
        }
        $sql .= " WHERE ps.organization_id = :org_id GROUP BY ps.id, ps.name, ps.color ORDER BY ps.position";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['org_id' => $orgId]);
        return $stmt->fetchAll();
    }

    public function getAgentPerformance($orgId) {
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
    }
}
?>
