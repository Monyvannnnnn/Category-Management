<?php
// register.php
require_once __DIR__ . "/includes/auth_helper.php";

// Redirect if already logged in
if (getCurrentUser()) {
    header("Location: index.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name     = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = trim($_POST['role'] ?? 'admin');
    $isAjax   = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || (isset($_POST['ajax']) && $_POST['ajax'] == '1');

    $res = createUserAccount($conn, $name, $username, $email, $password, $role);
    if ($res['success']) {
        // Auto Login upon successful registration
        $_SESSION['user_id']   = $res['user']['id'];
        $_SESSION['user_name'] = $res['user']['name'];
        $_SESSION['name']      = $res['user']['name'];
        $_SESSION['username']  = $res['user']['username'];
        $_SESSION['email']     = $res['user']['email'];
        $_SESSION['role']      = $res['user']['role'];

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'redirect' => 'index.php', 'user' => $res['user']]);
            exit;
        } else {
            header("Location: index.php");
            exit;
        }
    } else {
        $error = $res['message'];
        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => $error]);
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Create User Account - Inventory System</title>
    <!-- Google Font & Font Awesome -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body {
            background-color: #0f141c;
            color: #f8fafc;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            padding: 20px;
        }

        .auth-container {
            width: 100%;
            max-width: 440px;
        }

        .auth-card {
            background: #161d2a;
            border: 1px solid #242f42;
            border-radius: 16px;
            padding: 34px 28px;
            box-shadow: 0 12px 36px rgba(0, 0, 0, 0.4);
            position: relative;
            overflow: hidden;
        }

        .auth-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #6366f1, #a78bfa);
        }

        .auth-header {
            text-align: center;
            margin-bottom: 24px;
        }

        .auth-logo-icon {
            width: 52px;
            height: 52px;
            background: rgba(99, 102, 241, 0.15);
            color: #6366f1;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            margin-bottom: 12px;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        .auth-header h1 {
            font-size: 22px;
            font-weight: 700;
            color: #f8fafc;
            margin: 0 0 6px 0;
        }

        .auth-header p {
            color: #94a3b8;
            font-size: 13px;
            margin: 0;
        }

        .alert-box {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 13px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            margin-bottom: 6px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 14px;
            transition: color 0.2s ease;
        }

        .form-control-custom {
            width: 100%;
            background: #0f141c;
            border: 1px solid #2f3e57;
            border-radius: 10px;
            padding: 11px 16px 11px 42px;
            color: #f8fafc;
            font-size: 13px;
            font-family: inherit;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-control-custom:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        .form-control-custom:focus + .input-icon,
        .input-wrapper:focus-within .input-icon {
            color: #6366f1;
        }

        .toggle-password {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            cursor: pointer;
            font-size: 14px;
            padding: 4px;
            transition: color 0.2s ease;
        }

        .toggle-password:hover {
            color: #a78bfa;
        }

        .btn-submit {
            width: 100%;
            background: #6366f1;
            color: #ffffff;
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.3);
        }

        .btn-submit:hover {
            background: #4f46e5;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(99, 102, 241, 0.4);
        }

        .auth-footer {
            margin-top: 20px;
            padding-top: 16px;
            border-top: 1px solid #242f42;
            text-align: center;
            font-size: 13px;
            color: #94a3b8;
        }

        .auth-footer a {
            color: #6366f1;
            text-decoration: none;
            font-weight: 600;
            margin-left: 4px;
            transition: color 0.2s;
        }

        .auth-footer a:hover {
            color: #a78bfa;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="auth-container">
    <div class="auth-card">
        <div class="auth-header">
            <div class="auth-logo-icon">
                <i class="fa-solid fa-user-plus"></i>
            </div>
            <h1>Create Account</h1>
            <p>Register a new user account for Inventory System</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-box alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php" id="registerForm">
            <div class="form-group">
                <label for="name">Full Name</label>
                <div class="input-wrapper">
                    <input type="text" class="form-control-custom" id="name" name="name" 
                           placeholder="e.g. John Doe" required 
                           value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                    <i class="fa-solid fa-id-card input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-wrapper">
                    <input type="text" class="form-control-custom" id="username" name="username" 
                           placeholder="e.g. johndoe" required 
                           value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                    <i class="fa-solid fa-user input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-wrapper">
                    <input type="email" class="form-control-custom" id="email" name="email" 
                           placeholder="e.g. john@example.com" required 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <i class="fa-solid fa-envelope input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" class="form-control-custom" id="password" name="password" 
                           placeholder="Create a strong password" required minlength="4">
                    <i class="fa-solid fa-lock input-icon"></i>
                    <i class="fa-solid fa-eye toggle-password" id="togglePasswordBtn"></i>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-user-check"></i> Register Account
            </button>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="login.php">Sign In here</a>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const passwordInput = document.getElementById('password');

    if (toggleBtn && passwordInput) {
        toggleBtn.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });
    }
});
</script>

</body>
</html>
