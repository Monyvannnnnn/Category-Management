<?php
/**
 * Telegram Bot Command Poller & Processor
 * Multi-Tenant Support: User A -> Bot A -> Chat A
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/notify_bot.php';

header("Content-Type: application/json; charset=utf-8");

$defaultBotToken = "8560470449:AAEuX9eLYvk0wxh65Rc0d8iNhObzVzni-x8";

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
        ['command' => 'bi',         'description' => '📊 Open Live BI Dashboard Mini App'],
        ['command' => 'fieldbi',    'description' => '🌾 Open Field BI Web App'],
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

if (!function_exists('getCustomReplyKeyboard')) {
    function getCustomReplyKeyboard() {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (empty($host) || strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false || strpos($host, '::1') !== false) {
            $baseUrl = "https://report-push-v2.vercel.app";
        } else {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'https';
            $script = $_SERVER['SCRIPT_NAME'] ?? '';
            $dir = rtrim(dirname($script), '/\\');
            if ($dir === '.' || $dir === '/' || $dir === '\\') {
                $dir = '';
            }
            $baseUrl = "{$scheme}://{$host}{$dir}";
        }

        $fieldBiUrl = "https://app.fieldbi.com/?page=promptdemo&rpf=zGR88xyzPD&action=page&frm=RMt_ph898";
        $prodUrl    = "{$baseUrl}/products.php";
        $catUrl     = "{$baseUrl}/index.php";

        return [
            'keyboard' => [
                [
                    ['text' => '🌾 Field BI Mini App', 'web_app' => ['url' => $fieldBiUrl]]
                ],
                [
                    ['text' => '📦 All Products'],
                    ['text' => '🏷️ Categories']
                ],
                [
                    ['text' => '⚠️ Low Stock'],
                    ['text' => '🚫 Out of Stock']
                ],
                [
                    ['text' => '📈 Inventory Summary'],
                    ['text' => '❓ Help & Commands']
                ]
            ],
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ];
    }
}

function sendTelegramMessage($chatId, $text, $botToken) {
    return sendTelegramMessageWithMarkup($chatId, $text, $botToken, getCustomReplyKeyboard());
}

function sendTelegramMessageWithMarkup($chatId, $text, $botToken, $replyMarkup = null) {
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    $postData = [
        'chat_id'    => $chatId,
        'text'       => $text,
        'parse_mode' => 'HTML'
    ];
    if (empty($replyMarkup)) {
        $replyMarkup = getCustomReplyKeyboard();
    }
    $postData['reply_markup'] = is_string($replyMarkup) ? $replyMarkup : json_encode($replyMarkup);

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

if (!function_exists('sendTemporaryLoadingMessage')) {
    function sendTemporaryLoadingMessage($chatId, $botToken, $customText = null) {
        $text = !empty($customText) ? $customText : "⏳ <i>Processing live inventory request...</i>";
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $payload = [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'HTML'
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $resRaw = curl_exec($ch);
        curl_close($ch);
        $res = json_decode($resRaw, true);
        return (int)($res['result']['message_id'] ?? 0);
    }
}

if (!function_exists('replyOrEditMessage')) {
    function replyOrEditMessage($chatId, $text, $botToken, $replyMarkup = null, $loadingMsgId = 0) {
        if (!empty($loadingMsgId) && function_exists('editTelegramMessageText')) {
            return editTelegramMessageText($chatId, $loadingMsgId, $text, $botToken, $replyMarkup);
        }
        return sendTelegramMessageWithMarkup($chatId, $text, $botToken, $replyMarkup);
    }
}

/**
 * Handle user bot connection binding via /start <code>
 */
function handleCodeBinding($conn, $chatId, $code) {
    $code = trim($code);
    if (empty($code)) {
        return null;
    }

    $userBot = null;
    $isFreshBind = false;

    // 1. Try exact connection code match for valid website user account
    $stmt = db_prepare($conn, "SELECT b.* FROM user_telegram_bots b LEFT JOIN users u ON b.user_id = u.id WHERE UPPER(TRIM(b.connection_code)) = UPPER(TRIM(?)) LIMIT 1");
    if ($stmt) {
        db_stmt_bind_param($stmt, "s", $code);
        db_stmt_execute($stmt);
        $res = db_stmt_get_result($stmt);
        $userBot = db_fetch_assoc($res);
        db_stmt_close($stmt);
        if ($userBot) $isFreshBind = true;
    }

    // Bind chat_id & clear connection_code for matched userBot
    if ($userBot) {
        $nowStr = date('Y-m-d H:i:s');
        $upd = db_prepare($conn, "UPDATE user_telegram_bots SET chat_id = ?, connected_at = ?, connection_code = NULL, code_expires_at = NULL WHERE id = ?");
        if ($upd) {
            db_stmt_bind_param($upd, "ssi", $chatId, $nowStr, $userBot['id']);
            db_stmt_execute($upd);
            db_stmt_close($upd);
        }
        $userBot['chat_id'] = $chatId;
        $userBot['connected_at'] = $nowStr;
        $userBot['is_fresh_bind'] = true;
        return $userBot;
    }

    return null;
}

/**
 * Helper to fetch connected bot row for incoming chatId
 */
function getConnectedUserByChatIdMySQLi($conn, $chatId) {
    if (empty($chatId)) return null;
    $stmt = db_prepare($conn, "SELECT b.*, u.username FROM user_telegram_bots b LEFT JOIN users u ON b.user_id = u.id WHERE b.chat_id = ? AND b.chat_id IS NOT NULL AND b.chat_id != '' LIMIT 1");
    if ($stmt) {
        db_stmt_bind_param($stmt, "s", $chatId);
        db_stmt_execute($stmt);
        $res = db_stmt_get_result($stmt);
        $row = db_fetch_assoc($res);
        db_stmt_close($stmt);
        if (!empty($row)) return $row;
    }
    // Fallback: Default to account user #1 if chat_id isn't bound yet
    return ['user_id' => 1, 'chat_id' => $chatId];
}

/**
 * Process Command for specific user_id
 */
function processTelegramCommand($conn, $chatId, $text, $botToken, $userId = 1, $updateId = 0) {
    // Database-level Atomic Update Deduplication Lock
    if (!empty($updateId)) {
        $updateIdStr = (string)$updateId;
        try {
            @db_query($conn, "CREATE TABLE IF NOT EXISTS processed_telegram_updates (
              update_id varchar(100) NOT NULL,
              processed_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (update_id)
            )");

            // 1. Check if updateId was already processed
            $chkStmt = db_prepare($conn, "SELECT update_id FROM processed_telegram_updates WHERE update_id = ? LIMIT 1");
            if ($chkStmt) {
                db_stmt_bind_param($chkStmt, "s", $updateIdStr);
                if (db_stmt_execute($chkStmt)) {
                    $resChk = db_stmt_get_result($chkStmt);
                    $foundRow = db_fetch_assoc($resChk);
                    db_stmt_close($chkStmt);

                    if ($foundRow) {
                        // Update already processed! Exit immediately to prevent duplicate messages!
                        return;
                    }
                }
            }

            // 2. Insert update_id to claim lock
            $insStmt = db_prepare($conn, "INSERT IGNORE INTO processed_telegram_updates (update_id) VALUES (?)");
            if ($insStmt) {
                db_stmt_bind_param($insStmt, "s", $updateIdStr);
                @db_stmt_execute($insStmt);
                @db_stmt_close($insStmt);
            }
        } catch (Throwable $t) {
            // Safe fallback: proceed with command processing
        }
    }

    // Immediately send "typing..." status indicator to Telegram chat header
    if (function_exists('sendTelegramChatAction')) {
        sendTelegramChatAction($chatId, 'typing', $botToken);
    }

    // Robust Button Text Normalization (Strips emojis & unicode variations)
    $cleanText = strtolower(trim($text));
    $normText  = strtolower(trim(preg_replace('/[^\w\s]/u', '', $text)));

    $buttonMap = [
        'all products'            => '/products',
        'products'                => '/products',
        'categories'              => '/categories',
        'category list'           => '/categories',
        'bi analytics mini app'   => '/bi',
        'bi dashboard mini app'   => '/bi',
        'bi dashboard'            => '/bi',
        'search product'          => '/search',
        'low stock'               => '/lowstock',
        'out of stock'            => '/outofstock',
        'inventory summary'       => '/summary',
        'help commands'           => '/help',
        'help'                    => '/help'
    ];

    if (isset($buttonMap[$cleanText])) {
        $command = $buttonMap[$cleanText];
        $rawArg  = '';
        $arg     = '';
    } elseif (isset($buttonMap[$normText])) {
        $command = $buttonMap[$normText];
        $rawArg  = '';
        $arg     = '';
    } elseif (strpos($normText, 'out of stock') !== false) {
        $command = '/outofstock';
        $rawArg  = '';
        $arg     = '';
    } elseif (strpos($normText, 'low stock') !== false) {
        $command = '/lowstock';
        $rawArg  = '';
        $arg     = '';
    } elseif (strpos($normText, 'inventory summary') !== false || strpos($normText, 'summary') !== false) {
        $command = '/summary';
        $rawArg  = '';
        $arg     = '';
    } elseif (strpos($normText, 'all products') !== false) {
        $command = '/products';
        $rawArg  = '';
        $arg     = '';
    } elseif (strpos($normText, 'categories') !== false) {
        $command = '/categories';
        $rawArg  = '';
        $arg     = '';
    } else {
        $parts   = explode(' ', $text, 2);
        $command = strtolower($parts[0]);
        $command = explode('@', $command)[0];
        $rawArg  = trim($parts[1] ?? '');
        $arg     = strtolower($rawArg);
    }

    // 1. Connection Code Binding (/start <code> or plain /start)
    if ($command === '/start') {
        $alreadyConnected = getConnectedUserByChatIdMySQLi($conn, $chatId);
        
        // If account is ALREADY connected and no new code provided, send welcome info
        if ($alreadyConnected && empty($rawArg)) {
            $uId = (int)$alreadyConnected['user_id'];
            if (function_exists('registerSubscriberChatId')) {
                registerSubscriberChatId($conn, $chatId);
            }
            $msg = "✅ <b>TELEGRAM BOT CONNECTED</b>\n"
                 . "═════════════════════════════\n"
                 . "Your Telegram Chat ID: <code>{$chatId}</code>\n"
                 . "Linked to Website Account User #{$uId}.\n\n"
                 . "Type /help to see all available commands!";
            sendTelegramMessage($chatId, $msg, $botToken);
            return;
        }

        $targetCode = $rawArg;
        if (empty($targetCode)) {
            // Auto-fallback: check if there is a pending, unexpired connection code waiting to be linked
            $stmtPending = db_prepare($conn, "SELECT b.* FROM user_telegram_bots b WHERE (b.chat_id IS NULL OR b.chat_id = '') AND b.connection_code IS NOT NULL AND b.connection_code != '' AND (b.code_expires_at IS NULL OR b.code_expires_at >= NOW()) ORDER BY b.id DESC LIMIT 1");
            if ($stmtPending) {
                db_stmt_execute($stmtPending);
                $resPending = db_stmt_get_result($stmtPending);
                $pendingRow = db_fetch_assoc($resPending);
                db_stmt_close($stmtPending);
                if ($pendingRow && !empty($pendingRow['connection_code'])) {
                    $targetCode = $pendingRow['connection_code'];
                }
            }
        }

        if (!empty($targetCode)) {
            $boundBot = handleCodeBinding($conn, $chatId, $targetCode);
            if ($boundBot) {
                $uId = (int)$boundBot['user_id'];
                if (function_exists('registerSubscriberChatId')) {
                    registerSubscriberChatId($conn, $chatId);
                }
                $msg = "✅ <b>TELEGRAM BOT CONNECTED SUCCESSFULLY!</b>\n"
                     . "═════════════════════════════\n"
                     . "Your Telegram Chat ID: <code>{$chatId}</code>\n"
                     . "Linked to Website Account User #{$uId}.\n\n"
                     . "Type /help to see all available commands!";
                sendTelegramMessage($chatId, $msg, $botToken);
                return;
            } else if ($alreadyConnected) {
                // If code failed/used but user is already connected, show welcome
                $uId = (int)$alreadyConnected['user_id'];
                $msg = "✅ <b>TELEGRAM BOT CONNECTED</b>\n"
                     . "═════════════════════════════\n"
                     . "Your Telegram Chat ID: <code>{$chatId}</code>\n"
                     . "Linked to Website Account User #{$uId}.\n\n"
                     . "Type /help to see all available commands!";
                sendTelegramMessage($chatId, $msg, $botToken);
                return;
            } else {
                $msg = "❌ <b>INVALID OR EXPIRED CONNECTION CODE</b>\n"
                     . "═════════════════════════════\n"
                     . "⚠️ The connection code provided is invalid or has expired.\n\n"
                     . "🔑 <b>How to Connect:</b>\n"
                     . "1. Log into your Inventory Account on the website.\n"
                     . "2. Click <b>Connect Telegram</b> to generate a valid connection code.\n"
                     . "3. Tap <b>Open Bot & Press START</b> to link automatically!";
                sendTelegramMessage($chatId, $msg, $botToken);
                return;
            }
        }
    }

    // 2. Strict Access Control Guard & User Bot Lookup
    $userBot = getConnectedUserByChatIdMySQLi($conn, $chatId);

    if (!$userBot || empty($userBot['user_id']) || empty($userBot['chat_id'])) {
        $msg = "❌ <b>ACCESS DENIED: ACCOUNT DISCONNECTED OR NOT LINKED</b>\n"
             . "═════════════════════════════\n"
             . "📱 <b>Your Chat ID:</b> <code>{$chatId}</code>\n\n"
             . "⚠️ You are disconnected or not yet connected to a website account.\n"
             . "Telegram commands are disabled until you log in to the website and connect your account.\n\n"
             . "🔑 <b>How to Connect:</b>\n"
             . "1. Log into your Inventory Account on the website.\n"
             . "2. Navigate to <b>Settings ➜ Telegram Bot Settings</b>.\n"
             . "3. Click <b>Connect Bot</b> or generate your connection code.\n"
             . "4. Send <code>/start &lt;YOUR_CODE&gt;</code> here in chat to link!";
        sendTelegramMessage($chatId, $msg, $botToken);
        return;
    }

    if (function_exists('registerSubscriberChatId')) {
        registerSubscriberChatId($conn, $chatId);
    }

    // Authenticated User ID from DB
    $userId = (int)$userBot['user_id'];

    // Send immediate temporary loading message (e.g. ⏳ Processing request...)
    $loadingMsgId = sendTemporaryLoadingMessage($chatId, $botToken, "⏳ <i>Processing live inventory request...</i>");

    // 3. Process commands for connected user
    switch ($command) {
        case '/start':
            $baseUrl    = getAppBaseUrl();
            $miniAppUrl = "{$baseUrl}/fieldbi.php";
            $directUrl  = "https://app.fieldbi.com/?page=promptdemo&rpf=zGR88xyzPD&action=page&frm=RMt_ph898";
            $msg = "🚀 <b>WELCOME TO INVENTORY MANAGEMENT BOT</b>\n"
                 . "═════════════════════════════\n"
                 . "Status: <b>Connected ✅</b>\n"
                 . "Account User ID: <code>#{$userId}</code>\n"
                 . "Connected Chat ID: <code>{$chatId}</code>\n\n"
                 . "Tap below to launch <b>Field BI Mini App</b> or open in <b>Direct Browser</b>!";
            $markup = [
                'inline_keyboard' => [
                    [
                        ['text' => '📱 Open in Mini App', 'web_app' => ['url' => $miniAppUrl]],
                        ['text' => '🌐 Open Direct Browser', 'url' => $directUrl]
                    ]
                ]
            ];
            replyOrEditMessage($chatId, $msg, $botToken, $markup, $loadingMsgId);
            break;

        case '/bi':
        case '/report':
        case '/miniapp':
            $baseUrl    = getAppBaseUrl();
            $miniAppUrl = "{$baseUrl}/fieldbi.php";
            $directUrl  = "https://app.fieldbi.com/?page=promptdemo&rpf=zGR88xyzPD&action=page&frm=RMt_ph898";
            $msg = "🌾 <b>FIELD BI ANALYTICS & REPORTING</b>\n"
                 . "═════════════════════════════\n"
                 . "Tap below to launch Field BI directly inside Telegram Mini App or open in Direct Browser!\n\n"
                 . "⚡ <i>Real-time Field BI Platform Integration.</i>";
            $markup = [
                'inline_keyboard' => [
                    [
                        ['text' => '📱 Open in Mini App', 'web_app' => ['url' => $miniAppUrl]],
                        ['text' => '🌐 Open Direct Browser', 'url' => $directUrl]
                    ],
                    [
                        ['text' => '🏷 View Categories', 'url' => "{$baseUrl}/index.php"],
                        ['text' => '📦 View Products', 'url' => "{$baseUrl}/products.php"]
                    ]
                ]
            ];
            replyOrEditMessage($chatId, $msg, $botToken, $markup, $loadingMsgId);
            break;

        case '/fieldbi':
        case '/field':
            $baseUrl    = getAppBaseUrl();
            $miniAppUrl = "{$baseUrl}/fieldbi.php";
            $directUrl  = "https://app.fieldbi.com/?page=promptdemo&rpf=zGR88xyzPD&action=page&frm=RMt_ph898";
            $msg = "🌾 <b>FIELD BI MINI APP & PLATFORM</b>\n"
                 . "═════════════════════════════\n"
                 . "Tap below to view Field BI directly inside Telegram Mini App or open in Direct Browser!\n\n"
                 . "🔗 <b>Target URL:</b> <code>{$directUrl}</code>";
            $markup = [
                'inline_keyboard' => [
                    [
                        ['text' => '📱 Open in Mini App', 'web_app' => ['url' => $miniAppUrl]],
                        ['text' => '🌐 Open Direct Browser', 'url' => $directUrl]
                    ]
                ]
            ];
            replyOrEditMessage($chatId, $msg, $botToken, $markup, $loadingMsgId);
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
            replyOrEditMessage($chatId, $msg, $botToken, null, $loadingMsgId);
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
            replyOrEditMessage($chatId, $msg, $botToken, null, $loadingMsgId);
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
            replyOrEditMessage($chatId, $msg, $botToken, null, $loadingMsgId);
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
                replyOrEditMessage($chatId, $msg, $botToken, null, $loadingMsgId);
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
            replyOrEditMessage($chatId, $msg, $botToken, null, $loadingMsgId);
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
            replyOrEditMessage($chatId, $msg, $botToken, null, $loadingMsgId);
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
            replyOrEditMessage($chatId, $msg, $botToken, null, $loadingMsgId);
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
            replyOrEditMessage($chatId, $msg, $botToken, null, $loadingMsgId);
            break;

        // 8. /product <code|name>
        case '/product':
        case '/products':
        case '/orders':
            $baseUrl = getAppBaseUrl();
            $prodUrl = "{$baseUrl}/products.php";
            $biUrl   = "https://app.fieldbi.com/?page=promptdemo&rpf=zGR88xyzPD&action=page&frm=RMt_ph898";

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

                $markup = [
                    'inline_keyboard' => [
                        [
                            ['text' => '📦 View All Products', 'url' => $prodUrl],
                            ['text' => '🌾 Field BI Report', 'url' => $biUrl]
                        ]
                    ]
                ];
                replyOrEditMessage($chatId, $msg, $botToken, $markup, $loadingMsgId);
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

                $pSearchUrl = "{$baseUrl}/products.php?search=" . urlencode($r['product_code']);
                $markup = [
                    'inline_keyboard' => [
                        [
                            ['text' => "📦 View Details ({$pCode})", 'url' => $pSearchUrl],
                            ['text' => '🌾 Field BI Report', 'url' => $biUrl]
                        ]
                    ]
                ];

                if (!empty($r['image'])) {
                    if (function_exists('deleteTelegramMessage')) {
                        deleteTelegramMessage($chatId, $loadingMsgId, $botToken);
                    }
                    sendSingleTelegramPhoto($chatId, $r['image'], $msg, $botToken, $markup);
                } else {
                    replyOrEditMessage($chatId, $msg, $botToken, $markup, $loadingMsgId);
                }
            } else {
                $msg = "❌ <b>PRODUCT NOT FOUND</b> for '<b>" . htmlspecialchars($rawArg) . "</b>'";
                $markup = [
                    'inline_keyboard' => [
                        [
                            ['text' => '📦 View All Products', 'url' => $prodUrl]
                        ]
                    ]
                ];
                replyOrEditMessage($chatId, $msg, $botToken, $markup, $loadingMsgId);
            }
            db_stmt_close($stmt);
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
            replyOrEditMessage($chatId, $msg, $botToken, null, $loadingMsgId);
            break;

        // 10. /summary & /report
        case '/report':
        case '/summary':
            $baseUrl    = getAppBaseUrl();
            $fieldBiUrl = "https://app.fieldbi.com/?page=promptdemo&rpf=zGR88xyzPD&action=page&frm=RMt_ph898";
            $prodUrl    = "{$baseUrl}/products.php";

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

            $msg = "📈 <b>LIVE INVENTORY EXECUTIVE SUMMARY</b>\n"
                 . "═════════════════════════════\n"
                 . "💎 <b>Total Asset Valuation:</b> <b>\${$totalVal}</b>\n"
                 . "📦 <b>Total Active Products:</b> <b>{$prodCnt} items</b>\n"
                 . "🔢 <b>Total Stock Units:</b> <b>{$totalQty} units</b>\n"
                 . "🏷️ <b>Total Categories:</b> <b>{$catCnt} categories</b>\n"
                 . "═════════════════════════════\n"
                 . "🕐 <i>Updated: " . date('d M Y │ H:i') . "</i>";

            $markup = [
                'inline_keyboard' => [
                    [
                        ['text' => '🌾 Open Field BI Report', 'url' => $fieldBiUrl]
                    ],
                    [
                        ['text' => '📦 View All Products', 'url' => $prodUrl],
                        ['text' => '🏷️ View Categories', 'url' => "{$baseUrl}/index.php"]
                    ]
                ]
            ];
            replyOrEditMessage($chatId, $msg, $botToken, $markup, $loadingMsgId);
            break;

        // 11. /valuation
        case '/valuation':
            $baseUrl    = getAppBaseUrl();
            $fieldBiUrl = "https://app.fieldbi.com/?page=promptdemo&rpf=zGR88xyzPD&action=page&frm=RMt_ph898";
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
                     . "💰 <b>Total Asset Valuation:</b> <b>\${$totalVal}</b>\n"
                     . "📦 <b>Total Products Listed:</b> <b>{$prods} items</b>\n"
                     . "🔢 <b>Total Stock Quantity:</b> <b>{$stock} units</b>\n"
                     . "🏷️ <b>Average Unit Price:</b> <b>\${$avgPrice}</b>\n"
                     . "═════════════════════════════\n"
                     . "🕐 <i>Updated: " . date('d M Y │ H:i') . "</i>";
            } else {
                $msg = "💎 <b>VALUATION REPORT</b>\nNo inventory data found.";
            }
            db_stmt_close($stmt);

            $markup = [
                'inline_keyboard' => [
                    [
                        ['text' => '🌾 Open Field BI Report', 'url' => $fieldBiUrl]
                    ]
                ]
            ];
            replyOrEditMessage($chatId, $msg, $botToken, $markup, $loadingMsgId);
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
            replyOrEditMessage($chatId, $msg, $botToken, null, $loadingMsgId);
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
            replyOrEditMessage($chatId, $msg, $botToken, null, $loadingMsgId);
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
            replyOrEditMessage($chatId, $msg, $botToken, null, $loadingMsgId);
            break;

        // 16. /push <message>
        case '/push':
            if (empty($rawArg)) {
                $msg = "📤 <b>SEND PUSH NOTIFICATION</b>\n"
                     . "═════════════════════════════\n"
                     . "Usage: <code>/push &lt;your message&gt;</code>\n"
                     . "Example: <code>/push Inventory audit completed!</code>";
                replyOrEditMessage($chatId, $msg, $botToken, null, $loadingMsgId);
                break;
            }
            $nowStr = date('Y-m-d H:i:s');
            $msg = "📢 <b>MANUAL TELEGRAM PUSH</b>\n"
                 . "<i>Pushed: {$nowStr}</i>\n"
                 . "───────────────────────\n"
                 . htmlspecialchars($rawArg) . "\n"
                 . "───────────────────────\n"
                 . "<i>Sent via Telegram Command</i>";
            replyOrEditMessage($chatId, $msg, $botToken, null, $loadingMsgId);
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
            replyOrEditMessage($chatId, $msg, $botToken, null, $loadingMsgId);
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
            replyOrEditMessage($chatId, $msg, $botToken, null, $loadingMsgId);
            break;

        // 19. /help
        case '/help':
        default:
            $baseUrl    = getAppBaseUrl();
            $biUrl      = "{$baseUrl}/report_bi.php";
            $fieldBiUrl = "https://app.fieldbi.com/?page=promptdemo&rpf=zGR88xyzPD&action=page&frm=RMt_ph898";
            $prodUrl    = "{$baseUrl}/products.php";

            $msg = "⚡ <b>INVENTORY BOT COMMAND CENTER</b>\n"
                 . "<i>Executive Control & Real-time Analytics</i>\n"
                 . "═════════════════════════════\n\n"
                 . "📊 <b>MINI APPS & DASHBOARDS</b>\n"
                 . "├ <code>/bi</code> — 📊 Open Executive BI Analytics Mini App\n"
                 . "└ <code>/fieldbi</code> — 🌾 Open Field BI Web App\n\n"
                 . "📦 <b>PRODUCT MANAGEMENT</b>\n"
                 . "├ <code>/product &lt;code&gt;</code> — 📦 Detailed product info\n"
                 . "├ <code>/products</code> — 📋 All products overview\n"
                 . "├ <code>/search &lt;keyword&gt;</code> — 🔍 Search products\n"
                 . "├ <code>/searchall &lt;key&gt;</code> — 🔎 Search products & categories\n"
                 . "└ <code>/sort [price|stock|date]</code> — ↕️ Sort items\n\n"
                 . "🏷️ <b>CATEGORY CATALOG</b>\n"
                 . "├ <code>/categories</code> — 🏷️ Category list & totals\n"
                 . "└ <code>/category &lt;code&gt;</code> — 📁 Category info\n\n"
                 . "📈 <b>VALUATION & STOCK ANALYTICS</b>\n"
                 . "├ <code>/summary</code> — 📈 Live inventory summary\n"
                 . "├ <code>/valuation</code> — 💎 Financial asset valuation\n"
                 . "├ <code>/lowstock</code> — ⚠️ Low stock warnings (≤ 5)\n"
                 . "├ <code>/outofstock</code> — 🚨 Out of stock items (0 units)\n"
                 . "└ <code>/topstock</code> — 🏆 Top 10 highest stock\n\n"
                 . "📜 <b>AUDIT LOG & NOTIFICATIONS</b>\n"
                 . "├ <code>/added</code> — 🆕 Recently added items\n"
                 . "├ <code>/updated</code> — ✏️ Recently modified items\n"
                 . "├ <code>/today</code> — 📅 Today's activity log\n"
                 . "├ <code>/history</code> — 📜 Audit history log\n"
                 . "├ <code>/push &lt;msg&gt;</code> — 📤 Send custom push alert\n"
                 . "├ <code>/toggle</code> — 🔔 Toggle auto notification\n"
                 . "└ <code>/settings</code> — ⚙️ Account connection info\n\n"
                 . "═════════════════════════════\n"
                 . "💡 <i>Tap any command above or use the keyboard below!</i>";

            $markup = [
                'inline_keyboard' => [
                    [
                        ['text' => '🌾 Open Field BI Report', 'url' => $fieldBiUrl]
                    ],
                    [
                        ['text' => '📦 All Products', 'url' => $prodUrl],
                        ['text' => '🏷️ Categories', 'url' => "{$baseUrl}/index.php"]
                    ]
                ]
            ];
            replyOrEditMessage($chatId, $msg, $botToken, $markup, $loadingMsgId);
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

    $url = "https://api.telegram.org/bot{$botToken}/getUpdates?offset={$offset}&timeout=0";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);
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
