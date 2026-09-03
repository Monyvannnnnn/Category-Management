<?php
/**
 * Telegram Bot Continuous Daemon Worker
 * Premium Styled Inventory Management System
 */

set_time_limit(0);
ignore_user_abort(true);

require_once __DIR__ . '/database.php';

$botToken = "8587070306:AAHHGV2Z6ZzmOiDi6dxL8GnXqQPqDNBuDd8";

function registerBotCommands($botToken) {
    $url = "https://api.telegram.org/bot{$botToken}/setMyCommands";
    $commands = [
        ['command' => 'search',     'description' => '🔍 Search product by name or code (/search <keyword>)'],
        ['command' => 'categories', 'description' => '🏷️ View all item categories'],
        ['command' => 'sort',       'description' => '↕️ View items sorted (/sort price | stock | date)'],
        ['command' => 'lowstock',   'description' => '⚠️ List critical items with stock <= 5'],
        ['command' => 'summary',    'description' => '📊 Real-time total categories, products, & valuation'],
        ['command' => 'valuation',  'description' => '💎 Financial report and average pricing breakdown'],
        ['command' => 'added',      'description' => '🆕 List recently added products and categories'],
        ['command' => 'updated',    'description' => '✏️ List recently modified products and categories'],
        ['command' => 'help',       'description' => '❓ Show all command usage and examples']
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['commands' => $commands]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_exec($ch);
    curl_close($ch);
}

function sendTelegramMessage($chatId, $text, $botToken) {
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $postData = [
        'chat_id'    => $chatId,
        'text'       => $text,
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
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    if (defined('CURL_IPRESOLVE_V4')) {
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    }
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

function processTelegramCommand($conn, $chatId, $text, $botToken) {
    $parts   = explode(' ', $text, 2);
    $command = strtolower($parts[0]);
    $command = explode('@', $command)[0]; // Remove @botname suffix
    $arg     = strtolower(trim($parts[1] ?? ''));

    switch ($command) {
        // ----------------------------------------------------
        // 1. /search <keyword>
        // ----------------------------------------------------
        case '/search':
            if (empty($arg)) {
                $msg = "⚠️ <b>INVALID SEARCH FORMAT</b>\n"
                     . "═════════════════════════════\n"
                     . "Usage: <code>/search &lt;keyword&gt;</code>\n"
                     . "Example: <code>/search Camera</code>";
                sendTelegramMessage($chatId, $msg, $botToken);
                break;
            }
            $stmt = mysqli_prepare($conn, "SELECT p.product_code, p.product_name, p.price, p.quantity, c.category_name FROM product p LEFT JOIN category c ON p.category_id = c.id WHERE p.product_name LIKE ? OR p.product_code LIKE ? OR c.category_name LIKE ? LIMIT 5");
            $searchArg = "%" . $arg . "%";
            mysqli_stmt_bind_param($stmt, "sss", $searchArg, $searchArg, $searchArg);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                $msg = "🔍 <b>PRODUCT SEARCH DIRECTORY</b>\n"
                     . "<i>Query: '<b>" . htmlspecialchars($arg) . "</b>' • Results</i>\n"
                     . "═════════════════════════════\n\n";
                while ($r = mysqli_fetch_assoc($result)) {
                    $code  = htmlspecialchars($r['product_code']);
                    $name  = htmlspecialchars($r['product_name']);
                    $cat   = htmlspecialchars($r['category_name'] ?? 'Unassigned');
                    $price = number_format((float)$r['price'], 2);
                    $qty   = (int)$r['quantity'];
                    $msg  .= "📦 <b>{$name}</b>\n"
                          . "├ 🆔 Code: <code>{$code}</code>\n"
                          . "├ 🏷️ Category: <code>{$cat}</code>\n"
                          . "├ 💰 Price: <b>\${$price}</b>\n"
                          . "└ 🔢 Stock: <b>{$qty} units</b>\n\n";
                }
                $msg .= "─────────────────────────────\n"
                      . "💡 <i>Refine your query for specific product codes</i>";
            } else {
                $msg = "❌ <b>NO MATCHES FOUND</b>\n"
                     . "═════════════════════════════\n"
                     . "No products matching '<b>" . htmlspecialchars($arg) . "</b>' were found in the database.";
            }
            mysqli_stmt_close($stmt);
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // ----------------------------------------------------
        // 2. /categories
        // ----------------------------------------------------
        case '/categories':
            $res = mysqli_query($conn, "SELECT c.category_code, c.category_name, COUNT(p.id) AS prod_count, COALESCE(SUM(p.quantity), 0) AS total_qty FROM category c LEFT JOIN product p ON c.id = p.category_id GROUP BY c.id ORDER BY c.category_name ASC");
            if ($res && mysqli_num_rows($res) > 0) {
                $totalCats = mysqli_num_rows($res);
                $msg = "🏷️ <b>CATEGORY MANAGEMENT OVERVIEW</b>\n"
                     . "<i>Total Listed: <b>{$totalCats} Categories</b></i>\n"
                     . "═════════════════════════════\n\n";
                while ($r = mysqli_fetch_assoc($res)) {
                    $code = htmlspecialchars($r['category_code']);
                    $name = htmlspecialchars($r['category_name']);
                    $cnt  = (int)$r['prod_count'];
                    $qty  = (int)$r['total_qty'];
                    $msg .= "📁 <b>{$name}</b>\n"
                          . "├ 🆔 Code: <code>{$code}</code>\n"
                          . "├ 📦 Products: <b>{$cnt} items</b>\n"
                          . "└ 🔢 Total Stock: <b>{$qty} units</b>\n\n";
                }
                $msg .= "─────────────────────────────\n"
                      . "📊 <i>Use /summary for complete inventory valuation</i>";
            } else {
                $msg = "📂 <b>NO CATEGORIES FOUND</b>\n"
                     . "═════════════════════════════\n"
                     . "The category directory is currently empty.";
            }
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // ----------------------------------------------------
        // 3. /sort [price | stock | date]
        // ----------------------------------------------------
        case '/sort':
            $orderBy = "p.id DESC";
            $sortLabel = "Date Created (Newest First)";
            if ($arg === 'price') {
                $orderBy = "p.price DESC";
                $sortLabel = "Price (High to Low)";
            } elseif ($arg === 'stock') {
                $orderBy = "p.quantity ASC";
                $sortLabel = "Stock Quantity (Low to High)";
            } elseif ($arg === 'date') {
                $orderBy = "p.created_at DESC";
                $sortLabel = "Date Created (Newest First)";
            }

            $res = mysqli_query($conn, "SELECT p.product_code, p.product_name, p.price, p.quantity FROM product p ORDER BY {$orderBy} LIMIT 10");
            if ($res && mysqli_num_rows($res) > 0) {
                $msg = "↕️ <b>SORTED INVENTORY CATALOG</b>\n"
                     . "<i>Sorted By: <b>{$sortLabel}</b></i>\n"
                     . "═════════════════════════════\n\n";
                $i = 1;
                while ($r = mysqli_fetch_assoc($res)) {
                    $code  = htmlspecialchars($r['product_code']);
                    $name  = htmlspecialchars($r['product_name']);
                    $price = number_format((float)$r['price'], 2);
                    $qty   = (int)$r['quantity'];
                    $msg  .= "<b>{$i}. {$name}</b>\n"
                          . "   └ <code>{$code}</code> • 💰 <b>\${$price}</b> • 🔢 Stock: <b>{$qty}</b>\n\n";
                    $i++;
                }
                $msg .= "─────────────────────────────\n"
                      . "💡 <i>Try: <code>/sort price</code> • <code>/sort stock</code> • <code>/sort date</code></i>";
            } else {
                $msg = "📦 <b>NO PRODUCTS TO SORT</b>\n"
                     . "═════════════════════════════\n"
                     . "Product catalog is empty.";
            }
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // ----------------------------------------------------
        // 4. /lowstock
        // ----------------------------------------------------
        case '/lowstock':
            $res = mysqli_query($conn, "SELECT p.product_code, p.product_name, p.quantity, c.category_name FROM product p LEFT JOIN category c ON p.category_id = c.id WHERE p.quantity <= 5 ORDER BY p.quantity ASC");
            if ($res && mysqli_num_rows($res) > 0) {
                $msg = "⚠️ <b>CRITICAL LOW STOCK ALERTS</b>\n"
                     . "<i>Threshold: <b>≤ 5 units remaining</b></i>\n"
                     . "═════════════════════════════\n\n";
                while ($r = mysqli_fetch_assoc($res)) {
                    $code = htmlspecialchars($r['product_code']);
                    $name = htmlspecialchars($r['product_name']);
                    $cat  = htmlspecialchars($r['category_name'] ?? 'Unassigned');
                    $qty  = (int)$r['quantity'];
                    $msg .= "🚨 <b>{$name}</b>\n"
                          . "├ 🆔 Code: <code>{$code}</code>\n"
                          . "├ 🏷️ Category: <code>{$cat}</code>\n"
                          . "└ ⚠️ Stock Level: <b><u>{$qty} units remaining</u></b>\n\n";
                }
                $msg .= "─────────────────────────────\n"
                      . "📢 <i>Please reorder stock items to prevent stockout.</i>";
            } else {
                $msg = "✅ <b>HEALTHY STOCK LEVELS</b>\n"
                     . "═════════════════════════════\n"
                     . "All items in the warehouse have more than 5 units in stock.";
            }
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // ----------------------------------------------------
        // 5. /summary
        // ----------------------------------------------------
        case '/summary':
            $catRes = mysqli_query($conn, "SELECT COUNT(*) AS total_cats FROM category");
            $totalCats = ($catRes && $catRow = mysqli_fetch_assoc($catRes)) ? (int)$catRow['total_cats'] : 0;

            $prodRes = mysqli_query($conn, "SELECT COUNT(*) AS total_prods, COALESCE(SUM(quantity), 0) AS total_stock, COALESCE(SUM(price * quantity), 0) AS total_val, COALESCE(AVG(price), 0) as avg_price FROM product");
            $totalProds = 0; $totalStock = 0; $totalVal = 0.00; $avgPrice = 0.00;
            if ($prodRes && $prodRow = mysqli_fetch_assoc($prodRes)) {
                $totalProds = (int)$prodRow['total_prods'];
                $totalStock = (int)$prodRow['total_stock'];
                $totalVal   = (float)$prodRow['total_val'];
                $avgPrice   = (float)$prodRow['avg_price'];
            }

            $lowStockRes = mysqli_query($conn, "SELECT COUNT(*) AS low_count FROM product WHERE quantity <= 5");
            $lowCount = ($lowStockRes && $lowRow = mysqli_fetch_assoc($lowStockRes)) ? (int)$lowRow['low_count'] : 0;

            $nowStr = date('d/m/Y H:i:s');
            $msg = "📊 <b>EXECUTIVE INVENTORY DASHBOARD</b>\n"
                 . "<i>Real-Time System Overview • {$nowStr}</i>\n"
                 . "═════════════════════════════\n\n"
                 . "🏷️ <b>Total Categories:</b> <code>" . number_format($totalCats) . "</code>\n"
                 . "📦 <b>Total Products Listed:</b> <code>" . number_format($totalProds) . "</code>\n"
                 . "🔢 <b>Total Stock Quantity:</b> <code>" . number_format($totalStock) . " units</code>\n"
                 . "💵 <b>Average Unit Price:</b> <code>\$" . number_format($avgPrice, 2) . "</code>\n"
                 . "💰 <b>Total Asset Valuation:</b> <code>\$" . number_format($totalVal, 2) . "</code>\n"
                 . "⚠️ <b>Low Stock Alert Items:</b> <code>" . number_format($lowCount) . " products</code>\n\n"
                 . "─────────────────────────────\n"
                 . "<i>Generated live from Inventory Database</i>";
            
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // ----------------------------------------------------
        // 6. /valuation
        // ----------------------------------------------------
        case '/valuation':
            $prodRes = mysqli_query($conn, "SELECT COUNT(*) AS total_prods, COALESCE(SUM(quantity), 0) AS total_stock, COALESCE(SUM(price * quantity), 0) AS total_val, COALESCE(AVG(price), 0) as avg_price, COALESCE(MAX(price), 0) as max_price, COALESCE(MIN(price), 0) as min_price FROM product");
            $row = mysqli_fetch_assoc($prodRes);

            $nowStr = date('d/m/Y H:i:s');
            $msg = "💎 <b>FINANCIAL & ASSET VALUATION REPORT</b>\n"
                 . "<i>Comprehensive Portfolio Breakdown • {$nowStr}</i>\n"
                 . "═════════════════════════════\n\n"
                 . "💰 <b>Total Asset Valuation:</b> <b>\$" . number_format((float)$row['total_val'], 2) . "</b>\n"
                 . "📦 <b>Total Active Products:</b> <code>" . number_format((int)$row['total_prods']) . " items</code>\n"
                 . "🔢 <b>Total Units in Stock:</b> <code>" . number_format((int)$row['total_stock']) . " units</code>\n"
                 . "💵 <b>Average Product Price:</b> <code>\$" . number_format((float)$row['avg_price'], 2) . "</code>\n"
                 . "📈 <b>Highest Price Product:</b> <code>\$" . number_format((float)$row['max_price'], 2) . "</code>\n"
                 . "📉 <b>Lowest Price Product:</b> <code>\$" . number_format((float)$row['min_price'], 2) . "</code>\n\n"
                 . "─────────────────────────────\n"
                 . "📈 <i>Warehouse portfolio valuation is healthy</i>";
            
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // ----------------------------------------------------
        // 7. /added
        // ----------------------------------------------------
        case '/added':
            $prodRes = mysqli_query($conn, "SELECT product_code, product_name, price, quantity FROM product ORDER BY id DESC LIMIT 5");
            $catRes  = mysqli_query($conn, "SELECT category_code, category_name FROM category ORDER BY id DESC LIMIT 5");

            $msg = "🆕 <b>RECENTLY ADDED CATALOG ITEMS</b>\n"
                 . "<i>Latest Additions to Database</i>\n"
                 . "═════════════════════════════\n\n"
                 . "<b>📦 Newly Added Products:</b>\n";
            if ($prodRes && mysqli_num_rows($prodRes) > 0) {
                while ($r = mysqli_fetch_assoc($prodRes)) {
                    $msg .= "├ <code>{$r['product_code']}</code> <b>{$r['product_name']}</b> (\${$r['price']} | Qty: {$r['quantity']})\n";
                }
            } else {
                $msg .= "└ <i>No products added yet.</i>\n";
            }

            $msg .= "\n<b>🏷️ Newly Added Categories:</b>\n";
            if ($catRes && mysqli_num_rows($catRes) > 0) {
                while ($r = mysqli_fetch_assoc($catRes)) {
                    $msg .= "├ <code>{$r['category_code']}</code> <b>{$r['category_name']}</b>\n";
                }
            } else {
                $msg .= "└ <i>No categories added yet.</i>\n";
            }

            $msg .= "\n─────────────────────────────\n"
                  . "🕒 <i>Sorted by newest entry date</i>";

            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // ----------------------------------------------------
        // 8. /updated
        // ----------------------------------------------------
        case '/updated':
            $prodRes = mysqli_query($conn, "SELECT product_code, product_name, lastupdate FROM product ORDER BY lastupdate DESC LIMIT 5");
            $catRes  = mysqli_query($conn, "SELECT category_code, category_name, lastupdate FROM category ORDER BY lastupdate DESC LIMIT 5");

            $msg = "✏️ <b>RECENTLY MODIFIED INVENTORY</b>\n"
                 . "<i>Audit Trail & Recent Edits</i>\n"
                 . "═════════════════════════════\n\n"
                 . "<b>📦 Updated Products:</b>\n";
            if ($prodRes && mysqli_num_rows($prodRes) > 0) {
                while ($r = mysqli_fetch_assoc($prodRes)) {
                    $time = date("d/m/Y H:i", strtotime($r['lastupdate']));
                    $msg .= "├ <code>{$r['product_code']}</code> <b>{$r['product_name']}</b> <i>({$time})</i>\n";
                }
            } else {
                $msg .= "└ <i>No products modified.</i>\n";
            }

            $msg .= "\n<b>🏷️ Updated Categories:</b>\n";
            if ($catRes && mysqli_num_rows($catRes) > 0) {
                while ($r = mysqli_fetch_assoc($catRes)) {
                    $time = date("d/m/Y H:i", strtotime($r['lastupdate']));
                    $msg .= "├ <code>{$r['category_code']}</code> <b>{$r['category_name']}</b> <i>({$time})</i>\n";
                }
            } else {
                $msg .= "└ <i>No categories modified.</i>\n";
            }

            $msg .= "\n─────────────────────────────\n"
                  . "🕒 <i>Shows recent audit timestamps</i>";

            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // ----------------------------------------------------
        // 9. /help or /start
        // ----------------------------------------------------
        case '/start':
        case '/help':
        default:
            $msg = "🤖 <b>INVENTORY BOT COMMAND CENTER</b>\n"
                 . "<i>Quick Reference & Action Menu</i>\n"
                 . "═════════════════════════════\n\n"
                 . "🔍 <code>/search &lt;keyword&gt;</code> — Search catalog by name or code\n"
                 . "🏷️ <code>/categories</code> — View item categories & stock counts\n"
                 . "↕️ <code>/sort [price|stock|date]</code> — View sorted product list\n"
                 . "⚠️ <code>/lowstock</code> — View critical items (qty ≤ 5)\n"
                 . "📊 <code>/summary</code> — Executive inventory summary dashboard\n"
                 . "💎 <code>/valuation</code> — Financial report & asset valuation\n"
                 . "🆕 <code>/added</code> — List recently added products & categories\n"
                 . "✏️ <code>/updated</code> — List recently modified inventory rows\n"
                 . "❓ <code>/help</code> — Show this interactive command guide\n\n"
                 . "─────────────────────────────\n"
                 . "<i>Tap any command above to execute instantly!</i>";
            
            sendTelegramMessage($chatId, $msg, $botToken);
            break;
    }
}

registerBotCommands($botToken);

$offsetFile = __DIR__ . '/telegram_offset.txt';

// Main infinite daemon loop
while (true) {
    $offset = file_exists($offsetFile) ? (int)file_get_contents($offsetFile) : 0;
    $getUpdatesUrl = "https://api.telegram.org/bot{$botToken}/getUpdates?offset={$offset}&timeout=10";

    $ch = curl_init($getUpdatesUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response) {
        $data = json_decode($response, true);
        if ($data && isset($data['result']) && is_array($data['result']) && !empty($data['result'])) {
            foreach ($data['result'] as $update) {
                $updateId = $update['update_id'];
                $offset = $updateId + 1;

                if (isset($update['message'])) {
                    $msgObj  = $update['message'];
                    $chatId  = $msgObj['chat']['id'] ?? '';
                    $text    = trim($msgObj['text'] ?? '');

                    if (!empty($chatId) && !empty($text)) {
                        processTelegramCommand($conn, $chatId, $text, $botToken);
                    }
                }
            }
            file_put_contents($offsetFile, $offset);
        }
    }

    sleep(1);
}
