# No-Login Multi-Tenant Telegram Notification System

A secure, user-isolated Telegram notification system built with PHP, MySQL, jQuery, Bootstrap, and Telegram Bot API. Each user connects their Telegram account via a one-time connection code — no website login required.

---

## Features

- **No Login Required** — Users connect via one-time connection code
- **User Isolation** — Each user only receives their own data
- **Secure Authorization** — Chat ID verification prevents cross-user access
- **One Bot, Multiple Users** — Single Telegram bot serves all users
- **Manual & Auto Push** — Send notifications manually or toggle auto-push
- **Connection Expiry** — Codes expire after 5 minutes
- **One-Time Use** — Each code can only be used once
- **Push History** — Track all sent notifications

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        WEBSITE                                   │
│                                                                  │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐       │
│  │  index.php   │    │  telegram_   │    │  send_user_  │       │
│  │  (Dashboard) │    │  connect.php │    │  push.php    │       │
│  └──────┬───────┘    └──────┬───────┘    └──────┬───────┘       │
│         │                   │                   │                │
│         └───────────────────┴───────────────────┘                │
│                             │                                    │
│                             ▼                                    │
│                    ┌──────────────┐                              │
│                    │   Database   │                              │
│                    │  (MySQL)     │                              │
│                    └──────────────┘                              │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ Webhook
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    TELEGRAM BOT API                              │
│                                                                  │
│  ┌──────────────┐    ┌──────────────┐    ┌──────────────┐       │
│  │   webhook    │───▶│   Verify     │───▶│   Send       │       │
│  │   .php       │    │   Auth       │    │   Message    │       │
│  └──────────────┘    └──────────────┘    └──────────────┘       │
│                             │                                    │
│                             ▼                                    │
│                    ┌──────────────┐                              │
│                    │  Telegram    │                              │
│                    │  Users       │                              │
│                    └──────────────┘                              │
└─────────────────────────────────────────────────────────────────┘
```

---

## Database Schema

### users Table
Stores website users (admin creates these manually).

| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT | Primary key |
| name | VARCHAR(100) | User's display name |
| created_at | TIMESTAMP | Creation time |

### telegram_connections Table
Stores Telegram connection data per user.

| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT | Primary key |
| user_id | INT | Foreign key to users.id |
| chat_id | VARCHAR(50) | Telegram chat ID (unique) |
| connection_code | VARCHAR(20) | One-time connection code |
| code_expires_at | DATETIME | Code expiration time |
| connected_at | DATETIME | When user connected |
| created_at | TIMESTAMP | Creation time |

**Indexes:**
- `UNIQUE(user_id)` — One connection per user
- `UNIQUE(chat_id)` — One user per Telegram account

### push_history Table
Logs all push notifications.

| Column | Type | Description |
|--------|------|-------------|
| id | INT AUTO_INCREMENT | Primary key |
| user_id | INT | Foreign key to users.id |
| message | TEXT | Message content |
| status | ENUM('success','failed') | Delivery status |
| sent_at | TIMESTAMP | When sent |

---

## File Structure

```
telegram-system/
├── config.php                  # Database + bot configuration
├── telegram_helper.php         # Telegram API helper functions
├── telegram_connect.php        # User connection page
├── webhook.php                 # Telegram webhook handler
├── send_user_push.php          # AJAX push endpoint
├── index.php                   # Admin dashboard
├── setup.sql                   # Database schema
└── README.md                   # This file
```

---

## Installation

### Prerequisites
- PHP 7.4+
- MySQL 5.7+
- Apache/Nginx
- SSL certificate (for webhooks)
- Telegram Bot Token (from @BotFather)

### Step 1: Create Database
```sql
CREATE DATABASE telegram_system;
USE telegram_system;
```

### Step 2: Run Schema
```bash
mysql -u root -p telegram_system < setup.sql
```

Or copy-paste `setup.sql` content into phpMyAdmin.

### Step 3: Configure config.php
```php
<?php
$conn = mysqli_connect("localhost", "root", "", "telegram_system");

define('BOT_TOKEN', 'YOUR_BOT_TOKEN_HERE');
define('BOT_USERNAME', 'YourBotUsername');
define('WEBHOOK_SECRET', 'your_random_secret_string');
define('TELEGRAM_API', 'https://api.telegram.org/bot');
?>
```

### Step 4: Create Users (Admin)
```sql
INSERT INTO users (name) VALUES 
('John Doe'),
('Jane Smith'),
('Bob Wilson');
```

### Step 5: Set Webhook
Open in browser:
```
https://api.telegram.org/bot<YOUR_BOT_TOKEN>/setWebhook?url=https://yoursite.com/webhook.php?secret=your_random_secret_string
```

Expected response:
```json
{"ok":true,"result":true,"description":"Webhook was set"}
```

---

## User Permission Flow

### Connection Process

```
┌─────────────────────────────────────────────────────────────────┐
│                    CONNECTION FLOW                                │
│                                                                  │
│  1. Admin creates user in database                               │
│     ↓                                                            │
│  2. User visits telegram_connect.php?user_id=X                   │
│     ↓                                                            │
│  3. User clicks "Generate Connection Code"                       │
│     ↓                                                            │
│  4. System generates: CONNECT-ABC123                             │
│     ↓                                                            │
│  5. User opens Telegram bot                                      │
│     ↓                                                            │
│  6. User sends: /start CONNECT-ABC123                            │
│     ↓                                                            │
│  7. System verifies code:                                        │
│     - Code exists?                                               │
│     - Not expired?                                               │
│     - Not used?                                                  │
│     - Belongs to correct user?                                   │
│     ↓                                                            │
│  8. System saves chat_id for that user                           │
│     ↓                                                            │
│  9. Code is cleared (one-time use)                               │
│     ↓                                                            │
│  10. User receives: "✅ Connected Successfully!"                 │
└─────────────────────────────────────────────────────────────────┘
```

### Authorization Check (Every Telegram Message)

```php
function verifyTelegramUserAuthorization($conn, $incomingChatId) {
    // Step 1: Find connection by chat_id
    $stmt = mysqli_prepare($conn, 
        "SELECT user_id FROM telegram_connections WHERE chat_id = ?"
    );
    mysqli_stmt_bind_param($stmt, "s", $incomingChatId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $connection = mysqli_fetch_assoc($result);
    
    // Step 2: If not found → reject
    if (!$connection) {
        return ['authorized' => false, 'error' => 'Not connected'];
    }
    
    // Step 3: Return user_id for data isolation
    return ['authorized' => true, 'user_id' => $connection['user_id']];
}
```

### Data Isolation (Push Notifications)

```php
function sendUserTelegramMessage($conn, $userId, $message) {
    // Step 1: Get ONLY this user's chat_id
    $stmt = mysqli_prepare($conn, 
        "SELECT chat_id FROM telegram_connections WHERE user_id = ? AND chat_id IS NOT NULL"
    );
    mysqli_stmt_bind_param($stmt, "i", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $connection = mysqli_fetch_assoc($result);
    
    // Step 2: If not connected → error
    if (!$connection) {
        return ['ok' => false, 'error' => 'User not connected'];
    }
    
    // Step 3: Send ONLY to this user's chat
    $sent = sendMessage($connection['chat_id'], $message);
    
    return ['ok' => $sent];
}
```

---

## Security Measures

### 1. One-Time Connection Codes
```php
// Code is cleared after successful connection
$stmt = mysqli_prepare($conn, 
    "UPDATE telegram_connections 
     SET chat_id = ?, connected_at = NOW(), connection_code = NULL 
     WHERE id = ?"
);
```

### 2. Code Expiration
```php
// Codes expire after 5 minutes
$expires = date('Y-m-d H:i:s', strtotime('+5 minutes'));

// Verification checks expiration
$stmt = mysqli_prepare($conn, 
    "SELECT id FROM telegram_connections 
     WHERE connection_code = ? AND code_expires_at > NOW()"
);
```

### 3. Chat ID Verification
```php
// Every Telegram message is verified
$auth = verifyTelegramUserAuthorization($conn, $chatId);

if (!$auth['authorized']) {
    sendMessage($chatId, "⛔️ Access Denied");
    exit;
}
```

### 4. One Chat Per User
```sql
UNIQUE KEY unique_chat (chat_id)
```
Prevents one Telegram account from being linked to multiple users.

### 5. One User Per Chat
```sql
UNIQUE KEY unique_user (user_id)
```
Prevents one user from having multiple Telegram accounts.

### 6. Prepared Statements
All database queries use prepared statements to prevent SQL injection.

### 7. Webhook Secret
```php
$secret = $_GET['secret'] ?? '';
if ($secret !== WEBHOOK_SECRET) {
    http_response_code(403);
    exit;
}
```

### 8. No Sensitive Data in Frontend
Bot tokens are never exposed in HTML or JavaScript.

---

## API Endpoints

### telegram_connect.php
**URL:** `telegram_connect.php?user_id=X`

**Purpose:** User connects their Telegram account

**Flow:**
1. User visits page with their `user_id`
2. Clicks "Generate Connection Code"
3. System generates unique code
4. User sends code to Telegram bot
5. System verifies and connects

### webhook.php
**URL:** `webhook.php?secret=YOUR_SECRET`

**Purpose:** Receives all Telegram updates

**Handles:**
- `/start CONNECTION_CODE` — Connect account
- `/status` — Check connection status
- `/help` — Show help
- All other commands — Requires authorization

### send_user_push.php
**URL:** `send_user_push.php` (POST only)

**Purpose:** Send push notification to specific user

**Parameters:**
```json
{
    "user_id": 1,
    "message": "Hello, this is a test notification!"
}
```

**Response:**
```json
{
    "ok": true
}
```

**Error Response:**
```json
{
    "ok": false,
    "error": "User not connected"
}
```

---

## Testing

### Local Testing (XAMPP)

1. **Start XAMPP** (Apache + MySQL)
2. **Create database** and run `setup.sql`
3. **Update config.php** with your bot token
4. **Create test users** in phpMyAdmin
5. **Test connection page:**
   ```
   http://localhost/telegram-system/telegram_connect.php?user_id=1
   ```
6. **Generate code** and send to your bot
7. **Test push:**
   ```
   http://localhost/telegram-system/index.php
   ```

### Webhook Testing (Local)

Use ngrok for local webhook testing:
```bash
ngrok http 80
```

Then set webhook:
```
https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://abc123.ngrok.io/webhook.php?secret=YOUR_SECRET
```

### Production Testing (Render)

1. **Deploy to Render**
2. **Set environment variables** (DB credentials)
3. **Set webhook URL**
4. **Test with real users**

---

## Bot Commands

| Command | Description | Auth Required |
|---------|-------------|---------------|
| `/start CONNECTION_CODE` | Connect Telegram account | No |
| `/status` | Check connection status | No |
| `/help` | Show available commands | No |
| Any other command | Execute authorized action | Yes |

---

## Troubleshooting

### Bot doesn't respond
- Check webhook is set correctly
- Verify `WEBHOOK_SECRET` matches
- Check server error logs

### "Invalid or Expired Code"
- Code expired after 5 minutes
- Code was already used
- Generate a new code

### "User not connected"
- User hasn't completed connection flow
- Chat ID not saved in database

### Messages not received
- Check `push_history` table for errors
- Verify bot token is correct
- Check Telegram API response

---

## FAQ

**Q: Can a user have multiple Telegram accounts?**
A: No. Each user can only connect one Telegram account.

**Q: Can one Telegram account be used by multiple users?**
A: No. Each Telegram account can only be linked to one user.

**Q: What happens if a code expires?**
A: User must generate a new code. Old codes are invalid.

**Q: Can I revoke a user's access?**
A: Yes. Delete their record from `telegram_connections` table.

**Q: Is the connection code secure?**
A: Yes. Codes are random, expire after 5 minutes, and can only be used once.

---

## License

MIT License — free to use and modify.

---

## Support

For issues, check:
1. Server error logs
2. `push_history` table for failed messages
3. Telegram API responses
