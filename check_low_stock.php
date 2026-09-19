<?php
/**
 * Standalone Low Stock Alert Checker & Dispatcher
 * Can be run via CLI, cron job, or called on-demand.
 */

require_once __DIR__ . "/database.php";
require_once __DIR__ . "/notify_bot.php";

header('Content-Type: application/json; charset=utf-8');

$conn = $conn ?? null;
if (!$conn) {
    die(json_encode(["ok" => false, "message" => "Database connection failed."]));
}

$threshold = getLowStockThreshold($conn);

// Query products where quantity <= threshold
$sql = "SELECT p.*, c.category_name 
        FROM product p 
        LEFT JOIN category c ON p.category_id = c.id 
        WHERE p.quantity <= {$threshold} 
        ORDER BY p.quantity ASC, p.id DESC";

$res = db_query($conn, $sql);
$lowStockItems = [];

if ($res) {
    while ($r = db_fetch_assoc($res)) {
        $lowStockItems[] = $r;
    }
}

$count = count($lowStockItems);

if ($count === 0) {
    echo json_encode([
        "ok" => true,
        "message" => "No low-stock items detected.",
        "low_stock_count" => 0,
        "threshold" => $threshold
    ]);
    exit;
}

// Build digest alert message
$baseUrl = getAppBaseUrl();
$dashboardUrl = $baseUrl . "/report_bi.php";
$productsUrl = $baseUrl . "/products.php";

$msg = "⚠️ <b>LOW STOCK INVENTORY SUMMARY</b> ⚠️\n"
     . "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
     . "Found <b>{$count} item(s)</b> at or below threshold (<b>{$threshold}</b>):\n\n";

foreach ($lowStockItems as $idx => $item) {
    $num = $idx + 1;
    $name = htmlspecialchars($item['product_name']);
    $code = htmlspecialchars($item['product_code']);
    $qty = (int)$item['quantity'];
    $price = number_format((float)$item['price'], 2);
    
    $statusEmoji = ($qty == 0) ? "🔴 <b>OUT OF STOCK</b>" : "⚠️ <b>LOW</b> ({$qty} left)";
    $msg .= "<b>{$num}. {$name}</b> (<code>{$code}</code>)\n"
          . "   └ Stock: {$statusEmoji} │ \${$price}\n";
}

$msg .= "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n"
      . "⚡ <i>Please restock these items soon!</i>";

$replyMarkup = [
    'inline_keyboard' => [
        [
            ['text' => '📊 View FieldBI Dashboard', 'url' => $dashboardUrl],
            ['text' => '📦 Manage Products', 'url' => $productsUrl]
        ]
    ]
];

$dispatchResult = sendTelegramNotification($msg, $conn, null, $replyMarkup);

echo json_encode([
    "ok" => true,
    "message" => "Low stock summary dispatched successfully.",
    "low_stock_count" => $count,
    "threshold" => $threshold,
    "items" => array_map(function($i) {
        return [
            "id" => $i["id"],
            "code" => $i["product_code"],
            "name" => $i["product_name"],
            "quantity" => (int)$i["quantity"]
        ];
    }, $lowStockItems),
    "telegram_response" => json_decode($dispatchResult, true) ?: $dispatchResult
]);
