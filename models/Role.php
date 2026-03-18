<?php
require_once __DIR__ . '/../config/db.php';

class Role {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Get all roles for an organization (and system roles if any)
     */
    public function getAllRoles($orgId) {
        $stmt = $this->pdo->prepare("SELECT * FROM roles WHERE organization_id = :org OR is_system = 1 ORDER BY is_system DESC, name ASC");
        $stmt->execute(['org' => $orgId]);
        return $stmt->fetchAll();
    }

    /**
     * Get a specific role
     */
    public function getRoleById($id, $orgId) {
        $stmt = $this->pdo->prepare("SELECT * FROM roles WHERE id = :id AND (organization_id = :org OR is_system = 1)");
        $stmt->execute(['id' => $id, 'org' => $orgId]);
        return $stmt->fetch();
    }

    /**
     * Create a new role with permissions
     */
    public function createRole($orgId, $name, $permissions, $createdBy) {
        $startedTransaction = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $startedTransaction = true;
        }
        try {

            // Insert role
            $stmt = $this->pdo->prepare("INSERT INTO roles (organization_id, name, created_by) VALUES (:org, :name, :created_by)");
            $stmt->execute([
                'org' => $orgId,
                'name' => $name,
                'created_by' => $createdBy
            ]);
            $roleId = $this->pdo->lastInsertId();

            // Insert permissions
            $this->savePermissions($roleId, $permissions);

            if ($startedTransaction) {
                $this->pdo->commit();
            }
            return $roleId;
        } catch (Exception $e) {
            if ($startedTransaction) {
                $this->pdo->rollBack();
            }
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Update a role and its permissions
     */
    public function updateRole($id, $orgId, $name, $permissions) {
        $startedTransaction = false;
        if (!$this->pdo->inTransaction()) {
            $this->pdo->beginTransaction();
            $startedTransaction = true;
        }
        try {

            // Check if role is system
            $role = $this->getRoleById($id, $orgId);
            if (!$role || $role['is_system']) {
                throw new Exception("Cannot edit system roles or roles you don't own.");
            }

            // Update role name
            $stmt = $this->pdo->prepare("UPDATE roles SET name = :name WHERE id = :id AND organization_id = :org");
            $stmt->execute(['name' => $name, 'id' => $id, 'org' => $orgId]);

            // Update permissions by deleting old and inserting new
            $delStmt = $this->pdo->prepare("DELETE FROM role_permissions WHERE role_id = :id");
            $delStmt->execute(['id' => $id]);

            $this->savePermissions($id, $permissions);

            if ($startedTransaction) {
                $this->pdo->commit();
            }
            return true;
        } catch (Exception $e) {
            if ($startedTransaction) {
                $this->pdo->rollBack();
            }
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Internal helper to insert permissions
     */
    private function savePermissions($roleId, $permissions) {
        $stmt = $this->pdo->prepare("INSERT INTO role_permissions (role_id, module_key, can_view, can_create, can_edit, can_delete) VALUES (:role, :mod, :v, :c, :e, :d)");
        foreach ($permissions as $mod => $perms) {
            $stmt->execute([
                'role' => $roleId,
                'mod'  => $mod,
                'v'    => $perms['can_view'] ?? 0,
                'c'    => $perms['can_create'] ?? 0,
                'e'    => $perms['can_edit'] ?? 0,
                'd'    => $perms['can_delete'] ?? 0,
            ]);
        }
    }

    /**
     * Delete a role
     */
    public function deleteRole($id, $orgId) {
        $role = $this->getRoleById($id, $orgId);
        if (!$role || $role['is_system']) {
            return false; // Cannot delete system roles
        }
        $stmt = $this->pdo->prepare("DELETE FROM roles WHERE id = :id AND organization_id = :org");
        return $stmt->execute(['id' => $id, 'org' => $orgId]);
    }

    /**
     * Get all permissions for a specific role
     */
    public function getRolePermissions($roleId) {
        $stmt = $this->pdo->prepare("SELECT * FROM role_permissions WHERE role_id = :id");
        $stmt->execute(['id' => $roleId]);
        $rows = $stmt->fetchAll();
        $perms = [];
        foreach ($rows as $r) {
            $perms[$r['module_key']] = $r;
        }
        return $perms;
    }

    /**
     * Check if a user has permission
     */
    public function hasPermission($roleId, $moduleKey, $action = 'can_view') {
        if (!$roleId) return false;

        $stmt = $this->pdo->prepare("SELECT $action FROM role_permissions WHERE role_id = :role AND module_key = :mod");
        $stmt->execute(['role' => $roleId, 'mod' => $moduleKey]);
        return (bool)$stmt->fetchColumn();
    }
}
