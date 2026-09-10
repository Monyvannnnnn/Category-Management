<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/helpers.php";

/**
 * Strict Authorization check for incoming Telegram messages.
 * Verifies that the incoming chat ID is registered to control the bot.
 */
function verifyTelegramUserAuthorization($pdo, $botToken, $incomingChatId) {
    $stmt = $pdo->prepare("SELECT * FROM user_telegram_bots WHERE bot_token = ? AND chat_id = ?");
    $stmt->execute([$botToken, $incomingChatId]);
    $userBot = $stmt->fetch();

    if ($userBot) {
        return $userBot;
    }
    return null;
}

/**
 * Validates a one-time connection code and binds incoming chat_id to the user.
 */
function verifyAndBindConnectionCode($pdo, $code, $chatId) {
    $now = date('Y-m-d H:i:s');

    if (!empty($code)) {
        // Find bot row with matching code that has not expired
        $stmt = $pdo->prepare("SELECT * FROM user_telegram_bots WHERE UPPER(connection_code) = UPPER(?) AND (code_expires_at IS NULL OR code_expires_at >= ?)");
        $stmt->execute([$code, $now]);
        $userBot = $stmt->fetch();
    } else {
        // If code parameter is empty (user typed /start manually), check for latest pending unexpired code
        $stmt = $pdo->prepare("SELECT * FROM user_telegram_bots WHERE connection_code IS NOT NULL AND (code_expires_at IS NULL OR code_expires_at >= ?) ORDER BY id DESC LIMIT 1");
        $stmt->execute([$now]);
        $userBot = $stmt->fetch();
    }

    if (!$userBot) {
        return false;
    }

    // Bind chat_id, set connected_at, and clear connection code to prevent reuse
    $update = $pdo->prepare("UPDATE user_telegram_bots SET chat_id = ?, connected_at = ?, connection_code = NULL, code_expires_at = NULL WHERE id = ?");
    $update->execute([$chatId, $now, $userBot['id']]);

    return $userBot;
}

/**
 * Returns user bot connection row if chat_id is registered and connected.
 */
function getConnectedUserByChatId($pdo, $chatId) {
    if (empty($chatId)) return null;
    $stmt = $pdo->prepare("SELECT * FROM user_telegram_bots WHERE chat_id = ? LIMIT 1");
    $stmt->execute([$chatId]);
    $userBot = $stmt->fetch();
    return $userBot ?: null;
}

