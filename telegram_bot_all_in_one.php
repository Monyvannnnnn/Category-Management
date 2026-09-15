<?php
/**
 * Telegram Bot All-In-One
 * 
 * A single reusable file for all Telegram bot operations:
 * - Send text messages
 * - Send photos with captions
 * - Send files (Excel, PDF, CSV, any)
 * - Push product reports
 * - Push inventory reports
 * - Generate and send Excel files
 * 
 * Usage:
 *   $bot = new TelegramBot($botToken, $chatId);
 *   $bot->message('Hello!');
 *   $bot->photo($imageUrl, 'Caption');
 *   $bot->file('/path/to/file.pdf', 'PDF Report');
 *   $bot->pushProduct($productData);
 *   $bot->pushSummary($summaryData);
 *   $bot->pushExcel($data, $headers, 'report.xlsx');
 * 
 * @author Your Name
 * @version 2.0.0
 */

class TelegramBot {
    
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
    
    // ========================================================
    // TEXT MESSAGES
    // ========================================================
    
    /**
     * Send text message
     * 
     * @param string $text Message text (HTML format)
     * @return array|bool
     */
    public function message($text) {
        return $this->api('sendMessage', [
            'chat_id' => $this->chatId,
            'text' => $text,
            'parse_mode' => 'HTML'
        ]);
    }
    
    // ========================================================
    // PHOTOS
    // ========================================================
    
    /**
     * Send photo with caption
     * 
     * @param string $photoUrl Public image URL or local path
     * @param string $caption  Message caption
     * @return array|bool
     */
    public function photo($photoUrl, $caption = '') {
        if (file_exists($photoUrl)) {
            $photo = new CURLFile(realpath($photoUrl), mime_content_type($photoUrl), basename($photoUrl));
        } else {
            $photo = $photoUrl;
        }
        
        return $this->api('sendPhoto', [
            'chat_id' => $this->chatId,
            'photo' => $photo,
            'caption' => $caption,
            'parse_mode' => 'HTML'
        ]);
    }
    
    // ========================================================
    // FILES (Excel, PDF, CSV, Any)
    // ========================================================
    
    /**
     * Send file/document
     * 
     * @param string $filePath Full path to file
     * @param string $caption  Message caption
     * @param string $filename Custom filename
     * @return array|bool
     */
    public function file($filePath, $caption = '', $filename = null) {
        if (!file_exists($filePath)) {
            return false;
        }
        
        $mime = function_exists('mime_content_type') ? @mime_content_type($filePath) : 'application/octet-stream';
        $name = $filename ?: basename($filePath);
        
        return $this->api('sendDocument', [
            'chat_id' => $this->chatId,
            'document' => new CURLFile(realpath($filePath), $mime, $name),
            'caption' => $caption,
            'parse_mode' => 'HTML'
        ]);
    }
    
    /**
     * Send Excel file
     * 
     * @param string $filePath Full path
     * @param string $caption  Caption
     * @return array|bool
     */
    public function excel($filePath, $caption = 'Excel Report') {
        return $this->file($filePath, $caption);
    }
    
    /**
     * Send PDF file
     * 
     * @param string $filePath Full path
     * @param string $caption  Caption
     * @return array|bool
     */
    public function pdf($filePath, $caption = 'PDF Report') {
        return $this->file($filePath, $caption);
    }
    
    /**
     * Send CSV file
     * 
     * @param string $filePath Full path
     * @param string $caption  Caption
     * @return array|bool
     */
    public function csv($filePath, $caption = 'CSV Report') {
        return $this->file($filePath, $caption);
    }
    
    // ========================================================
    // GENERATE & SEND EXCEL
    // ========================================================
    
    /**
     * Generate Excel from data and send
     * 
     * @param array  $data     Array of rows
     * @param array  $headers  Column headers
     * @param string $filename Output filename
     * @param string $caption  Message caption
     * @return array|bool
     */
    public function pushExcel($data, $headers, $filename = 'report.csv', $caption = 'Report') {
        $tempPath = sys_get_temp_dir() . '/' . $filename;
        
        $fp = fopen($tempPath, 'w');
        fputcsv($fp, $headers);
        
        foreach ($data as $row) {
            fputcsv($fp, $row);
        }
        
        fclose($fp);
        
        $result = $this->file($tempPath, $caption);
        @unlink($tempPath);
        
        return $result;
    }
    
    // ========================================================
    // PRODUCT REPORTS
    // ========================================================
    
    /**
     * Push single product
     * 
     * @param array $product Product data
     * @return array|bool
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
            return $this->photo($product['image'], $caption);
        }
        
        return $this->message($caption);
    }
    
    /**
     * Push low stock report
     * 
     * @param array $products Array of products
     * @return array|bool
     */
    public function pushLowStock($products) {
        $msg = "⚠️ <b>LOW STOCK WARNING</b>\n━━━━━━━━━━━━━━━━━━\n\n";
        
        if (empty($products)) {
            $msg .= "✅ No low stock items!";
            return $this->message($msg);
        }
        
        foreach ($products as $i => $p) {
            $msg .= ($i + 1) . ". 🔦 <b>" . htmlspecialchars($p['product_name'] ?? 'N/A') . "</b>\n"
                  . "   └ " . htmlspecialchars($p['product_code'] ?? 'N/A') . " | " . ($p['quantity'] ?? 0) . " units\n\n";
        }
        
        $msg .= "━━━━━━━━━━━━━━━━━━\n📊 Total: " . count($products) . " items";
        return $this->message($msg);
    }
    
    /**
     * Push out of stock report
     * 
     * @param array $products Array of products
     * @return array|bool
     */
    public function pushOutOfStock($products) {
        $msg = "🚫 <b>OUT OF STOCK</b>\n━━━━━━━━━━━━━━━━━━\n\n";
        
        if (empty($products)) {
            $msg .= "✅ All items in stock!";
            return $this->message($msg);
        }
        
        foreach ($products as $i => $p) {
            $msg .= ($i + 1) . ". ❌ <b>" . htmlspecialchars($p['product_name'] ?? 'N/A') . "</b>\n"
                  . "   └ " . htmlspecialchars($p['product_code'] ?? 'N/A') . "\n\n";
        }
        
        $msg .= "━━━━━━━━━━━━━━━━━━\n📊 Total: " . count($products) . " items";
        return $this->message($msg);
    }
    
    /**
     * Push inventory summary
     * 
     * @param array $summary Summary data
     * @return array|bool
     */
    public function pushSummary($summary) {
        $msg = "📊 <b>INVENTORY SUMMARY</b>\n"
             . "━━━━━━━━━━━━━━━━━━\n"
             . "📦 Products: " . ($summary['total_products'] ?? 0) . "\n"
             . "🔢 Stock: " . ($summary['total_stock'] ?? 0) . " units\n"
             . "💰 Value: $" . number_format((float)($summary['total_value'] ?? 0), 2) . "\n"
             . "🏷️ Categories: " . ($summary['total_categories'] ?? 0) . "\n"
             . "━━━━━━━━━━━━━━━━━━\n"
             . "🕐 " . date('d M Y │ H:i');
        
        return $this->message($msg);
    }
    
    /**
     * Push recently added
     * 
     * @param array $products Array of products
     * @return array|bool
     */
    public function pushRecentlyAdded($products) {
        $msg = "🆕 <b>RECENTLY ADDED</b>\n━━━━━━━━━━━━━━━━━━\n\n";
        
        if (empty($products)) {
            $msg .= "📭 No recent additions";
            return $this->message($msg);
        }
        
        foreach ($products as $i => $p) {
            $msg .= ($i + 1) . ". 📦 <b>" . htmlspecialchars($p['product_name'] ?? 'N/A') . "</b>\n"
                  . "   └ " . htmlspecialchars($p['product_code'] ?? 'N/A') . " | $" . number_format((float)($p['price'] ?? 0), 2) . "\n\n";
        }
        
        return $this->message($msg);
    }
    
    /**
     * Push recently updated
     * 
     * @param array $products Array of products
     * @return array|bool
     */
    public function pushRecentlyUpdated($products) {
        $msg = "✏️ <b>RECENTLY UPDATED</b>\n━━━━━━━━━━━━━━━━━━\n\n";
        
        if (empty($products)) {
            $msg .= "📭 No recent updates";
            return $this->message($msg);
        }
        
        foreach ($products as $i => $p) {
            $msg .= ($i + 1) . ". 📦 <b>" . htmlspecialchars($p['product_name'] ?? 'N/A') . "</b>\n"
                  . "   └ " . htmlspecialchars($p['product_code'] ?? 'N/A') . " | " . date('d/m H:i', strtotime($p['lastupdate'] ?? 'now')) . "\n\n";
        }
        
        return $this->message($msg);
    }
    
    /**
     * Push custom message
     * 
     * @param string $text Custom message
     * @return array|bool
     */
    public function pushCustom($text) {
        return $this->message($text);
    }
    
    // ========================================================
    // API REQUEST
    // ========================================================
    
    /**
     * Make API request to Telegram
     * 
     * @param string $method API method
     * @param array  $data   Request data
     * @return array|bool
     */
    private function api($method, $data) {
        $url = $this->apiBase . $this->botToken . '/' . $method;
        
        // Method 1: Standard cURL
        $result = $this->curl($url, $data);
        
        // Method 2: DNS Bypass if failed
        if ($result === false || strpos($result, '"ok":true') === false) {
            $result = $this->curl($url, $data, true);
        }
        
        if ($result !== false && strpos($result, '"ok":true') !== false) {
            return json_decode($result, true);
        }
        
        return false;
    }
    
    /**
     * cURL request
     * 
     * @param string $url       API URL
     * @param array  $data      POST data
     * @param bool   $dnsBypass Use DNS bypass
     * @return string|bool
     */
    private function curl($url, $data, $dnsBypass = false) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        if ($dnsBypass && defined('CURLOPT_RESOLVE')) {
            $ips = ['149.154.167.220', '149.154.167.198', '91.108.56.160'];
            foreach ($ips as $ip) {
                curl_setopt($ch, CURLOPT_RESOLVE, ["api.telegram.org:443:$ip"]);
                $result = curl_exec($ch);
                if ($result !== false && strpos($result, '"ok":true') !== false) {
                    curl_close($ch);
                    return $result;
                }
            }
        } else {
            $result = curl_exec($ch);
            curl_close($ch);
            return $result;
        }
        
        curl_close($ch);
        return false;
    }
}

// ============================================================
// USAGE EXAMPLES (Uncomment to test)
// ============================================================

/*
$bot = new TelegramBot('YOUR_BOT_TOKEN', 'YOUR_CHAT_ID');

// Text
$bot->message('Hello World!');

// Photo
$bot->photo('https://example.com/image.jpg', 'Product photo');

// Files
$bot->excel('/path/to/report.xlsx', 'Excel Report');
$bot->pdf('/path/to/invoice.pdf', 'Invoice');
$bot->csv('/path/to/data.csv', 'Data Export');

// Generate Excel
$headers = ['Code', 'Name', 'Price'];
$data = [['PRD001', 'iPhone', '$999']];
$bot->pushExcel($data, $headers, 'products.csv');

// Product Reports
$product = [
    'id' => 26,
    'product_code' => 'PRD063',
    'product_name' => 'LED Flashlight',
    'category_name' => 'Packaging',
    'price' => 18.50,
    'quantity' => 50,
    'image' => 'https://example.com/image.jpg',
    'lastupdate' => '2026-09-12 02:39:50'
];
$bot->pushProduct($product);

// Summary
$bot->pushSummary([
    'total_products' => 50,
    'total_stock' => 500,
    'total_value' => 25000,
    'total_categories' => 10
]);

// Low Stock
$bot->pushLowStock([
    ['product_name' => 'iPhone', 'product_code' => 'PRD001', 'quantity' => 3]
]);

// Custom
$bot->pushCustom('Hello from my system!');
*/

?>
