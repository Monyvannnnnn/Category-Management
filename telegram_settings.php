<?php
// telegram_settings.php - API Handler for User-Specific Telegram Bot Configuration
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/includes/auth_helper.php";

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
        return $row;
    }
    return null;
}

switch ($action) {
    case 'get':
        $row = getUserBotRow($conn, $userId);
        $defaultBotToken = "8736337451:AAEtwDgtwUpWGnV4cIrMNKwNjHaAV8J18jc";
        $defaultBotUsername = "reportpush_bot";

        echo json_encode([
            'success' => true,
            'user_id' => $userId,
            'bot_token' => $row['bot_token'] ?? $defaultBotToken,
            'bot_username' => $row['bot_username'] ?? $defaultBotUsername,
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

        $existing = getUserBotRow($conn, $userId);
        if ($existing) {
            $stmt = db_prepare($conn, "UPDATE user_telegram_bots SET connection_code = ?, code_expires_at = DATE_ADD(NOW(), INTERVAL 30 MINUTE) WHERE user_id = ?");
            db_stmt_bind_param($stmt, "si", $code, $userId);
            db_stmt_execute($stmt);
            db_stmt_close($stmt);
        } else {
            $defaultToken = "8736337451:AAEtwDgtwUpWGnV4cIrMNKwNjHaAV8J18jc";
            $defaultUsername = "reportpush_bot";
            $stmt = db_prepare($conn, "INSERT INTO user_telegram_bots (user_id, bot_token, bot_username, connection_code, code_expires_at) VALUES (?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))");
            db_stmt_bind_param($stmt, "isss", $userId, $defaultToken, $defaultUsername, $code);
            db_stmt_execute($stmt);
            db_stmt_close($stmt);
        }

        $botUsername = $existing['bot_username'] ?? "reportpush_bot";
        if (empty($botUsername)) $botUsername = "reportpush_bot";

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
