<?php
/**
 * Telegram Bot Notification Handler
 * Optimized for InfinityFree & Local Hosting (with DNS Resolution Bypass)
 */

if (!function_exists('getAppBaseUrl')) {
    function getAppBaseUrl() {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (empty($host) || strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false || strpos($host, '::1') !== false) {
            return "https://report-push-v2.vercel.app";
        }
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'https';
        $script = $_SERVER['SCRIPT_NAME'] ?? '';
        $dir = rtrim(dirname($script), '/\\');
        if ($dir === '.' || $dir === '/' || $dir === '\\') {
            $dir = '';
        }
        return "{$scheme}://{$host}{$dir}";
    }
}

if (!function_exists('sendTelegramChatAction')) {
    function sendTelegramChatAction($chatId, $action = 'typing', $customBotToken = null) {
        $botToken = !empty($customBotToken) ? $customBotToken : "8560470449:AAEuX9eLYvk0wxh65Rc0d8iNhObzVzni-x8";
        $url = "https://api.telegram.org/bot{$botToken}/sendChatAction";
        $payload = [
            'chat_id' => $chatId,
            'action'  => $action
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
}

if (!function_exists('editTelegramMessageText')) {
    function editTelegramMessageText($chatId, $messageId, $text, $customBotToken = null, $replyMarkup = null) {
        if (empty($messageId)) {
            return sendSingleTelegramNotification($chatId, $text, $customBotToken, $replyMarkup);
        }
        $botToken = !empty($customBotToken) ? $customBotToken : "8560470449:AAEuX9eLYvk0wxh65Rc0d8iNhObzVzni-x8";
        $url = "https://api.telegram.org/bot{$botToken}/editMessageText";
        $payload = [
            'chat_id'    => $chatId,
            'message_id' => (int)$messageId,
            'text'       => $text,
            'parse_mode' => 'HTML'
        ];
        if (!empty($replyMarkup)) {
            $markupArr = is_string($replyMarkup) ? json_decode($replyMarkup, true) : $replyMarkup;
            // Telegram editMessageText strictly ONLY accepts inline_keyboard arrays!
            if (is_array($markupArr) && isset($markupArr['inline_keyboard'])) {
                $payload['reply_markup'] = $markupArr;
            }
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $res = curl_exec($ch);
        curl_close($ch);

        $resDec = json_decode($res, true);
        if (empty($resDec['ok'])) {
            return sendSingleTelegramNotification($chatId, $text, $customBotToken, $replyMarkup);
        }
        return $res;
    }
}

if (!function_exists('deleteTelegramMessage')) {
    function deleteTelegramMessage($chatId, $messageId, $customBotToken = null) {
        if (empty($messageId)) return false;
        $botToken = !empty($customBotToken) ? $customBotToken : "8560470449:AAEuX9eLYvk0wxh65Rc0d8iNhObzVzni-x8";
        $url = "https://api.telegram.org/bot{$botToken}/deleteMessage";
        $payload = ['chat_id' => $chatId, 'message_id' => (int)$messageId];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $res = curl_exec($ch);
        curl_close($ch);
        return $res;
    }
}

function sendSingleTelegramNotification($chatId, $message, $customBotToken = null, $replyMarkup = null) {
    $botToken = !empty($customBotToken) ? $customBotToken : "8560470449:AAEuX9eLYvk0wxh65Rc0d8iNhObzVzni-x8"; 
    $url = "https://api.telegram.org/bot$botToken/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    if (!empty($replyMarkup)) {
        $data['reply_markup'] = is_string($replyMarkup) ? $replyMarkup : json_encode($replyMarkup);
    } else {
        $baseUrl    = getAppBaseUrl();
        $miniAppUrl = "{$baseUrl}/fieldbi.php";
        $directUrl  = "https://app.fieldbi.com/?page=promptdemo&rpf=zGR88xyzPD&action=page&frm=RMt_ph898";
        $prodUrl    = "{$baseUrl}/products.php";
        $btnText    = "📊 View Inventory";

        $data['reply_markup'] = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $btnText, 'url' => $prodUrl]
                ],
                [
                    ['text' => '📱 Open in Mini App', 'web_app' => ['url' => $miniAppUrl]],
                    ['text' => '🌐 Direct Browser', 'url' => $directUrl]
                ]
            ]
        ]);
    }

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
    if ($result === false && function_exists('curl_init')) {
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
    if ($result === false) {
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
 * Send a single Photo with Caption via Telegram Bot API
 */
function sendSingleTelegramPhoto($chatId, $photoUrl, $caption, $customBotToken = null, $replyMarkup = null) {
    $botToken = !empty($customBotToken) ? $customBotToken : "8560470449:AAEuX9eLYvk0wxh65Rc0d8iNhObzVzni-x8"; 
    $url = "https://api.telegram.org/bot$botToken/sendPhoto";

    $isLocalFile = false;
    if (is_string($photoUrl) && !empty($photoUrl)) {
        if (file_exists($photoUrl)) {
            $isLocalFile = true;
        } else {
            $absPath = __DIR__ . '/' . ltrim($photoUrl, '/');
            if (file_exists($absPath)) {
                $photoUrl = $absPath;
                $isLocalFile = true;
            } elseif (!preg_match('~^https?://~i', $photoUrl)) {
                $baseUrl = getAppBaseUrl();
                $photoUrl = rtrim($baseUrl, '/') . '/' . ltrim($photoUrl, '/');
            }
        }
    }

    if ($isLocalFile) {
        $mime = function_exists('mime_content_type') ? @mime_content_type($photoUrl) : 'image/jpeg';
        if (!$mime) $mime = 'image/jpeg';
        $cFile = new CURLFile(realpath($photoUrl), $mime, basename($photoUrl));
        $data = [
            'chat_id' => $chatId,
            'photo' => $cFile,
            'caption' => $caption,
            'parse_mode' => 'HTML'
        ];
    } else {
        $data = [
            'chat_id' => $chatId,
            'photo' => $photoUrl,
            'caption' => $caption,
            'parse_mode' => 'HTML'
        ];
    }

    if (!empty($replyMarkup)) {
        $data['reply_markup'] = is_string($replyMarkup) ? $replyMarkup : json_encode($replyMarkup);
    } else {
        $baseUrl    = getAppBaseUrl();
        $miniAppUrl = "{$baseUrl}/fieldbi.php";
        $directUrl  = "https://app.fieldbi.com/?page=promptdemo&rpf=zGR88xyzPD&action=page&frm=RMt_ph898";
        $prodUrl    = "{$baseUrl}/products.php";
        $btnText    = "📊 View Inventory";

        $data['reply_markup'] = json_encode([
            'inline_keyboard' => [
                [
                    ['text' => $btnText, 'url' => $prodUrl]
                ],
                [
                    ['text' => '📱 Open in Mini App', 'web_app' => ['url' => $miniAppUrl]],
                    ['text' => '🌐 Direct Browser', 'url' => $directUrl]
                ]
            ]
        ]);
    }

    $result = false;
    $curlError = '';

    // Method 1: Fast Standard cURL
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $isLocalFile ? $data : http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);

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
    if ($result === false && function_exists('curl_init')) {
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
            curl_setopt($ch, CURLOPT_TIMEOUT, 5);
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
    if ($result === false) {
        $options = [
            'http' => [
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n" .
                             "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
                'timeout' => 4,
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

    if ($result === false) {
        return json_encode([
            "ok" => false,
            "description" => "PHP unable to send photo via Telegram API. " . ($curlError ? "cURL error: " . $curlError : "Check outgoing server connection.")
        ]);
    }

    return $result;
}

/**
 * Send photo notification to specific user or all subscribers
 */
function sendTelegramPhotoNotification($message, $photoUrl, $conn = null, $userId = null, $replyMarkup = null) {
    if (!$conn) {
        global $conn;
    }

    $chat = getEffectiveTelegramChat($conn, $userId);
    if (!empty($chat['chat_id'])) {
        $photoRes = sendSingleTelegramPhoto($chat['chat_id'], $photoUrl, $message, $chat['bot_token'], $replyMarkup);
        $resDec = json_decode($photoRes, true);
        if (is_array($resDec) && !empty($resDec['ok'])) {
            return $photoRes;
        }
        return sendSingleTelegramNotification($chat['chat_id'], $message, $chat['bot_token'], $replyMarkup);
    }

    return json_encode([
        "ok" => false,
        "message" => "Telegram is not connected. Please connect your Telegram account first in Telegram Settings.",
        "description" => "Telegram is not connected. Please connect your Telegram account first in Telegram Settings."
    ]);
}

/**
 * Universal photo push helper function
 */
function sendPhotoToTelegram($param1, $param2, $param3, $param4 = null) {
    // If called as sendPhotoToTelegram($chatId, $photoUrl, $caption, $customBotToken)
    if (is_string($param1) && (preg_match('/^-?\d+$/', trim($param1)) || strpos($param1, 'http') !== 0)) {
        return sendSingleTelegramPhoto($param1, $param2, $param3, $param4);
    }
    // If called as sendPhotoToTelegram($message, $photoUrl, $conn, $userId)
    return sendTelegramPhotoNotification($param1, $param2, $param3, $param4);
}

/**
 * Send a single Document (Excel, PDF, CSV, TXT) via Telegram Bot API
 */
function sendSingleTelegramDocument($chatId, $filePath, $caption = '', $customBotToken = null, $fileName = null) {
    if (!file_exists($filePath)) {
        return json_encode(["ok" => false, "description" => "Document file not found."]);
    }
    $botToken = !empty($customBotToken) ? $customBotToken : "8560470449:AAEuX9eLYvk0wxh65Rc0d8iNhObzVzni-x8";
    $url = "https://api.telegram.org/bot$botToken/sendDocument";

    $mimeType = function_exists('mime_content_type') ? @mime_content_type($filePath) : 'application/octet-stream';
    if (!$mimeType) $mimeType = 'application/octet-stream';

    $cFile = new CURLFile(realpath($filePath), $mimeType, $fileName ?: basename($filePath));

    $postData = [
        'chat_id' => $chatId,
        'document' => $cFile,
        'caption' => $caption,
        'parse_mode' => 'HTML'
    ];

    $result = false;
    $curlError = '';

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $result = curl_exec($ch);
        if ($result === false) {
            $curlError = curl_error($ch);
        }
        curl_close($ch);
    }

    // Fallback: DNS Resolution Bypass if cURL fails
    if (($result === false || (is_string($result) && strpos($result, '"ok":true') === false)) && function_exists('curl_init')) {
        $telegramIPs = ['149.154.167.220'];
        foreach ($telegramIPs as $ip) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);

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

    if ($result !== false) {
        return $result;
    }

    return json_encode(["ok" => false, "description" => "Failed to send document via cURL. " . ($curlError ? "cURL error: " . $curlError : "")]);
}

/**
 * Get effective Telegram chat and bot token for a user or fallback
 */
function getEffectiveTelegramChat($conn = null, $userId = null) {
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

    // 1. Try specific user_id in user_telegram_bots
    if ($userId !== null && (int)$userId > 0 && $conn) {
        $uId = (int)$userId;
        $stmt = db_prepare($conn, "SELECT chat_id, bot_token FROM user_telegram_bots WHERE user_id = ? AND chat_id IS NOT NULL AND chat_id != '' LIMIT 1");
        if ($stmt) {
            db_stmt_bind_param($stmt, "i", $uId);
            db_stmt_execute($stmt);
            $res = db_stmt_get_result($stmt);
            $row = db_fetch_assoc($res);
            db_stmt_close($stmt);
            if ($row && !empty(trim($row['chat_id']))) {
                return ['chat_id' => trim($row['chat_id']), 'bot_token' => !empty($row['bot_token']) ? trim($row['bot_token']) : null];
            }
        }
    }

    // 2. Fallback: Try ANY connected bot in user_telegram_bots
    if ($conn) {
        $res = db_query($conn, "SELECT chat_id, bot_token FROM user_telegram_bots WHERE chat_id IS NOT NULL AND chat_id != '' ORDER BY id DESC LIMIT 1");
        if ($res && $row = db_fetch_assoc($res)) {
            if (!empty(trim($row['chat_id']))) {
                return ['chat_id' => trim($row['chat_id']), 'bot_token' => !empty($row['bot_token']) ? trim($row['bot_token']) : null];
            }
        }
    }

    // 3. Fallback: Try subscribers in telegram_subscribers
    $subs = getSubscriberChatIds($conn);
    if (!empty($subs)) {
        foreach ($subs as $cid => $bToken) {
            if (!empty($cid)) {
                return ['chat_id' => (string)$cid, 'bot_token' => $bToken];
            }
        }
    }

    return null;
}

/**
 * Send a Document (Excel, PDF, CSV, TXT, HTML) via Telegram Bot API
 */
function sendTelegramDocument($filePath, $caption = '', $conn = null, $userId = null, $fileName = null) {
    if (!$conn) {
        global $conn;
    }

    $chat = getEffectiveTelegramChat($conn, $userId);
    if (!empty($chat['chat_id'])) {
        return sendSingleTelegramDocument($chat['chat_id'], $filePath, $caption, $chat['bot_token'], $fileName);
    }

    return json_encode([
        "ok" => false,
        "message" => "Telegram is not connected. Please connect your Telegram account first in Telegram Settings.",
        "description" => "Telegram is not connected. Please connect your Telegram account first in Telegram Settings."
    ]);
}

/**
 * Format product push in Premium Style (Design 3)
 */
function formatProductPushPremium($product, $pushType = 'Manual') {
    $name = htmlspecialchars($product['product_name'] ?? 'N/A');
    $code = htmlspecialchars($product['product_code'] ?? 'N/A');
    $price = number_format((float)($product['price'] ?? 0), 2);
    $qty = (int)($product['quantity'] ?? 0);
    $totalVal = (float)($product['price'] ?? 0) * $qty;
    $formattedVal = ($totalVal == (int)$totalVal) ? number_format($totalVal, 0) : number_format($totalVal, 2);
    $timeStr = date('h:i A');

    $msg = "🔦 {$name} │ {$code}\n"
         . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
         . "💵 \${$price}  │  📦 {$qty} units  │  💎 \${$formattedVal}\n"
         . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
         . "🕐 {$timeStr}  │  📤 {$pushType} Push";

    return $msg;
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
    $chat = getEffectiveTelegramChat($conn, $userId);
    return !empty($chat['chat_id']);
}

/**
 * Send notification to specific user (or auto-detect logged-in user for per-user isolation)
 */
function sendTelegramNotification($message, $conn = null, $userId = null, $replyMarkup = null) {
    if (!$conn) {
        global $conn;
    }

    $chat = getEffectiveTelegramChat($conn, $userId);
    if (!empty($chat['chat_id'])) {
        return sendSingleTelegramNotification($chat['chat_id'], $message, $chat['bot_token'], $replyMarkup);
    }

    return json_encode([
        "ok" => false,
        "message" => "Telegram is not connected. Please connect your Telegram account first in Telegram Settings.",
        "description" => "Telegram is not connected. Please connect your Telegram account first in Telegram Settings."
    ]);
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

/**
 * Get system Low Stock Threshold (default 5 if not set)
 */
function getLowStockThreshold($conn = null) {
    if (!$conn) {
        global $conn;
    }
    if ($conn) {
        ensureSettingsTableExists($conn);
        $res = @db_query($conn, "SELECT setting_value FROM system_settings WHERE setting_key = 'low_stock_threshold'");
        if ($res && $row = db_fetch_assoc($res)) {
            $val = (int)$row['setting_value'];
            if ($val >= 0) return $val;
        }
    }
    return 5; // Default threshold fallback
}

/**
 * Set system Low Stock Threshold
 */
function setLowStockThreshold($conn, $threshold) {
    if (!$conn) return false;
    ensureSettingsTableExists($conn);
    $val = max(0, (int)$threshold);

    if ($conn instanceof PgSqlConnWrapper) {
        $stmt = db_prepare($conn, "INSERT INTO system_settings (setting_key, setting_value) VALUES ('low_stock_threshold', ?) ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value");
        if ($stmt) {
            $sVal = (string)$val;
            db_stmt_bind_param($stmt, "s", $sVal);
            db_stmt_execute($stmt);
            db_stmt_close($stmt);
            return true;
        }
    }

    $stmt = db_prepare($conn, "INSERT INTO system_settings (setting_key, setting_value) VALUES ('low_stock_threshold', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    if ($stmt) {
        $sVal = (string)$val;
        db_stmt_bind_param($stmt, "s", $sVal);
        db_stmt_execute($stmt);
        db_stmt_close($stmt);
        return true;
    }

    return false;
}

/**
 * Send Low Stock Alert for a single product to Telegram subscribers/user
 */
function sendLowStockAlert($product, $conn = null, $userId = null) {
    if (!$conn) {
        global $conn;
    }

    $threshold = getLowStockThreshold($conn);
    $name = htmlspecialchars($product['product_name'] ?? 'N/A');
    $code = htmlspecialchars($product['product_code'] ?? 'N/A');
    $category = htmlspecialchars($product['category_name'] ?? 'General');
    $price = number_format((float)($product['price'] ?? 0), 2);
    $qty = (int)($product['quantity'] ?? 0);
    $imgUrl = $product['image'] ?? null;

    $alertMsg = "⚠️ <b>CRITICAL LOW STOCK ALERT!</b> ⚠️\n"
              . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
              . "📦 <b>Product:</b> {$name}\n"
              . "🏷️ <b>Code:</b> <code>{$code}</code>\n"
              . "📊 <b>Category:</b> {$category}\n"
              . "💵 <b>Price:</b> \${$price}\n"
              . "📉 <b>Current Stock:</b> <b>{$qty}</b> unit(s) <i>(Threshold: {$threshold})</i>\n"
              . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
              . "⚡ <i>Action Required: Please restock this item immediately!</i>";

    $baseUrl = getAppBaseUrl();
    $dashboardUrl = "https://app.fieldbi.com/?page=promptdemo&rpf=zGR88xyzPD&action=page&frm=RMt_ph898";
    $productsUrl = $baseUrl . "/products.php";

    $replyMarkup = [
        'inline_keyboard' => [
            [
                ['text' => '🌾 View FieldBI Website', 'url' => $dashboardUrl],
                ['text' => '📦 Manage Products', 'url' => $productsUrl]
            ]
        ]
    ];

    if (!empty($imgUrl)) {
        return sendTelegramPhotoNotification($alertMsg, $imgUrl, $conn, $userId, $replyMarkup);
    } else {
        return sendTelegramNotification($alertMsg, $conn, $userId, $replyMarkup);
    }
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
