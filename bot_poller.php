<?php
/**
 * Telegram Bot Command Poller & Processor
 * Premium Styled Inventory Management System
 * Updated with 8 new commands
 */

require_once __DIR__ . '/database.php';

header("Content-Type: application/json; charset=utf-8");

$botToken = "8587070306:AAHHGV2Z6ZzmOiDi6dxL8GnXqQPqDNBuDd8";

function registerBotCommands($botToken) {
    $url = "https://api.telegram.org/bot{$botToken}/setMyCommands";
    $commands = [
        ['command' => 'search',     'description' => '🔍 Search product by name or code (/search <keyword>)'],
        ['command' => 'searchall',  'description' => '🔍 Search all records (/searchall <keyword>)'],
        ['command' => 'categories', 'description' => '🏷️ View all item categories'],
        ['command' => 'category',   'description' => '🏷️ View category detail (/category <code>)'],
        ['command' => 'sort',       'description' => '↕️ View items sorted (/sort price | stock | date)'],
        ['command' => 'lowstock',   'description' => '⚠️ List critical items with stock <= 5'],
        ['command' => 'topstock',   'description' => '📊 Top 10 highest stock items'],
        ['command' => 'product',    'description' => '📦 View product detail (/product <code>)'],
        ['command' => 'summary',    'description' => '📊 Real-time total categories, products, & valuation'],
        ['command' => 'valuation',  'description' => '💎 Financial report and average pricing breakdown'],
        ['command' => 'added',      'description' => '🆕 List recently added products and categories'],
        ['command' => 'updated',    'description' => '✏️ List recently modified products and categories'],
        ['command' => 'history',    'description' => '📜 Combined activity log'],
        ['command' => 'push',       'description' => '📤 Push item to Telegram (/push <code>)'],
        ['command' => 'outofstock', 'description' => '🚫 View out of stock items (0 units)'],
        ['command' => 'today',      'description' => '📅 Today\'s activity summary'],
        ['command' => 'chatid',     'description' => '💬 Get your chat ID'],
        ['command' => 'toggle',     'description' => '🔔 Toggle auto-notify (/toggle auto)'],
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
        // 2. /searchall <keyword>
        // ----------------------------------------------------
        case '/searchall':
            if (empty($arg)) {
                $msg = "⚠️ <b>INVALID SEARCH FORMAT</b>\n"
                     . "═════════════════════════════\n"
                     . "Usage: <code>/searchall &lt;keyword&gt;</code>\n"
                     . "Example: <code>/searchall Camera</code>";
                sendTelegramMessage($chatId, $msg, $botToken);
                break;
            }
            $stmt = mysqli_prepare($conn, "SELECT p.product_code, p.product_name, p.price, p.quantity, c.category_name FROM product p LEFT JOIN category c ON p.category_id = c.id WHERE p.product_name LIKE ? OR p.product_code LIKE ? OR c.category_name LIKE ? ORDER BY p.product_name ASC");
            $searchArg = "%" . $arg . "%";
            mysqli_stmt_bind_param($stmt, "sss", $searchArg, $searchArg, $searchArg);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                $total = mysqli_num_rows($result);
                $msg = "🔍 <b>COMPLETE SEARCH RESULTS</b>\n"
                     . "<i>Found <b>{$total}</b> results for '<b>" . htmlspecialchars($arg) . "</b>'</i>\n"
                     . "═════════════════════════════\n\n";
                $count = 0;
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
                    $count++;
                    if ($count >= 10) {
                        $msg .= "─────────────────────────────\n"
                              . "💡 <i>Showing 10 of {$total} results. Refine your query for more specific results.</i>";
                        break;
                    }
                }
                if ($count < 10) {
                    $msg .= "─────────────────────────────\n"
                          . "💡 <i>Showing all {$total} results</i>";
                }
            } else {
                $msg = "❌ <b>NO MATCHES FOUND</b>\n"
                     . "═════════════════════════════\n"
                     . "No products matching '<b>" . htmlspecialchars($arg) . "</b>' were found in the database.";
            }
            mysqli_stmt_close($stmt);
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // ----------------------------------------------------
        // 3. /categories
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
        // 4. /category <code>
        // ----------------------------------------------------
        case '/category':
            if (empty($arg)) {
                $msg = "⚠️ <b>INVALID CATEGORY FORMAT</b>\n"
                     . "═════════════════════════════\n"
                     . "Usage: <code>/category &lt;category_code&gt;</code>\n"
                     . "Example: <code>/category CAT-10</code>";
                sendTelegramMessage($chatId, $msg, $botToken);
                break;
            }
            $stmt = mysqli_prepare($conn, "SELECT id, category_code, category_name, created_at FROM category WHERE category_code = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $arg);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $catRow = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$catRow) {
                $msg = "❌ <b>CATEGORY NOT FOUND</b>\n"
                     . "═════════════════════════════\n"
                     . "Category with code '<b>" . htmlspecialchars($arg) . "</b>' was not found.";
                sendTelegramMessage($chatId, $msg, $botToken);
                break;
            }

            $catId = (int)$catRow['id'];
            $catCode = htmlspecialchars($catRow['category_code']);
            $catName = htmlspecialchars($catRow['category_name']);
            $catCreated = date('d/m/Y H:i', strtotime($catRow['created_at']));

            $prodStmt = mysqli_prepare($conn, "SELECT product_code, product_name, price, quantity FROM product WHERE category_id = ? ORDER BY product_name ASC");
            mysqli_stmt_bind_param($prodStmt, "i", $catId);
            mysqli_stmt_execute($prodStmt);
            $prodResult = mysqli_stmt_get_result($prodStmt);

            $totalProds = mysqli_num_rows($prodResult);
            $totalStock = 0;
            $totalVal = 0.00;

            $msg = "🏷️ <b>CATEGORY DETAIL</b>\n"
                 . "═════════════════════════════\n"
                 . "🆔 Code: <code>{$catCode}</code>\n"
                 . "📛 Name: <b>{$catName}</b>\n"
                 . "📦 Products: <b>{$totalProds} items</b>\n"
                 . "📅 Created: <code>{$catCreated}</code>\n\n";

            if ($prodResult && $totalProds > 0) {
                $msg .= "── PRODUCTS IN THIS CATEGORY ──\n\n";
                $i = 1;
                while ($p = mysqli_fetch_assoc($prodResult)) {
                    $pCode = htmlspecialchars($p['product_code']);
                    $pName = htmlspecialchars($p['product_name']);
                    $pPrice = number_format((float)$p['price'], 2);
                    $pQty = (int)$p['quantity'];
                    $totalStock += $pQty;
                    $totalVal += (float)$p['price'] * $pQty;
                    $msg .= "<b>{$i}. {$pName}</b>\n"
                          . "   └ <code>{$pCode}</code> • 💰 <b>\${$pPrice}</b> • 🔢 Stock: <b>{$pQty}</b>\n\n";
                    $i++;
                }
                $msg .= "─────────────────────────────\n"
                      . "🔢 Total Stock: <b>{$totalStock} units</b>\n"
                      . "💰 Total Valuation: <b>\$" . number_format($totalVal, 2) . "</b>";
            } else {
                $msg .= "└ <i>No products in this category</i>";
            }
            mysqli_stmt_close($prodStmt);
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // ----------------------------------------------------
        // 5. /sort [price | stock | date]
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
        // 6. /lowstock
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
        // 7. /topstock
        // ----------------------------------------------------
        case '/topstock':
            $res = mysqli_query($conn, "SELECT p.product_code, p.product_name, p.quantity, p.price, c.category_name FROM product p LEFT JOIN category c ON p.category_id = c.id ORDER BY p.quantity DESC LIMIT 10");
            if ($res && mysqli_num_rows($res) > 0) {
                $msg = "📊 <b>TOP 10 HIGHEST STOCK ITEMS</b>\n"
                     . "═════════════════════════════\n\n";
                $i = 1;
                $totalUnits = 0;
                $totalValue = 0.00;
                while ($r = mysqli_fetch_assoc($res)) {
                    $code  = htmlspecialchars($r['product_code']);
                    $name  = htmlspecialchars($r['product_name']);
                    $qty   = (int)$r['quantity'];
                    $price = number_format((float)$r['price'], 2);
                    $cat   = htmlspecialchars($r['category_name'] ?? 'Unassigned');
                    $totalUnits += $qty;
                    $totalValue += (float)$r['price'] * $qty;
                    $msg .= "<b>{$i}. 📦 {$name}</b>\n"
                          . "   └ <code>{$code}</code> | 🔢 Stock: <b>{$qty} units</b> | 💰 <b>\${$price}</b>\n"
                          . "   └ 🏷️ Category: <code>{$cat}</code>\n\n";
                    $i++;
                }
                $msg .= "─────────────────────────────\n"
                      . "📊 Total units in top 10: <b>{$totalUnits}</b>\n"
                      . "💰 Combined value: <b>\$" . number_format($totalValue, 2) . "</b>";
            } else {
                $msg = "📦 <b>NO PRODUCTS FOUND</b>\n"
                     . "═════════════════════════════\n"
                     . "Product catalog is empty.";
            }
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // ----------------------------------------------------
        // 8. /product <code>
        // ----------------------------------------------------
        case '/product':
            if (empty($arg)) {
                $msg = "⚠️ <b>INVALID PRODUCT FORMAT</b>\n"
                     . "═════════════════════════════\n"
                     . "Usage: <code>/product &lt;product_code&gt;</code>\n"
                     . "Example: <code>/product PRD-101</code>";
                sendTelegramMessage($chatId, $msg, $botToken);
                break;
            }
            $stmt = mysqli_prepare($conn, "SELECT p.*, c.category_name FROM product p LEFT JOIN category c ON p.category_id = c.id WHERE p.product_code = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $arg);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $prod = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);

            if (!$prod) {
                $msg = "❌ <b>PRODUCT NOT FOUND</b>\n"
                     . "═════════════════════════════\n"
                     . "Product with code '<b>" . htmlspecialchars($arg) . "</b>' was not found.";
                sendTelegramMessage($chatId, $msg, $botToken);
                break;
            }

            $pCode = htmlspecialchars($prod['product_code']);
            $pName = htmlspecialchars($prod['product_name']);
            $pCat = htmlspecialchars($prod['category_name'] ?? 'Unassigned');
            $pPrice = number_format((float)$prod['price'], 2);
            $pQty = (int)$prod['quantity'];
            $pTotal = number_format((float)$prod['price'] * $pQty, 2);
            $pCreated = date('d/m/Y H:i', strtotime($prod['created_at']));
            $pUpdated = date('d/m/Y H:i', strtotime($prod['lastupdate']));

            if ($pQty == 0) {
                $pStatus = "❌ Out of Stock";
            } elseif ($pQty <= 5) {
                $pStatus = "⚠️ Low Stock";
            } else {
                $pStatus = "✅ In Stock";
            }

            $msg = "📦 <b>PRODUCT DETAIL</b>\n"
                 . "═════════════════════════════\n"
                 . "🆔 Code: <code>{$pCode}</code>\n"
                 . "📛 Name: <b>{$pName}</b>\n"
                 . "🏷️ Category: <code>{$pCat}</code>\n"
                 . "💰 Price: <b>\${$pPrice}</b>\n"
                 . "🔢 Stock: <b>{$pQty} units</b>\n"
                 . "📊 Total Value: <b>\${$pTotal}</b>\n"
                 . "📅 Created: <code>{$pCreated}</code>\n"
                 . "✏️ Last Updated: <code>{$pUpdated}</code>\n"
                 . "─────────────────────────────\n"
                 . "Status: <b>{$pStatus}</b>";
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // ----------------------------------------------------
        // 9. /summary
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
        // 10. /valuation
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
        // 11. /added
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
        // 12. /updated
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
        // 13. /history
        // ----------------------------------------------------
        case '/history':
            $prodNewRes = mysqli_query($conn, "SELECT product_code, product_name, price, quantity FROM product ORDER BY id DESC LIMIT 10");
            $prodUpdRes = mysqli_query($conn, "SELECT product_code, product_name, lastupdate FROM product ORDER BY lastupdate DESC LIMIT 10");
            $catNewRes  = mysqli_query($conn, "SELECT category_code, category_name FROM category ORDER BY id DESC LIMIT 5");

            $msg = "📜 <b>INVENTORY ACTIVITY LOG</b>\n"
                 . "═════════════════════════════\n\n"
                 . "<b>🆕 RECENTLY ADDED (10)</b>\n";
            if ($prodNewRes && mysqli_num_rows($prodNewRes) > 0) {
                while ($r = mysqli_fetch_assoc($prodNewRes)) {
                    $msg .= "├ <code>{$r['product_code']}</code> <b>{$r['product_name']}</b> (\${$r['price']} | Qty: {$r['quantity']})\n";
                }
            } else {
                $msg .= "└ <i>No products added yet.</i>\n";
            }

            $msg .= "\n<b>✏️ RECENTLY UPDATED (10)</b>\n";
            if ($prodUpdRes && mysqli_num_rows($prodUpdRes) > 0) {
                while ($r = mysqli_fetch_assoc($prodUpdRes)) {
                    $time = date("d/m/Y H:i", strtotime($r['lastupdate']));
                    $msg .= "├ <code>{$r['product_code']}</code> <b>{$r['product_name']}</b> <i>({$time})</i>\n";
                }
            } else {
                $msg .= "└ <i>No products modified.</i>\n";
            }

            $msg .= "\n<b>🏷️ NEW CATEGORIES (5)</b>\n";
            if ($catNewRes && mysqli_num_rows($catNewRes) > 0) {
                while ($r = mysqli_fetch_assoc($catNewRes)) {
                    $msg .= "├ <code>{$r['category_code']}</code> <b>{$r['category_name']}</b>\n";
                }
            } else {
                $msg .= "└ <i>No categories added yet.</i>\n";
            }

            $msg .= "\n─────────────────────────────\n"
                  . "🕒 <i>Combined audit trail log</i>";

            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // ----------------------------------------------------
        // 14. /push <code>
        // ----------------------------------------------------
        case '/push':
            if (empty($arg)) {
                $msg = "⚠️ <b>INVALID PUSH FORMAT</b>\n"
                     . "═════════════════════════════\n"
                     . "Usage: <code>/push &lt;product_code&gt;</code> or <code>/push &lt;category_code&gt;</code>\n"
                     . "Example: <code>/push PRD-101</code> or <code>/push CAT-10</code>";
                sendTelegramMessage($chatId, $msg, $botToken);
                break;
            }

            // Search product first
            $prodStmt = mysqli_prepare($conn, "SELECT p.*, c.category_name FROM product p LEFT JOIN category c ON p.category_id = c.id WHERE p.product_code = ? LIMIT 1");
            mysqli_stmt_bind_param($prodStmt, "s", $arg);
            mysqli_stmt_execute($prodStmt);
            $prodResult = mysqli_stmt_get_result($prodStmt);
            $prod = mysqli_fetch_assoc($prodResult);
            mysqli_stmt_close($prodStmt);

            if ($prod) {
                $pCode = htmlspecialchars($prod['product_code']);
                $pName = htmlspecialchars($prod['product_name']);
                $pCat = htmlspecialchars($prod['category_name'] ?? 'Unassigned');
                $pPrice = number_format((float)$prod['price'], 2);
                $pQty = (int)$prod['quantity'];
                $pTotal = number_format((float)$prod['price'] * $pQty, 2);
                if ($pQty == 0) {
                    $pStatus = "❌ Out of Stock";
                } elseif ($pQty <= 5) {
                    $pStatus = "⚠️ Low Stock";
                } else {
                    $pStatus = "✅ In Stock";
                }

                $msg = "📦 <b>PRODUCT PUSH NOTIFICATION</b>\n"
                     . "═════════════════════════════\n"
                     . "🆔 Code: <code>{$pCode}</code>\n"
                     . "📛 Name: <b>{$pName}</b>\n"
                     . "🏷️ Category: <code>{$pCat}</code>\n"
                     . "💰 Price: <b>\${$pPrice}</b>\n"
                     . "🔢 Stock: <b>{$pQty} units</b>\n"
                     . "📊 Total Value: <b>\${$pTotal}</b>\n"
                     . "─────────────────────────────\n"
                     . "Status: <b>{$pStatus}</b>";
                sendTelegramMessage($chatId, $msg, $botToken);
                break;
            }

            // Search category
            $catStmt = mysqli_prepare($conn, "SELECT id, category_code, category_name FROM category WHERE category_code = ? LIMIT 1");
            mysqli_stmt_bind_param($catStmt, "s", $arg);
            mysqli_stmt_execute($catStmt);
            $catResult = mysqli_stmt_get_result($catStmt);
            $cat = mysqli_fetch_assoc($catResult);
            mysqli_stmt_close($catStmt);

            if ($cat) {
                $catId = (int)$cat['id'];
                $catCode = htmlspecialchars($cat['category_code']);
                $catName = htmlspecialchars($cat['category_name']);

                $prodCountStmt = mysqli_prepare($conn, "SELECT COUNT(*) AS cnt, COALESCE(SUM(quantity), 0) AS total_qty, COALESCE(SUM(price * quantity), 0) AS total_val FROM product WHERE category_id = ?");
                mysqli_stmt_bind_param($prodCountStmt, "i", $catId);
                mysqli_stmt_execute($prodCountStmt);
                $prodCountResult = mysqli_stmt_get_result($prodCountStmt);
                $prodCount = mysqli_fetch_assoc($prodCountResult);
                mysqli_stmt_close($prodCountStmt);

                $cnt = (int)$prodCount['cnt'];
                $totalQty = (int)$prodCount['total_qty'];
                $totalVal = number_format((float)$prodCount['total_val'], 2);

                $msg = "🏷️ <b>CATEGORY PUSH NOTIFICATION</b>\n"
                     . "═════════════════════════════\n"
                     . "🆔 Code: <code>{$catCode}</code>\n"
                     . "📛 Name: <b>{$catName}</b>\n"
                     . "📦 Products: <b>{$cnt} items</b>\n"
                     . "🔢 Total Stock: <b>{$totalQty} units</b>\n"
                     . "💰 Total Valuation: <b>\${$totalVal}</b>";
                sendTelegramMessage($chatId, $msg, $botToken);
                break;
            }

            $msg = "❌ <b>ITEM NOT FOUND</b>\n"
                 . "═════════════════════════════\n"
                 . "No product or category with code '<b>" . htmlspecialchars($arg) . "</b>' was found.";
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // ----------------------------------------------------
        // 15. /outofstock
        // ----------------------------------------------------
        case '/outofstock':
            $res = mysqli_query($conn, "SELECT p.product_code, p.product_name, p.quantity, c.category_name FROM product p LEFT JOIN category c ON p.category_id = c.id WHERE p.quantity = 0 ORDER BY p.product_name ASC");
            if ($res && mysqli_num_rows($res) > 0) {
                $msg = "🚫 <b>OUT OF STOCK ALERTS</b>\n"
                     . "<i>Items with <b>0 units</b> remaining</i>\n"
                     . "═════════════════════════════\n\n";
                while ($r = mysqli_fetch_assoc($res)) {
                    $code = htmlspecialchars($r['product_code']);
                    $name = htmlspecialchars($r['product_name']);
                    $cat  = htmlspecialchars($r['category_name'] ?? 'Unassigned');
                    $msg .= "❌ <b>{$name}</b>\n"
                          . "├ 🆔 Code: <code>{$code}</code>\n"
                          . "├ 🏷️ Category: <code>{$cat}</code>\n"
                          . "└ 🔢 Stock: <b>0 units</b>\n\n";
                }
                $msg .= "─────────────────────────────\n"
                      . "⚠️ <i>These items need immediate restocking!</i>";
            } else {
                $msg = "✅ <b>ALL ITEMS IN STOCK</b>\n"
                     . "═════════════════════════════\n"
                     . "No items are currently out of stock.";
            }
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // ----------------------------------------------------
        // 16. /today
        // ----------------------------------------------------
        case '/today':
            $today = date('Y-m-d');
            
            $prodNew = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM product WHERE DATE(created_at) = '{$today}'");
            $prodNewCount = ($prodNew && $r = mysqli_fetch_assoc($prodNew)) ? (int)$r['cnt'] : 0;
            
            $prodUpd = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM product WHERE DATE(lastupdate) = '{$today}' AND DATE(created_at) != '{$today}'");
            $prodUpdCount = ($prodUpd && $r = mysqli_fetch_assoc($prodUpd)) ? (int)$r['cnt'] : 0;
            
            $catNew = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM category WHERE DATE(created_at) = '{$today}'");
            $catNewCount = ($catNew && $r = mysqli_fetch_assoc($catNew)) ? (int)$r['cnt'] : 0;
            
            $catUpd = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM category WHERE DATE(lastupdate) = '{$today}' AND DATE(created_at) != '{$today}'");
            $catUpdCount = ($catUpd && $r = mysqli_fetch_assoc($catUpd)) ? (int)$r['cnt'] : 0;

            $msg = "📅 <b>TODAY'S ACTIVITY</b>\n"
                 . "<i>" . date('d/m/Y') . "</i>\n"
                 . "═════════════════════════════\n\n"
                 . "📦 <b>Products:</b>\n"
                 . "├ 🆕 Added: <b>{$prodNewCount}</b>\n"
                 . "└ ✏️ Updated: <b>{$prodUpdCount}</b>\n\n"
                 . "🏷️ <b>Categories:</b>\n"
                 . "├ 🆕 Added: <b>{$catNewCount}</b>\n"
                 . "└ ✏️ Updated: <b>{$catUpdCount}</b>\n\n"
                 . "─────────────────────────────\n"
                 . "📊 Total changes: <b>" . ($prodNewCount + $prodUpdCount + $catNewCount + $catUpdCount) . "</b>";
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // ----------------------------------------------------
        // 17. /chatid
        // ----------------------------------------------------
        case '/chatid':
            $msg = "💬 <b>CHAT INFORMATION</b>\n"
                 . "═════════════════════════════\n"
                 . "🆔 Your Chat ID: <code>{$chatId}</code>\n\n"
                 . "─────────────────────────────\n"
                 . "💡 <i>Use this ID for debugging or bot configuration.</i>";
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // ----------------------------------------------------
        // 18. /toggle auto
        // ----------------------------------------------------
        case '/toggle':
            if ($arg !== 'auto') {
                $msg = "⚠️ <b>INVALID TOGGLE FORMAT</b>\n"
                     . "═════════════════════════════\n"
                     . "Usage: <code>/toggle auto</code>\n\n"
                     . "🔔 <b>Available toggles:</b>\n"
                     . "└ <code>auto</code> — Toggle auto-notifications";
                sendTelegramMessage($chatId, $msg, $botToken);
                break;
            }

            // Ensure settings table exists
            mysqli_query($conn, "CREATE TABLE IF NOT EXISTS `system_settings` (`setting_key` varchar(50) NOT NULL, `setting_value` varchar(255) NOT NULL, PRIMARY KEY (`setting_key`)) DEFAULT CHARSET=utf8mb4");

            // Get current value
            $res = mysqli_query($conn, "SELECT setting_value FROM system_settings WHERE setting_key = 'auto_telegram_notify'");
            $currentVal = '1'; // default enabled
            if ($res && $row = mysqli_fetch_assoc($res)) {
                $currentVal = trim($row['setting_value']);
            }

            // Toggle
            $newVal = ($currentVal === '1') ? '0' : '1';
            mysqli_query($conn, "REPLACE INTO system_settings (setting_key, setting_value) VALUES ('auto_telegram_notify', '{$newVal}')");

            if ($newVal === '1') {
                $msg = "🔔 <b>AUTO NOTIFICATIONS ENABLED</b>\n"
                     . "═════════════════════════════\n"
                     . "Telegram auto-push is now <b>ON</b>.\n"
                     . "New products/categories will be pushed automatically.\n\n"
                     . "💡 Use <code>/push &lt;code&gt;</code> to manually push items anytime.";
            } else {
                $msg = "🔕 <b>AUTO NOTIFICATIONS DISABLED</b>\n"
                     . "═════════════════════════════\n"
                     . "Telegram auto-push is now <b>OFF</b>.\n"
                     . "Notifications will not be sent automatically.\n\n"
                     . "💡 Use <code>/push &lt;code&gt;</code> to manually push items.";
            }
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // ----------------------------------------------------
        // 17. /help or /start
        // ----------------------------------------------------
        case '/start':
        case '/help':
        default:
            $msg = "🤖 <b>INVENTORY BOT COMMAND CENTER</b>\n"
                 . "<i>Quick Reference & Action Menu</i>\n"
                 . "═════════════════════════════\n\n"
                 . "<b>📦 PRODUCT COMMANDS</b>\n"
                 . "🔍 <code>/search &lt;keyword&gt;</code> — Search products (top 5)\n"
                 . "🔍 <code>/searchall &lt;keyword&gt;</code> — Search all matching records\n"
                 . "📦 <code>/product &lt;code&gt;</code> — View full product details\n"
                 . "↕️ <code>/sort [price|stock|date]</code> — Sort product list\n"
                 . "⚠️ <code>/lowstock</code> — View critical items (qty ≤ 5)\n"
                 . "📊 <code>/topstock</code> — Top 10 highest stock items\n\n"
                 . "<b>🏷️ CATEGORY COMMANDS</b>\n"
                 . "🏷️ <code>/categories</code> — View all categories\n"
                 . "🏷️ <code>/category &lt;code&gt;</code> — View category + products\n\n"
                 . "<b>📊 REPORTS & EXPORTS</b>\n"
                 . "📊 <code>/summary</code> — Executive dashboard\n"
                 . "💎 <code>/valuation</code> — Financial valuation report\n"
                 . "📜 <code>/history</code> — Combined activity log\n"
                 . "🚫 <code>/outofstock</code> — Out of stock items (0 units)\n"
                 . "📅 <code>/today</code> — Today's activity summary\n"
                 . "💬 <code>/chatid</code> — Get your chat ID\n\n"
                 . "<b>🔔 NOTIFICATIONS</b>\n"
                 . "📤 <code>/push &lt;code&gt;</code> — Push item to Telegram\n"
                 . "🔔 <code>/toggle auto</code> — Toggle auto-notifications\n\n"
                 . "<b>📜 ACTIVITY LOGS</b>\n"
                 . "🆕 <code>/added</code> — Recently added items\n"
                 . "✏️ <code>/updated</code> — Recently modified items\n\n"
                 . "❓ <code>/help</code> — Show this command guide\n\n"
                 . "─────────────────────────────\n"
                 . "<i>Tap any command above to execute instantly!</i>";
            
            sendTelegramMessage($chatId, $msg, $botToken);
            break;
    }
}

registerBotCommands($botToken);

$offsetFile = __DIR__ . '/telegram_offset.txt';
$offset = file_exists($offsetFile) ? (int)file_get_contents($offsetFile) : 0;

$getUpdatesUrl = "https://api.telegram.org/bot{$botToken}/getUpdates?offset={$offset}&timeout=2";

$ch = curl_init($getUpdatesUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$processed = 0;

if ($data && isset($data['result']) && is_array($data['result'])) {
    foreach ($data['result'] as $update) {
        $updateId = $update['update_id'];
        $offset = $updateId + 1;

        if (isset($update['message'])) {
            $msgObj  = $update['message'];
            $chatId  = $msgObj['chat']['id'] ?? '';
            $text    = trim($msgObj['text'] ?? '');

            if (!empty($chatId) && !empty($text)) {
                processTelegramCommand($conn, $chatId, $text, $botToken);
                $processed++;
            }
        }
    }
    file_put_contents($offsetFile, $offset);
}

echo json_encode([
    'status' => 'success',
    'processed_count' => $processed,
    'next_offset' => $offset,
    'timestamp' => date('Y-m-d H:i:s')
]);
