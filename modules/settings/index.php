<?php
$pageTitle = 'Profile & API';
require_once '../../config/auth.php';
requireLogin();

$userRole = getUserRole();
if (!in_array($userRole, ['super_admin', 'org_owner', 'org_admin'])) {
    redirect(BASE_URL . 'modules/settings/profile.php');
}

require_once '../../config/db.php';

$orgId = getOrgId();
$userId = $_SESSION['user_id'];
$success = $error = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$name || !$email) {
        $error = 'Name and Email are required.';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $stmt->execute(['email' => $email, 'id' => $userId]);
        if ($stmt->fetch()) {
            $error = 'Email is already in use by another account.';
        } else {
            if ($password) {
                if (strlen($password) < 6) {
                    $error = 'Password must be at least 6 characters.';
                } else {
                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("UPDATE users SET name = :n, email = :e, phone = :p, password = :pwd WHERE id = :id");
                    $stmt->execute(['n' => $name, 'e' => $email, 'p' => $phone, 'pwd' => $hash, 'id' => $userId]);
                    $success = 'Profile & Password updated successfully!';
                    $_SESSION['user_name'] = $name;
                }
            } else {
                $stmt = $pdo->prepare("UPDATE users SET name = :n, email = :e, phone = :p WHERE id = :id");
                $stmt->execute(['n' => $name, 'e' => $email, 'p' => $phone, 'id' => $userId]);
                $success = 'Profile updated successfully!';
                $_SESSION['user_name'] = $name;
            }
        }
    }
}

// Get User Info
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmtUser->execute(['id' => $userId]);
$user = $stmtUser->fetch();

// Get organization info
$orgStmt = $pdo->prepare("SELECT * FROM organizations WHERE id = :id");
$orgStmt->execute(['id' => $orgId]);
$org = $orgStmt->fetch();

// Get subscription
$subStmt = $pdo->prepare("SELECT s.*, p.name as plan_name, p.max_users, p.max_leads, p.max_deals, p.price, p.billing_cycle, p.features FROM subscriptions s INNER JOIN plans p ON s.plan_id = p.id WHERE s.organization_id = :org ORDER BY s.id DESC LIMIT 1");
$subStmt->execute(['org' => $orgId]);
$subscription = $subStmt->fetch();

// Get API keys
$apiStmt = $pdo->prepare("SELECT * FROM api_keys WHERE organization_id = :org");
$apiStmt->execute(['org' => $orgId]);
$apiKeys = $apiStmt->fetchAll();

// Counts
$userCountStmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE organization_id = :org AND is_active = 1");
$userCountStmt->execute(['org' => $orgId]);
$userCount = $userCountStmt->fetchColumn();

$leadCountStmt = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE organization_id = :org");
$leadCountStmt->execute(['org' => $orgId]);
$leadCount = $leadCountStmt->fetchColumn();

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-person-gear me-2 text-primary"></i>Profile & API Settings</h4>
        <p class="text-muted small mb-0">Manage your personal account, subscription, and developer integrations.</p>
    </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success border-0 rounded-3 d-flex align-items-center mb-4 shadow-sm">
    <i class="bi bi-check-circle-fill me-2 fs-5"></i><?= e($success) ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger border-0 rounded-3 mb-4 shadow-sm">
    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i><?= e($error) ?>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Left Column: Profile & API -->
    <div class="col-lg-7">
        
        <!-- Personal Profile -->
        <div class="card shadow-sm border-0 mb-4 bg-white rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 pt-4 pb-2 px-4">
                <h6 class="fw-bold mb-0 d-flex align-items-center"><i class="bi bi-person-circle fs-5 me-2 text-primary"></i> My Profile</h6>
            </div>
            <div class="card-body px-4 pb-4">
                <form method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted mb-1">Full Name</label>
                            <input type="text" class="form-control" name="name" value="<?= e($user['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted mb-1">Email Address</label>
                            <input type="email" class="form-control" name="email" value="<?= e($user['email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted mb-1">Phone Number</label>
                            <input type="text" class="form-control" name="phone" value="<?= e($user['phone']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small text-muted mb-1">Role</label>
                            <input type="text" class="form-control bg-light text-muted" value="<?= ucfirst(str_replace('_', ' ', $user['role'])) ?>" readonly disabled>
                        </div>
                        <div class="col-12 mt-4">
                            <label class="form-label fw-semibold small text-muted mb-1">Change Password <span class="fw-normal">(Optional)</span></label>
                            <input type="password" class="form-control" name="password" minlength="6" placeholder="Leave blank to keep current password">
                            <div class="form-text small">Must be at least 6 characters long.</div>
                        </div>
                    </div>
                    <div class="mt-4 pt-2 border-top">
                        <button type="submit" class="btn btn-primary fw-semibold px-4 rounded-3"><i class="bi bi-check-circle me-1"></i> Update Profile</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- API Keys -->
        <div class="card shadow-sm border-0 bg-white rounded-4 overflow-hidden">
            <div class="card-header bg-white border-0 pt-4 pb-2 px-4 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 d-flex align-items-center"><i class="bi bi-key fs-5 me-2 text-warning"></i> Developer API Keys</h6>
            </div>
            <div class="card-body px-4 pb-4">
                
                <div class="alert alert-info border-0 bg-info bg-opacity-10 d-flex mb-4 rounded-3">
                    <i class="bi bi-info-circle-fill text-info mt-1 me-3 fs-5"></i>
                    <div>
                        <strong class="text-info-emphasis d-block mb-1">What is an API Key?</strong>
                        <span class="small text-muted">An API key acts like a secret password that allows external services (like Zapier, Make.com, or your custom website) to securely communicate with your CRM. You can use it to automatically push new leads from your website straight into this database without any manual entry.</span>
                    </div>
                </div>

                <?php if (empty($apiKeys)): ?>
                    <div class="text-center py-4 bg-light rounded-3 border-dashed">
                        <i class="bi bi-shield-lock text-muted fs-2 mb-2"></i>
                        <p class="text-muted small mb-0">No API keys generated yet.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light text-muted small text-uppercase">
                                <tr>
                                    <th>Key Name</th>
                                    <th>Token</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($apiKeys as $key): ?>
                                <tr>
                                    <td class="fw-semibold text-dark"><?= e($key['name']) ?></td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <code class="bg-light p-1 rounded text-dark user-select-all"><?= e(substr($key['api_key'], 0, 8)) ?>...<?= e(substr($key['api_key'], -8)) ?></code>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $key['is_active'] ? 'success' : 'danger' ?> bg-opacity-10 text-<?= $key['is_active'] ? 'success' : 'danger' ?> border border-<?= $key['is_active'] ? 'success' : 'danger' ?> border-opacity-25 rounded-pill px-2">
                                            <?= $key['is_active'] ? 'Active' : 'Disabled' ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <!-- Right Column: Subscription & Usage -->
    <div class="col-lg-5">
        
        <div class="card shadow-sm border-0 h-100 bg-white rounded-4 overflow-hidden position-relative">

            
            <div class="card-header bg-white border-0 pt-4 pb-2 px-4 position-relative z-1 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 d-flex align-items-center"><i class="bi bi-gem fs-5 me-2 text-primary"></i> Active Subscription</h6>
                <?php if ($userRole === 'super_admin' || $userRole === 'org_owner'): ?>
                    <a href="<?= BASE_URL ?>modules/billing/" class="btn btn-sm btn-outline-primary fw-semibold rounded-pill px-3 transition-hover">Upgrade</a>
                <?php endif; ?>
            </div>
            
            <div class="card-body px-4 pb-4 position-relative z-1">
                <?php if ($subscription): ?>
                    <!-- Plan Header -->
                    <div class="d-flex align-items-center mb-4 pb-3 border-bottom">
                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-3" style="width:50px; height:50px;">
                            <i class="bi bi-hdd-network text-primary fs-4"></i>
                        </div>
                        <div>
                            <h4 class="fw-bolder mb-0 text-dark"><?= e($subscription['plan_name']) ?> <span class="badge bg-<?= $subscription['status']==='active'?'success':($subscription['status']==='trial'?'info':'danger') ?> align-middle ms-2" style="font-size: 0.6rem; vertical-align: middle !important;"><?= strtoupper($subscription['status']) ?></span></h4>
                            <div class="small fw-semibold text-muted mt-1">Billed <?= ucfirst($subscription['billing_cycle'] ?? 'Monthly') ?></div>
                        </div>
                    </div>
                    
                    <!-- Usage Metrics -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-end mb-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">User Seats</h6>
                                <span class="small text-muted"><?= $userCount ?> of <?= $subscription['max_users'] ?> used</span>
                            </div>
                            <div class="fw-bold fs-5 text-dark"><?= round(($userCount / max(1,$subscription['max_users'])) * 100) ?>%</div>
                        </div>
                        <div class="progress" style="height: 8px; border-radius: 10px;">
                            <div class="progress-bar bg-primary" style="width: <?= min(100, round(($userCount / max(1,$subscription['max_users'])) * 100)) ?>%"></div>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-end mb-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Data Storage (Leads)</h6>
                                <span class="small text-muted"><?= number_format($leadCount) ?> of <?= number_format($subscription['max_leads']) ?> used</span>
                            </div>
                            <div class="fw-bold fs-5 text-dark"><?= round(($leadCount / max(1,$subscription['max_leads'])) * 100) ?>%</div>
                        </div>
                        <div class="progress" style="height: 8px; border-radius: 10px;">
                            <?php $lPct = min(100, round(($leadCount / max(1,$subscription['max_leads'])) * 100)); ?>
                            <div class="progress-bar <?= $lPct > 85 ? 'bg-danger' : 'bg-success' ?>" style="width: <?= $lPct ?>%"></div>
                        </div>
                    </div>

                    <!-- Renewal Info -->
                    <div class="bg-light rounded-4 p-3 mt-4 border">
                        <div class="d-flex align-items-center">
                            <i class="bi bi-calendar-check text-muted fs-4 me-3"></i>
                            <div>
                                <div class="small fw-bold text-dark mb-0">Next Billing Date</div>
                                <div class="small text-muted">
                                    <?php if ($subscription['expires_at']): ?>
                                        <?= formatDate($subscription['expires_at']) ?>
                                    <?php else: ?>
                                        Does not expire
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <div class="text-center py-5 h-100 d-flex flex-column justify-content-center align-items-center">
                        <div class="rounded-circle bg-light d-flex align-items-center justify-content-center mb-3" style="width:80px; height:80px;">
                            <i class="bi bi-wallet2 fs-1 text-muted"></i>
                        </div>
                        <h4 class="fw-bold text-dark">No Active Plan</h4>
                        <p class="text-muted small max-w-sm mx-auto mb-4">You are currently operating without a subscription limits package. Upgrade to unlock more leads and users.</p>
                        <a href="<?= BASE_URL ?>modules/billing/" class="btn btn-primary fw-semibold px-4 rounded-pill shadow-sm">View Pricing Plans</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<style>
.border-dashed { border: 1px dashed #dee2e6; }
.transition-hover { transition: all 0.2s ease-in-out; }
.transition-hover:hover { transform: translateY(-2px); }
</style>

<?php include '../../includes/footer.php'; ?>
