<?php
$pageTitle = 'My Profile';
require_once '../../config/auth.php';
requireLogin();
require_once '../../config/db.php';

$userId = $_SESSION['user_id'];
$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$name || !$email) {
        $error = 'Name and Email are required.';
    } else {
        // Check if email belongs to someone else
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

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-person-circle me-2 text-primary"></i>My Profile</h4>
        <p class="text-muted small mb-0">Update your account details and password.</p>
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

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 pt-4 pb-0">
                <h6 class="fw-bold mb-0">Account Details</h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role</label>
                        <input type="text" class="form-control bg-light" value="<?= ucfirst(str_replace('_', ' ', $user['role'])) ?>" readonly disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="<?= e($user['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" value="<?= e($user['email']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Phone</label>
                        <input type="text" class="form-control" name="phone" value="<?= e($user['phone']) ?>">
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">New Password</label>
                        <input type="password" class="form-control" name="password" minlength="6" placeholder="Leave blank to keep current password">
                        <div class="form-text">Must be at least 6 characters.</div>
                    </div>
                    <button type="submit" class="btn btn-primary px-4 fw-semibold"><i class="bi bi-check-circle me-1"></i> Save Changes</button>
                    <a href="javascript:history.back()" class="btn btn-outline-secondary ms-2">Cancel</a>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
