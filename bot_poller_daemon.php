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

// Auto-ensure atomic updates table exists
mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `processed_telegram_updates` (
  `update_id` bigint(20) NOT NULL,
  `processed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`update_id`)
) ENGINE=InnoDB;");

// Global offset and processed updates registry for multi-bot polling
$offsetMap = [];

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
                    $updateId = (int)$update['update_id'];
                    $offsetMap[$bToken] = $updateId + 1;

                    // Atomic Database-Level Deduplication Lock across concurrent container instances
                    $insStmt = mysqli_prepare($conn, "INSERT IGNORE INTO processed_telegram_updates (update_id) VALUES (?)");
                    if ($insStmt) {
                        mysqli_stmt_bind_param($insStmt, "i", $updateId);
                        mysqli_stmt_execute($insStmt);
                        $affected = mysqli_stmt_affected_rows($insStmt);
                        mysqli_stmt_close($insStmt);

                        if ($affected === 0) {
                            // Another container instance already claimed and processed this update_id!
                            continue;
                        }
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
