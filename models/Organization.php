<?php
require_once __DIR__ . '/../config/db.php';

class Organization {
    private $pdo;

    public function __construct($pdo) { $this->pdo = $pdo; }

    public function getAll($search = '', $status = '') {
        $sql = "SELECT o.*, 
                    u.name as owner_name_user,
                    u.email as owner_email_user,
                    p.name as plan_name,
                    (SELECT COUNT(*) FROM users WHERE organization_id = o.id) as total_users,
                    (SELECT COUNT(*) FROM leads WHERE organization_id = o.id) as total_leads,
                    (SELECT COUNT(*) FROM deals WHERE organization_id = o.id) as total_deals
                FROM organizations o
                LEFT JOIN users u ON o.owner_id = u.id
                LEFT JOIN plans p ON o.subscription_plan_id = p.id
                WHERE 1=1";
        $params = [];
        if ($search) {
            $sql .= " AND (o.name LIKE :s OR o.email LIKE :s2)";
            $params['s'] = "%$search%";
            $params['s2'] = "%$search%";
        }
        if ($status) {
            $sql .= " AND o.status = :status";
            $params['status'] = $status;
        }
        $sql .= " ORDER BY o.created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare(
            "SELECT o.*, u.name as owner_name_user, u.email as owner_email,
                    p.name as plan_name, p.price as plan_price,
                    (SELECT COUNT(*) FROM users WHERE organization_id = o.id) as total_users,
                    (SELECT COUNT(*) FROM leads WHERE organization_id = o.id) as total_leads,
                    (SELECT COUNT(*) FROM deals WHERE organization_id = o.id) as total_deals,
                    (SELECT COUNT(*) FROM followups WHERE organization_id = o.id AND status='pending') as pending_followups
             FROM organizations o
             LEFT JOIN users u ON o.owner_id = u.id
             LEFT JOIN plans p ON o.subscription_plan_id = p.id
             WHERE o.id = :id"
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function create($data) {
        $slug = $this->makeSlug($data['name']);
        $stmt = $this->pdo->prepare(
            "INSERT INTO organizations (name, owner_name, email, phone, address, subscription_plan_id, status, slug)
             VALUES (:name, :owner_name, :email, :phone, :address, :plan_id, :status, :slug)"
        );
        $stmt->execute([
            'name'       => $data['name'],
            'owner_name' => $data['owner_name'] ?? '',
            'email'      => $data['email'] ?? '',
            'phone'      => $data['phone'] ?? '',
            'address'    => $data['address'] ?? '',
            'plan_id'    => $data['plan_id'] ?: null,
            'status'     => $data['status'] ?? 'active',
            'slug'       => $slug,
        ]);
        return $this->pdo->lastInsertId();
    }

    public function update($id, $data) {
        $stmt = $this->pdo->prepare(
            "UPDATE organizations SET name=:name, owner_name=:owner_name, email=:email,
             phone=:phone, address=:address, subscription_plan_id=:plan_id, status=:status
             WHERE id=:id"
        );
        return $stmt->execute([
            'name'       => $data['name'],
            'owner_name' => $data['owner_name'] ?? '',
            'email'      => $data['email'] ?? '',
            'phone'      => $data['phone'] ?? '',
            'address'    => $data['address'] ?? '',
            'plan_id'    => $data['plan_id'] ?: null,
            'status'     => $data['status'],
            'id'         => $id,
        ]);
    }

    public function setOwner($orgId, $userId) {
        $stmt = $this->pdo->prepare("UPDATE organizations SET owner_id = :uid WHERE id = :id");
        return $stmt->execute(['uid' => $userId, 'id' => $orgId]);
    }

    public function updateStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE organizations SET status = :status WHERE id = :id");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM organizations WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getPlatformStats() {
        return [
            'total'     => $this->pdo->query("SELECT COUNT(*) FROM organizations")->fetchColumn(),
            'active'    => $this->pdo->query("SELECT COUNT(*) FROM organizations WHERE status='active'")->fetchColumn(),
            'suspended' => $this->pdo->query("SELECT COUNT(*) FROM organizations WHERE status='suspended'")->fetchColumn(),
            'inactive'  => $this->pdo->query("SELECT COUNT(*) FROM organizations WHERE status='inactive'")->fetchColumn(),
        ];
    }

    public function getGrowthChart() {
        return $this->pdo->query(
            "SELECT DATE_FORMAT(created_at,'%Y-%m') as month, DATE_FORMAT(created_at,'%b %Y') as label, COUNT(*) as count
             FROM organizations WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY month, label ORDER BY month"
        )->fetchAll();
    }

    public function seedDefaultStages($orgId) {
        $stages = [
            ['New Lead', '#6366f1', 1],
            ['Contacted', '#06b6d4', 2],
            ['Interested', '#10b981', 3],
            ['Office Visited', '#f59e0b', 4],
            ['Site Visited', '#84cc16', 5],
            ['Site + Office Visited', '#ec4899', 6],
            ['Converted', '#10b981', 7],
            ['Dropped', '#ef4444', 8]
        ];
        $stmt = $this->pdo->prepare("INSERT INTO pipeline_stages (organization_id, name, color, position) VALUES (?, ?, ?, ?)");
        foreach ($stages as $s) {
            $stmt->execute([$orgId, $s[0], $s[1], $s[2]]);
        }
    }

    private function makeSlug($name) {
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));
        $base = $slug;
        $i = 1;
        while ($this->pdo->query("SELECT COUNT(*) FROM organizations WHERE slug='$slug'")->fetchColumn()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
?>
