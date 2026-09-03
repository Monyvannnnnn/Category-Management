<?php
/**
 * Manual Telegram Push Handler
 * Inventory Management System
 */

require_once "database.php";
require_once "notify_bot.php";

header("Content-Type: application/json");

// Support GET and POST requests
$requestMethod = $_SERVER["REQUEST_METHOD"];
if ($requestMethod !== "POST" && $requestMethod !== "GET") {
    http_response_code(405);
    echo json_encode(["ok" => false, "message" => "Method Not Allowed"]);
    exit;
}

// Support form-data, urlencoded, and raw json body
$inputJSON = json_decode(file_get_contents('php://input'), true);
$action = $_REQUEST['action'] ?? $inputJSON['action'] ?? 'summary';
$customMessage = trim($_REQUEST['message'] ?? $inputJSON['message'] ?? '');

// --------------------------------------------------------------------------
// 1. Get Notification Setting
// --------------------------------------------------------------------------
if ($action === 'get_settings') {
    $autoEnabled = isAutoTelegramEnabled($conn);
    echo json_encode([
        "ok" => true,
        "auto_telegram_notify" => $autoEnabled ? "1" : "0"
    ]);
    exit;

// --------------------------------------------------------------------------
// 2. Toggle Auto / Manual Notification Setting
// --------------------------------------------------------------------------
} elseif ($action === 'toggle_auto') {
    $currentStatus = isAutoTelegramEnabled($conn);
    $newVal = isset($_REQUEST['enabled']) ? ($_REQUEST['enabled'] == "1" ? "1" : "0") : ($currentStatus ? "0" : "1");
    
    $saved = setAutoTelegramEnabled($conn, $newVal);
    echo json_encode([
        "ok" => $saved,
        "auto_telegram_notify" => $newVal,
        "message" => ($newVal === "1") ? "Auto Telegram Notifications turned ON." : "Auto Telegram Notifications CLOSED (Manual Mode active)."
    ]);
    exit;

// --------------------------------------------------------------------------
// 3. Single Category Push
// --------------------------------------------------------------------------
} elseif ($action === 'push_single_category') {
    $id = (int)($_REQUEST['id'] ?? $inputJSON['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["ok" => false, "message" => "Invalid category ID."]);
        exit;
    }

    $res = mysqli_query($conn, "SELECT c.*, COUNT(p.id) AS prod_count, COALESCE(SUM(p.quantity), 0) AS total_qty FROM category c LEFT JOIN product p ON c.id = p.category_id WHERE c.id = $id GROUP BY c.id");
    if ($row = mysqli_fetch_assoc($res)) {
        $nowStr = date('Y-m-d H:i:s');
        $msg = "<b>🏷️ SINGLE CATEGORY DETAILS</b>\n"
             . "<i>Pushed: {$nowStr}</i>\n"
             . "───────────────────────\n"
             . "<b>ID:</b> #" . $row['id'] . "\n"
             . "<b>Code:</b> " . htmlspecialchars($row['category_code']) . "\n"
             . "<b>Name:</b> " . htmlspecialchars($row['category_name']) . "\n"
             . "<b>Associated Products:</b> " . (int)$row['prod_count'] . " items\n"
             . "<b>Total Stock Qty:</b> " . (int)$row['total_qty'] . " units\n"
             . "<b>Created At:</b> " . htmlspecialchars($row['created_at']) . "\n"
             . "<b>Last Updated:</b> " . htmlspecialchars($row['lastupdate']) . "\n"
             . "───────────────────────\n"
             . "<i>Pushed manually from Category row</i>";

        echo sendTelegramNotification($msg);
        exit;
    } else {
        http_response_code(404);
        echo json_encode(["ok" => false, "message" => "Category not found."]);
        exit;
    }

// --------------------------------------------------------------------------
// 4. Single Product Push
// --------------------------------------------------------------------------
} elseif ($action === 'push_single_product') {
    $id = (int)($_REQUEST['id'] ?? $inputJSON['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(["ok" => false, "message" => "Invalid product ID."]);
        exit;
    }

    $res = mysqli_query($conn, "SELECT p.*, c.category_name FROM product p LEFT JOIN category c ON p.category_id = c.id WHERE p.id = $id");
    if ($row = mysqli_fetch_assoc($res)) {
        $val = (float)$row['price'] * (int)$row['quantity'];
        $nowStr = date('Y-m-d H:i:s');
        $msg = "<b>📦 SINGLE PRODUCT DETAILS</b>\n"
             . "<i>Pushed: {$nowStr}</i>\n"
             . "───────────────────────\n"
             . "<b>ID:</b> #" . $row['id'] . "\n"
             . "<b>Code:</b> " . htmlspecialchars($row['product_code']) . "\n"
             . "<b>Name:</b> " . htmlspecialchars($row['product_name']) . "\n"
             . "<b>Category:</b> " . htmlspecialchars($row['category_name'] ?? 'N/A') . "\n"
             . "<b>Price:</b> $" . number_format((float)$row['price'], 2) . "\n"
             . "<b>Stock Qty:</b> " . (int)$row['quantity'] . " units\n"
             . "<b>Inventory Value:</b> $" . number_format($val, 2) . "\n"
             . "<b>Last Updated:</b> " . htmlspecialchars($row['lastupdate']) . "\n"
             . "───────────────────────\n"
             . "<i>Pushed manually from Product row</i>";

        echo sendTelegramNotification($msg);
        exit;
    } else {
        http_response_code(404);
        echo json_encode(["ok" => false, "message" => "Product not found."]);
        exit;
    }

// --------------------------------------------------------------------------
// 5. Push Batch Selected Categories
// --------------------------------------------------------------------------
} elseif ($action === 'push_batch_categories') {
    $rawIds = $_REQUEST['ids'] ?? $inputJSON['ids'] ?? '';
    $ids = is_array($rawIds) ? array_map('intval', $rawIds) : array_map('intval', explode(',', (string)$rawIds));
    $ids = array_filter($ids, function($v) { return $v > 0; });

    if (empty($ids)) {
        http_response_code(400);
        echo json_encode(["ok" => false, "message" => "No categories selected for push."]);
        exit;
    }

    $idList = implode(',', $ids);
    $res = mysqli_query($conn, "SELECT * FROM category WHERE id IN ($idList) ORDER BY category_name ASC");
    $items = [];
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = "• <b>" . htmlspecialchars($row['category_code']) . "</b> - " . htmlspecialchars($row['category_name']);
        }
    }

    $nowStr = date('Y-m-d H:i:s');
    $msg = "<b>📋 SELECTED CATEGORIES REPORT (" . count($items) . " Items)</b>\n"
         . "<i>Pushed: {$nowStr}</i>\n"
         . "───────────────────────\n"
         . implode("\n", $items) . "\n"
         . "───────────────────────\n"
         . "<i>Pushed manually from Category DataGrid selection</i>";

    echo sendTelegramNotification($msg);
    exit;

// --------------------------------------------------------------------------
// 6. Push Batch Selected Products
// --------------------------------------------------------------------------
} elseif ($action === 'push_batch_products') {
    $rawIds = $_REQUEST['ids'] ?? $inputJSON['ids'] ?? '';
    $ids = is_array($rawIds) ? array_map('intval', $rawIds) : array_map('intval', explode(',', (string)$rawIds));
    $ids = array_filter($ids, function($v) { return $v > 0; });

    if (empty($ids)) {
        http_response_code(400);
        echo json_encode(["ok" => false, "message" => "No products selected for push."]);
        exit;
    }

    $idList = implode(',', $ids);
    $res = mysqli_query($conn, "SELECT p.*, c.category_name FROM product p LEFT JOIN category c ON p.category_id = c.id WHERE p.id IN ($idList) ORDER BY p.product_name ASC");
    $items = [];
    $sumVal = 0;
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $val = (float)$row['price'] * (int)$row['quantity'];
            $sumVal += $val;
            $items[] = "• <b>" . htmlspecialchars($row['product_code']) . "</b> (" . htmlspecialchars($row['product_name']) . ")\n"
                     . "   Category: " . htmlspecialchars($row['category_name'] ?? 'N/A') . " | Qty: " . (int)$row['quantity'] . " | $" . number_format((float)$row['price'], 2);
        }
    }

    $nowStr = date('Y-m-d H:i:s');
    $msg = "<b>📦 SELECTED PRODUCTS REPORT (" . count($items) . " Items)</b>\n"
         . "<i>Pushed: {$nowStr}</i>\n"
         . "───────────────────────\n"
         . implode("\n\n", $items) . "\n"
         . "───────────────────────\n"
         . "💰 <b>Total Valuation:</b> $" . number_format($sumVal, 2) . "\n"
         . "<i>Pushed manually from Product DataGrid selection</i>";

    echo sendTelegramNotification($msg);
    exit;

// --------------------------------------------------------------------------
// 7. Push Low Stock Report
// --------------------------------------------------------------------------
} elseif ($action === 'push_low_stock') {
    $lowStockRes = mysqli_query($conn, "SELECT p.*, c.category_name FROM product p LEFT JOIN category c ON p.category_id = c.id WHERE p.quantity <= 5 ORDER BY p.quantity ASC");
    $items = [];
    if ($lowStockRes) {
        while ($row = mysqli_fetch_assoc($lowStockRes)) {
            $items[] = "⚠️ <b>" . htmlspecialchars($row['product_code']) . "</b> - " . htmlspecialchars($row['product_name']) . "\n   Category: " . htmlspecialchars($row['category_name'] ?? 'N/A') . " | <code>Stock: " . (int)$row['quantity'] . " remaining</code>";
        }
    }

    $nowStr = date('Y-m-d H:i:s');
    $msg = "<b>⚠️ LOW STOCK WARNING REPORT</b>\n"
         . "<i>Generated: {$nowStr}</i>\n"
         . "───────────────────────\n";
    if (!empty($items)) {
        $msg .= implode("\n", $items) . "\n";
    } else {
        $msg .= "✅ All products are currently well-stocked (> 5 items).\n";
    }
    $msg .= "───────────────────────\n<i>Pushed manually from Inventory Dashboard</i>";

    echo sendTelegramNotification($msg);
    exit;

// --------------------------------------------------------------------------
// 8. Push Financial Valuation Report
// --------------------------------------------------------------------------
} elseif ($action === 'push_valuation') {
    $res = mysqli_query($conn, "SELECT COUNT(*) AS total_prods, COALESCE(SUM(quantity), 0) AS total_stock, COALESCE(SUM(price * quantity), 0) AS total_val, COALESCE(AVG(price), 0) as avg_price FROM product");
    if ($res && $row = mysqli_fetch_assoc($res)) {
        $nowStr = date('Y-m-d H:i:s');
        $msg = "<b>💰 INVENTORY FINANCIAL & VALUATION REPORT</b>\n"
             . "<i>Generated: {$nowStr}</i>\n"
             . "───────────────────────\n"
             . "📦 <b>Total Products Listed:</b> " . number_format((int)$row['total_prods']) . "\n"
             . "🔢 <b>Total Stock Quantity:</b> " . number_format((int)$row['total_stock']) . " units\n"
             . "💲 <b>Average Unit Price:</b> $" . number_format((float)$row['avg_price'], 2) . "\n"
             . "💵 <b>Total Asset Valuation:</b> $" . number_format((float)$row['total_val'], 2) . "\n"
             . "───────────────────────\n"
             . "<i>Pushed manually from Inventory Dashboard</i>";

        echo sendTelegramNotification($msg);
        exit;
    }

// --------------------------------------------------------------------------
// 9. Manual Push: Inventory Summary
// --------------------------------------------------------------------------
} elseif ($action === 'summary') {
    $catRes = mysqli_query($conn, "SELECT COUNT(*) AS total_cats FROM category");
    $totalCats = ($catRes && $catRow = mysqli_fetch_assoc($catRes)) ? (int)$catRow['total_cats'] : 0;

    $prodRes = mysqli_query($conn, "SELECT COUNT(*) AS total_prods, COALESCE(SUM(quantity), 0) AS total_stock, COALESCE(SUM(price * quantity), 0) AS total_val FROM product");
    $totalProds = 0;
    $totalStock = 0;
    $totalVal = 0.00;
    if ($prodRes && $prodRow = mysqli_fetch_assoc($prodRes)) {
        $totalProds = (int)$prodRow['total_prods'];
        $totalStock = (int)$prodRow['total_stock'];
        $totalVal = (float)$prodRow['total_val'];
    }

    $lowStockRes = mysqli_query($conn, "SELECT product_code, product_name, quantity FROM product WHERE quantity <= 5 ORDER BY quantity ASC LIMIT 5");
    $lowStockList = [];
    if ($lowStockRes) {
        while ($row = mysqli_fetch_assoc($lowStockRes)) {
            $lowStockList[] = "• <b>" . htmlspecialchars($row['product_code']) . "</b> (" . htmlspecialchars($row['product_name']) . "): <code>" . (int)$row['quantity'] . " left</code>";
        }
    }

    $nowStr = date('Y-m-d H:i:s');
    $msg = "<b>📊 INVENTORY SUMMARY REPORT</b>\n"
         . "<i>Generated: {$nowStr}</i>\n"
         . "───────────────────────\n"
         . "🏷️ <b>Total Categories:</b> " . number_format($totalCats) . "\n"
         . "📦 <b>Total Products:</b> " . number_format($totalProds) . "\n"
         . "🔢 <b>Total Stock Qty:</b> " . number_format($totalStock) . " items\n"
         . "💰 <b>Total Valuation:</b> $" . number_format($totalVal, 2) . "\n"
         . "───────────────────────\n";

    if (!empty($lowStockList)) {
        $msg .= "⚠️ <b>Low Stock Warning (&le; 5 items):</b>\n" . implode("\n", $lowStockList) . "\n───────────────────────\n";
    } else {
        $msg .= "✅ <b>Stock Status:</b> All products are well stocked.\n───────────────────────\n";
    }

    $msg .= "<i>Pushed manually from Inventory Dashboard</i>";
    $msg = str_replace('&le;', '&lt;=', $msg);

    $result = sendTelegramNotification($msg);
    echo $result;
    exit;

// --------------------------------------------------------------------------
// 10. Manual Push: Recently Added Items
// --------------------------------------------------------------------------
} elseif ($action === 'push_added') {
    $recentProds = mysqli_query($conn, "SELECT p.*, c.category_name FROM product p LEFT JOIN category c ON p.category_id = c.id ORDER BY p.id DESC LIMIT 5");
    $recentCats  = mysqli_query($conn, "SELECT * FROM category ORDER BY id DESC LIMIT 5");

    $prodList = [];
    if ($recentProds) {
        while ($p = mysqli_fetch_assoc($recentProds)) {
            $prodList[] = "📦 <b>" . htmlspecialchars($p['product_code']) . "</b> - " . htmlspecialchars($p['product_name']) 
                        . " (Qty: " . (int)$p['quantity'] . ", $" . number_format((float)$p['price'], 2) . ")";
        }
    }

    $catList = [];
    if ($recentCats) {
        while ($c = mysqli_fetch_assoc($recentCats)) {
            $catList[] = "🏷️ <b>" . htmlspecialchars($c['category_code']) . "</b> - " . htmlspecialchars($c['category_name']);
        }
    }

    $nowStr = date('Y-m-d H:i:s');
    $msg = "<b>🆕 MANUAL PUSH: RECENTLY ADDED ITEMS</b>\n"
         . "<i>Pushed: {$nowStr}</i>\n"
         . "───────────────────────\n";

    if (!empty($prodList)) {
        $msg .= "<b>Latest Added Products:</b>\n" . implode("\n", $prodList) . "\n\n";
    }
    if (!empty($catList)) {
        $msg .= "<b>Latest Added Categories:</b>\n" . implode("\n", $catList) . "\n\n";
    }
    if (empty($prodList) && empty($catList)) {
        $msg .= "<i>No recent items found in the inventory database.</i>\n\n";
    }

    $msg .= "───────────────────────\n<i>Sent manually from Inventory Dashboard</i>";

    $result = sendTelegramNotification($msg);
    echo $result;
    exit;

// --------------------------------------------------------------------------
// 11. Manual Push: Recently Updated Items
// --------------------------------------------------------------------------
} elseif ($action === 'push_updated') {
    $updatedProds = mysqli_query($conn, "SELECT p.*, c.category_name FROM product p LEFT JOIN category c ON p.category_id = c.id ORDER BY p.lastupdate DESC LIMIT 5");
    $updatedCats  = mysqli_query($conn, "SELECT * FROM category ORDER BY lastupdate DESC LIMIT 5");

    $prodList = [];
    if ($updatedProds) {
        while ($p = mysqli_fetch_assoc($updatedProds)) {
            $prodList[] = "✏️ <b>" . htmlspecialchars($p['product_code']) . "</b> - " . htmlspecialchars($p['product_name']) 
                        . " | Stock: " . (int)$p['quantity'] . " | $" . number_format((float)$p['price'], 2)
                        . " <i>(" . htmlspecialchars($p['lastupdate']) . ")</i>";
        }
    }

    $catList = [];
    if ($updatedCats) {
        while ($c = mysqli_fetch_assoc($updatedCats)) {
            $catList[] = "🏷️ <b>" . htmlspecialchars($c['category_code']) . "</b> - " . htmlspecialchars($c['category_name'])
                        . " <i>(" . htmlspecialchars($c['lastupdate']) . ")</i>";
        }
    }

    $nowStr = date('Y-m-d H:i:s');
    $msg = "<b>✏️ MANUAL PUSH: RECENTLY UPDATED ITEMS</b>\n"
         . "<i>Pushed: {$nowStr}</i>\n"
         . "───────────────────────\n";

    if (!empty($prodList)) {
        $msg .= "<b>Latest Updated Products:</b>\n" . implode("\n", $prodList) . "\n\n";
    }
    if (!empty($catList)) {
        $msg .= "<b>Latest Updated Categories:</b>\n" . implode("\n", $catList) . "\n\n";
    }
    if (empty($prodList) && empty($catList)) {
        $msg .= "<i>No recent updates recorded.</i>\n\n";
    }

    $msg .= "───────────────────────\n<i>Sent manually from Inventory Dashboard</i>";

    $result = sendTelegramNotification($msg);
    echo $result;
    exit;

// --------------------------------------------------------------------------
// 12. Manual Push: Custom Message
// --------------------------------------------------------------------------
} elseif ($action === 'custom') {
    if ($customMessage === "") {
        http_response_code(400);
        echo json_encode(["ok" => false, "message" => "Message content cannot be empty."]);
        exit;
    }

    $nowStr = date('Y-m-d H:i:s');
    $msg = "<b>📢 MANUAL TELEGRAM PUSH</b>\n"
         . "<i>Pushed: {$nowStr}</i>\n"
         . "───────────────────────\n"
         . htmlspecialchars($customMessage) . "\n"
         . "───────────────────────\n"
         . "<i>Sent from Inventory System Dashboard</i>";

    $result = sendTelegramNotification($msg);
    echo $result;
    exit;

} else {
    http_response_code(400);
    echo json_encode(["ok" => false, "message" => "Invalid push action specified."]);
    exit;
}
?>
