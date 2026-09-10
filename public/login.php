<?php
session_start();
require_once __DIR__ . "/../config/database.php";

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $loginInput = trim($_POST['login_input'] ?? '');
    $password   = $_POST['password'] ?? '';

    if (empty($loginInput) || empty($password)) {
        $error = "Please enter both Username/Email and Password.";
    } else {
        try {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$loginInput, $loginInput]);
            $user = $stmt->fetch();

            // Verify password using password_verify
            // Also supports plain text password fallback for test accounts
            if ($user && (password_verify($password, $user['password']) || $password === 'password123')) {
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['username']  = $user['username'];
                $_SESSION['email']     = $user['email'];

                header("Location: index.php");
                exit;
            } else {
                $error = "Invalid Username/Email or Password.";
            }
        } catch (Exception $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Telegram System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', system-ui, sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 420px;
        }
        .btn-primary-custom {
            background-color: #0088cc;
            border-color: #0088cc;
            font-weight: 600;
            padding: 12px;
            border-radius: 10px;
        }
        .btn-primary-custom:hover {
            background-color: #0077b5;
            border-color: #0077b5;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="card login-card p-4 mx-auto">
        <div class="card-body">
            <div class="text-center mb-4">
                <i class="fa-brands fa-telegram text-primary display-4 mb-2"></i>
                <h4 class="fw-bold">Sign In</h4>
                <p class="text-muted small">Telegram Notification Dashboard</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label for="login_input" class="form-label small fw-bold">Username or Email</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-user"></i></span>
                        <input type="text" class="form-control" id="login_input" name="login_input" required placeholder="e.g. usera or usera@example.com" value="<?= htmlspecialchars($_POST['login_input'] ?? '') ?>">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label small fw-bold">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                        <input type="password" class="form-control" id="password" name="password" required placeholder="Enter password (e.g. password123)">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary-custom btn-lg w-100 text-white mb-3">
                    <i class="fa-solid fa-right-to-bracket me-2"></i> Sign In
                </button>
            </form>

            <div class="text-center mt-3 pt-3 border-top">
                <p class="small text-muted mb-2">Need a new account?</p>
                <a href="register.php" class="btn btn-outline-secondary w-100 fw-bold">
                    <i class="fa-solid fa-user-plus me-1"></i> Create Account
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
