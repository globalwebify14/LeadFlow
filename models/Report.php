<?php
require_once __DIR__ . '/../config/db.php';

class Report {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getLeadsByStatus($orgId) {
        $stmt = $this->pdo->prepare("SELECT status, COUNT(*) as count FROM leads WHERE organization_id = :org GROUP BY status ORDER BY count DESC");
        $stmt->execute(['org' => $orgId]);
        return $stmt->fetchAll();
    }

    public function getLeadsBySource($orgId) {
        $stmt = $this->pdo->prepare("SELECT COALESCE(source, 'Unknown') as source, COUNT(*) as count FROM leads WHERE organization_id = :org GROUP BY source ORDER BY count DESC");
        $stmt->execute(['org' => $orgId]);
        return $stmt->fetchAll();
    }

    public function getLeadsByPriority($orgId) {
        $stmt = $this->pdo->prepare("SELECT priority, COUNT(*) as count FROM leads WHERE organization_id = :org GROUP BY priority");
        $stmt->execute(['org' => $orgId]);
        return $stmt->fetchAll();
    }

    public function getConversionRate($orgId) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM leads WHERE organization_id = :org");
        $stmt->execute(['org' => $orgId]);
        $total = $stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM leads WHERE organization_id = :org AND status IN ('Done','Closed Won')");
        $stmt->execute(['org' => $orgId]);
        $converted = $stmt->fetchColumn();

        return [
            'total' => $total,
            'converted' => $converted,
            'rate' => $total > 0 ? round(($converted / $total) * 100, 1) : 0,
        ];
    }

    public function getAgentPerformance($orgId) {
        $stmt = $this->pdo->prepare(
            "SELECT u.name, 
                    COUNT(l.id) as total_leads,
                    SUM(CASE WHEN l.status IN ('Done','Closed Won') THEN 1 ELSE 0 END) as converted,
                    ROUND(SUM(CASE WHEN l.status IN ('Done','Closed Won') THEN 1 ELSE 0 END) / NULLIF(COUNT(l.id),0) * 100, 1) as conv_rate
             FROM users u 
             LEFT JOIN leads l ON l.assigned_to = u.id AND l.organization_id = :org
             WHERE u.organization_id = :org2 AND u.role IN ('agent','manager')
             GROUP BY u.id, u.name ORDER BY total_leads DESC"
        );
        $stmt->execute(['org' => $orgId, 'org2' => $orgId]);
        return $stmt->fetchAll();
    }

    public function getMonthlyGrowth($orgId) {
        $stmt = $this->pdo->prepare(
            "SELECT DATE_FORMAT(created_at, '%b %Y') as label, DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
             FROM leads WHERE organization_id = :org AND created_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH) 
             GROUP BY label, month ORDER BY month"
        );
        $stmt->execute(['org' => $orgId]);
        return $stmt->fetchAll();
    }

    public function getPipelinePerformance($orgId) {
        $stmt = $this->pdo->prepare(
            "SELECT ps.name, ps.color, COUNT(d.id) as deals_count, COALESCE(SUM(d.value), 0) as total_value
             FROM pipeline_stages ps
             LEFT JOIN deals d ON d.stage_id = ps.id AND d.organization_id = :org
             WHERE ps.organization_id = :org2
             GROUP BY ps.id, ps.name, ps.color
             ORDER BY ps.position"
        );
        $stmt->execute(['org' => $orgId, 'org2' => $orgId]);
        return $stmt->fetchAll();
    }

    public function getDealRevenueByMonth($orgId) {
        $stmt = $this->pdo->prepare(
            "SELECT DATE_FORMAT(updated_at, '%b %Y') as label, DATE_FORMAT(updated_at, '%Y-%m') as month, COALESCE(SUM(value),0) as revenue
             FROM deals WHERE organization_id = :org AND status = 'won' AND updated_at >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
             GROUP BY label, month ORDER BY month"
        );
        $stmt->execute(['org' => $orgId]);
        return $stmt->fetchAll();
    }
}
?>
