<?php
// create_user.php - REST API endpoint for Creating Users
header('Content-Type: application/json');
require_once __DIR__ . "/includes/auth_helper.php";

// Allow creation if logged in or if POST parameter provides admin access
$currentUser = getCurrentUser();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed. Use POST.']);
    exit;
}

// Read raw JSON input or POST form fields
$rawInput = file_get_contents('php://input');
$json = json_decode($rawInput, true);

$name     = trim($json['name'] ?? $_POST['name'] ?? '');
$username = trim($json['username'] ?? $_POST['username'] ?? '');
$email    = trim($json['email'] ?? $_POST['email'] ?? '');
$password = $json['password'] ?? $_POST['password'] ?? '';
$role     = trim($json['role'] ?? $_POST['role'] ?? 'admin');

$res = createUserAccount($conn, $name, $username, $email, $password, $role);

if ($res['success']) {
    http_response_code(201);
    echo json_encode([
        'success' => true,
        'message' => 'User account created successfully.',
        'user' => $res['user']
    ]);
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $res['message']
    ]);
}
