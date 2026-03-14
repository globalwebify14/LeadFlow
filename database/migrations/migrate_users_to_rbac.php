<?php
// migrate_users_to_rbac.php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../models/Role.php';

$roleModel = new Role($pdo);

// All available modules
$allModules = [
    'dashboard', 'leads', 'pipeline', 'deals', 
    'tasks', 'reports', 'users', 'org_settings', 'facebook_leads', 'automation'
];

// Define default template roles
$templates = [
    'Org Owner' => [
        'is_system' => 1,
        'perms' => array_fill_keys($allModules, ['can_view'=>1, 'can_create'=>1, 'can_edit'=>1, 'can_delete'=>1])
    ],
    'Org Admin' => [
        'is_system' => 1,
        'perms' => array_fill_keys($allModules, ['can_view'=>1, 'can_create'=>1, 'can_edit'=>1, 'can_delete'=>1])
    ],
    'Team Lead' => [
        'is_system' => 1,
        'perms' => array_fill_keys(['dashboard', 'leads', 'pipeline', 'deals', 'tasks', 'reports'], ['can_view'=>1, 'can_create'=>1, 'can_edit'=>1, 'can_delete'=>1])
    ],
    'Agent' => [
        'is_system' => 1,
        'perms' => array_fill_keys(['dashboard', 'leads', 'pipeline', 'deals', 'tasks'], ['can_view'=>1, 'can_create'=>1, 'can_edit'=>1, 'can_delete'=>0])
    ]
];

try {
    $pdo->beginTransaction();

    // 1. Fetch all organizations
    $orgs = $pdo->query("SELECT id FROM organizations")->fetchAll();

    foreach ($orgs as $org) {
        $orgId = $org['id'];

        $roleIds = [];

        // 2. Create template roles for each organization
        foreach ($templates as $roleName => $data) {
            // check if exists
            $stmt = $pdo->prepare("SELECT id FROM roles WHERE organization_id = :org AND name = :name");
            $stmt->execute(['org' => $orgId, 'name' => $roleName]);
            $existing = $stmt->fetchColumn();

            if ($existing) {
                $roleIds[$roleName] = $existing;
            } else {
                // insert role
                $stmt = $pdo->prepare("INSERT INTO roles (organization_id, name, is_system) VALUES (:org, :name, :sys)");
                $stmt->execute(['org' => $orgId, 'name' => $roleName, 'sys' => $data['is_system']]);
                $roleId = $pdo->lastInsertId();
                $roleIds[$roleName] = $roleId;

                // insert perms
                $stmtPerm = $pdo->prepare("INSERT INTO role_permissions (role_id, module_key, can_view, can_create, can_edit, can_delete) VALUES (:role, :mod, :v, :c, :e, :d)");
                foreach ($data['perms'] as $modKey => $perms) {
                    $stmtPerm->execute([
                        'role' => $roleId,
                        'mod'  => $modKey,
                        'v'    => $perms['can_view'],
                        'c'    => $perms['can_create'],
                        'e'    => $perms['can_edit'],
                        'd'    => $perms['can_delete']
                    ]);
                }
            }
        }

        // 3. Map users in this org to the new roles
        $mapping = [
            'org_owner' => $roleIds['Org Owner'],
            'org_admin' => $roleIds['Org Admin'],
            'team_lead' => $roleIds['Team Lead'],
            'agent'     => $roleIds['Agent']
        ];

        foreach ($mapping as $oldRole => $newRoleId) {
            $updateStmt = $pdo->prepare("UPDATE users SET role_id = :rid WHERE organization_id = :org AND role = :oldrole AND role_id IS NULL");
            $updateStmt->execute(['rid' => $newRoleId, 'org' => $orgId, 'oldrole' => $oldRole]);
        }
    }

    $pdo->commit();
    echo "User migration to RBAC completed successfully!\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "Migration failed: " . $e->getMessage() . "\n";
}
