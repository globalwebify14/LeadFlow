<?php
require_once __DIR__ . '/../config/db.php';

class Followup {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll($orgId, $filters = [], $userId = null) {
        $sql = "SELECT f.*, l.name as lead_name, l.phone as lead_phone, d.name as deal_name, u.name as user_name
                FROM followups f
                LEFT JOIN leads l ON f.lead_id = l.id
                LEFT JOIN deals d ON f.deal_id = d.id
                LEFT JOIN users u ON f.user_id = u.id
                WHERE f.organization_id = :org_id";
        $params = [':org_id' => $orgId];

        if ($userId) {
            $sql .= " AND f.user_id = :user_id";
            $params[':user_id'] = $userId;
        }
        if (!empty($filters['status'])) {
            $sql .= " AND f.status = :status";
            $params[':status'] = $filters['status'];
        }
        if (!empty($filters['date'])) {
            if ($filters['date'] === 'today') {
                $sql .= " AND f.followup_date = CURDATE()";
            } elseif ($filters['date'] === 'overdue') {
                $sql .= " AND f.followup_date < CURDATE() AND f.status = 'pending'";
            } elseif ($filters['date'] === 'upcoming') {
                $sql .= " AND f.followup_date > CURDATE() AND f.status = 'pending'";
            }
        }

        $sql .= " ORDER BY f.followup_date ASC, f.followup_time ASC";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => &$val) { $stmt->bindParam($key, $val); }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT f.*, l.name as lead_name, d.name as deal_name FROM followups f LEFT JOIN leads l ON f.lead_id = l.id LEFT JOIN deals d ON f.deal_id = d.id WHERE f.id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $stmt = $this->pdo->prepare("INSERT INTO followups (organization_id, lead_id, deal_id, user_id, title, description, followup_date, followup_time, priority) VALUES (:org, :lead, :deal, :user, :title, :desc, :date, :time, :priority)");
        $result = $stmt->execute([
            'org' => $data['organization_id'],
            'lead' => $data['lead_id'] ?: null,
            'deal' => $data['deal_id'] ?: null,
            'user' => $data['user_id'],
            'title' => $data['title'],
            'desc' => $data['description'] ?? null,
            'date' => $data['followup_date'],
            'time' => $data['followup_time'] ?: null,
            'priority' => $data['priority'] ?? 'medium',
        ]);
        return $result ? $this->pdo->lastInsertId() : false;
    }

    public function complete($id) {
        $stmt = $this->pdo->prepare("UPDATE followups SET status = 'completed', completed_at = NOW() WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function markMissed($id) {
        $stmt = $this->pdo->prepare("UPDATE followups SET status = 'missed' WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM followups WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getOverdueCount($orgId, $userId = null) {
        $sql = "SELECT COUNT(*) FROM followups WHERE organization_id = :org AND followup_date < CURDATE() AND status = 'pending'";
        $params = ['org' => $orgId];
        if ($userId) {
            $sql .= " AND user_id = :uid";
            $params['uid'] = $userId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    public function getTodayCount($orgId, $userId = null) {
        $sql = "SELECT COUNT(*) FROM followups WHERE organization_id = :org AND followup_date = CURDATE() AND status = 'pending'";
        $params = ['org' => $orgId];
        if ($userId) {
            $sql .= " AND user_id = :uid";
            $params['uid'] = $userId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
?>
