<?php
session_start();
require_once __DIR__ . "/../config/database.php";

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = "";
$success = "";

if (($_SERVER["REQUEST_METHOD"] ?? '') === "POST") {
    $name     = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($name) || empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address format.";
    } else {
        try {
            $pdo = getDBConnection();

            // Check if username or email already exists
            $chk = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $chk->execute([$username, $email]);
            if ($chk->fetch()) {
                $error = "Username or Email is already taken.";
            } else {
                // Hash password securely
                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                // Insert into users
                $stmt = $pdo->prepare("INSERT INTO users (name, username, email, password) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $username, $email, $hashedPassword]);
                $userId = $pdo->lastInsertId();

                // Create default bot setting for new user
                $botStmt = $pdo->prepare("INSERT INTO user_telegram_bots (user_id, bot_token, bot_username) VALUES (?, ?, ?)");
                $botStmt->execute([$userId, DEFAULT_BOT_TOKEN, DEFAULT_BOT_USERNAME]);

                // Auto Login
                $_SESSION['user_id']   = $userId;
                $_SESSION['user_name'] = $name;
                $_SESSION['username']  = $username;
                $_SESSION['email']     = $email;

                header("Location: index.php");
                exit;
            }
        } catch (Exception $e) {
            $error = "Registration Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Telegram System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .register-card {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            width: 100%;
            max-width: 440px;
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
    <div class="card register-card p-4 mx-auto">
        <div class="card-body">
            <div class="text-center mb-4">
                <i class="fa-brands fa-telegram text-primary display-4 mb-2"></i>
                <h4 class="fw-bold">Create Account</h4>
                <p class="text-muted small">Register for Telegram Notifications</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show small" role="alert">
                    <i class="fa-solid fa-circle-exclamation me-2"></i> <?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="register.php">
                <div class="mb-3">
                    <label for="name" class="form-label small fw-bold">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" required placeholder="e.g. John Doe" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="username" class="form-label small fw-bold">Username</label>
                    <input type="text" class="form-control" id="username" name="username" required placeholder="e.g. johndoe" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label small fw-bold">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" required placeholder="e.g. john@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label small fw-bold">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required placeholder="Create a secure password">
                </div>

                <button type="submit" class="btn btn-primary-custom btn-lg w-100 text-white mb-3">
                    <i class="fa-solid fa-user-plus me-2"></i> Register Account
                </button>
            </form>

            <div class="text-center mt-2 small">
                Already have an account? <a href="login.php" class="text-decoration-none fw-bold">Sign In here</a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
