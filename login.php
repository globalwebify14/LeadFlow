<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: modules/dashboard/');
    exit;
}

$error = '';
$email = ''; // Initialize to avoid warning

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/db.php';
    require_once 'models/User.php';
    
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields.';
    } else {
        $stmt = $pdo->prepare("SELECT u.*, o.name as org_name FROM users u LEFT JOIN organizations o ON u.organization_id = o.id WHERE u.email = :email AND u.is_active = 1 LIMIT 1");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['organization_id'] = $user['organization_id'];
            $_SESSION['org_name'] = $user['org_name'] ?? 'My Company';

            // Update last login
            $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
            
            header('Location: modules/dashboard/');
            exit;
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — LeadFlow</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #4f46e5;
            --primary-dark: #3730a3;
            --bg: #f8fafc;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            background-image: radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.05) 0px, transparent 50%), 
                              radial-gradient(at 100% 100%, rgba(99, 102, 241, 0.05) 0px, transparent 50%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 48px;
            width: 100%;
            max-width: 460px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
            border: 1px solid #e2e8f0;
        }
        .brand {
            font-family: 'Poppins', sans-serif;
            font-size: 28px;
            font-weight: 800;
            color: #1e293b;
            text-align: center;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .brand i { color: var(--primary); }
        .brand span { color: var(--primary); }
        .subtitle {
            text-align: center;
            color: #64748b;
            margin-bottom: 40px;
            font-size: 15px;
        }
        .form-label {
            font-weight: 600;
            font-size: 13px;
            color: #1e293b;
            margin-bottom: 8px;
        }
        .form-control {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            font-size: 15px;
            transition: all 0.2s;
        }
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }
        .btn-login {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: #fff;
            border: none;
            padding: 14px;
            border-radius: 12px;
            width: 100%;
            font-weight: 700;
            margin-top: 10px;
            transition: all 0.3s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(79, 70, 229, 0.4);
        }
        .error-alert {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fee2e2;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            text-align: center;
        }
        .demo-box {
            margin-top: 32px;
            padding: 20px;
            background: #f1f5f9;
            border-radius: 16px;
            text-align: center;
            font-size: 13px;
            color: #64748b;
        }
        .demo-box strong { color: #1e293b; }
        .back-home {
            display: block;
            text-align: center;
            margin-top: 24px;
            color: #64748b;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
        }
        .back-home:hover { color: var(--primary); }
    </style>
</head>
<body>
    <div class="login-card">
        <a href="index.php" class="brand text-decoration-none">
            <i class="bi bi-rocket-takeoff-fill"></i> Lead<span>Flow</span>
        </a>
        <p class="subtitle">Sign in to your dashboard</p>

        <?php if ($error): ?>
            <div class="error-alert">
                <i class="bi bi-exclamation-circle-fill me-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" id="loginForm">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="name@company.com" value="<?= htmlspecialchars($email) ?>" required>
            </div>
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <label class="form-label mb-0">Password</label>
                    <a href="#" class="text-decoration-none small fw-medium" style="color: var(--primary);">Forgot?</a>
                </div>
                <div class="position-relative">
                    <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
                    <button type="button" class="btn position-absolute end-0 top-50 translate-middle-y border-0 text-muted" onclick="togglePassword()">
                        <i class="bi bi-eye" id="toggleIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn-login shadow-lg">Sign In <i class="bi bi-arrow-right ms-2"></i></button>
        </form>

        <div class="demo-box">
            <p class="mb-1">Want to try the demo?</p>
            <strong>admin@crm.com</strong> / <strong>admin123</strong>
        </div>

        <a href="index.php" class="back-home"><i class="bi bi-arrow-left me-2"></i>Back to home</a>
    </div>

    <script>
        function togglePassword() {
            const pwd = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');
            
            if (pwd.type === 'password') {
                pwd.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                pwd.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
    </script>
</body>
</html>
