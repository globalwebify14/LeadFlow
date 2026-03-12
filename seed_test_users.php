<?php
/**
 * Test Users Seeder
 * Visit: http://localhost/seed_test_users.php
 * DELETE THIS FILE after seeding!
 */
require_once 'config/db.php';

$users = [
    ['name' => 'Super Admin', 'email' => 'superadmin@crm.com', 'role' => 'super_admin', 'org' => null],
    ['name' => 'Org Owner',   'email' => 'owner@crm.com',      'role' => 'org_owner',   'org' => 1],
    ['name' => 'Org Admin',   'email' => 'orgadmin@crm.com',   'role' => 'org_admin',   'org' => 1],
    ['name' => 'Team Lead',   'email' => 'teamlead@crm.com',   'role' => 'team_lead',   'org' => 1],
    ['name' => 'Sales Agent', 'email' => 'agent@crm.com',      'role' => 'agent',       'org' => 1],
];

$password = password_hash('Test@1234', PASSWORD_DEFAULT);

$results = [];
foreach ($users as $u) {
    $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, organization_id, is_active) 
                           VALUES (:name, :email, :password, :role, :org, 1)
                           ON DUPLICATE KEY UPDATE role = :role2, password = :password2");
    $stmt->execute([
        'name'      => $u['name'],
        'email'     => $u['email'],
        'password'  => $password,
        'role'      => $u['role'],
        'org'       => $u['org'],
        'role2'     => $u['role'],
        'password2' => $password,
    ]);
    $results[] = $u;
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8">
<title>Seed Test Users</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
<div class="container" style="max-width:640px;">
    <div class="card shadow border-0">
        <div class="card-header bg-success text-white"><h5 class="mb-0">✅ Test Users Created Successfully</h5></div>
        <div class="card-body">
            <table class="table table-bordered align-middle mb-0">
                <thead class="table-dark"><tr><th>Role</th><th>Email</th><th>Password</th></tr></thead>
                <tbody>
                    <?php foreach ($results as $u): ?>
                    <tr>
                        <td><span class="badge bg-primary"><?= $u['role'] ?></span></td>
                        <td><code><?= $u['email'] ?></code></td>
                        <td><code>Test@1234</code></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <div class="alert alert-warning mt-3 mb-0">
                ⚠️ <strong>Delete this file after seeding!</strong>
                Delete <code>seed_test_users.php</code> from your project root.
            </div>
        </div>
        <div class="card-footer d-flex gap-2">
            <a href="<?= BASE_URL ?>login.php" class="btn btn-primary">Go to Login →</a>
        </div>
    </div>
</div>
</body></html>
