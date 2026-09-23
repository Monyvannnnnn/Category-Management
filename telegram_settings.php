<?php
// telegram_settings.php - API Handler for User-Specific Telegram Bot Configuration
header("Content-Type: application/json; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . "/includes/auth_helper.php";
require_once __DIR__ . "/bot_poller.php";

$user = getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

$userId = (int)$user['id'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'get';

/**
 * Helper to fetch user bot config
 */
function getUserBotRow($conn, $userId) {
    $stmt = db_prepare($conn, "SELECT * FROM user_telegram_bots WHERE user_id = ? LIMIT 1");
    if ($stmt) {
        db_stmt_bind_param($stmt, "i", $userId);
        db_stmt_execute($stmt);
        $res = db_stmt_get_result($stmt);
        $row = db_fetch_assoc($res);
        db_stmt_close($stmt);

        $defaultToken = getDefaultBotToken();
        if ($row && !empty($defaultToken) && ($row['bot_token'] !== $defaultToken || $row['bot_username'] === 'reportpush_bot')) {
            $newToken = $defaultToken;
            $newUsername = defined('DEFAULT_BOT_USERNAME') ? DEFAULT_BOT_USERNAME : 'enginebi_bot';
            $upd = db_prepare($conn, "UPDATE user_telegram_bots SET bot_token = ?, bot_username = ? WHERE id = ?");
            if ($upd) {
                db_stmt_bind_param($upd, "ssi", $newToken, $newUsername, $row['id']);
                db_stmt_execute($upd);
                db_stmt_close($upd);
            }
            $row['bot_token'] = $newToken;
            $row['bot_username'] = $newUsername;
        }

        return $row;
    }
    return null;
}

switch ($action) {
    case 'get':
        $defaultBotToken = getDefaultBotToken();
        $defaultBotUsername = defined('DEFAULT_BOT_USERNAME') ? DEFAULT_BOT_USERNAME : "enginebi_bot";
        $row = getUserBotRow($conn, $userId);

        $botToken = !empty($row['bot_token']) ? $row['bot_token'] : $defaultBotToken;
        $botUsername = (!empty($row['bot_username']) && $row['bot_username'] !== 'reportpush_bot') ? $row['bot_username'] : $defaultBotUsername;

        // Auto-poll once if chat_id is empty to instantly bind Telegram START messages on localhost/web
        if (empty($row['chat_id']) && function_exists('pollTelegramUpdatesOnce')) {
            pollTelegramUpdatesOnce($conn, $botToken);
            $row = getUserBotRow($conn, $userId);
        }

        echo json_encode([
            'success' => true,
            'user_id' => $userId,
            'bot_token' => $botToken,
            'bot_username' => $botUsername,
            'chat_id' => $row['chat_id'] ?? null,
            'connection_code' => $row['connection_code'] ?? null,
            'code_expires_at' => $row['code_expires_at'] ?? null,
            'connected_at' => $row['connected_at'] ?? null,
            'is_connected' => !empty($row['chat_id'])
        ]);
        exit;

    case 'save_bot':
        $rawInput = file_get_contents('php://input');
        $json = json_decode($rawInput, true);

        $botToken    = trim($_POST['bot_token'] ?? $json['bot_token'] ?? '');
        $botUsername = trim($_POST['bot_username'] ?? $json['bot_username'] ?? '');
        $botUsername = ltrim($botUsername, '@');

        if (empty($botToken)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Bot Token is required.']);
            exit;
        }

        $existing = getUserBotRow($conn, $userId);
        if ($existing) {
            $stmt = db_prepare($conn, "UPDATE user_telegram_bots SET bot_token = ?, bot_username = ? WHERE user_id = ?");
            db_stmt_bind_param($stmt, "ssi", $botToken, $botUsername, $userId);
            db_stmt_execute($stmt);
            db_stmt_close($stmt);
        } else {
            $stmt = db_prepare($conn, "INSERT INTO user_telegram_bots (user_id, bot_token, bot_username) VALUES (?, ?, ?)");
            db_stmt_bind_param($stmt, "iss", $userId, $botToken, $botUsername);
            db_stmt_execute($stmt);
            db_stmt_close($stmt);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Telegram Bot configuration saved successfully!'
        ]);
        exit;

    case 'generate_code':
        $code = str_pad((string)rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        $defaultToken = getDefaultBotToken();
        $defaultUsername = defined('DEFAULT_BOT_USERNAME') ? DEFAULT_BOT_USERNAME : "enginebi_bot";

        $existing = getUserBotRow($conn, $userId);
        $botUsername = (!empty($existing['bot_username']) && $existing['bot_username'] !== 'reportpush_bot') ? $existing['bot_username'] : $defaultUsername;
        $botToken = !empty($existing['bot_token']) ? $existing['bot_token'] : $defaultToken;

        if ($existing) {
            $stmt = db_prepare($conn, "UPDATE user_telegram_bots SET connection_code = ?, code_expires_at = ?, bot_username = ?, bot_token = ? WHERE user_id = ?");
            db_stmt_bind_param($stmt, "ssssi", $code, $expiresAt, $botUsername, $botToken, $userId);
            db_stmt_execute($stmt);
            db_stmt_close($stmt);
        } else {
            $stmt = db_prepare($conn, "INSERT INTO user_telegram_bots (user_id, bot_token, bot_username, connection_code, code_expires_at) VALUES (?, ?, ?, ?, ?)");
            db_stmt_bind_param($stmt, "issss", $userId, $botToken, $botUsername, $code, $expiresAt);
            db_stmt_execute($stmt);
            db_stmt_close($stmt);
        }

        // Auto-register current Vercel domain Webhook with Telegram API
        $host = $_SERVER['HTTP_HOST'] ?? 'report-push-v2.vercel.app';
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'https';
        $webhookUrl = "{$scheme}://{$host}/set_commands.php";
        $bToken = $botToken;

        if (function_exists('curl_init') && strpos($host, 'localhost') === false && strpos($host, '127.0.0.1') === false) {
            $whApiUrl = "https://api.telegram.org/bot{$bToken}/setWebhook?url=" . urlencode($webhookUrl);
            $ch = curl_init($whApiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
            @curl_exec($ch);
            @curl_close($ch);
        }

        $deepLink = "https://t.me/" . ltrim($botUsername, '@') . "?start=" . $code;

        echo json_encode([
            'success' => true,
            'code' => $code,
            'bot_username' => $botUsername,
            'deep_link' => $deepLink,
            'expires_at' => $expiresAt
        ]);
        exit;

    case 'disconnect':
        $stmt = db_prepare($conn, "UPDATE user_telegram_bots SET chat_id = NULL, connection_code = NULL, code_expires_at = NULL, connected_at = NULL WHERE user_id = ?");
        db_stmt_bind_param($stmt, "i", $userId);
        db_stmt_execute($stmt);
        db_stmt_close($stmt);

        echo json_encode([
            'success' => true,
            'message' => 'Telegram Bot disconnected successfully.'
        ]);
        exit;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action parameter.']);
        exit;
}
