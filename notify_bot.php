<?php
/**
 * Telegram Bot Notification Handler
 * Optimized for InfinityFree & Local Hosting (with DNS Resolution Bypass)
 */

function sendTelegramNotification($message) {
    $botToken = "8587070306:AAHHGV2Z6ZzmOiDi6dxL8GnXqQPqDNBuDd8"; 
    $chatId = "7892238736"; 
    $url = "https://api.telegram.org/bot$botToken/sendMessage";
    $data = [
        'chat_id' => $chatId,
        'text' => $message,
        'parse_mode' => 'HTML'
    ];

    $result = false;
    $curlError = '';

    // Method 1: Standard cURL
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
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

    // Method 2: InfinityFree DNS Bypass (If host DNS fails to resolve api.telegram.org)
    if (($result === false || (is_string($result) && strpos($result, '"ok":true') === false)) && function_exists('curl_init')) {
        $telegramIPs = ['149.154.167.220', '149.154.167.198', '91.108.56.160'];
        foreach ($telegramIPs as $ip) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
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

    // Method 3: Fallback to stream context file_get_contents
    if ($result === false || (is_string($result) && strpos($result, '"ok":true') === false)) {
        $options = [
            'http' => [
                'header'  => "Content-Type: application/x-www-form-urlencoded\r\n" .
                             "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
                'timeout' => 15,
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
 * Ensure system_settings table exists (Auto-create for InfinityFree & Local)
 */
function ensureSettingsTableExists($conn) {
    if (!$conn) return;
    $sql = "CREATE TABLE IF NOT EXISTS `system_settings` (
      `setting_key` varchar(50) NOT NULL,
      `setting_value` varchar(255) NOT NULL,
      PRIMARY KEY (`setting_key`)
    ) DEFAULT CHARSET=utf8mb4;";
    @mysqli_query($conn, $sql);
}

/**
 * Check if Auto Telegram notifications are enabled in system_settings
 */
function isAutoTelegramEnabled($conn) {
    if (!$conn) return true;
    ensureSettingsTableExists($conn);
    $res = @mysqli_query($conn, "SELECT setting_value FROM system_settings WHERE setting_key = 'auto_telegram_notify'");
    if ($res && $row = mysqli_fetch_assoc($res)) {
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
    $valEsc = mysqli_real_escape_string($conn, $val);
    $res = @mysqli_query($conn, "REPLACE INTO system_settings (setting_key, setting_value) VALUES ('auto_telegram_notify', '$valEsc')");
    return ($res !== false);
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
