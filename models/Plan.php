<?php
require_once __DIR__ . '/../config/db.php';

class Plan {
    private $pdo;

    public function __construct($pdo) { $this->pdo = $pdo; }

    public function getAll() {
        return $this->pdo->query(
            "SELECT p.*, (SELECT COUNT(*) FROM organizations WHERE subscription_plan_id = p.id) as total_orgs
             FROM plans p ORDER BY p.price ASC"
        )->fetchAll();
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM plans WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    public function getActive() {
        return $this->pdo->query("SELECT * FROM plans WHERE is_active = 1 ORDER BY price ASC")->fetchAll();
    }

    public function create($data) {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $data['name']));
        $stmt = $this->pdo->prepare(
            "INSERT INTO plans (name, slug, price, yearly_price, max_users, max_leads, max_deals, storage_limit, description, is_active)
             VALUES (:name, :slug, :price, :yearly_price, :max_users, :max_leads, :max_deals, :storage, :desc, :active)"
        );
        $stmt->execute([
            'name'         => $data['name'],
            'slug'         => $slug,
            'price'        => $data['monthly_price'] ?? 0,
            'yearly_price' => $data['yearly_price'] ?? 0,
            'max_users'    => $data['max_users'] ?? 5,
            'max_leads'    => $data['max_leads'] ?? 1000,
            'max_deals'    => $data['max_deals'] ?? 500,
            'storage'      => $data['storage_limit'] ?? 1024,
            'desc'         => $data['description'] ?? '',
            'active'       => 1,
        ]);
        return $this->pdo->lastInsertId();
    }

    public function update($id, $data) {
        $stmt = $this->pdo->prepare(
            "UPDATE plans SET name=:name, price=:price, yearly_price=:yearly_price,
             max_users=:max_users, max_leads=:max_leads, max_deals=:max_deals,
             storage_limit=:storage, description=:desc, is_active=:active WHERE id=:id"
        );
        return $stmt->execute([
            'name'         => $data['name'],
            'price'        => $data['monthly_price'] ?? 0,
            'yearly_price' => $data['yearly_price'] ?? 0,
            'max_users'    => $data['max_users'] ?? 5,
            'max_leads'    => $data['max_leads'] ?? 1000,
            'max_deals'    => $data['max_deals'] ?? 500,
            'storage'      => $data['storage_limit'] ?? 1024,
            'desc'         => $data['description'] ?? '',
            'active'       => $data['is_active'] ?? 1,
            'id'           => $id,
        ]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM plans WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function toggleStatus($id) {
        $stmt = $this->pdo->prepare("UPDATE plans SET is_active = NOT is_active WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}
?>
