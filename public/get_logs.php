<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/helpers.php";

$userId = $_REQUEST['user_id'] ?? 1;

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM telegram_push_logs WHERE user_id = ? ORDER BY id DESC LIMIT 5");
    $stmt->execute([$userId]);
    $logs = $stmt->fetchAll();

    jsonResponse(['success' => true, 'logs' => $logs]);
} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
}
