<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/helpers.php";

$userId = $_REQUEST['user_id'] ?? 1;

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM user_telegram_bots WHERE user_id = ?");
    $stmt->execute([$userId]);
    $userBot = $stmt->fetch();

    if ($userBot && !empty($userBot['chat_id'])) {
        jsonResponse([
            'connected' => true,
            'user_id' => intval($userId),
            'chat_id' => maskChatId($userBot['chat_id']),
            'connected_at' => $userBot['connected_at'] ?? $userBot['created_at']
        ]);
    } else {
        jsonResponse([
            'connected' => false,
            'user_id' => intval($userId),
            'code' => $userBot['connection_code'] ?? null
        ]);
    }
} catch (Exception $e) {
    jsonResponse(['connected' => false, 'error' => $e->getMessage()], 500);
}
