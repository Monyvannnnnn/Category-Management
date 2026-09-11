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
        ['command' => 'search',     'description' => '🔍 Search product by name or code (/search <keyword>)'],
        ['command' => 'categories', 'description' => '🏷️ View all item categories'],
        ['command' => 'summary',    'description' => '📊 Real-time total categories, products, & valuation'],
        ['command' => 'lowstock',   'description' => '⚠️ List critical items with stock <= 5'],
        ['command' => 'start',      'description' => '🚀 Welcome & Account Binding (/start <code>)'],
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

            $msg = "📊 <b>YOUR REAL-TIME INVENTORY REPORT</b>\n"
                 . "═════════════════════════════\n"
                 . "🏷️ Total Categories: <b>{$catCnt}</b>\n"
                 . "📦 Total Products: <b>{$prodCnt}</b>\n"
                 . "🔢 Total Items In Stock: <b>{$totalQty} units</b>\n"
                 . "💵 Total Asset Valuation: <b>\${$totalVal}</b>\n";
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        case '/orders':
        case '/products':
        case '/product':
            $stmt = db_prepare($conn, "SELECT p.product_code, p.product_name, p.price, p.quantity, c.category_name FROM product p LEFT JOIN category c ON p.category_id = c.id WHERE p.user_id = ? ORDER BY p.id DESC LIMIT 10");
            db_stmt_bind_param($stmt, "i", $userId);
            db_stmt_execute($stmt);
            $res = db_stmt_get_result($stmt);

            if ($res && db_num_rows($res) > 0) {
                $msg = "📦 <b>YOUR ORDERS & PRODUCTS OVERVIEW</b>\n"
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
                $msg = "📦 <b>NO PRODUCTS / ORDERS FOUND</b>";
            }
            db_stmt_close($stmt);
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

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

        case '/search':
            if (empty($arg)) {
                $msg = "⚠️ <b>INVALID SEARCH FORMAT</b>\n"
                     . "Usage: <code>/search &lt;keyword&gt;</code>\n"
                     . "Example: <code>/search Mouse</code>";
                sendTelegramMessage($chatId, $msg, $botToken);
                break;
            }
            $stmt = db_prepare($conn, "SELECT p.product_code, p.product_name, p.price, p.quantity, c.category_name FROM product p LEFT JOIN category c ON p.category_id = c.id WHERE p.user_id = ? AND (p.product_name LIKE ? OR p.product_code LIKE ? OR c.category_name LIKE ?) LIMIT 5");
            $searchArg = "%" . $arg . "%";
            db_stmt_bind_param($stmt, "isss", $userId, $searchArg, $searchArg, $searchArg);
            db_stmt_execute($stmt);
            $result = db_stmt_get_result($stmt);

            if ($result && db_num_rows($result) > 0) {
                $msg = "🔍 <b>YOUR PRODUCT SEARCH DIRECTORY</b>\n"
                     . "<i>Query: '<b>" . htmlspecialchars($arg) . "</b>'</i>\n"
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
                $msg = "❌ <b>NO MATCHES FOUND</b> for '<b>" . htmlspecialchars($arg) . "</b>'";
            }
            db_stmt_close($stmt);
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        case '/categories':
        case '/category':
            if (!empty($rawArg)) {
                $stmt = db_prepare($conn, "SELECT c.id, c.category_code, c.category_name, c.created_at, COUNT(p.id) AS prod_count, COALESCE(SUM(p.quantity), 0) AS total_qty FROM category c LEFT JOIN product p ON c.id = p.category_id WHERE c.user_id = ? AND (UPPER(c.category_code) = UPPER(?) OR c.id = ? OR UPPER(c.category_name) LIKE UPPER(?)) GROUP BY c.id LIMIT 1");
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
                    $msg = "🏷️ <b>SINGLE CATEGORY DETAILS</b>\n"
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
            }

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

        case '/help':
        default:
            $msg = "ℹ️ <b>AVAILABLE COMMANDS</b>\n"
                 . "═════════════════════════════\n"
                 . "📊 <code>/report</code> - Inventory report & valuation\n"
                 . "📦 <code>/orders</code> - Recent orders & products\n"
                 . "⚙️ <code>/settings</code> - Account settings & connection\n"
                 . "🏷️ <code>/categories</code> - Category overview\n"
                 . "🔍 <code>/search &lt;keyword&gt;</code> - Search products\n"
                 . "⚠️ <code>/lowstock</code> - View low stock items\n"
                 . "🚀 <code>/start</code> - Connection status";
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
