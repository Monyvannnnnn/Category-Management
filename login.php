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
            max-width: 350px;
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
            display: flex;
            align-items: center;
        }

        .input-icon {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 15px;
            pointer-events: none;
            z-index: 10;
            transition: color 0.2s ease;
        }

        .form-control-custom {
            width: 100%;
            background: #0f141c;
            border: 1px solid #2f3e57;
            border-radius: 10px;
            padding: 12px 42px 12px 48px !important;
            color: #f8fafc;
            font-size: 14px;
            font-family: inherit;
            outline: none;
            transition: all 0.2s ease;
        }

        .form-control-custom:-webkit-autofill,
        .form-control-custom:-webkit-autofill:hover, 
        .form-control-custom:-webkit-autofill:focus, 
        .form-control-custom:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 1000px #0f141c inset !important;
            -webkit-text-fill-color: #f8fafc !important;
            caret-color: #f8fafc !important;
            padding-left: 48px !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        .form-control-custom:focus {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.2);
        }

        .form-control-custom:focus ~ .input-icon,
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
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #7c3aed 100%);
            background-size: 200% 200%;
            background-position: 0% 50%;
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
            position: relative;
            overflow: hidden;
            transition: transform 0.45s cubic-bezier(0.25, 1, 0.3, 1), 
                        background-position 0.65s cubic-bezier(0.25, 1, 0.3, 1), 
                        box-shadow 0.45s cubic-bezier(0.25, 1, 0.3, 1);
            box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35);
        }

        .btn-submit i {
            transition: transform 0.4s cubic-bezier(0.25, 1, 0.3, 1);
        }

        .btn-submit:hover {
            background-position: 100% 50%;
            transform: translateY(-2px) scale(1.01);
            box-shadow: 0 8px 25px -4px rgba(99, 102, 241, 0.55), 0 0 15px rgba(124, 58, 237, 0.35);
        }

        .btn-submit:hover i {
            transform: translateX(3px) scale(1.15);
        }

        .btn-submit:active {
            transform: translateY(0) scale(0.985);
            box-shadow: 0 2px 8px rgba(99, 102, 241, 0.4);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        /* Shimmer beam effect on hover */
        .btn-submit::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -60%;
            width: 50%;
            height: 200%;
            background: linear-gradient(
                90deg,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.35) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            transform: rotate(25deg);
            transition: left 0.85s cubic-bezier(0.25, 1, 0.3, 1), opacity 0.85s cubic-bezier(0.25, 1, 0.3, 1);
            opacity: 0;
            pointer-events: none;
        }

        .btn-submit:hover::after {
            left: 130%;
            opacity: 1;
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
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: wrap;
            gap: 6px;
        }

        .auth-footer a {
            color: #818cf8;
            text-decoration: none;
            font-weight: 600;
            padding: 5px 12px;
            border-radius: 8px;
            background: rgba(99, 102, 241, 0.1);
            border: 1px solid rgba(99, 102, 241, 0.25);
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: transform 0.4s cubic-bezier(0.25, 1, 0.3, 1), 
                        background 0.4s cubic-bezier(0.25, 1, 0.3, 1), 
                        border-color 0.4s cubic-bezier(0.25, 1, 0.3, 1), 
                        color 0.4s cubic-bezier(0.25, 1, 0.3, 1), 
                        box-shadow 0.4s cubic-bezier(0.25, 1, 0.3, 1);
        }

        .auth-footer a i {
            font-size: 11px;
            transition: transform 0.4s cubic-bezier(0.25, 1, 0.3, 1);
        }

        .auth-footer a:hover {
            color: #ffffff;
            background: rgba(99, 102, 241, 0.25);
            border-color: #6366f1;
            transform: translateY(-1.5px);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
        }

        .auth-footer a:hover i {
            transform: translateX(3px);
        }

        .auth-footer a:active {
            transform: translateY(0) scale(0.97);
            transition: transform 0.15s ease;
        }

        .btn-submit:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        @media (max-width: 480px) {
            body {
                padding: 10px 8px max(40px, env(safe-area-inset-bottom));
                align-items: flex-start;
                justify-content: center;
            }
            .auth-container {
                max-width: 340px;
                width: 92%;
                margin: 8px auto 0 auto;
            }
            .auth-card {
                padding: 20px 16px;
                border-radius: 12px;
            }
            .auth-header {
                margin-bottom: 16px;
            }
            .auth-logo-icon {
                width: 44px;
                height: 44px;
                font-size: 20px;
                margin-bottom: 8px;
                border-radius: 10px;
            }
            .auth-header h1 {
                font-size: 20px;
            }
            .auth-header p {
                font-size: 11.5px;
            }
            .form-group {
                margin-bottom: 12px;
            }
            .form-control-custom {
                padding: 10px 38px 10px 42px !important;
                font-size: 13.5px;
                border-radius: 8px;
            }
            .btn-submit {
                padding: 11px;
                font-size: 13.5px;
                border-radius: 8px;
            }
            .demo-box {
                margin-top: 14px;
                padding: 8px 10px;
                font-size: 11px;
            }
            .auth-footer {
                margin-top: 14px;
                padding-top: 12px;
                font-size: 12px;
            }
        }
    </style>

    <!-- Telegram Mini App WebApp SDK -->
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <script>
        if (window.Telegram && window.Telegram.WebApp) {
            window.Telegram.WebApp.ready();
            window.Telegram.WebApp.expand();
        }
    </script>
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
            <span>Don't have an account?</span>
            <a href="register.php">Create User Account <i class="fa-solid fa-arrow-right"></i></a>
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

    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function() {
            const submitBtn = this.querySelector('.btn-submit');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.classList.add("btn-loading");
            LoadingOverlay.show("Signing In...");
            }
        });
    }
});
</script>

</body>
</html>
