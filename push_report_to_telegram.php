<?php
/**
 * Push Report to Telegram
 * 
 * A reusable PHP file for pushing product reports to Telegram.
 * Can be used independently in any project.
 * 
 * Usage:
 *   $pusher = new TelegramReportPusher($botToken, $chatId);
 *   $pusher->pushProduct($productData);
 *   $pusher->pushLowStock($products);
 *   $pusher->pushSummary($summaryData);
 *   $pusher->pushHTMLReportCurrentPage($currentPageProducts);
 *   $pusher->pushHTMLReportAllPages($allProducts);
 * 
 * @author Your Name
 * @version 1.0.0
 */

class TelegramReportPusher {
    
    private $botToken;
    private $chatId;
    private $apiBase = 'https://api.telegram.org/bot';
    
    /**
     * Constructor
     * 
     * @param string $botToken Telegram Bot Token
     * @param string $chatId   Telegram Chat ID
     */
    public function __construct($botToken, $chatId) {
        $this->botToken = $botToken;
        $this->chatId = $chatId;
    }
    
    /**
     * Send text message
     * 
     * @param string $text Message text (HTML format)
     * @return array|bool Response or false on failure
     */
    public function sendMessage($text) {
        return $this->apiRequest('sendMessage', [
            'chat_id' => $this->chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ]);
    }
    
    /**
     * Send photo with caption
     * 
     * @param string $photoUrl Public image URL
     * @param string $caption  Message caption (HTML format)
     * @return array|bool Response or false on failure
     */
    public function sendPhoto($photoUrl, $caption) {
        return $this->apiRequest('sendPhoto', [
            'chat_id' => $this->chatId,
            'photo' => $photoUrl,
            'caption' => $caption,
            'parse_mode' => 'HTML'
        ]);
    }
    
    /**
     * Push single product details
     * 
     * @param array $product Product data (product_name, product_code, category_name, price, quantity, image)
     * @return array|bool Response or false on failure
     */
    public function pushProduct($product) {
        $name = htmlspecialchars($product['product_name'] ?? 'N/A');
        $code = htmlspecialchars($product['product_code'] ?? 'N/A');
        $id = htmlspecialchars($product['id'] ?? 'N/A');
        $category = htmlspecialchars($product['category_name'] ?? 'N/A');
        $price = number_format((float)($product['price'] ?? 0), 2);
        $qty = (int)($product['quantity'] ?? 0);
        $value = number_format((float)($product['price'] ?? 0) * $qty, 2);
        $updated = date('d M Y │ H:i', strtotime($product['lastupdate'] ?? 'now'));
        
        $caption = "🔦 <b>{$name}</b>\n"
                 . "━━━━━━━━━━━━━━━━━━\n"
                 . "┃ {$code} │ #{$id} │ {$category}\n"
                 . "┃ 💵 \${$price} │ 📦 {$qty} units\n"
                 . "┃ 💰 Value: \${$value}\n"
                 . "━━━━━━━━━━━━━━━━━━\n"
                 . "🕐 {$updated}\n"
                 . "📤 Manual Push";
        
        if (!empty($product['image'])) {
            return $this->sendPhoto($product['image'], $caption);
        }
        
        return $this->sendMessage($caption);
    }
    
    /**
     * Push low stock report
     * 
     * @param array $products Array of products with low stock
     * @return array|bool Response or false on failure
     */
    public function pushLowStock($products) {
        $msg = "⚠️ <b>LOW STOCK WARNING</b>\n"
             . "━━━━━━━━━━━━━━━━━━\n\n";
        
        if (empty($products)) {
            $msg .= "✅ No low stock items!";
            return $this->sendMessage($msg);
        }
        
        foreach ($products as $i => $p) {
            $name = htmlspecialchars($product['product_name'] ?? 'N/A');
            $code = htmlspecialchars($product['product_code'] ?? 'N/A');
            $qty = (int)($product['quantity'] ?? 0);
            
            $msg .= ($i + 1) . ". 🔦 <b>{$name}</b>\n"
                  . "   └ Code: {$code} | Stock: {$qty} units\n\n";
        }
        
        $msg .= "━━━━━━━━━━━━━━━━━━\n"
              . "📊 Total: " . count($products) . " items";
        
        return $this->sendMessage($msg);
    }
    
    /**
     * Push out of stock report
     * 
     * @param array $products Array of out of stock products
     * @return array|bool Response or false on failure
     */
    public function pushOutOfStock($products) {
        $msg = "🚫 <b>OUT OF STOCK REPORT</b>\n"
             . "━━━━━━━━━━━━━━━━━━\n\n";
        
        if (empty($products)) {
            $msg .= "✅ All items in stock!";
            return $this->sendMessage($msg);
        }
        
        foreach ($products as $i => $p) {
            $name = htmlspecialchars($product['product_name'] ?? 'N/A');
            $code = htmlspecialchars($product['product_code'] ?? 'N/A');
            
            $msg .= ($i + 1) . ". ❌ <b>{$name}</b>\n"
                  . "   └ Code: {$code}\n\n";
        }
        
        $msg .= "━━━━━━━━━━━━━━━━━━\n"
              . "📊 Total: " . count($products) . " items";
        
        return $this->sendMessage($msg);
    }
    
    /**
     * Push inventory summary
     * 
     * @param array $summary Summary data (total_products, total_stock, total_value, total_categories)
     * @return array|bool Response or false on failure
     */
    public function pushSummary($summary) {
        $products = (int)($summary['total_products'] ?? 0);
        $stock = (int)($summary['total_stock'] ?? 0);
        $value = number_format((float)($summary['total_value'] ?? 0), 2);
        $categories = (int)($summary['total_categories'] ?? 0);
        
        $msg = "📊 <b>INVENTORY SUMMARY</b>\n"
             . "━━━━━━━━━━━━━━━━━━\n"
             . "📦 Products: {$products}\n"
             . "🔢 Total Stock: {$stock} units\n"
             . "💰 Total Value: \${$value}\n"
             . "🏷️ Categories: {$categories}\n"
             . "━━━━━━━━━━━━━━━━━━\n"
             . "🕐 " . date('d M Y │ H:i');
        
        return $this->sendMessage($msg);
    }
    
    /**
     * Push recently added items
     * 
     * @param array $products Array of recently added products
     * @return array|bool Response or false on failure
     */
    public function pushRecentlyAdded($products) {
        $msg = "🆕 <b>RECENTLY ADDED</b>\n"
             . "━━━━━━━━━━━━━━━━━━\n\n";
        
        if (empty($products)) {
            $msg .= "📭 No recent additions";
            return $this->sendMessage($msg);
        }
        
        foreach ($products as $i => $p) {
            $name = htmlspecialchars($product['product_name'] ?? 'N/A');
            $code = htmlspecialchars($product['product_code'] ?? 'N/A');
            $price = number_format((float)($product['price'] ?? 0), 2);
            
            $msg .= ($i + 1) . ". 📦 <b>{$name}</b>\n"
                  . "   └ {$code} | \${$price}\n\n";
        }
        
        return $this->sendMessage($msg);
    }
    
    /**
     * Push recently updated items
     * 
     * @param array $products Array of recently updated products
     * @return array|bool Response or false on failure
     */
    public function pushRecentlyUpdated($products) {
        $msg = "✏️ <b>RECENTLY UPDATED</b>\n"
             . "━━━━━━━━━━━━━━━━━━\n\n";
        
        if (empty($products)) {
            $msg .= "📭 No recent updates";
            return $this->sendMessage($msg);
        }
        
        foreach ($products as $i => $p) {
            $name = htmlspecialchars($product['product_name'] ?? 'N/A');
            $code = htmlspecialchars($product['product_code'] ?? 'N/A');
            $updated = date('d/m H:i', strtotime($product['lastupdate'] ?? 'now'));
            
            $msg .= ($i + 1) . ". 📦 <b>{$name}</b>\n"
                  . "   └ {$code} | {$updated}\n\n";
        }
        
        return $this->sendMessage($msg);
    }
    
    /**
     * Push HTML Report File
     * 
     * @param array  $products Array of products for HTML report
     * @param string $title    Report Title
     * @param string $scope    Report Scope ('all' or 'current')
     * @return array|bool Response or false on failure
     */
    public function pushHTMLReport($products, $title = 'INVENTORY REPORT', $scope = 'all') {
        require_once __DIR__ . '/push_file_to_telegram.php';
        $filePusher = new TelegramFilePusher($this->botToken, $this->chatId);
        $scopeTag = ($scope === 'current') ? ' (CURRENT PAGE)' : ' (ALL PAGES)';
        $filePrefix = ($scope === 'current') ? 'inventory_report_current_page_' : 'inventory_report_all_pages_';
        return $filePusher->generateAndSendHTML(
            $products, 
            $title . $scopeTag, 
            $filePrefix . date('Ymd_His') . '.html', 
            "🌐 <b>{$title}{$scopeTag} (.HTML File)</b>"
        );
    }

    /**
     * Push HTML Report File for Current Page items
     * 
     * @param array  $products Array of products visible on current page
     * @param string $title    Report Title
     * @return array|bool Response or false on failure
     */
    public function pushHTMLReportCurrentPage($products, $title = 'INVENTORY REPORT') {
        return $this->pushHTMLReport($products, $title, 'current');
    }

    /**
     * Push HTML Report File for All Pages items
     * 
     * @param array  $products Array of all inventory products
     * @param string $title    Report Title
     * @return array|bool Response or false on failure
     */
    public function pushHTMLReportAllPages($products, $title = 'INVENTORY REPORT') {
        return $this->pushHTMLReport($products, $title, 'all');
    }

    /**
     * Push custom message
     * 
     * @param string $message Custom message text
     * @return array|bool Response or false on failure
     */
    public function pushCustom($message) {
        return $this->sendMessage($message);
    }
    
    /**
     * Make API request to Telegram
     * 
     * @param string $method API method name
     * @param array  $data   Request data
     * @return array|bool Response or false on failure
     */
    private function apiRequest($method, $data) {
        $url = $this->apiBase . $this->botToken . '/' . $method;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        
        $result = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($result === false) {
            error_log("Telegram API Error: {$error}");
            return false;
        }
        
        return json_decode($result, true);
    }
}

// ============================================================
// STANDALONE USAGE EXAMPLE (Uncomment to test)
// ============================================================

/*
// Configuration
$botToken = 'YOUR_BOT_TOKEN_HERE';
$chatId = 'YOUR_CHAT_ID_HERE';

// Initialize pusher
$pusher = new TelegramReportPusher($botToken, $chatId);

// Example: Push single product
$product = [
    'id' => 26,
    'product_code' => 'PRD063',
    'product_name' => 'LED Flashlight',
    'category_name' => 'Packaging',
    'price' => 18.50,
    'quantity' => 50,
    'image' => 'https://example.com/image.jpg', // Optional
    'lastupdate' => '2026-09-12 02:39:50'
];
$pusher->pushProduct($product);

// Example: Push low stock report
$lowStock = [
    ['product_name' => 'iPhone 15', 'product_code' => 'PRD001', 'quantity' => 3],
    ['product_name' => 'MacBook Pro', 'product_code' => 'PRD002', 'quantity' => 1],
];
$pusher->pushLowStock($lowStock);

// Example: Push summary
$summary = [
    'total_products' => 50,
    'total_stock' => 500,
    'total_value' => 25000,
    'total_categories' => 10
];
$pusher->pushSummary($summary);

// Example: Push HTML Report file (Current Page & All Pages)
$sampleData = [
    ['id' => 1, 'product_code' => 'PRD001', 'product_name' => 'iPhone 15', 'category_name' => 'Phones', 'price' => 999, 'quantity' => 10],
];
$pusher->pushHTMLReportCurrentPage($sampleData, 'INVENTORY REPORT');
$pusher->pushHTMLReportAllPages($sampleData, 'INVENTORY REPORT');

// Example: Push custom message
$pusher->pushCustom('Hello from my system!');
*/

?>
