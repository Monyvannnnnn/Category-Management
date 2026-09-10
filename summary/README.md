# Inventory Management System

A full-stack inventory management web application with Telegram bot integration, built with PHP, MySQL, jQuery, and DevExtreme DataGrid.

---

## Features

### Web Application
- **Category Management** — Add, edit, delete, and view product categories
- **Product Management** — Full CRUD for products with category assignment
- **DataGrid** — Sortable, searchable, paginated data tables (DevExtreme)
- **Export** — PDF, Excel, CSV export functionality
- **Telegram Integration** — Push notifications and manual reports

### Telegram Bot (16 Commands)
| Command | Description |
|---|---|
| `/search <keyword>` | Search products (top 5) |
| `/searchall <keyword>` | Search all matching products |
| `/categories` | List all categories |
| `/category <code>` | View category details + products |
| `/sort [price\|stock\|date]` | Sort products |
| `/lowstock` | View low stock alerts (qty ≤ 5) |
| `/topstock` | Top 10 highest stock items |
| `/product <code>` | View product details |
| `/summary` | Executive dashboard |
| `/valuation` | Financial valuation report |
| `/added` | Recently added items |
| `/updated` | Recently modified items |
| `/history` | Combined activity log |
| `/push <code>` | Push item to Telegram |
| `/toggle auto` | Toggle auto-notifications |
| `/help` | Show all commands |

---

## Tech Stack

| Layer | Technology |
|---|---|
| **Backend** | PHP 8.x |
| **Database** | MySQL / MariaDB |
| **Frontend** | HTML5, CSS3, jQuery |
| **UI Components** | DevExtreme DataGrid |
| **Icons** | Font Awesome 6 |
| **Telegram** | Telegram Bot API |
| **Deployment** | Render (Docker) |

---

## Project Structure

```
Inventory/
├── index.php              # Category management page
├── products.php           # Product management page
├── create.php             # Create category/product
├── edit.php               # Edit category/product
├── delete.php             # Delete category/product
├── database.php           # Database connection + auto-init tables
├── db_config.php          # Multi-environment DB configuration
├── bot_poller.php         # Telegram bot (poller version)
├── set_commands.php       # Telegram bot (webhook version)
├── manual_push.php        # Manual push notification handler
├── notify_bot.php         # Telegram notification functions
├── export_pdf.php         # PDF export
├── style.css              # Custom styles
├── categories.sql         # Database schema + seed data
├── render.yaml            # Render deployment config
├── Dockerfile             # Docker configuration
├── docker-compose.yml     # Local Docker setup
├── entrypoint.sh          # Docker entrypoint script
├── js/
│   ├── app.js             # Custom JavaScript
│   └── KhmerOSSiemreap.js # Khmer font for PDF export
├── css/
│   └── style.css          # Stylesheet
├── font/                  # PDF fonts
└── scripts/               # Additional scripts
```

---

## Setup

### Local Development (XAMPP)

1. **Copy files to htdocs:**
   ```
   C:\xampp\htdocs\Inventory\
   ```

2. **Start XAMPP:**
   - Apache
   - MySQL (port 3307)

3. **Create database:**
   - Open `http://localhost:3307/phpmyadmin`
   - Create database: `inventory`

4. **Configure `db_config.php`:**
   ```php
   // Local environment auto-detected — no changes needed
   ```

5. **Open in browser:**
   ```
   http://localhost/Inventory/index.php
   ```

### Docker (Local)

```bash
docker-compose up --build
```

---

## Database Configuration

The app supports multiple environments automatically:

| Environment | Detection | Database |
|---|---|---|
| **Local (XAMPP)** | `localhost` or `127.0.0.1` | MySQL on port 3307 |
| **Docker** | `entrypoint.sh` exists | MySQL on port 3306 |
| **Render** | `DB_HOST` env var set | External MySQL/PostgreSQL |
| **InfinityFree** | Remote hosting fallback | External MySQL |

### Manual Configuration

Edit `db_config.php`:

```php
// For external MySQL (PlanetScale, InfinityFree, etc.)
return [
    "host" => "your-host.com",
    "user" => "your-username",
    "pass" => "your-password",
    "name" => "your-database",
    "port" => 3306
];
```

---

## Telegram Bot Setup

### 1. Create Bot
1. Open Telegram → search **@BotFather**
2. Send `/newbot`
3. Follow instructions → get bot token

### 2. Set Webhook (Production)
```
https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://YOUR-URL.onrender.com/set_commands.php
```

### 3. Register Commands
Send to **@BotFather**:
```
/setcommands
```

Then paste:
```
search - Search product by name or code
searchall - Search all matching products
categories - List all categories
category - View category detail
sort - Sort products
lowstock - Low stock alerts
topstock - Top 10 highest stock
product - View product details
summary - Executive dashboard
valuation - Financial report
added - Recently added items
updated - Recently modified items
history - Combined activity log
push - Push item to Telegram
toggle - Toggle auto-notify
help - Show all commands
```

### 4. Local Testing (Poller)
```bash
cd Inventory
:loop
php bot_poller.php
timeout /t 2 /nobreak >nul
goto loop
```

---

## Deployment (Render)

### 1. Push to GitHub
```bash
git init
git add .
git commit -m "Initial commit"
git remote add origin https://github.com/YOUR_USERNAME/inventory.git
git push -u origin main
```

### 2. Create Render Service
1. Go to **https://dashboard.render.com**
2. Click **New +** → **Web Service**
3. Connect your GitHub repo
4. Choose **Docker** environment
5. Set environment variables:
   ```
   DB_HOST=your-mysql-host
   DB_USER=your-username
   DB_PASS=your-password
   DB_NAME=your-database
   DB_PORT=3306
   ```

### 3. Create Database (Optional)
- Render PostgreSQL (free, expires 90 days)
- Or use external MySQL (PlanetScale, InfinityFree)

---

## Environment Variables

| Variable | Description | Example |
|---|---|---|
| `DB_HOST` | Database host | `sql310.infinityfree.com` |
| `DB_USER` | Database username | `if0_42693065` |
| `DB_PASS` | Database password | `your-password` |
| `DB_NAME` | Database name | `inventory` |
| `DB_PORT` | Database port | `3306` |

---

## API Endpoints

### DataGrid (AJAX)
| URL | Method | Description |
|---|---|---|
| `index.php?action=read` | GET | Get all categories |
| `products.php?action=read` | GET | Get all products |
| `products.php?action=get_categories` | GET | Get categories list |

### Telegram Bot
| URL | Method | Description |
|---|---|---|
| `set_commands.php` | POST | Webhook endpoint |
| `bot_poller.php` | GET | Poller endpoint |
| `manual_push.php` | POST | Manual push handler |

---

## Security Notes

⚠️ **Important:**
- Change default database passwords
- Use environment variables for secrets
- Don't commit `.env` files to git
- Rotate Telegram bot tokens if exposed

---

## License

MIT License — free to use and modify.

---

## Author

**Chhourn CryMunyvann**
- Telegram: @ChhournCryMunyvann
- Project: Inventory Management System
