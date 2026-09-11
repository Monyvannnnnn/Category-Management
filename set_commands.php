<?php
/**
 * Telegram Bot Command Webhook & Handler
 * Premium Styled Inventory Management System
 * Delegates command processing to bot_poller.php
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/notify_bot.php';
require_once __DIR__ . '/bot_poller.php';

$botToken = "8736337451:AAEtwDgtwUpWGnV4cIrMNKwNjHaAV8J18jc";

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

    echo json_encode([
        "ok" => true,
        "message" => "Telegram Bot Webhook and all 18 commands registered successfully!",
        "webhook_url" => $webhookUrl,
        "webhook_response" => json_decode($whRes, true),
        "commands_response" => json_decode($cmdRes, true)
    ]);
    exit;
}

// Read incoming Telegram update (Webhook mode)
$content = file_get_contents("php://input");
$update  = json_decode($content, true);

if (!isset($update["message"])) exit;

$updateId = (int)($update["update_id"] ?? 0);
$chatId   = $update["message"]["chat"]["id"] ?? '';
$text     = trim($update["message"]["text"] ?? '');

if (empty($chatId) || empty($text)) exit;

processTelegramCommand($conn, $chatId, $text, $botToken, 1, $updateId);
exit;
