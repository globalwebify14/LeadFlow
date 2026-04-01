<?php
require_once __DIR__ . '/../config/db.php';

class Report {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    private function applyDateFilter(&$sql, &$params, $tableAlias, $dateFrom, $dateTo, $dateCol = 'created_at') {
        if ($dateFrom) {
            $sql .= " AND DATE($tableAlias.$dateCol) >= :dateFrom";
            $params['dateFrom'] = $dateFrom;
        }
        if ($dateTo) {
            $sql .= " AND DATE($tableAlias.$dateCol) <= :dateTo";
            $params['dateTo'] = $dateTo;
        }
    }

    public function getLeadSummary($orgId, $agentId = null, $dateFrom = null, $dateTo = null) {
        $sql = "SELECT 
                    COUNT(*) as total_leads,
                    SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as leads_today,
                    SUM(CASE WHEN assigned_to IS NOT NULL THEN 1 ELSE 0 END) as assigned_leads,
                    SUM(CASE WHEN assigned_to IS NULL THEN 1 ELSE 0 END) as unassigned_leads,
                    SUM(CASE WHEN status != 'New Lead' THEN 1 ELSE 0 END) as contacted_leads,
                    SUM(CASE WHEN status = 'New Lead' THEN 1 ELSE 0 END) as uncontacted_leads,
                    SUM(CASE WHEN status IN ('Done', 'Closed Won') THEN 1 ELSE 0 END) as converted_leads
                FROM leads WHERE organization_id = :org";
        $params = ['org' => $orgId];
        
        if ($agentId) {
            $sql .= " AND assigned_to = :userId";
            $params['userId'] = $agentId;
        }
        $this->applyDateFilter($sql, $params, 'leads', $dateFrom, $dateTo);
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function getLeadsByStatus($orgId, $userId = null, $dateFrom = null, $dateTo = null) {
        $sql = "SELECT status, COUNT(*) as count FROM leads WHERE organization_id = :org";
        $params = ['org' => $orgId];
        if ($userId) {
            $sql .= " AND assigned_to = :userId";
            $params['userId'] = $userId;
        }
        $this->applyDateFilter($sql, $params, 'leads', $dateFrom, $dateTo);
        
        $sql .= " GROUP BY status ORDER BY count DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getLeadsBySource($orgId, $userId = null, $dateFrom = null, $dateTo = null) {
        $sql = "SELECT COALESCE(source, 'Unknown') as source, COUNT(*) as count FROM leads WHERE organization_id = :org";
        $params = ['org' => $orgId];
        if ($userId) {
            $sql .= " AND assigned_to = :userId";
            $params['userId'] = $userId;
        }
        $this->applyDateFilter($sql, $params, 'leads', $dateFrom, $dateTo);
        
        $sql .= " GROUP BY source ORDER BY count DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getConversionRate($orgId, $userId = null, $dateFrom = null, $dateTo = null) {
        $summary = $this->getLeadSummary($orgId, $userId, $dateFrom, $dateTo);
        $total = $summary['total_leads'] ?? 0;
        
        $deals = $this->getDealsRevenueReport($orgId, $userId, $dateFrom, $dateTo);
        $converted = $deals['total_closed_deals'] ?? 0;
        
        return [
            'total' => $total,
            'converted' => $converted,
            'rate' => $total > 0 ? min(100, round(($converted / $total) * 100, 1)) : 0,
        ];
    }

    public function getMonthlyGrowth($orgId, $userId = null, $dateFrom = null, $dateTo = null) {
        $sql = "SELECT DATE_FORMAT(created_at, '%b %d') as label, DATE_FORMAT(created_at, '%Y-%m-%d') as day, COUNT(*) as count 
             FROM leads WHERE organization_id = :org";
        $params = ['org' => $orgId];
        if ($userId) {
            $sql .= " AND assigned_to = :userId";
            $params['userId'] = $userId;
        }
        $this->applyDateFilter($sql, $params, 'leads', $dateFrom, $dateTo);
        $sql .= " GROUP BY label, day ORDER BY day";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rawData = $stmt->fetchAll();

        if (!$dateFrom || !$dateTo) {
            return $rawData;
        }

        try {
            $startDate = new DateTime($dateFrom);
            $endDate = new DateTime($dateTo);
        } catch (Exception $e) {
            return $rawData;
        }

        if ($startDate->diff($endDate)->days > 366) {
            return $rawData;
        }

        $mapped = [];
        foreach ($rawData as $r) {
            $mapped[$r['day']] = $r['count'];
        }

        $results = [];
        $current = clone $startDate;
        while ($current <= $endDate) {
            $dayStr = $current->format('Y-m-d');
            $labelStr = $current->format('M d'); 
            $results[] = [
                'label' => $labelStr,
                'day' => $dayStr,
                'count' => $mapped[$dayStr] ?? 0
            ];
            $current->modify('+1 day');
        }

        return $results;
    }

    public function getAgentAdvancedPerformance($orgId, $userId = null, $dateFrom = null, $dateTo = null) {
        // We will sum up leads assigned in the period and deals closed in the period
        $sql = "SELECT u.id, u.name, 
                    (SELECT COUNT(*) FROM leads l WHERE l.assigned_to = u.id";
        $params = ['org' => $orgId];
        
        $dateFilterLeads = "";
        if ($dateFrom) { $dateFilterLeads .= " AND DATE(l.created_at) >= :dFrom"; $params['dFrom'] = $dateFrom; }
        if ($dateTo) { $dateFilterLeads .= " AND DATE(l.created_at) <= :dTo"; $params['dTo'] = $dateTo; }
        $sql .= $dateFilterLeads . ") as total_leads,";
        
        $sql .= "   (SELECT COUNT(*) FROM leads l WHERE l.assigned_to = u.id AND l.status NOT IN ('New Lead')";
        $sql .= $dateFilterLeads . ") as contacted_leads,";
        
        $sql .= "   (SELECT COUNT(*) FROM deals d WHERE d.assigned_to = u.id AND d.status = 'won'";
        $dateFilterDeals = "";
        if ($dateFrom) { $dateFilterDeals .= " AND DATE(d.updated_at) >= :dFrom"; }
        if ($dateTo) { $dateFilterDeals .= " AND DATE(d.updated_at) <= :dTo"; }
        $sql .= $dateFilterDeals . ") as converted_deals";
        
        $sql .= " FROM users u
                  WHERE u.organization_id = :org AND u.role IN ('agent','team_lead','org_admin') AND u.is_active = 1";
                  
        if ($userId) {
            $sql .= " AND u.id = :userId";
            $params['userId'] = $userId;
        }
        
        $sql .= " ORDER BY converted_deals DESC, total_leads DESC";
                  
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll();
        
        foreach ($results as &$row) {
            $row['conv_rate'] = $row['total_leads'] > 0 ? round(($row['converted_deals'] / $row['total_leads']) * 100, 1) : 0;
        }
        return $results;
    }

    public function getLeadDistribution($orgId, $userId = null, $dateFrom = null, $dateTo = null) {
        $sql = "SELECT u.name as agent_name, COUNT(l.id) as assigned_count
                FROM users u
                LEFT JOIN leads l ON l.assigned_to = u.id";
        $params = ['org' => $orgId];
        $dateFilter = "";
        if ($dateFrom) { $dateFilter .= " AND DATE(l.created_at) >= :dFrom"; $params['dFrom'] = $dateFrom; }
        if ($dateTo) { $dateFilter .= " AND DATE(l.created_at) <= :dTo"; $params['dTo'] = $dateTo; }
        
        $sql .= $dateFilter;
        $sql .= " WHERE u.organization_id = :org AND u.role IN ('agent','team_lead','org_admin') AND u.is_active = 1";
        if ($userId) {
            $sql .= " AND u.id = :userId";
            $params['userId'] = $userId;
        }
        $sql .= " GROUP BY u.id, u.name
                  ORDER BY assigned_count DESC";
                  
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAgentResponseTime($orgId, $userId = null, $dateFrom = null, $dateTo = null) {
        $sql = "SELECT u.name as agent_name, 
                       AVG(TIMESTAMPDIFF(MINUTE, l.created_at, (
                           SELECT MIN(a.created_at) FROM lead_activities a WHERE a.lead_id = l.id AND a.user_id = u.id
                       ))) as avg_response_minutes
                FROM leads l
                JOIN users u ON l.assigned_to = u.id
                WHERE l.organization_id = :org AND l.assigned_to IS NOT NULL AND l.status != 'New Lead'";
        
        $params = ['org' => $orgId];
        if ($userId) {
            $sql .= " AND u.id = :userId";
            $params['userId'] = $userId;
        }
        $this->applyDateFilter($sql, $params, 'l', $dateFrom, $dateTo);
        
        $sql .= " GROUP BY u.id, u.name ORDER BY avg_response_minutes ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getDealsRevenueReport($orgId, $agentId = null, $dateFrom = null, $dateTo = null) {
        $sql = "SELECT 
                    SUM(CASE WHEN status = 'won' THEN 1 ELSE 0 END) as total_closed_deals,
                    SUM(CASE WHEN status = 'won' THEN value ELSE 0 END) as total_revenue,
                    AVG(CASE WHEN status = 'won' THEN value ELSE NULL END) as avg_deal_value
                FROM deals WHERE organization_id = :org";
        $params = ['org' => $orgId];
        if ($agentId) { $sql .= " AND assigned_to = :uid"; $params['uid'] = $agentId; }
        
        $this->applyDateFilter($sql, $params, 'deals', $dateFrom, $dateTo, 'updated_at');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function getFollowUpStatusReport($orgId, $agentId = null, $dateFrom = null, $dateTo = null) {
        $sql = "SELECT 
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN status = 'pending' AND followup_date < CURDATE() THEN 1 ELSE 0 END) as overdue_tasks
                FROM followups WHERE organization_id = :org";
        $params = ['org' => $orgId];
        if ($agentId) { $sql .= " AND user_id = :uid"; $params['uid'] = $agentId; }
        
        $this->applyDateFilter($sql, $params, 'followups', $dateFrom, $dateTo, 'followup_date');
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch();
    }

    public function getFacebookCampaignReport($orgId, $userId = null, $dateFrom = null, $dateTo = null) {
        $sql = "SELECT 
                    l.facebook_page_id,
                    p.page_name,
                    COALESCE(l.meta_campaign, 'Unknown Campaign') as campaign_name,
                    COUNT(*) as lead_count
                FROM leads l
                LEFT JOIN facebook_pages p ON l.facebook_page_id = p.page_id
                WHERE l.organization_id = :org AND l.source = 'facebook_ads'";
        
        $params = ['org' => $orgId];
        if ($userId) {
            $sql .= " AND l.assigned_to = :userId";
            $params['userId'] = $userId;
        }
        $this->applyDateFilter($sql, $params, 'l', $dateFrom, $dateTo);
        
        $sql .= " GROUP BY l.facebook_page_id, p.page_name, l.meta_campaign
                  ORDER BY lead_count DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getPipelinePerformance($orgId, $userId = null, $dateFrom = null, $dateTo = null) {
        $sql = "SELECT ps.name, ps.color,
                (SELECT COUNT(l.id) FROM leads l WHERE l.pipeline_stage_id = ps.id AND l.organization_id = :org1";
        $params = ['org1' => $orgId];
        $lf = "";
        if ($userId) { $lf .= " AND l.assigned_to = :u1"; $params['u1'] = $userId; }
        if ($dateFrom) { $lf .= " AND DATE(l.created_at) >= :dF1"; $params['dF1'] = $dateFrom; }
        if ($dateTo) { $lf .= " AND DATE(l.created_at) <= :dT1"; $params['dT1'] = $dateTo; }
        $sql .= $lf . ") as leads_count,
        
                (SELECT COUNT(d.id) FROM deals d WHERE d.stage_id = ps.id AND d.organization_id = :org2";
        $params['org2'] = $orgId;
        $df = "";
        if ($userId) { $df .= " AND d.assigned_to = :u2"; $params['u2'] = $userId; }
        if ($dateFrom) { $df .= " AND DATE(d.updated_at) >= :dF2"; $params['dF2'] = $dateFrom; }
        if ($dateTo) { $df .= " AND DATE(d.updated_at) <= :dT2"; $params['dT2'] = $dateTo; }
        $sql .= $df . ") as deals_count
        
             FROM pipeline_stages ps
             WHERE ps.organization_id = :org3
             ORDER BY ps.position";
        $params['org3'] = $orgId;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getDetailedLeadsReport($orgId, $agentId = null, $dateFrom = null, $dateTo = null, $limit = 50) {
        $sql = "SELECT l.id, l.name, l.phone, l.status, l.created_at, l.source,
                       u.name as agent_name, 
                       ps.name as stage_name, ps.color as stage_color,
                       (SELECT note FROM lead_notes ln WHERE ln.lead_id = l.id ORDER BY ln.created_at DESC LIMIT 1) as latest_note,
                       (SELECT CONCAT(followup_date, ' ', followup_time) FROM followups f WHERE f.lead_id = l.id ORDER BY f.created_at DESC LIMIT 1) as latest_followup_date,
                       (SELECT status FROM followups f WHERE f.lead_id = l.id ORDER BY f.created_at DESC LIMIT 1) as latest_followup_status
                FROM leads l
                LEFT JOIN users u ON l.assigned_to = u.id
                LEFT JOIN pipeline_stages ps ON l.pipeline_stage_id = ps.id
                WHERE l.organization_id = :org";
        $params = ['org' => $orgId];
        
        if ($agentId) {
            $sql .= " AND l.assigned_to = :userId";
            $params['userId'] = $agentId;
        }
        $this->applyDateFilter($sql, $params, 'l', $dateFrom, $dateTo);
        
        $sql .= " ORDER BY l.created_at DESC LIMIT " . (int)$limit;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getFollowUpsListReport($orgId, $agentId = null, $dateFrom = null, $dateTo = null, $limit = 50) {
        $sql = "SELECT f.*, 
                       l.name as lead_name, l.phone as lead_phone,
                       u.name as agent_name
                FROM followups f
                JOIN leads l ON f.lead_id = l.id
                LEFT JOIN users u ON f.user_id = u.id
                WHERE f.organization_id = :org";
        $params = ['org' => $orgId];
        
        if ($agentId) {
            $sql .= " AND f.user_id = :userId";
            $params['userId'] = $agentId;
        }
        
        // Filter by Scheduled Date so Admin can ask "What followups are scheduled for today?"
        $this->applyDateFilter($sql, $params, 'f', $dateFrom, $dateTo, 'followup_date');
        
        $sql .= " ORDER BY f.followup_date ASC, f.followup_time ASC LIMIT " . (int)$limit;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
?>
