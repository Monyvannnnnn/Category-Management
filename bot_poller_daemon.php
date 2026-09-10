<?php
/**
 * Multi-Tenant Telegram Bot Continuous Daemon Worker
 * User A -> Bot A -> Chat A
 * User B -> Bot B -> Chat B
 * User C -> Bot C -> Chat C
 */

set_time_limit(0);
ignore_user_abort(true);

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/notify_bot.php';

header("Content-Type: application/json; charset=utf-8");

$defaultBotToken = "8736337451:AAEtwDgtwUpWGnV4cIrMNKwNjHaAV8J18jc";

function registerBotCommands($botToken) {
    $url = "https://api.telegram.org/bot{$botToken}/setMyCommands";
    $commands = [
        ['command' => 'search',     'description' => '🔍 Search product by name or code (/search <keyword>)'],
        ['command' => 'categories', 'description' => '🏷️ View all item categories'],
        ['command' => 'category',   'description' => '🏷️ View category detail (/category <code>)'],
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
    $now = date('Y-m-d H:i:s');
    if (!empty($code)) {
        $stmt = mysqli_prepare($conn, "SELECT * FROM user_telegram_bots WHERE UPPER(connection_code) = UPPER(?) AND (code_expires_at IS NULL OR code_expires_at >= ?)");
        mysqli_stmt_bind_param($stmt, "ss", $code, $now);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT * FROM user_telegram_bots WHERE connection_code IS NOT NULL AND (code_expires_at IS NULL OR code_expires_at >= ?) ORDER BY id DESC LIMIT 1");
        mysqli_stmt_bind_param($stmt, "s", $now);
    }

    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $userBot = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($userBot) {
        $upd = mysqli_prepare($conn, "UPDATE user_telegram_bots SET chat_id = ?, connected_at = ?, connection_code = NULL, code_expires_at = NULL WHERE id = ?");
        mysqli_stmt_bind_param($upd, "ssi", $chatId, $now, $userBot['id']);
        mysqli_stmt_execute($upd);
        mysqli_stmt_close($upd);
        return $userBot;
    }
    return null;
}

/**
 * Process Command for specific user_id
 */
function processTelegramCommand($conn, $chatId, $text, $botToken, $userId = 1) {
    if (function_exists('registerSubscriberChatId')) {
        registerSubscriberChatId($conn, $chatId);
    }

    $parts   = explode(' ', $text, 2);
    $command = strtolower($parts[0]);
    $command = explode('@', $command)[0];
    $rawArg  = trim($parts[1] ?? '');
    $arg     = strtolower($rawArg);

    // 1. One-Time Connection Code Binding (/start CONNECT-XXXXXX)
    if ($command === '/start' && !empty($rawArg)) {
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
    $userBot = handleCodeBinding($conn, $chatId, '');
    if (!$userBot || empty($userBot['user_id'])) {
        $msg = "❌ <b>This Telegram account is not connected.</b>\n"
             . "═════════════════════════════\n"
             . "Please connect your account first on the website.";
        sendTelegramMessage($chatId, $msg, $botToken);
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
            $catStmt = mysqli_prepare($conn, "SELECT COUNT(*) as cat_cnt FROM category WHERE user_id = ?");
            mysqli_stmt_bind_param($catStmt, "i", $userId);
            mysqli_stmt_execute($catStmt);
            $catRes = mysqli_stmt_get_result($catStmt);
            $catCnt = ($r = mysqli_fetch_assoc($catRes)) ? (int)$r['cat_cnt'] : 0;
            mysqli_stmt_close($catStmt);

            $prodStmt = mysqli_prepare($conn, "SELECT COUNT(*) as prod_cnt, COALESCE(SUM(quantity), 0) as total_qty, COALESCE(SUM(price * quantity), 0) as total_val FROM product WHERE user_id = ?");
            mysqli_stmt_bind_param($prodStmt, "i", $userId);
            mysqli_stmt_execute($prodStmt);
            $prodRes = mysqli_stmt_get_result($prodStmt);
            $pData = mysqli_fetch_assoc($prodRes);
            $prodCnt = (int)($pData['prod_cnt'] ?? 0);
            $totalQty = (int)($pData['total_qty'] ?? 0);
            $totalVal = number_format((float)($pData['total_val'] ?? 0), 2);
            mysqli_stmt_close($prodStmt);

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
            $stmt = mysqli_prepare($conn, "SELECT p.product_code, p.product_name, p.price, p.quantity, c.category_name FROM product p LEFT JOIN category c ON p.category_id = c.id WHERE p.user_id = ? ORDER BY p.id DESC LIMIT 10");
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);

            if ($res && mysqli_num_rows($res) > 0) {
                $msg = "📦 <b>YOUR ORDERS & PRODUCTS OVERVIEW</b>\n"
                     . "═════════════════════════════\n\n";
                while ($r = mysqli_fetch_assoc($res)) {
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
            mysqli_stmt_close($stmt);
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        case '/settings':
            $stmt = mysqli_prepare($conn, "SELECT name, email, username FROM users WHERE id = ?");
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $uRow = mysqli_fetch_assoc($res);
            mysqli_stmt_close($stmt);

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
            $stmt = mysqli_prepare($conn, "SELECT p.product_code, p.product_name, p.price, p.quantity, c.category_name FROM product p LEFT JOIN category c ON p.category_id = c.id WHERE p.user_id = ? AND (p.product_name LIKE ? OR p.product_code LIKE ? OR c.category_name LIKE ?) LIMIT 5");
            $searchArg = "%" . $arg . "%";
            mysqli_stmt_bind_param($stmt, "isss", $userId, $searchArg, $searchArg, $searchArg);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($result && mysqli_num_rows($result) > 0) {
                $msg = "🔍 <b>YOUR PRODUCT SEARCH DIRECTORY</b>\n"
                     . "<i>Query: '<b>" . htmlspecialchars($arg) . "</b>'</i>\n"
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
            } else {
                $msg = "❌ <b>NO MATCHES FOUND</b> for '<b>" . htmlspecialchars($arg) . "</b>'";
            }
            mysqli_stmt_close($stmt);
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        case '/categories':
            $stmt = mysqli_prepare($conn, "SELECT c.category_code, c.category_name, COUNT(p.id) AS prod_count, COALESCE(SUM(p.quantity), 0) AS total_qty FROM category c LEFT JOIN product p ON c.id = p.category_id WHERE c.user_id = ? GROUP BY c.id ORDER BY c.category_name ASC");
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);

            if ($res && mysqli_num_rows($res) > 0) {
                $totalCats = mysqli_num_rows($res);
                $msg = "🏷️ <b>YOUR CATEGORY OVERVIEW</b>\n"
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
            } else {
                $msg = "📂 <b>NO CATEGORIES FOUND</b>";
            }
            mysqli_stmt_close($stmt);
            sendTelegramMessage($chatId, $msg, $botToken);
            break;

        case '/lowstock':
            $stmt = mysqli_prepare($conn, "SELECT product_code, product_name, quantity, price FROM product WHERE user_id = ? AND quantity <= 5 ORDER BY quantity ASC");
            mysqli_stmt_bind_param($stmt, "i", $userId);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);

            if ($res && mysqli_num_rows($res) > 0) {
                $msg = "⚠️ <b>LOW STOCK WARNING (&le; 5 units)</b>\n"
                     . "═════════════════════════════\n\n";
                while ($r = mysqli_fetch_assoc($res)) {
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
            mysqli_stmt_close($stmt);
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

// Global offset registry for multi-bot polling
$offsetMap = [];

echo "Starting Multi-Tenant Telegram Bot Poller Daemon...\n";

while (true) {
    // 1. Fetch all configured user bots
    $bots = [];

    // System default bot
    $bots[] = [
        'user_id' => 1,
        'bot_token' => $defaultBotToken
    ];

    $res = mysqli_query($conn, "SELECT user_id, bot_token FROM user_telegram_bots WHERE bot_token IS NOT NULL AND bot_token != ''");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $token = trim($row['bot_token']);
            if ($token !== $defaultBotToken) {
                $bots[] = [
                    'user_id' => (int)$row['user_id'],
                    'bot_token' => $token
                ];
            }
        }
    }

    // 2. Poll updates for each bot
    foreach ($bots as $b) {
        $bToken = $b['bot_token'];
        $bUserId = $b['user_id'];
        $bOffset = $offsetMap[$bToken] ?? 0;

        $getUpdatesUrl = "https://api.telegram.org/bot{$bToken}/getUpdates?offset={$bOffset}&timeout=2";

        $ch = curl_init($getUpdatesUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        curl_close($ch);

        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data && isset($data['result']) && is_array($data['result'])) {
                foreach ($data['result'] as $update) {
                    $updateId = $update['update_id'];
                    $offsetMap[$bToken] = $updateId + 1;

                    if (isset($update['message'])) {
                        $msgObj  = $update['message'];
                        $chatId  = $msgObj['chat']['id'] ?? '';
                        $text    = trim($msgObj['text'] ?? '');

                        if (!empty($chatId) && !empty($text)) {
                            echo "[" . date('Y-m-d H:i:s') . "] Bot (User {$bUserId}) received: '$text' from Chat ID: $chatId\n";
                            processTelegramCommand($conn, $chatId, $text, $bToken, $bUserId);
                        }
                    }
                }
            }
        }
    }

    sleep(1);
}
