<?php
// login.php
require_once __DIR__ . "/includes/auth_helper.php";

// Redirect if already authenticated
if (getCurrentUser()) {
    header("Location: index.php");
    exit;
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $loginInput = trim($_POST['login_input'] ?? $_POST['username'] ?? '');
    $password   = $_POST['password'] ?? '';
    $isAjax     = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                  || (isset($_POST['ajax']) && $_POST['ajax'] == '1');

    if (empty($loginInput) || empty($password)) {
        $error = "Please enter both Username/Email and Password.";
    } else {
        $res = authenticateUser($conn, $loginInput, $password);
        if ($res['success']) {
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
        }
    }

    if ($isAjax && !empty($error)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => $error]);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sign In - Inventory System</title>
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
            max-width: 420px;
        }

        .auth-card {
            background: #161d2a;
            border: 1px solid #242f42;
            border-radius: 16px;
            padding: 36px 30px;
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
            margin-bottom: 28px;
        }

        .auth-logo-icon {
            width: 56px;
            height: 56px;
            background: rgba(99, 102, 241, 0.15);
            color: #6366f1;
            border-radius: 14px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 14px;
            border: 1px solid rgba(99, 102, 241, 0.3);
        }

        .auth-header h1 {
            font-size: 24px;
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

        .alert-success {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #86efac;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #94a3b8;
            margin-bottom: 8px;
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
            padding: 12px 16px 12px 42px;
            color: #f8fafc;
            font-size: 14px;
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
            padding: 13px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(99, 102, 241, 0.3);
        }

        .btn-submit:hover {
            background: #4f46e5;
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(99, 102, 241, 0.4);
        }

        .btn-submit:active {
            transform: translateY(0);
        }

        .demo-box {
            background: rgba(255, 255, 255, 0.03);
            border: 1px dashed #2f3e57;
            border-radius: 10px;
            padding: 10px 14px;
            margin-top: 20px;
            font-size: 12px;
            color: #94a3b8;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .demo-box code {
            color: #a78bfa;
            font-weight: 600;
            background: rgba(167, 139, 250, 0.1);
            padding: 2px 6px;
            border-radius: 4px;
        }

        .auth-footer {
            margin-top: 24px;
            padding-top: 18px;
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
                <i class="fa-solid fa-boxes-stacked"></i>
            </div>
            <h1>Inventory Portal</h1>
            <p>Sign in to access Category & Product Management</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert-box alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" id="loginForm">
            <div class="form-group">
                <label for="login_input">Username or Email</label>
                <div class="input-wrapper">
                    <input type="text" class="form-control-custom" id="login_input" name="login_input" 
                           placeholder="e.g. admin or admin@example.com" required 
                           value="<?= htmlspecialchars($_POST['login_input'] ?? '') ?>">
                    <i class="fa-solid fa-user input-icon"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-wrapper">
                    <input type="password" class="form-control-custom" id="password" name="password" 
                           placeholder="Enter your password" required>
                    <i class="fa-solid fa-lock input-icon"></i>
                    <i class="fa-solid fa-eye toggle-password" id="togglePasswordBtn"></i>
                </div>
            </div>

            <button type="submit" class="btn-submit">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In
            </button>
        </form>

        <div class="demo-box">
            <span><i class="fa-solid fa-key me-1"></i> Default Admin:</span>
            <span><code>admin</code> / <code>1234</code></span>
        </div>

        <div class="auth-footer">
            Don't have an account? <a href="register.php">Create User Account</a>
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
