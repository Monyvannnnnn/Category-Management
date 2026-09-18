<?php
/**
 * Telegram Bot Command Webhook & Handler
 * Premium Styled Inventory Management System
 * Delegates command processing to bot_poller.php
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/notify_bot.php';
require_once __DIR__ . '/bot_poller.php';

$botToken = "8560470449:AAEuX9eLYvk0wxh65Rc0d8iNhObzVzni-x8";

// If accessed via GET browser request, register Webhook and all 18 commands with Telegram BotFather API
if ($_SERVER['REQUEST_METHOD'] === 'GET' || isset($_GET['action'])) {
    header("Content-Type: application/json; charset=utf-8");
    $host = $_SERVER['HTTP_HOST'] ?? 'report-push-v2.vercel.app';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'https';
    $webhookUrl = "{$scheme}://{$host}/set_commands.php";

    $whApiUrl = "https://api.telegram.org/bot{$botToken}/setWebhook?url=" . urlencode($webhookUrl);
    $ch = curl_init($whApiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $whRes = curl_exec($ch);
    curl_close($ch);

    $cmdRes = registerBotCommands($botToken);

    // Set Telegram Chat Menu Button to open Field BI Mini App
    $fieldBiAppUrl = "{$scheme}://{$host}/fieldbi.php";
    $menuBtnUrl = "https://api.telegram.org/bot{$botToken}/setChatMenuButton";
    $menuBtnPayload = [
        'menu_button' => [
            'type' => 'web_app',
            'text' => '🌾 Field BI',
            'web_app' => [
                'url' => $fieldBiAppUrl
            ]
        ]
    ];
    $chMB = curl_init($menuBtnUrl);
    curl_setopt($chMB, CURLOPT_POST, true);
    curl_setopt($chMB, CURLOPT_POSTFIELDS, json_encode($menuBtnPayload));
    curl_setopt($chMB, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($chMB, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chMB, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($chMB, CURLOPT_SSL_VERIFYHOST, 0);
    $menuBtnRes = curl_exec($chMB);
    curl_close($chMB);

    echo json_encode([
        "ok" => true,
        "message" => "Telegram Bot Webhook, Commands, and Live BI Mini App Menu Button registered successfully!",
        "webhook_url" => $webhookUrl,
        "webhook_response" => json_decode($whRes, true),
        "commands_response" => json_decode($cmdRes, true),
        "menu_button_response" => json_decode($menuBtnRes, true)
    ]);
    exit;
}

// Read incoming Telegram update (Webhook mode)
$content = file_get_contents("php://input");
$update  = json_decode($content, true);

if (isset($update["message"])) {
    $updateId = (int)($update["update_id"] ?? 0);
    $chatId   = $update["message"]["chat"]["id"] ?? '';
    $text     = trim($update["message"]["text"] ?? '');

    if (!empty($chatId) && !empty($text)) {
        processTelegramCommand($conn, $chatId, $text, $botToken, 1, $updateId);
    }
}

http_response_code(200);
header("Content-Type: application/json");
echo json_encode(["ok" => true]);
exit;
