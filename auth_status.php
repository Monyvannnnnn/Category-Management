<?php
// auth_status.php - Returns JSON information about current logged-in user
header('Content-Type: application/json');
require_once __DIR__ . "/includes/auth_helper.php";

$user = getCurrentUser();

if ($user) {
    echo json_encode([
        'authenticated' => true,
        'user' => $user
    ]);
} else {
    echo json_encode([
        'authenticated' => false,
        'user' => null
    ]);
}
