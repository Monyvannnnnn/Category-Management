<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/../includes/helpers.php";

$userId = $_REQUEST['user_id'] ?? 1;

try {
    $pdo = getDBConnection();
    
    // Check if user bot record exists
    $stmt = $pdo->prepare("SELECT * FROM user_telegram_bots WHERE user_id = ?");
    $stmt->execute([$userId]);
    $userBot = $stmt->fetch();

    if (!$userBot) {
        // Create bot record for user if missing
        $stmtIns = $pdo->prepare("INSERT INTO user_telegram_bots (user_id, bot_token, bot_username) VALUES (?, ?, ?)");
        $stmtIns->execute([$userId, DEFAULT_BOT_TOKEN, DEFAULT_BOT_USERNAME]);
        $botUsername = DEFAULT_BOT_USERNAME;
    } else {
        $botUsername = $userBot['bot_username'];
    }

    // Generate one-time connection code
    $code = generateConnectionCode("CONNECT-");
    $expiresAt = date("Y-m-d H:i:s", strtotime("+15 minutes"));
    
    // Update DB record with connection code and 15-minute expiration
    $upd = $pdo->prepare("UPDATE user_telegram_bots SET connection_code = ?, code_expires_at = ? WHERE user_id = ?");
    $upd->execute([$code, $expiresAt, $userId]);

    $deepLink = "https://t.me/" . $botUsername . "?start=" . $code;

    jsonResponse([
        'success' => true,
        'user_id' => intval($userId),
        'code' => $code,
        'deep_link' => $deepLink
    ]);

} catch (Exception $e) {
    jsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
}
