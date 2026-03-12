<?php
$pageTitle = 'Organization Settings';
require_once '../../config/auth.php';
requireLogin();
requireRole(['org_owner', 'org_admin']);
require_once '../../config/db.php';
require_once '../../models/ActivityLog.php';

$orgId = getOrgId();
$userRole = getUserRole();
$success = $error = '';

// Load org
$org = $pdo->prepare("SELECT * FROM organizations WHERE id = :id");
$org->execute(['id' => $orgId]);
$org = $org->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $website = trim($_POST['website'] ?? '');

    if (!$name) {
        $error = 'Organization name is required.';
    } else {
        // Handle logo upload
        $logo = $org['logo'] ?? null;
        if (!empty($_FILES['logo']['name'])) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                $uploadDir = __DIR__ . '/../../assets/uploads/logos/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'org_' . $orgId . '_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $filename)) {
                    $logo = BASE_URL . 'assets/uploads/logos/' . $filename;
                }
            } else {
                $error = 'Invalid logo file type. Use JPG, PNG, GIF, or WebP.';
            }
        }

        if (!$error) {
            $pdo->prepare("UPDATE organizations SET name=:name, email=:email, phone=:phone, address=:address, website=:website, logo=:logo WHERE id=:id")
                ->execute(['name'=>$name,'email'=>$email,'phone'=>$phone,'address'=>$address,'website'=>$website,'logo'=>$logo,'id'=>$orgId]);

            // Update session org name
            $_SESSION['org_name'] = $name;
            ActivityLog::write($pdo, 'org_settings_updated', "Organization '{$name}' settings updated");
            $success = 'Organization settings saved successfully!';

            // Reload
            $s = $pdo->prepare("SELECT * FROM organizations WHERE id = :id");
            $s->execute(['id' => $orgId]);
            $org = $s->fetch();
        }
    }
}

// Subscription info
$sub = $pdo->prepare("SELECT s.*, p.name as plan_name, p.max_users, p.max_leads, p.max_deals FROM subscriptions s JOIN plans p ON s.plan_id=p.id WHERE s.organization_id=:org ORDER BY s.id DESC LIMIT 1");
$sub->execute(['org' => $orgId]);
$subscription = $sub->fetch();

$userCount = $pdo->prepare("SELECT COUNT(*) FROM users WHERE organization_id=:o"); $userCount->execute(['o'=>$orgId]); $userCount = $userCount->fetchColumn();
$leadCount = $pdo->prepare("SELECT COUNT(*) FROM leads WHERE organization_id=:o"); $leadCount->execute(['o'=>$orgId]); $leadCount = $leadCount->fetchColumn();

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-building-gear me-2 text-primary"></i>Organization Settings</h4>
        <p class="text-muted small mb-0">Manage your organization profile and information</p>
    </div>
</div>

<?php if ($success): ?>
<div class="alert alert-success border-0 rounded-3 d-flex align-items-center mb-4">
    <i class="bi bi-check-circle-fill me-2 fs-5"></i><?= e($success) ?>
</div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-danger border-0 rounded-3 mb-4"><?= e($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Organization Profile Form -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h6 class="fw-bold mb-0"><i class="bi bi-pencil me-2 text-primary"></i>Organization Profile</h6>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <!-- Logo -->
                    <div class="mb-4 d-flex align-items-center gap-3">
                        <?php if (!empty($org['logo'])): ?>
                        <img src="<?= e($org['logo']) ?>" alt="Logo" class="rounded-3 border" style="width:72px;height:72px;object-fit:cover;">
                        <?php else: ?>
                        <div class="rounded-3 d-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary fw-bold" style="width:72px;height:72px;font-size:26px;">
                            <?= strtoupper(substr($org['name'], 0, 1)) ?>
                        </div>
                        <?php endif; ?>
                        <div>
                            <label class="form-label fw-semibold mb-1">Company Logo</label>
                            <input type="file" name="logo" class="form-control form-control-sm" accept="image/*">
                            <div class="form-text">JPG, PNG, WebP — Max 2MB</div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Organization Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?= e($_POST['name'] ?? $org['name']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Contact Email</label>
                            <input type="email" name="email" class="form-control" value="<?= e($_POST['email'] ?? $org['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control" value="<?= e($_POST['phone'] ?? $org['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Website</label>
                            <input type="url" name="website" class="form-control" placeholder="https://" value="<?= e($_POST['website'] ?? $org['website'] ?? '') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-semibold">Address</label>
                            <textarea name="address" class="form-control" rows="3"><?= e($_POST['address'] ?? $org['address'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <div class="mt-4">
                        <button type="submit" class="btn btn-primary fw-semibold px-4">
                            <i class="bi bi-check-lg me-1"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Sidebar: Subscription & Usage -->
    <div class="col-lg-4">
        <!-- Subscription -->
        <?php if ($userRole !== 'org_admin'): ?>
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h6 class="fw-bold mb-0"><i class="bi bi-credit-card me-2 text-success"></i>Subscription</h6>
            </div>
            <div class="card-body">
                <?php if ($subscription): ?>
                <div class="d-flex align-items-center gap-2 mb-3">
                    <h5 class="fw-bold mb-0"><?= e($subscription['plan_name']) ?></h5>
                    <span class="badge bg-<?= $subscription['status']==='active'?'success':($subscription['status']==='trial'?'info':'danger') ?>">
                        <?= ucfirst($subscription['status']) ?>
                    </span>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">Users</span>
                        <span class="fw-semibold"><?= $userCount ?> / <?= $subscription['max_users'] ?></span>
                    </div>
                    <div class="progress" style="height:5px;">
                        <div class="progress-bar bg-primary" style="width:<?= min(100, round($userCount/$subscription['max_users']*100)) ?>%"></div>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between small mb-1">
                        <span class="text-muted">Leads</span>
                        <span class="fw-semibold"><?= $leadCount ?> / <?= number_format($subscription['max_leads']) ?></span>
                    </div>
                    <div class="progress" style="height:5px;">
                        <div class="progress-bar bg-success" style="width:<?= min(100, round($leadCount/$subscription['max_leads']*100)) ?>%"></div>
                    </div>
                </div>
                <?php if ($subscription['expires_at']): ?>
                <p class="text-muted small mt-3 mb-0"><i class="bi bi-calendar me-1"></i>Expires: <?= formatDate($subscription['expires_at']) ?></p>
                <?php endif; ?>
                <?php else: ?>
                <p class="text-muted small">No active subscription. Contact your Super Admin.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Organization Info -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h6 class="fw-bold mb-0"><i class="bi bi-info-circle me-2 text-info"></i>Quick Info</h6>
            </div>
            <div class="card-body">
                <table class="table table-borderless table-sm small mb-0">
                    <tr><td class="text-muted">Status</td><td><span class="badge bg-<?= $org['status']==='active'?'success':'danger' ?>"><?= ucfirst($org['status']) ?></span></td></tr>
                    <tr><td class="text-muted">Total Users</td><td class="fw-semibold"><?= $userCount ?></td></tr>
                    <tr><td class="text-muted">Total Leads</td><td class="fw-semibold"><?= $leadCount ?></td></tr>
                    <tr><td class="text-muted">Created</td><td class="fw-semibold"><?= formatDate($org['created_at']) ?></td></tr>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
