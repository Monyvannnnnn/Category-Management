<?php
/**
 * Telegram Bot Update Listener (poll_updates.php)
 * Aligned with Inventory System Multi-Tenant Poller
 */
require_once __DIR__ . "/database.php";
require_once __DIR__ . "/bot_poller.php";

// Prevent multiple concurrent poller instances
$lockFp = fopen(__DIR__ . '/telegram_poller.lock', 'c+');
if (!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] Another instance of poll_updates.php is already running. Exiting.\n";
    exit(0);
}

echo "Starting Telegram Bot Update Listener...\n";

$offsetFile = __DIR__ . '/telegram_offset.txt';
$defaultBotToken = "8736337451:AAEtwDgtwUpWGnV4cIrMNKwNjHaAV8J18jc";

registerBotCommands($defaultBotToken);

$processedUpdates = [];

while (true) {
    $offset = file_exists($offsetFile) ? (int)file_get_contents($offsetFile) : 0;
    $url = "https://api.telegram.org/bot{$defaultBotToken}/getUpdates?offset={$offset}&timeout=5";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['result']) && is_array($data['result'])) {
            foreach ($data['result'] as $update) {
                $updateId = $update['update_id'];
                $offset = $updateId + 1;
                file_put_contents($offsetFile, $offset);

                if (isset($processedUpdates[$updateId])) {
                    continue; // Skip duplicate update processing
                }
                $processedUpdates[$updateId] = true;
                if (count($processedUpdates) > 1000) {
                    $processedUpdates = array_slice($processedUpdates, -500, 500, true);
                }

                if (isset($update['message'])) {
                    $msgObj  = $update['message'];
                    $chatId  = $msgObj['chat']['id'] ?? '';
                    $text    = trim($msgObj['text'] ?? '');

                    if (!empty($chatId) && !empty($text)) {
                        echo "[" . date('Y-m-d H:i:s') . "] Received: '$text' from Chat ID: $chatId\n";
                        processTelegramCommand($conn, $chatId, $text, $defaultBotToken, 1, $updateId);
                    }
                }
            }
        }
    }

    sleep(1);
}
