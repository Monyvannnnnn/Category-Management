<?php
// logout.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        '/',
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

@session_unset();
@session_destroy();

// Redirect to login page
header("Location: login.php");
exit;
