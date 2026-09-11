<?php
/**
 * Telegram Bot Notification Handler
 * Optimized for InfinityFree & Local Hosting (with DNS Resolution Bypass)
 */

function sendSingleTelegramNotification($chatId, $message, $customBotToken = null) {
    $botToken = !empty($customBotToken) ? $customBotToken : "8736337451:AAEtwDgtwUpWGnV4cIrMNKwNjHaAV8J18jc"; 
    $url = "https://api.telegram.org/bot$botToken/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $result = false;
    $curlError = '';

    // Method 1: Fast Standard cURL
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 4);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if (defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }

        $result = curl_exec($ch);
        if ($result === false) {
            $curlError = curl_error($ch);
        }
        curl_close($ch);
    }

    // Method 2: Fast DNS Resolution Bypass
    if (($result === false || (is_string($result) && strpos($result, '"ok":true') === false)) && function_exists('curl_init')) {
        $telegramIPs = ['149.154.167.220'];
        foreach ($telegramIPs as $ip) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_TIMEOUT, 3);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

            if (defined('CURLOPT_RESOLVE')) {
                curl_setopt($ch, CURLOPT_RESOLVE, ["api.telegram.org:443:$ip"]);
            }

            $res = curl_exec($ch);
            if ($res !== false && strpos($res, '"ok":true') !== false) {
                $result = $res;
                curl_close($ch);
                break;
            }
            curl_close($ch);
        }
    }

    // Method 3: Fast Fallback Stream Context
    if ($result === false || (is_string($result) && strpos($result, '"ok":true') === false)) {
        $options = [
            'http' => [
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n" .
                             "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
                'timeout' => 3,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false
            ]
        ];
        $context = stream_context_create($options);
        $streamRes = @file_get_contents($url, false, $context);
        if ($streamRes !== false) {
            $result = $streamRes;
        }
    }

    // If all failed, return formatted JSON error
    if ($result === false) {
        return json_encode([
            "ok" => false,
            "description" => "PHP unable to connect to Telegram API. " . ($curlError ? "cURL error: " . $curlError : "Check outgoing server connection.")
        ]);
    }

    return $result;
}

/**
 * Ensure telegram_subscribers table exists
 */
function ensureSubscribersTableExists($conn = null) {
    if (!$conn) {
        global $conn;
    }
    if (!$conn) return;
    $sql = "CREATE TABLE IF NOT EXISTS telegram_subscribers (
      chat_id varchar(100) NOT NULL,
      created_at timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (chat_id)
    );";
    @db_query($conn, $sql);
}

/**
 * Register a Telegram user chat ID into subscriber list so ALL users can receive notifications
 */
function registerSubscriberChatId($conn, $chatId) {
    if (empty($chatId)) return;
    ensureSubscribersTableExists($conn);
    if ($conn) {
        $chatIdEsc = db_real_escape_string($conn, $chatId);
        @db_query($conn, "INSERT IGNORE INTO telegram_subscribers (chat_id) VALUES ('$chatIdEsc')");
    }
}

/**
 * Get all registered subscriber and connected user Chat IDs from DB
 */
function getSubscriberChatIds($conn = null) {
    if (!$conn) {
        global $conn;
    }
    $targets = []; // Map of chatId => botToken
    if ($conn) {
        ensureSubscribersTableExists($conn);

        // 1. Fetch all connected user bots
        $res1 = @db_query($conn, "SELECT chat_id, bot_token FROM user_telegram_bots WHERE chat_id IS NOT NULL AND chat_id != ''");
        if ($res1) {
            while ($row = db_fetch_assoc($res1)) {
                $cid = trim($row['chat_id']);
                if (!empty($cid) && !isset($targets[$cid])) {
                    $targets[$cid] = !empty($row['bot_token']) ? trim($row['bot_token']) : null;
                }
            }
        }

        // 2. Fetch from standalone subscribers list
        $res2 = @db_query($conn, "SELECT chat_id FROM telegram_subscribers WHERE chat_id IS NOT NULL AND chat_id != ''");
        if ($res2) {
            while ($row = db_fetch_assoc($res2)) {
                $cid = trim($row['chat_id']);
                if (!empty($cid) && !isset($targets[$cid])) {
                    $targets[$cid] = null;
                }
            }
        }
    }
    return $targets;
}

/**
 * Helper to check if a specific user is currently connected to Telegram
 */
function isUserTelegramConnected($conn = null, $userId = null) {
    if (!$conn) {
        global $conn;
    }
    if ($userId === null || (int)$userId <= 0) {
        if (function_exists('getCurrentUser')) {
            $u = getCurrentUser();
            if (!empty($u['id'])) {
                $userId = (int)$u['id'];
            }
        }
        if (($userId === null || (int)$userId <= 0) && isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
            $userId = (int)$_SESSION['user_id'];
        }
    }
    if ($userId === null || (int)$userId <= 0) {
        $userId = 1;
    }
    if ($userId !== null && (int)$userId > 0) {
        $uId = (int)$userId;
        if ($conn) {
            $stmt = db_prepare($conn, "SELECT chat_id FROM user_telegram_bots WHERE user_id = ? AND chat_id IS NOT NULL AND chat_id != '' LIMIT 1");
            if ($stmt) {
                db_stmt_bind_param($stmt, "i", $uId);
                db_stmt_execute($stmt);
                $res = db_stmt_get_result($stmt);
                $row = db_fetch_assoc($res);
                db_stmt_close($stmt);
                if ($row && !empty(trim($row['chat_id']))) {
                    return true;
                }
            }
            // Fallback for stateless serverless sessions: check any connected row
            $resAny = db_query($conn, "SELECT chat_id FROM user_telegram_bots WHERE chat_id IS NOT NULL AND chat_id != '' LIMIT 1");
            if ($resAny) {
                $rowAny = db_fetch_assoc($resAny);
                if ($rowAny && !empty(trim($rowAny['chat_id']))) {
                    return true;
                }
            }
        }
    }
    return false;
}

/**
 * Send notification to specific user (or auto-detect logged-in user for per-user isolation)
 */
function sendTelegramNotification($message, $conn = null, $userId = null) {
    if (!$conn) {
        global $conn;
    }

    // Auto-detect logged-in user ID if not explicitly passed
    if ($userId === null || (int)$userId <= 0) {
        if (function_exists('getCurrentUser')) {
            $u = getCurrentUser();
            if (!empty($u['id'])) {
                $userId = (int)$u['id'];
            }
        }
        if (($userId === null || (int)$userId <= 0) && isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0) {
            $userId = (int)$_SESSION['user_id'];
        }
    }

    // Per-User Isolation Mode: If userId is resolved, send to that specific user's chat_id
    if ($userId !== null && (int)$userId > 0) {
        $uId = (int)$userId;
        if ($conn) {
            $stmt = db_prepare($conn, "SELECT chat_id, bot_token FROM user_telegram_bots WHERE user_id = ? AND chat_id IS NOT NULL AND chat_id != '' LIMIT 1");
            if ($stmt) {
                db_stmt_bind_param($stmt, "i", $uId);
                db_stmt_execute($stmt);
                $res = db_stmt_get_result($stmt);
                $row = db_fetch_assoc($res);
                db_stmt_close($stmt);

                if ($row && !empty(trim($row['chat_id']))) {
                    $bToken = !empty($row['bot_token']) ? trim($row['bot_token']) : null;
                    return sendSingleTelegramNotification(trim($row['chat_id']), $message, $bToken);
                }
            }
        }

        // If user is specified/logged-in but has not connected to Telegram, return failure
        return json_encode([
            "ok" => false, 
            "message" => "Telegram is not connected. Please connect your Telegram account first in Telegram Settings.",
            "description" => "Telegram is not connected. Please connect your Telegram account first in Telegram Settings."
        ]);
    }

    // Broadcast Mode / Fallback: Send to ALL connected Telegram users & subscribers
    $targets = getSubscriberChatIds($conn);

    if (empty($targets)) {
        $uInfo = ($userId !== null && (int)$userId > 0) ? " for User ID {$userId}" : "";
        return json_encode([
            "ok" => false, 
            "message" => "No connected Telegram account found{$uInfo}. Please connect your Telegram bot in Settings.",
            "description" => "No connected Telegram account found{$uInfo}. Please connect your Telegram bot in Settings."
        ]);
    }

    $successCount = 0;
    $lastRes = false;

    foreach ($targets as $cid => $bToken) {
        $res = sendSingleTelegramNotification($cid, $message, $bToken);
        $lastRes = $res;
        if ($res && (strpos($res, '"ok":true') !== false || strpos($res, '"ok": true') !== false)) {
            $successCount++;
        }
    }

    if ($successCount > 0) {
        return json_encode(["ok" => true, "delivered_chats" => $successCount]);
    }

    return $lastRes;
}

/**
 * Ensure system_settings table exists (Auto-create for InfinityFree & Local)
 */
function ensureSettingsTableExists($conn) {
    if (!$conn) return;
    $sql = "CREATE TABLE IF NOT EXISTS system_settings (
      setting_key varchar(50) NOT NULL,
      setting_value varchar(255) NOT NULL,
      PRIMARY KEY (setting_key)
    );";
    @db_query($conn, $sql);
}

/**
 * Check if Auto Telegram notifications are enabled in system_settings
 */
function isAutoTelegramEnabled($conn) {
    if (!$conn) return true;
    ensureSettingsTableExists($conn);
    $res = @db_query($conn, "SELECT setting_value FROM system_settings WHERE setting_key = 'auto_telegram_notify'");
    if ($res && $row = db_fetch_assoc($res)) {
        return (trim($row['setting_value']) === '1');
    }
    return true;
}

/**
 * Set Auto Telegram notification status (1 = enabled / auto, 0 = disabled / manual)
 */
function setAutoTelegramEnabled($conn, $status) {
    if (!$conn) return false;
    ensureSettingsTableExists($conn);
    $val = ($status === '1' || $status === 1 || $status === true || $status === 'true') ? '1' : '0';

    if ($conn instanceof PgSqlConnWrapper) {
        $stmt = db_prepare($conn, "INSERT INTO system_settings (setting_key, setting_value) VALUES ('auto_telegram_notify', ?) ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value");
        if ($stmt) {
            db_stmt_bind_param($stmt, "s", $val);
            db_stmt_execute($stmt);
            db_stmt_close($stmt);
            return true;
        }
    }

    $stmt = db_prepare($conn, "INSERT INTO system_settings (setting_key, setting_value) VALUES ('auto_telegram_notify', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    if ($stmt) {
        db_stmt_bind_param($stmt, "s", $val);
        db_stmt_execute($stmt);
        db_stmt_close($stmt);
        return true;
    }

    $valEsc = db_real_escape_string($conn, $val);
    @db_query($conn, "UPDATE system_settings SET setting_value = '$valEsc' WHERE setting_key = 'auto_telegram_notify'");
    return true;
}



// Standalone execution when accessed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    header('Content-Type: application/json');
    
    $inputJSON = json_decode(file_get_contents('php://input'), true);
    $message = $_REQUEST['message'] ?? $inputJSON['message'] ?? "🔔 <b>Inventory System Connected!</b>\nTelegram notifications are active.";

    $result = sendTelegramNotification($message);
    echo $result;
}
?>
