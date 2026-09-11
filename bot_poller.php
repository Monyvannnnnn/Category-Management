<?php
/**
 * Telegram Bot Command Poller & Processor
 * Multi-Tenant Support: User A -> Bot A -> Chat A
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/notify_bot.php';

header("Content-Type: application/json; charset=utf-8");

$defaultBotToken = "8736337451:AAEtwDgtwUpWGnV4cIrMNKwNjHaAV8J18jc";

function registerBotCommands($botToken) {
    $url = "https://api.telegram.org/bot{$botToken}/setMyCommands";
    $commands = [
        ['command' => 'search',     'description' => '🔍 Search product (/search <keyword>)'],
        ['command' => 'searchall',  'description' => '🔍 Search all records (/searchall <keyword>)'],
        ['command' => 'categories', 'description' => '🏷 List all categories'],
        ['command' => 'category',   'description' => '🏷 Category info (/category <code|name>)'],
        ['command' => 'sort',       'description' => '↕️ Sort items (/sort price|stock|date)'],
        ['command' => 'lowstock',   'description' => '⚠️ Low stock items (<= 5)'],
        ['command' => 'topstock',   'description' => '📊 Top 10 highest stock'],
        ['command' => 'product',    'description' => '📦 Product info (/product <code|name>)'],
        ['command' => 'outofstock', 'description' => '🚫 Out of stock items'],
        ['command' => 'summary',    'description' => '📊 Live inventory summary'],
        ['command' => 'valuation',  'description' => '💎 Financial report'],
        ['command' => 'added',      'description' => '🆕 Recently added items'],
        ['command' => 'updated',    'description' => '✏️ Recently modified items'],
        ['command' => 'history',    'description' => '📜 Activity log'],
        ['command' => 'today',      'description' => '📅 Today\'s activity'],
        ['command' => 'push',       'description' => '📤 Send to Telegram (/push <message>)'],
        ['command' => 'toggle',     'description' => '🔔 Toggle auto-notify'],
        ['command' => 'help',       'description' => '❓ View all commands']
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['commands' => $commands]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
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
    $res = curl_exec($ch);
    curl_close($ch);
    return $res;
}

/**
 * Handle user bot connection binding via /start <code>
 */
function handleCodeBinding($conn, $chatId, $code) {
    $code = trim($code);
    $userBot = null;
    $isFreshBind = false;

    // 1. Try exact connection code match if code provided
    if (!empty($code)) {
        $stmt = db_prepare($conn, "SELECT * FROM user_telegram_bots WHERE UPPER(TRIM(connection_code)) = UPPER(TRIM(?)) LIMIT 1");
        if ($stmt) {
            db_stmt_bind_param($stmt, "s", $code);
            db_stmt_execute($stmt);
            $res = db_stmt_get_result($stmt);
            $userBot = db_fetch_assoc($res);
            db_stmt_close($stmt);
            if ($userBot) $isFreshBind = true;
        }
    }

    // 2. Fallback: Check for any pending unlinked connection code
    if (!$userBot) {
        $stmt = db_prepare($conn, "SELECT * FROM user_telegram_bots WHERE connection_code IS NOT NULL AND connection_code != '' ORDER BY id DESC LIMIT 1");
        if ($stmt) {
            db_stmt_execute($stmt);
            $res = db_stmt_get_result($stmt);
            $userBot = db_fetch_assoc($res);
            db_stmt_close($stmt);
            if ($userBot) $isFreshBind = true;
        }
    }

    // 3. Fallback: Check for any unlinked user bot record (chat_id IS NULL OR chat_id = '')
    if (!$userBot) {
        $stmt = db_prepare($conn, "SELECT * FROM user_telegram_bots WHERE chat_id IS NULL OR chat_id = '' ORDER BY id ASC LIMIT 1");
        if ($stmt) {
            db_stmt_execute($stmt);
            $res = db_stmt_get_result($stmt);
            $userBot = db_fetch_assoc($res);
            db_stmt_close($stmt);
            if ($userBot) $isFreshBind = true;
        }
    }

    // 4. Fallback: Get primary user in user_telegram_bots table
    if (!$userBot) {
        $stmt = db_prepare($conn, "SELECT * FROM user_telegram_bots ORDER BY id ASC LIMIT 1");
        if ($stmt) {
            db_stmt_execute($stmt);
            $res = db_stmt_get_result($stmt);
            $userBot = db_fetch_assoc($res);
            db_stmt_close($stmt);
        }
    }

    // 5. Ultimate Fallback: Create row for default User #1
    if (!$userBot) {
        $uCheck = db_query($conn, "SELECT id FROM users ORDER BY id ASC LIMIT 1");
        $defaultUserId = ($uCheck && $uRow = db_fetch_assoc($uCheck)) ? (int)$uRow['id'] : 1;
        $defaultToken = "8736337451:AAEtwDgtwUpWGnV4cIrMNKwNjHaAV8J18jc";
        $defaultUsername = "reportpush_bot";

        $ins = db_prepare($conn, "INSERT INTO user_telegram_bots (user_id, bot_token, bot_username, chat_id, connected_at) VALUES (?, ?, ?, ?, NOW())");
        if ($ins) {
            db_stmt_bind_param($ins, "isss", $defaultUserId, $defaultToken, $defaultUsername, $chatId);
            db_stmt_execute($ins);
            db_stmt_close($ins);
            return [
                'id' => 1,
                'user_id' => $defaultUserId,
                'chat_id' => $chatId,
                'is_fresh_bind' => true,
                'connected_at' => date('Y-m-d H:i:s')
            ];
        }
    }

    // Bind chat_id & clear connection_code for found userBot
    if ($userBot) {
        if (empty($userBot['chat_id']) || $userBot['chat_id'] !== $chatId || !empty($userBot['connection_code'])) {
            $isFreshBind = true;
            $upd = db_prepare($conn, "UPDATE user_telegram_bots SET chat_id = ?, connected_at = NOW(), connection_code = NULL, code_expires_at = NULL WHERE id = ?");
            if ($upd) {
                db_stmt_bind_param($upd, "si", $chatId, $userBot['id']);
                db_stmt_execute($upd);
                db_stmt_close($upd);
            }
            $userBot['connected_at'] = date('Y-m-d H:i:s');
        }
        $userBot['chat_id'] = $chatId;
        $userBot['is_fresh_bind'] = $isFreshBind;
        return $userBot;
    }

    return null;
}

/**
 * Helper to fetch connected bot row for incoming chatId
 */
function getConnectedUserByChatIdMySQLi($conn, $chatId) {
    if (empty($chatId)) return null;
    $stmt = db_prepare($conn, "SELECT * FROM user_telegram_bots WHERE chat_id = ? LIMIT 1");
    if ($stmt) {
        db_stmt_bind_param($stmt, "s", $chatId);
        db_stmt_execute($stmt);
        $res = db_stmt_get_result($stmt);
        $row = db_fetch_assoc($res);
        db_stmt_close($stmt);
        return $row;
    }
    return null;
}

/**
 * Process Command for specific user_id
 */
function processTelegramCommand($conn, $chatId, $text, $botToken, $userId = 1, $updateId = 0) {
    // Database-level Atomic Update Deduplication Lock
    if (!empty($updateId)) {
        @db_query($conn, "CREATE TABLE IF NOT EXISTS processed_telegram_updates (
          update_id varchar(100) NOT NULL,
          processed_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (update_id)
        );");

        // Clean up overflow / old entries
        @db_query($conn, "DELETE FROM processed_telegram_updates WHERE update_id = '2147483647' OR processed_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");

        $updateIdStr = (string)$updateId;
        $insStmt = db_prepare($conn, "INSERT IGNORE INTO processed_telegram_updates (update_id) VALUES (?)");
        if ($insStmt) {
            db_stmt_bind_param($insStmt, "s", $updateIdStr);
            db_stmt_execute($insStmt);
            $affected = ($conn instanceof PgSqlConnWrapper) ? ($insStmt ? 1 : 0) : mysqli_stmt_affected_rows($insStmt);
            db_stmt_close($insStmt);

            if ($affected === 0) {
                // Already processed by Webhook, Daemon, or another poller instance!
                return;
            }
        }
    }

    if (function_exists('registerSubscriberChatId')) {
        registerSubscriberChatId($conn, $chatId);
    }

    $parts   = explode(' ', $text, 2);
    $command = strtolower($parts[0]);
    $command = explode('@', $command)[0];
    $rawArg  = trim($parts[1] ?? '');
    $arg     = strtolower($rawArg);

    // 1. Connection Code Binding (/start or /start <code>)
    if ($command === '/start') {
        $boundBot = handleCodeBinding($conn, $chatId, $rawArg);
        if ($boundBot) {
            $uId = (int)$boundBot['user_id'];
            $msg = "✅ <b>TELEGRAM BOT CONNECTED SUCCESSFULLY!</b>\n"
                 . "═════════════════════════════\n"
                 . "Your Telegram Chat ID: <code>{$chatId}</code>\n"
                 . "Linked to Website Account User #{$uId}.\n\n"
                 . "Type /help to see all available commands!";
            sendTelegramMessage($chatId, $msg, $botToken);
            return;
        }
    }

    // 2. Strict Access Control Guard: Check if chat_id is connected in DB
    $userBot = getConnectedUserByChatIdMySQLi($conn, $chatId);
    if (!$userBot || empty($userBot['user_id'])) {
        if ($command === '/help') {
            $msg = "❌ <b>ACCESS DENIED: ACCOUNT NOT CONNECTED</b>\n"
                 . "═════════════════════════════\n"
                 . "📱 <b>Your Chat ID:</b> <code>{$chatId}</code>\n\n"
                 . "⚠️ This Telegram account is not linked to any Inventory account.\n\n"
                 . "🔑 <b>How to Connect:</b>\n"
                 . "1. Log into your Inventory Account on the website.\n"
                 . "2. Navigate to <b>Settings ➜ Telegram Bot Settings</b>.\n"
                 . "3. Click <b>Connect Bot</b> or copy your connection code.\n"
                 . "4. Click the link or send <code>/start &lt;YOUR_CODE&gt;</code> here!";
            sendTelegramMessage($chatId, $msg, $botToken);
        }
        return;
    }

    // Authenticated User ID from DB
    $userId = (int)$userBot['user_id'];

    // 3. Process commands for connected user
    switch ($command) {
        case '/start':
            $msg = "🚀 <b>WELCOME TO INVENTORY MANAGEMENT BOT</b>\n"
                 . "═════════════════════════════\n"
                 . "Status: <b>Connected ✅</b>\n"
                 . "Account User ID: <code>#{$userId}</code>\n"
                 . "Connected Chat ID: <code>{$chatId}</code>\n\n"
                 . "Type /help to see available inventory commands!";
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // 1. /search <keyword>
        case '/search':
            if (empty($arg)) {
                $msg = "🔍 <b>SEARCH PRODUCT</b>\n"
                     . "═════════════════════════════\n"
                     . "Usage: <code>/search &lt;keyword&gt;</code>\n"
                     . "Example: <code>/search laptop</code>";
                sendTelegramMessage($chatId, $msg, $botToken);
                break;
            }
            $stmt = db_prepare($conn, "SELECT p.product_code, p.product_name, p.price, p.quantity, c.category_name FROM product p LEFT JOIN category c ON p.category_id = c.id WHERE p.user_id = ? AND (p.product_name LIKE ? OR p.product_code LIKE ?) ORDER BY p.id DESC LIMIT 10");
            $searchArg = "%" . $arg . "%";
            db_stmt_bind_param($stmt, "iss", $userId, $searchArg, $searchArg);
            db_stmt_execute($stmt);
            $result = db_stmt_get_result($stmt);

            if ($result && db_num_rows($result) > 0) {
                $msg = "🔍 <b>PRODUCT SEARCH RESULTS</b>\n"
                     . "<i>Query: '<b>" . htmlspecialchars($rawArg) . "</b>'</i>\n"
                     . "═════════════════════════════\n\n";
                while ($r = db_fetch_assoc($result)) {
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
            } else {
                $msg = "❌ <b>NO MATCHES FOUND</b> for '<b>" . htmlspecialchars($rawArg) . "</b>'";
            }
            db_stmt_close($stmt);
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // 2. /searchall <keyword>
        case '/searchall':
            if (empty($arg)) {
                $msg = "🔍 <b>SEARCH ALL RECORDS</b>\n"
                     . "═════════════════════════════\n"
                     . "Usage: <code>/searchall &lt;keyword&gt;</code>\n"
                     . "Example: <code>/searchall electronic</code>";
                sendTelegramMessage($chatId, $msg, $botToken);
                break;
            }
            $searchArg = "%" . $arg . "%";
            $pStmt = db_prepare($conn, "SELECT p.product_code, p.product_name, p.price, p.quantity FROM product p WHERE p.user_id = ? AND (p.product_name LIKE ? OR p.product_code LIKE ?) LIMIT 5");
            db_stmt_bind_param($pStmt, "iss", $userId, $searchArg, $searchArg);
            db_stmt_execute($pStmt);
            $pRes = db_stmt_get_result($pStmt);

            $cStmt = db_prepare($conn, "SELECT category_code, category_name FROM category WHERE user_id = ? AND (category_name LIKE ? OR category_code LIKE ?) LIMIT 5");
            db_stmt_bind_param($cStmt, "iss", $userId, $searchArg, $searchArg);
            db_stmt_execute($cStmt);
            $cRes = db_stmt_get_result($cStmt);

            $msg = "🔍 <b>ALL MATCHING RECORDS</b>\n"
                 . "<i>Query: '<b>" . htmlspecialchars($rawArg) . "</b>'</i>\n"
                 . "═════════════════════════════\n\n";
            $hasContent = false;

            if ($pRes && db_num_rows($pRes) > 0) {
                $hasContent = true;
                $msg .= "📦 <b>Matching Products:</b>\n";
                while ($r = db_fetch_assoc($pRes)) {
                    $msg .= "• <b>" . htmlspecialchars($r['product_name']) . "</b> (<code>" . htmlspecialchars($r['product_code']) . "</code>) — Qty: " . (int)$r['quantity'] . "\n";
                }
                $msg .= "\n";
            }
            db_stmt_close($pStmt);

            if ($cRes && db_num_rows($cRes) > 0) {
                $hasContent = true;
                $msg .= "🏷️ <b>Matching Categories:</b>\n";
                while ($r = db_fetch_assoc($cRes)) {
                    $msg .= "• <b>" . htmlspecialchars($r['category_name']) . "</b> (<code>" . htmlspecialchars($r['category_code']) . "</code>)\n";
                }
            }
            db_stmt_close($cStmt);

            if (!$hasContent) {
                $msg = "❌ <b>NO RECORDS FOUND</b> for '<b>" . htmlspecialchars($rawArg) . "</b>'";
            }
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // 3. /categories
        case '/categories':
            $stmt = db_prepare($conn, "SELECT c.category_code, c.category_name, COUNT(p.id) AS prod_count, COALESCE(SUM(p.quantity), 0) AS total_qty FROM category c LEFT JOIN product p ON c.id = p.category_id WHERE c.user_id = ? GROUP BY c.id ORDER BY c.category_name ASC");
            db_stmt_bind_param($stmt, "i", $userId);
            db_stmt_execute($stmt);
            $res = db_stmt_get_result($stmt);

            if ($res && db_num_rows($res) > 0) {
                $totalCats = db_num_rows($res);
                $msg = "🏷️ <b>YOUR CATEGORY OVERVIEW</b>\n"
                     . "<i>Total Listed: <b>{$totalCats} Categories</b></i>\n"
                     . "═════════════════════════════\n\n";
                while ($r = db_fetch_assoc($res)) {
                    $code = htmlspecialchars($r['category_code']);
                    $name = htmlspecialchars($r['category_name']);
                    $cnt  = (int)$r['prod_count'];
                    $qty  = (int)$r['total_qty'];
                    $msg .= "📁 <b>{$name}</b>\n"
                          . "├ 🆔 Code: <code>{$code}</code>\n"
                          . "├ 📦 Products: <b>{$cnt} items</b>\n"
                          . "└ 🔢 Total Stock: <b>{$qty} units</b>\n\n";
                }
            } else {
                $msg = "📂 <b>NO CATEGORIES FOUND</b>";
            }
            db_stmt_close($stmt);
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // 4. /category <code|name>
        case '/category':
            if (empty($rawArg)) {
                // If no arg, list categories
                $stmt = db_prepare($conn, "SELECT c.category_code, c.category_name, COUNT(p.id) AS prod_count FROM category c LEFT JOIN product p ON c.id = p.category_id WHERE c.user_id = ? GROUP BY c.id ORDER BY c.category_name ASC");
                db_stmt_bind_param($stmt, "i", $userId);
                db_stmt_execute($stmt);
                $res = db_stmt_get_result($stmt);
                if ($res && db_num_rows($res) > 0) {
                    $msg = "🏷️ <b>CATEGORY DIRECTORY</b>\n"
                         . "═════════════════════════════\n\n";
                    while ($r = db_fetch_assoc($res)) {
                        $msg .= "• <b>" . htmlspecialchars($r['category_name']) . "</b> (<code>" . htmlspecialchars($r['category_code']) . "</code>) — " . (int)$r['prod_count'] . " items\n";
                    }
                    $msg .= "\n💡 <i>Use <code>/category &lt;code&gt;</code> for detailed category info.</i>";
                } else {
                    $msg = "📂 <b>NO CATEGORIES FOUND</b>";
                }
                db_stmt_close($stmt);
                sendTelegramMessage($chatId, $msg, $botToken);
                break;
            }

            $stmt = db_prepare($conn, "SELECT c.id, c.category_code, c.category_name, COUNT(p.id) AS prod_count, COALESCE(SUM(p.quantity), 0) AS total_qty FROM category c LEFT JOIN product p ON c.id = p.category_id WHERE c.user_id = ? AND (UPPER(c.category_code) = UPPER(?) OR c.id = ? OR UPPER(c.category_name) LIKE UPPER(?)) GROUP BY c.id LIMIT 1");
            $catIdArg = (int)$rawArg;
            $catLikeArg = "%" . $rawArg . "%";
            db_stmt_bind_param($stmt, "isis", $userId, $rawArg, $catIdArg, $catLikeArg);
            db_stmt_execute($stmt);
            $res = db_stmt_get_result($stmt);

            if ($res && $r = db_fetch_assoc($res)) {
                $cId   = (int)$r['id'];
                $cCode = htmlspecialchars($r['category_code']);
                $cName = htmlspecialchars($r['category_name']);
                $cnt   = (int)$r['prod_count'];
                $qty   = (int)$r['total_qty'];
                $msg = "🏷️ <b>CATEGORY DETAILS</b>\n"
                     . "═════════════════════════════\n"
                     . "<b>ID:</b> #{$cId}\n"
                     . "<b>Code:</b> <code>{$cCode}</code>\n"
                     . "<b>Name:</b> {$cName}\n"
                     . "<b>Associated Products:</b> {$cnt} items\n"
                     . "<b>Total Stock Qty:</b> {$qty} units";
            } else {
                $msg = "❌ <b>CATEGORY NOT FOUND</b> for '<b>" . htmlspecialchars($rawArg) . "</b>'";
            }
            db_stmt_close($stmt);
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // 5. /sort [price|stock|date]
        case '/sort':
            $sortField = 'p.quantity DESC';
            $sortLabel = 'Stock Quantity (High to Low)';
            if ($arg === 'price') {
                $sortField = 'p.price DESC';
                $sortLabel = 'Price (High to Low)';
            } elseif ($arg === 'date') {
                $sortField = 'p.id DESC';
                $sortLabel = 'Date Added (Newest)';
            }

            $stmt = db_prepare($conn, "SELECT p.product_code, p.product_name, p.price, p.quantity FROM product p WHERE p.user_id = ? ORDER BY {$sortField} LIMIT 10");
            db_stmt_bind_param($stmt, "i", $userId);
            db_stmt_execute($stmt);
            $res = db_stmt_get_result($stmt);

            if ($res && db_num_rows($res) > 0) {
                $msg = "↕️ <b>SORTED PRODUCT LIST</b>\n"
                     . "<i>Sorted by: <b>{$sortLabel}</b></i>\n"
                     . "═════════════════════════════\n\n";
                while ($r = db_fetch_assoc($res)) {
                    $msg .= "• <b>" . htmlspecialchars($r['product_name']) . "</b> (<code>" . htmlspecialchars($r['product_code']) . "</code>)\n"
                         . "   └ Stock: <b>" . (int)$r['quantity'] . "</b> | $" . number_format((float)$r['price'], 2) . "\n";
                }
            } else {
                $msg = "📦 <b>NO PRODUCTS FOUND TO SORT</b>";
            }
            db_stmt_close($stmt);
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // 6. /lowstock
        case '/lowstock':
            $stmt = db_prepare($conn, "SELECT product_code, product_name, quantity, price FROM product WHERE user_id = ? AND quantity <= 5 ORDER BY quantity ASC");
            db_stmt_bind_param($stmt, "i", $userId);
            db_stmt_execute($stmt);
            $res = db_stmt_get_result($stmt);

            if ($res && db_num_rows($res) > 0) {
                $msg = "⚠️ <b>LOW STOCK WARNING (&le; 5 units)</b>\n"
                     . "═════════════════════════════\n\n";
                while ($r = db_fetch_assoc($res)) {
                    $code = htmlspecialchars($r['product_code']);
                    $name = htmlspecialchars($r['product_name']);
                    $qty  = (int)$r['quantity'];
                    $price = number_format((float)$r['price'], 2);
                    $msg .= "⚠️ <b>{$name}</b> (<code>{$code}</code>)\n"
                          . "   └ Stock: <b>{$qty} units</b> | \${$price}\n";
                }
            } else {
                $msg = "✅ <b>ALL STOCK LEVELS HEALTHY!</b>\nNo items with quantity &le; 5.";
            }
            db_stmt_close($stmt);
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // 7. /topstock
        case '/topstock':
            $stmt = db_prepare($conn, "SELECT product_code, product_name, quantity, price FROM product WHERE user_id = ? ORDER BY quantity DESC LIMIT 10");
            db_stmt_bind_param($stmt, "i", $userId);
            db_stmt_execute($stmt);
            $res = db_stmt_get_result($stmt);

            if ($res && db_num_rows($res) > 0) {
                $msg = "📊 <b>TOP 10 HIGHEST STOCK ITEMS</b>\n"
                     . "═════════════════════════════\n\n";
                while ($r = db_fetch_assoc($res)) {
                    $code = htmlspecialchars($r['product_code']);
                    $name = htmlspecialchars($r['product_name']);
                    $qty  = (int)$r['quantity'];
                    $price = number_format((float)$r['price'], 2);
                    $msg .= "🏆 <b>{$name}</b> (<code>{$code}</code>)\n"
                          . "   └ Stock: <b>{$qty} units</b> | \${$price}\n";
                }
            } else {
                $msg = "📦 <b>NO PRODUCTS FOUND</b>";
            }
            db_stmt_close($stmt);
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // 8. /product <code|name>
        case '/product':
        case '/products':
        case '/orders':
            if (empty($rawArg)) {
                $stmt = db_prepare($conn, "SELECT p.product_code, p.product_name, p.price, p.quantity, c.category_name FROM product p LEFT JOIN category c ON p.category_id = c.id WHERE p.user_id = ? ORDER BY p.id DESC LIMIT 10");
                db_stmt_bind_param($stmt, "i", $userId);
                db_stmt_execute($stmt);
                $res = db_stmt_get_result($stmt);

                if ($res && db_num_rows($res) > 0) {
                    $msg = "📦 <b>YOUR PRODUCTS OVERVIEW</b>\n"
                         . "═════════════════════════════\n\n";
                    while ($r = db_fetch_assoc($res)) {
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
                } else {
                    $msg = "📦 <b>NO PRODUCTS FOUND</b>";
                }
                db_stmt_close($stmt);
                sendTelegramMessage($chatId, $msg, $botToken);
                break;
            }

            $stmt = db_prepare($conn, "SELECT p.*, c.category_name FROM product p LEFT JOIN category c ON p.category_id = c.id WHERE p.user_id = ? AND (UPPER(p.product_code) = UPPER(?) OR p.id = ? OR UPPER(p.product_name) LIKE UPPER(?)) LIMIT 1");
            $pIdArg = (int)$rawArg;
            $pLikeArg = "%" . $rawArg . "%";
            db_stmt_bind_param($stmt, "isis", $userId, $rawArg, $pIdArg, $pLikeArg);
            db_stmt_execute($stmt);
            $res = db_stmt_get_result($stmt);

            if ($res && $r = db_fetch_assoc($res)) {
                $pId   = (int)$r['id'];
                $pCode = htmlspecialchars($r['product_code']);
                $pName = htmlspecialchars($r['product_name']);
                $cName = htmlspecialchars($r['category_name'] ?? 'Unassigned');
                $price = number_format((float)$r['price'], 2);
                $qty   = (int)$r['quantity'];
                $val   = number_format((float)$r['price'] * $qty, 2);
                $msg = "📦 <b>PRODUCT DETAILS</b>\n"
                     . "═════════════════════════════\n"
                     . "<b>ID:</b> #{$pId}\n"
                     . "<b>Code:</b> <code>{$pCode}</code>\n"
                     . "<b>Name:</b> {$pName}\n"
                     . "<b>Category:</b> {$cName}\n"
                     . "<b>Price:</b> \${$price}\n"
                     . "<b>Stock Qty:</b> {$qty} units\n"
                     . "<b>Inventory Value:</b> \${$val}";
            } else {
                $msg = "❌ <b>PRODUCT NOT FOUND</b> for '<b>" . htmlspecialchars($rawArg) . "</b>'";
            }
            db_stmt_close($stmt);
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // 9. /outofstock
        case '/outofstock':
            $stmt = db_prepare($conn, "SELECT product_code, product_name, price FROM product WHERE user_id = ? AND quantity = 0 ORDER BY product_name ASC");
            db_stmt_bind_param($stmt, "i", $userId);
            db_stmt_execute($stmt);
            $res = db_stmt_get_result($stmt);

            if ($res && db_num_rows($res) > 0) {
                $msg = "🚫 <b>OUT OF STOCK ITEMS (0 units)</b>\n"
                     . "═════════════════════════════\n\n";
                while ($r = db_fetch_assoc($res)) {
                    $code = htmlspecialchars($r['product_code']);
                    $name = htmlspecialchars($r['product_name']);
                    $price = number_format((float)$r['price'], 2);
                    $msg .= "❌ <b>{$name}</b> (<code>{$code}</code>) — \${$price}\n";
                }
            } else {
                $msg = "✅ <b>GREAT!</b> All products are currently in stock.";
            }
            db_stmt_close($stmt);
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // 10. /summary & /report
        case '/report':
        case '/summary':
            $catStmt = db_prepare($conn, "SELECT COUNT(*) as cat_cnt FROM category WHERE user_id = ?");
            db_stmt_bind_param($catStmt, "i", $userId);
            db_stmt_execute($catStmt);
            $catRes = db_stmt_get_result($catStmt);
            $catCnt = ($r = db_fetch_assoc($catRes)) ? (int)$r['cat_cnt'] : 0;
            db_stmt_close($catStmt);

            $prodStmt = db_prepare($conn, "SELECT COUNT(*) as prod_cnt, COALESCE(SUM(quantity), 0) as total_qty, COALESCE(SUM(price * quantity), 0) as total_val FROM product WHERE user_id = ?");
            db_stmt_bind_param($prodStmt, "i", $userId);
            db_stmt_execute($prodStmt);
            $prodRes = db_stmt_get_result($prodStmt);
            $pData = db_fetch_assoc($prodRes);
            $prodCnt = (int)($pData['prod_cnt'] ?? 0);
            $totalQty = (int)($pData['total_qty'] ?? 0);
            $totalVal = number_format((float)($pData['total_val'] ?? 0), 2);
            db_stmt_close($prodStmt);

            $msg = "📊 <b>LIVE INVENTORY SUMMARY REPORT</b>\n"
                 . "═════════════════════════════\n"
                 . "🏷️ Total Categories: <b>{$catCnt}</b>\n"
                 . "📦 Total Products: <b>{$prodCnt}</b>\n"
                 . "🔢 Total Items In Stock: <b>{$totalQty} units</b>\n"
                 . "💵 Total Asset Valuation: <b>\${$totalVal}</b>\n";
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // 11. /valuation
        case '/valuation':
            $stmt = db_prepare($conn, "SELECT COUNT(*) as total_prods, COALESCE(SUM(quantity), 0) as total_stock, COALESCE(SUM(price * quantity), 0) as total_val, COALESCE(AVG(price), 0) as avg_price FROM product WHERE user_id = ?");
            db_stmt_bind_param($stmt, "i", $userId);
            db_stmt_execute($stmt);
            $res = db_stmt_get_result($stmt);
            if ($res && $r = db_fetch_assoc($res)) {
                $prods    = number_format((int)$r['total_prods']);
                $stock    = number_format((int)$r['total_stock']);
                $avgPrice = number_format((float)$r['avg_price'], 2);
                $totalVal = number_format((float)$r['total_val'], 2);

                $msg = "💎 <b>FINANCIAL & ASSET VALUATION REPORT</b>\n"
                     . "═════════════════════════════\n"
                     . "📦 Total Products Listed: <b>{$prods}</b>\n"
                     . "🔢 Total Stock Quantity: <b>{$stock} units</b>\n"
                     . "💲 Average Unit Price: <b>\${$avgPrice}</b>\n"
                     . "💵 Total Asset Valuation: <b>\${$totalVal}</b>";
            } else {
                $msg = "💎 <b>VALUATION REPORT</b>\nNo inventory data found.";
            }
            db_stmt_close($stmt);
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // 12. /added
        case '/added':
            $stmt = db_prepare($conn, "SELECT product_code, product_name, price, quantity FROM product WHERE user_id = ? ORDER BY id DESC LIMIT 5");
            db_stmt_bind_param($stmt, "i", $userId);
            db_stmt_execute($stmt);
            $res = db_stmt_get_result($stmt);

            $msg = "🆕 <b>RECENTLY ADDED PRODUCTS</b>\n"
                 . "═════════════════════════════\n\n";
            if ($res && db_num_rows($res) > 0) {
                while ($r = db_fetch_assoc($res)) {
                    $msg .= "📦 <b>" . htmlspecialchars($r['product_name']) . "</b> (<code>" . htmlspecialchars($r['product_code']) . "</code>)\n"
                         . "   └ Stock: " . (int)$r['quantity'] . " | $" . number_format((float)$r['price'], 2) . "\n";
                }
            } else {
                $msg .= "<i>No recent items found.</i>";
            }
            db_stmt_close($stmt);
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // 13. /updated
        case '/updated':
            $stmt = db_prepare($conn, "SELECT product_code, product_name, price, quantity, lastupdate FROM product WHERE user_id = ? ORDER BY lastupdate DESC LIMIT 5");
            db_stmt_bind_param($stmt, "i", $userId);
            db_stmt_execute($stmt);
            $res = db_stmt_get_result($stmt);

            $msg = "✏️ <b>RECENTLY MODIFIED PRODUCTS</b>\n"
                 . "═════════════════════════════\n\n";
            if ($res && db_num_rows($res) > 0) {
                while ($r = db_fetch_assoc($res)) {
                    $msg .= "✏️ <b>" . htmlspecialchars($r['product_name']) . "</b> (<code>" . htmlspecialchars($r['product_code']) . "</code>)\n"
                         . "   └ Qty: " . (int)$r['quantity'] . " | $" . number_format((float)$r['price'], 2) . " <i>(" . htmlspecialchars($r['lastupdate']) . ")</i>\n";
                }
            } else {
                $msg .= "<i>No recent updates recorded.</i>";
            }
            db_stmt_close($stmt);
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // 14. /history
        case '/history':
            $pStmt = db_prepare($conn, "SELECT product_code, product_name, lastupdate FROM product WHERE user_id = ? ORDER BY lastupdate DESC LIMIT 5");
            db_stmt_bind_param($pStmt, "i", $userId);
            db_stmt_execute($pStmt);
            $pRes = db_stmt_get_result($pStmt);

            $cStmt = db_prepare($conn, "SELECT category_code, category_name, lastupdate FROM category WHERE user_id = ? ORDER BY lastupdate DESC LIMIT 5");
            db_stmt_bind_param($cStmt, "i", $userId);
            db_stmt_execute($cStmt);
            $cRes = db_stmt_get_result($cStmt);

            $msg = "📜 <b>RECENT ACTIVITY LOG</b>\n"
                 . "═════════════════════════════\n\n";

            if ($pRes && db_num_rows($pRes) > 0) {
                $msg .= "📦 <b>Product Activity:</b>\n";
                while ($r = db_fetch_assoc($pRes)) {
                    $msg .= "• <b>" . htmlspecialchars($r['product_name']) . "</b> (<code>" . htmlspecialchars($r['product_code']) . "</code>) — <i>" . htmlspecialchars($r['lastupdate']) . "</i>\n";
                }
                $msg .= "\n";
            }
            db_stmt_close($pStmt);

            if ($cRes && db_num_rows($cRes) > 0) {
                $msg .= "🏷️ <b>Category Activity:</b>\n";
                while ($r = db_fetch_assoc($cRes)) {
                    $msg .= "• <b>" . htmlspecialchars($r['category_name']) . "</b> (<code>" . htmlspecialchars($r['category_code']) . "</code>) — <i>" . htmlspecialchars($r['lastupdate']) . "</i>\n";
                }
            }
            db_stmt_close($cStmt);

            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // 15. /today
        case '/today':
            $today = date('Y-m-d');
            $pStmt = db_prepare($conn, "SELECT COUNT(*) as cnt FROM product WHERE user_id = ? AND DATE(created_at) = ?");
            db_stmt_bind_param($pStmt, "is", $userId, $today);
            db_stmt_execute($pStmt);
            $pRes = db_stmt_get_result($pStmt);
            $pCnt = ($r = db_fetch_assoc($pRes)) ? (int)$r['cnt'] : 0;
            db_stmt_close($pStmt);

            $cStmt = db_prepare($conn, "SELECT COUNT(*) as cnt FROM category WHERE user_id = ? AND DATE(created_at) = ?");
            db_stmt_bind_param($cStmt, "is", $userId, $today);
            db_stmt_execute($cStmt);
            $cRes = db_stmt_get_result($cStmt);
            $cCnt = ($r = db_fetch_assoc($cRes)) ? (int)$r['cnt'] : 0;
            db_stmt_close($cStmt);

            $msg = "📅 <b>TODAY'S ACTIVITY SUMMARY</b>\n"
                 . "<i>Date: " . date('d/m/Y') . "</i>\n"
                 . "═════════════════════════════\n\n"
                 . "📦 <b>Products Added Today:</b> <b>{$pCnt}</b>\n"
                 . "🏷️ <b>Categories Added Today:</b> <b>{$cCnt}</b>\n\n"
                 . "📊 Total Records Created Today: <b>" . ($pCnt + $cCnt) . "</b>";
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // 16. /push <message>
        case '/push':
            if (empty($rawArg)) {
                $msg = "📤 <b>SEND PUSH NOTIFICATION</b>\n"
                     . "═════════════════════════════\n"
                     . "Usage: <code>/push &lt;your message&gt;</code>\n"
                     . "Example: <code>/push Inventory audit completed!</code>";
                sendTelegramMessage($chatId, $msg, $botToken);
                break;
            }
            $nowStr = date('Y-m-d H:i:s');
            $msg = "📢 <b>MANUAL TELEGRAM PUSH</b>\n"
                 . "<i>Pushed: {$nowStr}</i>\n"
                 . "───────────────────────\n"
                 . htmlspecialchars($rawArg) . "\n"
                 . "───────────────────────\n"
                 . "<i>Sent via Telegram Command</i>";
            sendSingleTelegramNotification($chatId, $msg, $botToken);
            break;

        // 17. /toggle
        case '/toggle':
            $status = isAutoTelegramEnabled($conn);
            $newStatus = $status ? "0" : "1";
            setAutoTelegramEnabled($conn, $newStatus);

            if ($newStatus === "1") {
                $msg = "🔔 <b>AUTO-NOTIFICATIONS ENABLED</b>\n"
                     . "Automatic Telegram notifications are now <b>ON</b>.";
            } else {
                $msg = "🔕 <b>AUTO-NOTIFICATIONS DISABLED</b>\n"
                     . "Automatic Telegram notifications are now <b>OFF</b>.";
            }
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // 18. /settings
        case '/settings':
            $stmt = db_prepare($conn, "SELECT name, email, username FROM users WHERE id = ?");
            db_stmt_bind_param($stmt, "i", $userId);
            db_stmt_execute($stmt);
            $res = db_stmt_get_result($stmt);
            $uRow = db_fetch_assoc($res);
            db_stmt_close($stmt);

            $uName = htmlspecialchars($uRow['name'] ?? 'User #' . $userId);
            $uEmail = htmlspecialchars($uRow['email'] ?? 'N/A');

            $msg = "⚙️ <b>ACCOUNT SETTINGS & CONNECTION INFO</b>\n"
                 . "═════════════════════════════\n"
                 . "👤 <b>User:</b> {$uName}\n"
                 . "📧 <b>Email:</b> {$uEmail}\n"
                 . "🆔 <b>User ID:</b> <code>#{$userId}</code>\n"
                 . "📱 <b>Telegram Chat ID:</b> <code>{$chatId}</code>\n"
                 . "Status: <b>Connected ✅</b>";
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        // 19. /help
        case '/help':
        default:
            $msg = "🤖 <b>INVENTORY BOT COMMAND CENTER</b>\n"
                 . "<i>All Available Bot Commands</i>\n"
                 . "═════════════════════════════\n\n"
                 . "🔍 <code>/search &lt;keyword&gt;</code> — Search product\n"
                 . "🔍 <code>/searchall &lt;keyword&gt;</code> — Search all records\n"
                 . "🏷️ <code>/categories</code> — List all categories\n"
                 . "🏷️ <code>/category &lt;code&gt;</code> — Category info\n"
                 . "↕️ <code>/sort [price|stock|date]</code> — Sort items\n"
                 . "⚠️ <code>/lowstock</code> — Low stock items (≤ 5)\n"
                 . "📊 <code>/topstock</code> — Top 10 highest stock\n"
                 . "📦 <code>/product &lt;code&gt;</code> — Product info\n"
                 . "🚫 <code>/outofstock</code> — Out of stock items\n"
                 . "📊 <code>/summary</code> — Live inventory summary\n"
                 . "💎 <code>/valuation</code> — Financial report\n"
                 . "🆕 <code>/added</code> — Recently added items\n"
                 . "✏️ <code>/updated</code> — Recently modified items\n"
                 . "📜 <code>/history</code> — Activity log\n"
                 . "📅 <code>/today</code> — Today's activity\n"
                 . "📤 <code>/push &lt;msg&gt;</code> — Send to Telegram\n"
                 . "🔔 <code>/toggle</code> — Toggle auto-notify\n"
                 . "❓ <code>/help</code> — View all commands\n\n"
                 . "─────────────────────────────\n"
                 . "<i>Tap any command above to run it instantly!</i>";
            sendTelegramMessage($chatId, $msg, $botToken);
            break;
    }
}

/**
 * Poll pending Telegram updates for a specific bot token on demand (web serverless compatible)
 */
function pollTelegramUpdatesForBot($conn, $botToken) {
    if (empty($botToken)) return;
    $offsetFile = __DIR__ . '/telegram_offset.txt';
    $offset = file_exists($offsetFile) ? (int)file_get_contents($offsetFile) : 0;

    $url = "https://api.telegram.org/bot{$botToken}/getUpdates?offset={$offset}&timeout=2";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 4);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response !== false) {
        $data = json_decode($response, true);
        if ($data && isset($data['result']) && is_array($data['result'])) {
            foreach ($data['result'] as $update) {
                $updateId = $update['update_id'] ?? 0;
                $newOffset = $updateId + 1;
                @file_put_contents($offsetFile, $newOffset);

                if (isset($update['message'])) {
                    $msgObj = $update['message'];
                    $chatId = $msgObj['chat']['id'] ?? '';
                    $text   = trim($msgObj['text'] ?? '');
                    if (!empty($chatId) && !empty($text)) {
                        processTelegramCommand($conn, $chatId, $text, $botToken, 1, $updateId);
                    }
                }
            }
        }
    }
}
