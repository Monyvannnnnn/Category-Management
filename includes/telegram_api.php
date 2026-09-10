<?php
require_once __DIR__ . "/../config/database.php";
require_once __DIR__ . "/helpers.php";

/**
 * Dispatch low-level Telegram sendMessage API via cURL
 */
function sendTelegramRawMessage($botToken, $chatId, $message) {
    $url = "https://api.telegram.org/bot" . $botToken . "/sendMessage";
    $data = [
        'chat_id' => trim($chatId),
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'error' => 'cURL Error: ' . $curlError];
    }

    $result = json_decode($response, true);
    if (isset($result['ok']) && $result['ok'] === true) {
        return ['success' => true, 'error' => ''];
    }

    $errorMsg = $result['description'] ?? 'Unknown Telegram API Error';
    return ['success' => false, 'error' => $errorMsg];
}

/**
 * Sends a Telegram notification strictly isolated to a specific website user_id.
 * Logs the audit record in telegram_push_logs.
 */
function sendUserTelegramMessage($pdo, $userId, $message) {
    // 1. Look up user's bot settings & chat_id from MySQL
    $stmt = $pdo->prepare("SELECT * FROM user_telegram_bots WHERE user_id = ?");
    $stmt->execute([$userId]);
    $userBot = $stmt->fetch();

    if (!$userBot || empty($userBot['chat_id'])) {
        $err = "No connected Telegram account found for User ID #" . intval($userId);
        return ['success' => false, 'error' => $err];
    }

    $botToken = $userBot['bot_token'];
    $chatId = $userBot['chat_id'];

    // 2. Dispatch message
    $res = sendTelegramRawMessage($botToken, $chatId, $message);

    // 3. Audit log in telegram_push_logs
    $status = $res['success'] ? 'SUCCESS' : 'FAILED';
    $errorMsg = $res['error'] ?? null;

    try {
        $logStmt = $pdo->prepare("INSERT INTO telegram_push_logs (user_id, chat_id, message, status, error_message) VALUES (?, ?, ?, ?, ?)");
        $logStmt->execute([$userId, $chatId, $message, $status, $errorMsg]);
    } catch (Exception $e) {
        // Silently handle log insertion if DB transient error
    }

    return $res;
}
