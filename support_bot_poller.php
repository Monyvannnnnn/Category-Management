<?php
/**
 * Real-time Poller Daemon for Telegram Support Bot
 */

set_time_limit(0);
ignore_user_abort(true);

require_once __DIR__ . '/support_bot.php';

$botToken = BOT_TOKEN;
$offset = 0;

echo "[" . date('Y-m-d H:i:s') . "] Support Bot Poller started for token: {$botToken}\n";

while (true) {
    $url = "https://api.telegram.org/bot{$botToken}/getUpdates?offset={$offset}&timeout=5";
    
    $ctx = stream_context_create([
        'http' => ['timeout' => 10]
    ]);
    
    $response = @file_get_contents($url, false, $ctx);
    if ($response === false) {
        sleep(2);
        continue;
    }
    
    $data = json_decode($response, true);
    
    if (!empty($data['result'])) {
        foreach ($data['result'] as $up) {
            $updateId = $up['update_id'];
            echo "[" . date('Y-m-d H:i:s') . "] Processing update #{$updateId}...\n";
            
            try {
                processSupportBotUpdate($up);
            } catch (Exception $e) {
                echo "Error processing update: " . $e->getMessage() . "\n";
            }
            
            $offset = $updateId + 1;
        }
    }
    
    usleep(500000); // Sleep 0.5s between polls
}
