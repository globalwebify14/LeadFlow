<?php
require_once __DIR__ . '/../config/db.php';

class User {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all users for an organization
     */
    public function getAllUsers($orgId, $search = '', $role = '') {
        $sql = "SELECT * FROM users WHERE organization_id = :org_id";
        $params = [':org_id' => $orgId];

        if (!empty($search)) {
            $sql .= " AND (name LIKE :search OR email LIKE :search)";
            $params[':search'] = "%$search%";
        }
        if (!empty($role)) {
            $sql .= " AND role = :role";
            $params[':role'] = $role;
        }

        $sql .= " ORDER BY FIELD(role, 'admin','manager','agent'), name";

        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get user by ID
     */
    public function getUserById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Get user by email
     */
    public function getUserByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }

    /**
     * Create a new user
     */
    public function createUser($data) {
        $stmt = $this->pdo->prepare("INSERT INTO users (organization_id, name, email, password, phone, role) VALUES (:org_id, :name, :email, :password, :phone, :role)");
        return $stmt->execute([
            'org_id'   => $data['organization_id'],
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => password_hash($data['password'], PASSWORD_DEFAULT),
            'phone'    => $data['phone'] ?? null,
            'role'     => $data['role'] ?? 'agent',
        ]);
    }

    /**
     * Update user
     */
    public function updateUser($id, $data) {
        $fields = "name=:name, email=:email, phone=:phone, role=:role";
        $params = [
            'id'    => $id,
            'name'  => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role'  => $data['role'] ?? 'agent',
        ];

        // Only update password if provided
        if (!empty($data['password'])) {
            $fields .= ", password=:password";
            $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        // Active status
        if (isset($data['is_active'])) {
            $fields .= ", is_active=:is_active";
            $params['is_active'] = $data['is_active'];
        }

        $stmt = $this->pdo->prepare("UPDATE users SET $fields WHERE id=:id");
        return $stmt->execute($params);
    }

    /**
     * Delete a user
     */
    public function deleteUser($id) {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Get agents for assignment dropdowns
     */
    public function getAgents($orgId) {
        $stmt = $this->pdo->prepare("SELECT id, name, email, role FROM users WHERE organization_id = :org_id AND is_active = 1 AND role IN ('agent','manager','admin') ORDER BY name");
        $stmt->execute(['org_id' => $orgId]);
        return $stmt->fetchAll();
    }

    /**
     * Count users by role
     */
    public function countByRole($orgId) {
        $stmt = $this->pdo->prepare("SELECT role, COUNT(*) as count FROM users WHERE organization_id = :org_id GROUP BY role");
        $stmt->execute(['org_id' => $orgId]);
        return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    }

    /**
     * Update last login
     */
    public function updateLastLogin($id) {
        $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Check if email exists (excluding an ID for updates)
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email";
        $params = ['email' => $email];
        if ($excludeId) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() > 0;
    }

    /**
     * Get performance stats for an agent
     */
    public function getAgentStats($userId, $orgId) {
        $stats = [];
        
        // Leads assigned
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM leads WHERE assigned_to = :uid AND organization_id = :org");
        $stmt->execute(['uid' => $userId, 'org' => $orgId]);
        $stats['total_leads'] = $stmt->fetchColumn();

        // Leads converted (Done)
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM leads WHERE assigned_to = :uid AND organization_id = :org AND status = 'Done'");
        $stmt->execute(['uid' => $userId, 'org' => $orgId]);
        $stats['converted'] = $stmt->fetchColumn();

        // Deals value
        $stmt = $this->pdo->prepare("SELECT COALESCE(SUM(value), 0) FROM deals WHERE assigned_to = :uid AND organization_id = :org AND status = 'won'");
        $stmt->execute(['uid' => $userId, 'org' => $orgId]);
        $stats['deal_value'] = $stmt->fetchColumn();

        // Pending followups
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM followups WHERE user_id = :uid AND organization_id = :org AND status = 'pending'");
        $stmt->execute(['uid' => $userId, 'org' => $orgId]);
        $stats['pending_followups'] = $stmt->fetchColumn();

        return $stats;
    }
}
?>
