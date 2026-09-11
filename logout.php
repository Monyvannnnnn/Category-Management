<?php
// logout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Clear session array
$_SESSION = array();

// 2. Expire session cookies reliably across all platforms (XAMPP / Vercel / Browsers)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    $domain = !empty($params["domain"]) ? $params["domain"] : null;
    $secure = !empty($params["secure"]);
    $httponly = !empty($params["httponly"]);

    setcookie(session_name(), '', time() - 42000, '/', $domain, $secure, $httponly);
    setcookie(session_name(), '', time() - 42000, '/');
    setcookie('PHPSESSID', '', time() - 42000, '/');
}

// 3. Destroy session server-side
@session_unset();
@session_destroy();

// 4. Start a fresh session to set explicit logged_out marker so login.php never redirects back
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['logged_out'] = true;
session_write_close();

// 5. Redirect to login page
header("Location: login.php");
exit;

