<?php
/**
 * Multi-Tenant Telegram Bot Continuous Daemon Worker
 * User A -> Bot A -> Chat A
 * User B -> Bot B -> Chat B
 * User C -> Bot C -> Chat C
 */

set_time_limit(0);
ignore_user_abort(true);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/notify_bot.php';
require_once __DIR__ . '/bot_poller.php';

header("Content-Type: application/json; charset=utf-8");

// Single process lock to prevent concurrent daemon processes
$lockFp = fopen(__DIR__ . '/telegram_poller.lock', 'c+');
if (!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] Another instance of bot poller is already running. Exiting.\n";
    exit(0);
}

$defaultBotToken = "8736337451:AAEtwDgtwUpWGnV4cIrMNKwNjHaAV8J18jc";
registerBotCommands($defaultBotToken);

// Global offset and processed updates registry for multi-bot polling
$offsetMap = [];
$processedUpdates = [];

echo "[" . date('Y-m-d H:i:s') . "] Starting Multi-Tenant Telegram Bot Poller Daemon...\n";

while (true) {
    // 1. Fetch all configured user bots
    $bots = [];

    // System default bot
    $bots[] = [
        'user_id' => 1,
        'bot_token' => $defaultBotToken
    ];

    $res = mysqli_query($conn, "SELECT user_id, bot_token FROM user_telegram_bots WHERE bot_token IS NOT NULL AND bot_token != ''");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $token = trim($row['bot_token']);
            if ($token !== $defaultBotToken) {
                $bots[] = [
                    'user_id' => (int)$row['user_id'],
                    'bot_token' => $token
                ];
            }
        }
    }

    // 2. Poll updates for each bot
    foreach ($bots as $b) {
        $bToken = $b['bot_token'];
        $bUserId = $b['user_id'];
        $bOffset = $offsetMap[$bToken] ?? 0;

        $getUpdatesUrl = "https://api.telegram.org/bot{$bToken}/getUpdates?offset={$bOffset}&timeout=2";

        $ch = curl_init($getUpdatesUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data && isset($data['result']) && is_array($data['result'])) {
                foreach ($data['result'] as $update) {
                    $updateId = $update['update_id'];
                    $offsetMap[$bToken] = $updateId + 1;

                    // Deduplicate updates per bot token
                    $dedupKey = $bToken . '_' . $updateId;
                    if (isset($processedUpdates[$dedupKey])) {
                        continue;
                    }
                    $processedUpdates[$dedupKey] = true;
                    if (count($processedUpdates) > 1000) {
                        $processedUpdates = array_slice($processedUpdates, -500, 500, true);
                    }

                    if (isset($update['message'])) {
                        $msgObj  = $update['message'];
                        $chatId  = $msgObj['chat']['id'] ?? '';
                        $text    = trim($msgObj['text'] ?? '');

                        if (!empty($chatId) && !empty($text)) {
                            echo "[" . date('Y-m-d H:i:s') . "] Bot (User {$bUserId}) received: '$text' from Chat ID: $chatId\n";
                            processTelegramCommand($conn, $chatId, $text, $bToken, $bUserId);
                        }
                    }
                }
            }
        }
    }

    sleep(1);
}
