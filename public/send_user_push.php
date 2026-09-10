<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/helpers.php";
require_once __DIR__ . "/../includes/telegram_api.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    jsonResponse(['success' => false, 'error' => 'Method Not Allowed'], 405);
}

$userId  = $_POST['user_id'] ?? null;
$customMessage = trim($_POST['message'] ?? '');

if (!$userId) {
    jsonResponse(['success' => false, 'error' => 'Missing user_id parameter']);
}

if (empty($customMessage)) {
    $customMessage = "💰 <b>New Payment Received!</b>\n\n<b>Amount:</b> $10.00\n<b>Status:</b> Success\n<b>Date:</b> " . date("Y-m-d H:i:s");
}

try {
    $pdo = getDBConnection();
    $result = sendUserTelegramMessage($pdo, $userId, $customMessage);
    jsonResponse($result);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
}
