<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/helpers.php";

$userId = $_REQUEST['user_id'] ?? null;

if (!$userId) {
    jsonResponse(['success' => false, 'error' => 'Missing user_id parameter']);
}

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("UPDATE user_telegram_bots SET chat_id = NULL, connection_code = NULL, connected_at = NULL WHERE user_id = ?");
    $stmt->execute([$userId]);

    jsonResponse(['success' => true]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
}
