# Telegram File Push Guide (Excel, PDF, HTML, Image)

This document explains how Excel (`.xlsx`/`.xls`), PDF (`.pdf`), HTML (`.html`), and Image (`.jpg`/`.png`/`.webp`) files are pushed to Telegram in this project (`Inventory`), and how you can trigger these functions from any script or controller.

---

## 1. Architecture & Telegram API Endpoints Used

| File Category | File Extensions | Telegram API Method | PHP Handler Function |
|---|---|---|---|
| **Excel Documents** | `.xlsx`, `.xls`, `.csv` | `sendDocument` | `sendTelegramDocument()` |
| **PDF Documents** | `.pdf` | `sendDocument` | `sendTelegramDocument()` |
| **HTML Web Reports** | `.html`, `.htm` | `sendDocument` | `sendTelegramDocument()` |
| **Images / Screenshots** | `.jpg`, `.jpeg`, `.png`, `.webp` | `sendPhoto` | `sendTelegramPhotoNotification()` |

---

## 2. Helper Functions (`notify_bot.php` & `push_file_to_telegram.php`)

All Telegram pushing logic is located in `notify_bot.php` and `push_file_to_telegram.php`.

### A. Push Document (Excel, PDF, HTML, CSV)
```php
require_once "database.php";
require_once "notify_bot.php";

$filePath = "/path/to/inventory_report.html"; // Local HTML report file path
$caption = "🌐 <b>INVENTORY HTML REPORT</b>\n<i>Generated: " . date('Y-m-d H:i:s') . "</i>";
$userId = 1; // Connected website user ID (0 for all subscribers)
$customFileName = "Custom_Report_Name.html"; // Optional custom file name shown in Telegram

$responseJson = sendTelegramDocument($filePath, $caption, $conn, $userId, $customFileName);
$result = json_decode($responseJson, true);

if (isset($result['ok']) && $result['ok'] === true) {
    echo "HTML report file sent to Telegram successfully!";
} else {
    echo "Failed: " . ($result['description'] ?? $result['message']);
}
```

### B. OOP File Pusher Class (`push_file_to_telegram.php`)
```php
require_once "push_file_to_telegram.php";

$pusher = new TelegramFilePusher($botToken, $chatId);

// Send existing HTML file
$pusher->sendHTML('/path/to/report.html', '🌐 Interactive Web Report');

// Generate HTML report on the fly from data array and send to Telegram
$products = [
    ['id' => 1, 'product_code' => 'PRD001', 'product_name' => 'Laptop', 'category_name' => 'Electronics', 'price' => 1200, 'quantity' => 10],
];
$pusher->generateAndSendHTML($products, 'INVENTORY SUMMARY REPORT', 'inventory.html', '🌐 Inventory HTML Report');
```

### C. Push Image / Photo
```php
require_once "database.php";
require_once "notify_bot.php";

$photoPath = "/path/to/product_preview.jpg"; // Local path or URL
$caption = "🖼️ <b>PRODUCT PREVIEW</b>\nName: Wireless Keyboard\nPrice: $25.00";
$userId = 1;

$responseJson = sendTelegramPhotoNotification($caption, $photoPath, $conn, $userId);
```

### D. Direct Push using Custom Bot Token and Chat ID
If you want to bypass the database user lookup and send to a specific Chat ID directly:
```php
require_once "notify_bot.php";

$chatId = "123456789"; // Target Telegram Chat ID
$botToken = "YOUR_BOT_TOKEN"; // Bot Token

// Send HTML report document directly:
$res1 = sendSingleTelegramDocument($chatId, "/path/to/report.html", "🌐 HTML Report", $botToken, "report.html");

// Send Image directly:
$res2 = sendSingleTelegramPhoto($chatId, "/path/to/image.jpg", "🖼️ Image Preview", $botToken);
```

---

## 3. Manual Push Endpoints (`manual_push.php`)

You can send AJAX `POST` requests to `manual_push.php` from your frontend JavaScript or cURL:

### Push Excel Report (`action=push_excel_file`)
- **Parameters**: `action=push_excel_file`, `scope=all` (or `scope=current` with `ids=1,2,3`)
- **Direct Upload**: Pass `FormData` with key `file` containing an `.xlsx` file to forward an existing file.

### Push PDF Report (`action=push_pdf_file`)
- **Parameters**: `action=push_pdf_file`, `scope=all`
- **Direct Upload**: Pass `FormData` with key `file` containing a `.pdf` file.

### Push HTML Web Report (`action=push_html_file`)
- **Parameters**: `action=push_html_file`, `scope=all` (or `scope=current` with `ids=1,2,3`)
- **Direct Upload**: Pass `FormData` with key `file` containing an `.html` file.

### Push Image / Screenshot (`action=push_image_file`)
- **Parameters**: Pass `FormData` with `action=push_image_file` and key `file` containing the `.jpg`/`.png` binary or blob.

---

## 4. Frontend Integration Examples (JavaScript / jQuery)

### Push Generated HTML Report via AJAX
```javascript
$.ajax({
    url: "manual_push.php",
    type: "POST",
    data: {
        action: "push_html_file",
        scope: "all"
    },
    dataType: "json",
    success: function(res) {
        if (res.ok) {
            alert("HTML report pushed to Telegram successfully!");
        } else {
            alert("Error: " + res.message);
        }
    }
});
```

### Push Uploaded Image / File via FormData
```javascript
let formData = new FormData();
formData.append("action", "push_html_file");
formData.append("file", fileInput.files[0]); // <input type="file" id="fileInput">

$.ajax({
    url: "manual_push.php",
    type: "POST",
    data: formData,
    processData: false,
    contentType: false,
    dataType: "json",
    success: function(res) {
        if (res.ok) {
            alert("HTML File sent to Telegram!");
        }
    }
});
```
