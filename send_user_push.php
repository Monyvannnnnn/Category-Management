<?php
// send_user_push.php - Sends Push Notifications to the specific user's connected Telegram bot & chat
header("Content-Type: application/json; charset=utf-8");
require_once __DIR__ . "/includes/auth_helper.php";
require_once __DIR__ . "/notify_bot.php";

$user = getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

$userId = (int)$user['id'];
$rawInput = file_get_contents('php://input');
$json = json_decode($rawInput, true);

$message = trim($_POST['message'] ?? $json['message'] ?? '');

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Message parameter is required.']);
    exit;
}

// Fetch user's bot settings
$stmt = mysqli_prepare($conn, "SELECT * FROM user_telegram_bots WHERE user_id = ? LIMIT 1");
$userBot = null;
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $userBot = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
}

$botToken = $userBot['bot_token'] ?? "8736337451:AAEtwDgtwUpWGnV4cIrMNKwNjHaAV8J18jc";
$chatId   = $userBot['chat_id'] ?? null;

if (empty($chatId)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'No Telegram chat connected for your account. Please connect your bot first.'
    ]);
    exit;
}

// Dispatch notification using the user's specific bot token & chat ID
$url = "https://api.telegram.org/bot{$botToken}/sendMessage";
$postData = [
    'chat_id'    => $chatId,
    'text'       => $message,
    'parse_mode' => 'HTML'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
$response = curl_exec($ch);
curl_close($ch);

if ($response !== false) {
    $resData = json_decode($response, true);
    if ($resData && ($resData['ok'] ?? false)) {
        echo json_encode([
            'success' => true,
            'message' => 'Message sent successfully to your Telegram chat!'
        ]);
        exit;
    }
}

echo json_encode([
    'success' => false,
    'message' => 'Failed to deliver message via Telegram Bot API.',
    'raw_response' => $response
]);
