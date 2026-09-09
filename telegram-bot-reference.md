# Telegram Bot Integration (Inventory Project)

The user's Inventory Management System includes a Telegram bot with 19 commands for remote inventory access. This reference documents the architecture and commands.

## Architecture

Two methods to receive Telegram messages:

### Webhook (Production — Render)
- File: `set_commands.php`
- Telegram POSTs updates to this URL
- Set via: `https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://YOUR-URL.onrender.com/set_commands.php`

### Poller (Local Testing — XAMPP)
- File: `bot_poller.php`
- Runs in a loop, asks Telegram for new messages every 2 seconds
- Run: `php bot_poller.php` (loop with `timeout /t 2`)

## Command Processing Flow

1. Extract command + argument from message text
2. Match in `switch ($command)` statement
3. Query database with prepared statements
4. Format HTML response with emoji/icons
5. Send via `sendTelegramMessage($chatId, $text, $botToken)` using Telegram Bot API

## Registered Commands (19 total)

| Category | Commands |
|---|---|
| Search | `/search`, `/searchall` |
| Categories | `/categories`, `/category <code>` |
| Products | `/product <code>`, `/sort`, `/lowstock`, `/topstock`, `/outofstock` |
| Reports | `/summary`, `/valuation`, `/today` |
| Activity | `/added`, `/updated`, `/history` |
| Actions | `/push <code>`, `/toggle auto`, `/chatid` |
| Help | `/help` |

### Command Details

| Command | Description | DB Query |
|---|---|---|
| `/search <keyword>` | Search products (top 5) | `WHERE name/code/category LIKE ? LIMIT 5` |
| `/searchall <keyword>` | Search all matching products | `WHERE name/code/category LIKE ?` |
| `/categories` | List all categories with product counts | `LEFT JOIN product GROUP BY category` |
| `/category <code>` | Category details + all products inside | Exact match on `category_code` |
| `/product <code>` | Full product details | Exact match on `product_code` |
| `/sort [price\|stock\|date]` | Sort products | `ORDER BY price/quantity/created_at` |
| `/lowstock` | Items with qty ≤ 5 | `WHERE quantity <= 5` |
| `/topstock` | Top 10 highest stock | `ORDER BY quantity DESC LIMIT 10` |
| `/outofstock` | Items with 0 units | `WHERE quantity = 0` |
| `/summary` | Executive dashboard (totals) | `COUNT, SUM, AVG` aggregations |
| `/valuation` | Financial report (min/max/avg) | `COUNT, SUM, AVG, MIN, MAX` |
| `/added` | Recently added products/categories | `ORDER BY id DESC LIMIT 5` |
| `/updated` | Recently modified products/categories | `ORDER BY lastupdate DESC LIMIT 5` |
| `/history` | Combined activity log (10 products + 5 categories) | Combined added + updated queries |
| `/today` | Today's activity summary | `DATE(created_at) = CURDATE()` counts |
| `/push <code>` | Push specific item to Telegram | Exact match on product_code or category_code |
| `/chatid` | Get current chat ID | No DB query |
| `/toggle auto` | Toggle auto-notifications | `system_settings` table |
| `/help` | Show all commands | No DB query |

## BotFather Registration

Commands are auto-registered on every page load via `registerBotCommands($botToken)`. Manual registration via BotFather: `/setcommands` then paste command list.

### BotFather Command List (copy-paste ready)

```
search - 🔍 Search product by name or code (/search <keyword>)
searchall - 🔍 Search all records (/searchall <keyword>)
categories - 🏷️ View all item categories
category - 🏷️ View category detail (/category <code>)
sort - ↕️ View items sorted (/sort price | stock | date)
lowstock - ⚠️ List critical items with stock <= 5
topstock - 📊 Top 10 highest stock items
product - 📦 View product detail (/product <code>)
outofstock - 🚫 View out of stock items (0 units)
summary - 📊 Real-time total categories, products, & valuation
valuation - 💎 Financial report and average pricing breakdown
added - 🆕 List recently added products and categories
updated - ✏️ List recently modified products and categories
history - 📜 Combined activity log
today - 📅 Today's activity summary
chatid - 💬 Get your chat ID
push - 📤 Push item to Telegram (/push <code>)
toggle - 🔔 Toggle auto-notify (/toggle auto)
help - ❓ Show all command usage and examples
```

## Manual Push (Website → Telegram)

The "Telegram Notification Hub" modal (in `index.php` and `products.php`) has push buttons:

| Button | Color | manual_push.php action |
|---|---|---|
| Recently Added | Green | `push_added` |
| Recently Updated | Amber | `push_updated` |
| Low Stock Warning | Red | `push_low_stock` |
| Out of Stock | Dark/Black | `push_out_of_stock` |
| Valuation Report | Purple | `push_valuation` |
| Full Summary | Blue | `push_summary` |
| Custom Message | Cyan | `custom` |

Each button sends AJAX POST to `manual_push.php` with the appropriate action.

### Push Modal UI (2-column grid layout)

The push buttons use a compact 2-column grid design:
- Icon + title + short description per button
- Color-coded gradient icons (`.report-icon`)
- Custom message is full-width with inline input + send button

## Key Files

| File | Purpose |
|---|---|
| `set_commands.php` | Webhook endpoint (production) |
| `bot_poller.php` | Poller endpoint (local testing) |
| `bot_poller_daemon.php` | Alternative poller version |
| `manual_push.php` | Manual push notification handler |
| `notify_bot.php` | Telegram notification functions (auto-notify on CRUD) |
| `db_config.php` | Multi-environment DB configuration |
| `telegram_offset.txt` | Stores last poller offset |

## Database Tables Used

| Table | Commands |
|---|---|
| `product` | `/search`, `/searchall`, `/product`, `/sort`, `/lowstock`, `/topstock`, `/outofstock`, `/push`, `/summary`, `/valuation`, `/added`, `/updated`, `/history`, `/today` |
| `category` | `/categories`, `/category`, `/push`, `/added`, `/updated`, `/history`, `/today` |
| `system_settings` | `/toggle auto` (stores `auto_telegram_notify` setting) |

## Environment Variables (Render)

| Variable | Purpose |
|---|---|
| `DB_HOST` | Database host |
| `DB_USER` | Database username |
| `DB_PASS` | Database password |
| `DB_NAME` | Database name |
| `DB_PORT` | Database port |

## Telegram API Calls Used

| API Call | Purpose | Method |
|---|---|---|
| `sendMessage` | Send response to user | POST |
| `getUpdates` | Poll for new messages (poller only) | GET |
| `setWebhook` | Tell Telegram your webhook URL | POST |
| `setMyCommands` | Register commands in BotFather | POST |

## Important Notes

- Only Bot token (Bot ID) is configured in PHP files; hardcoded Chat IDs have been removed
- Any Telegram user sending commands or `/start` is automatically registered to use the bot and receive notifications
- All queries use prepared statements for security
- Output is escaped with `htmlspecialchars()`
- Prices formatted with `number_format($val, 2)`
- Dates formatted with `date('d/m/Y H:i', strtotime($col))`
- The bot uses HTML parse mode for Telegram formatting
- The poller saves offset to `telegram_offset.txt` to avoid reprocessing
- `manual_push.php` has a `push_out_of_stock` handler for the Out of Stock button
- The quantity editor now allows 0 (out of stock) with `min: 0` validation

## Testing Commands Locally

```bash
cd D:\xammp\htdocs\Inventory
:loop
php bot_poller.php
timeout /t 2 /nobreak >nul
goto loop
```

Then send any command to your bot via Telegram — the poller will pick it up.
