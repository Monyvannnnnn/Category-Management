# Telegram Bot Commands — How It Works

Complete step-by-step guide to understanding how the Inventory Telegram bot processes commands.

---

## Architecture Overview

```
User sends /help on Telegram
        ↓
Telegram servers receive the message
        ↓
Telegram forwards it to YOUR server (webhook or poller)
        ↓
Your PHP code processes the command
        ↓
PHP queries the database
        ↓
PHP formats a response
        ↓
PHP sends response back to Telegram API
        ↓
Telegram delivers it to the user
```

---

## Two Ways to Receive Messages

### Method 1: Webhook (Production — Render)

```
Telegram → POST request → set_commands.php → process command → response
```

**How it works:**
1. You tell Telegram your webhook URL:
   ```
   https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://your-site.com/set_commands.php
   ```
2. Every time a user sends a message, Telegram POSTs it to `set_commands.php`
3. Your code processes and responds

**File:** `set_commands.php`

```php
// 1. Read incoming message from Telegram
$content = file_get_contents("php://input");
$update = json_decode($content, true);

// 2. Extract chat ID and message text
$chatId = $update["message"]["chat"]["id"];
$text = trim($update["message"]["text"]);

// 3. Process the command
processTelegramCommand($conn, $chatId, $text, $botToken);
```

---

### Method 2: Poller (Local Testing — XAMPP)

```
bot_poller.php → Ask Telegram "any new messages?" → process → repeat
```

**How it works:**
1. You run `php bot_poller.php` in a loop
2. It asks Telegram: "Any new messages since offset X?"
3. Telegram responds with new messages
4. Your code processes them
5. Waits 2 seconds → repeats

**File:** `bot_poller.php`

```php
// 1. Ask Telegram for new messages
$getUpdatesUrl = "https://api.telegram.org/bot<TOKEN>/getUpdates?offset={$offset}&timeout=2";
$response = curl_exec($ch);

// 2. Process each new message
foreach ($data['result'] as $update) {
    $chatId = $update['message']['chat']['id'];
    $text = trim($update['message']['text']);
    
    processTelegramCommand($conn, $chatId, $text, $botToken);
    
    // 3. Update offset so we don't process the same message twice
    $offset = $update['update_id'] + 1;
}

// 4. Save offset to file for next run
file_put_contents($offsetFile, $offset);
```

---

## Command Processing Flow (Step by Step)

### Step 1: User Sends Command

User types: `/search mouse`

Telegram sends this JSON to your server:
```json
{
  "update_id": 123456789,
  "message": {
    "chat": { "id": "7892238736" },
    "text": "/search mouse"
  }
}
```

---

### Step 2: Extract Command and Argument

```php
// Split "/search mouse" into command + argument
$parts = explode(' ', $text, 2);

$command = strtolower($parts[0]);  // "/search"
$command = explode('@', $command)[0]; // Remove @botname if present
$arg = strtolower(trim($parts[1] ?? '')); // "mouse"
```

---

### Step 3: Match Command in Switch Statement

```php
switch ($command) {
    case '/search':
        // Handle search
        break;
    case '/categories':
        // Handle categories
        break;
    // ... etc
}
```

---

### Step 4: Query Database

```php
// Prepare search term
$searchArg = "%" . $arg . "%";  // "%mouse%"

// Prepare SQL statement
$stmt = mysqli_prepare($conn, 
    "SELECT p.product_code, p.product_name, p.price, p.quantity, c.category_name 
     FROM product p 
     LEFT JOIN category c ON p.category_id = c.id 
     WHERE p.product_name LIKE ? 
        OR p.product_code LIKE ? 
        OR c.category_name LIKE ? 
     LIMIT 5"
);

// Bind parameters (prevents SQL injection)
mysqli_stmt_bind_param($stmt, "sss", $searchArg, $searchArg, $searchArg);

// Execute
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
```

---

### Step 5: Format Response

```php
$msg = "🔍 <b>PRODUCT SEARCH DIRECTORY</b>\n"
     . "<i>Query: '<b>mouse</b>' • Results</i>\n"
     . "═════════════════════════════\n\n";

while ($r = mysqli_fetch_assoc($result)) {
    $code  = htmlspecialchars($r['product_code']);
    $name  = htmlspecialchars($r['product_name']);
    $price = number_format((float)$r['price'], 2);
    $qty   = (int)$r['quantity'];
    
    $msg .= "📦 <b>{$name}</b>\n"
          . "├ 🆔 Code: <code>{$code}</code>\n"
          . "├ 💰 Price: <b>\${$price}</b>\n"
          . "└ 🔢 Stock: <b>{$qty} units</b>\n\n";
}
```

---

### Step 6: Send Response via Telegram API

```php
function sendTelegramMessage($chatId, $text, $botToken) {
    $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
    
    $data = [
        'chat_id'    => $chatId,
        'text'       => $text,
        'parse_mode' => 'HTML'  // Allows <b>, <i>, <code> tags
    ];
    
    // Send via cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_exec($ch);
    curl_close($ch);
}
```

---

## Telegram API Calls Used

| API Call | Purpose | Method |
|---|---|---|
| `sendMessage` | Send response to user | POST |
| `getUpdates` | Poll for new messages (poller only) | GET |
| `setWebhook` | Tell Telegram your webhook URL | POST |
| `setMyCommands` | Register commands in BotFather | POST |

---

## BotFather Command Registration

**Why register commands?**
- Shows command menu when user types `/`
- Auto-completion in Telegram
- Description visible to users

**How to register:**
1. Open **@BotFather**
2. Send `/setcommands`
3. Select your bot
4. Send command list:
   ```
   search - Search product by name or code
   categories - View all categories
   summary - Executive dashboard
   ```

**Your bot auto-registers on every page load:**
```php
function registerBotCommands($botToken) {
    $commands = [
        ['command' => 'search', 'description' => '🔍 Search product by name or code'],
        ['command' => 'categories', 'description' => '🏷️ View all categories'],
        // ...
    ];
    
    // POST to Telegram API
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['commands' => $commands]));
}
```

---

## Database Tables Used by Bot

| Table | Usage |
|---|---|
| `product` | `/search`, `/searchall`, `/product`, `/sort`, `/lowstock`, `/topstock`, `/push`, `/export` |
| `category` | `/categories`, `/category`, `/push` |
| `system_settings` | `/toggle auto` (stores notification setting) |

---

## Error Handling

### If command is invalid:
```php
default:
    $msg = "❌ Unknown command. Use /help for available commands.";
    sendTelegramMessage($chatId, $msg, $botToken);
```

### If no results found:
```php
if (mysqli_num_rows($result) == 0) {
    $msg = "❌ <b>NO MATCHES FOUND</b>\n"
         . "No products matching '<b>{$arg}</b>' were found.";
}
```

### If Telegram API fails:
The code has 3 fallback methods:
1. Standard cURL
2. DNS bypass (InfinityFree compatibility)
3. `file_get_contents` fallback

---

## File Responsibility

| File | When It Runs | What It Does |
|---|---|---|
| `set_commands.php` | Every Telegram message (webhook) | Receives POST, processes, responds |
| `bot_poller.php` | Manual loop (local testing) | Polls Telegram, processes, responds |
| `registerBotCommands()` | On every page load | Updates command list with Telegram |
| `sendTelegramMessage()` | Called by processTelegramCommand | Sends response via Telegram API |
| `processTelegramCommand()` | Called by webhook or poller | Contains all command logic |

---

## Testing Locally (Step by Step)

1. **Start XAMPP** (Apache + MySQL)
2. **Open Command Prompt:**
   ```bash
   cd D:\xammp\htdocs\Inventory
   :loop
   php bot_poller.php
   timeout /t 2 /nobreak >nul
   goto loop
   ```
3. **Open Telegram** → send `/help` to your bot
4. **Watch the Command Prompt** — you'll see the request coming in
5. **Check Telegram** — you should receive a response

---

## Testing on Render (Step by Step)

1. **Push code to git:**
   ```bash
   git add . && git commit -m "Update bot" && git push
   ```
2. **Wait for Render to deploy**
3. **Set webhook:**
   ```
   https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://YOUR-URL.onrender.com/set_commands.php
   ```
4. **Open Telegram** → send `/help` to your bot
5. **Check Render Logs** for debugging

---

## Quick Reference

| What | File | URL |
|---|---|---|
| Webhook endpoint | `set_commands.php` | `https://your-site.com/set_commands.php` |
| Poller endpoint | `bot_poller.php` | Run locally via CLI |
| Set webhook | BotFather / API | `https://api.telegram.org/bot<TOKEN>/setWebhook` |
| Register commands | BotFather / API | `https://api.telegram.org/bot<TOKEN>/setMyCommands` |

---

**Key Takeaway:** The bot is just a PHP script that:
1. Receives JSON from Telegram
2. Queries the database
3. Formats a nice-looking message
4. Sends JSON back to Telegram

That's it! 🎯
