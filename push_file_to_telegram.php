<?php
/**
 * Push Excel & PDF Files to Telegram
 * 
 * A reusable PHP class for sending Excel and PDF files to Telegram.
 * Can be used independently in any project.
 * 
 * Usage:
 *   $filePusher = new TelegramFilePusher($botToken, $chatId);
 *   $filePusher->sendExcel($filePath, 'Product Report');
 *   $filePusher->sendPDF($filePath, 'Inventory Report');
 *   $filePusher->sendFile($filePath, 'Custom caption', 'custom_name.pdf');
 * 
 * @author Your Name
 * @version 1.0.0
 */

class TelegramFilePusher {
    
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
     * Send Excel file to Telegram
     * 
     * @param string $filePath Full path to Excel file
     * @param string $caption  Message caption (optional)
     * @return array|bool Response or false on failure
     */
    public function sendExcel($filePath, $caption = 'Excel Report') {
        $filename = basename($filePath);
        return $this->sendDocument($filePath, $caption, $filename);
    }
    
    /**
     * Send PDF file to Telegram
     * 
     * @param string $filePath Full path to PDF file
     * @param string $caption  Message caption (optional)
     * @return array|bool Response or false on failure
     */
    public function sendPDF($filePath, $caption = 'PDF Report') {
        $filename = basename($filePath);
        return $this->sendDocument($filePath, $caption, $filename);
    }
    
    /**
     * Send CSV file to Telegram
     * 
     * @param string $filePath Full path to CSV file
     * @param string $caption  Message caption (optional)
     * @return array|bool Response or false on failure
     */
    public function sendCSV($filePath, $caption = 'CSV Report') {
        $filename = basename($filePath);
        return $this->sendDocument($filePath, $caption, $filename);
    }
    
    /**
     * Send HTML file to Telegram
     * 
     * @param string $filePath Full path to HTML file
     * @param string $caption  Message caption (optional)
     * @return array|bool Response or false on failure
     */
    public function sendHTML($filePath, $caption = 'HTML Report') {
        $filename = basename($filePath);
        return $this->sendDocument($filePath, $caption, $filename);
    }
    
    /**
     * Send any file to Telegram
     * 
     * @param string $filePath Full path to file
     * @param string $caption  Message caption (optional)
     * @param string $filename Custom filename (optional)
     * @return array|bool Response or false on failure
     */
    public function sendFile($filePath, $caption = '', $filename = null) {
        return $this->sendDocument($filePath, $caption, $filename);
    }
    
    /**
     * Send document via Telegram API
     * 
     * @param string $filePath Full path to file
     * @param string $caption  Message caption
     * @param string $filename Custom filename
     * @return array|bool Response or false on failure
     */
    private function sendDocument($filePath, $caption = '', $filename = null) {
        // Check if file exists
        if (!file_exists($filePath)) {
            error_log("Telegram File Push Error: File not found - {$filePath}");
            return false;
        }
        
        $url = $this->apiBase . $this->botToken . '/sendDocument';
        
        // Get mime type
        $mimeType = function_exists('mime_content_type') 
            ? @mime_content_type($filePath) 
            : 'application/octet-stream';
        
        if (!$mimeType) {
            $mimeType = 'application/octet-stream';
        }
        
        // Use custom filename or original
        $sendFileName = $filename ?: basename($filePath);
        
        // Create CURLFile
        $cFile = new CURLFile(realpath($filePath), $mimeType, $sendFileName);
        
        $postData = [
            'chat_id' => $this->chatId,
            'document' => $cFile,
            'caption' => $caption,
            'parse_mode' => 'HTML'
        ];
        
        // Method 1: Standard cURL
        $result = $this->curlPost($url, $postData);
        
        // Method 2: DNS Bypass if first fails
        if ($result === false || strpos($result, '"ok":true') === false) {
            $result = $this->curlPost($url, $postData, true);
        }
        
        if ($result !== false && strpos($result, '"ok":true') !== false) {
            return json_decode($result, true);
        }
        
        error_log("Telegram File Push Error: Failed to send document");
        return false;
    }
    
    /**
     * Make cURL POST request
     * 
     * @param string $url       API URL
     * @param array  $postData  POST data
     * @param bool   $dnsBypass Use DNS bypass
     * @return string|bool Response or false on failure
     */
    private function curlPost($url, $postData, $dnsBypass = false) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        if ($dnsBypass && defined('CURLOPT_RESOLVE')) {
            $telegramIPs = ['149.154.167.220', '149.154.167.198', '91.108.56.160'];
            foreach ($telegramIPs as $ip) {
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
    
    /**
     * Generate Excel file and send
     * 
     * @param array  $data     Array of data rows
     * @param array  $headers  Column headers
     * @param string $filename Output filename
     * @param string $caption  Message caption
     * @return array|bool Response or false on failure
     */
    public function generateAndSendExcel($data, $headers, $filename = 'report.xlsx', $caption = 'Excel Report') {
        $tempPath = sys_get_temp_dir() . '/' . $filename;
        
        // Generate CSV content (simple format)
        $fp = fopen($tempPath, 'w');
        
        // Write headers
        fputcsv($fp, $headers);
        
        // Write data rows
        foreach ($data as $row) {
            fputcsv($fp, $row);
        }
        
        fclose($fp);
        
        $result = $this->sendExcel($tempPath, $caption);
        
        // Clean up temp file
        @unlink($tempPath);
        
        return $result;
    }
    
    /**
     * Generate PDF file and send (requires external library)
     * 
     * @param string $htmlContent HTML content for PDF
     * @param string $filename    Output filename
     * @param string $caption     Message caption
     * @return array|bool Response or false on failure
     */
    public function generateAndSendPDF($htmlContent, $filename = 'report.pdf', $caption = 'PDF Report') {
        $tempPath = sys_get_temp_dir() . '/' . $filename;
        
        // Note: Requires TCPDF, FPDF, or Dompdf library
        // This is a placeholder - implement with your preferred PDF library
        
        // Example with Dompdf:
        // use Dompdf\Dompdf;
        // $dompdf = new Dompdf();
        // $dompdf->loadHtml($htmlContent);
        // $dompdf->render();
        // file_put_contents($tempPath, $dompdf->output());
        
        // For now, return false
        error_log("PDF generation requires Dompdf or TCPDF library");
        return false;
    }

    /**
     * Generate HTML report file and send to Telegram
     * 
     * @param array  $rows     Array of product data rows
     * @param string $title    Report title
     * @param string $filename Output filename
     * @param string $caption  Message caption
     * @return array|bool Response or false on failure
     */
    public function generateAndSendHTML($rows, $title = 'INVENTORY REPORT', $filename = 'report.html', $caption = '📄 HTML Report') {
        require_once __DIR__ . '/includes/html_generator.php';
        $tempPath = sys_get_temp_dir() . '/' . $filename;

        $totalVal = 0;
        foreach ($rows as $r) {
            $totalVal += ((float)($r['price'] ?? 0) * (int)($r['quantity'] ?? 0));
        }

        $htmlGen = new InventoryHTML();
        $htmlContent = $htmlGen->generateProductsHTML($title, $rows, $totalVal);

        file_put_contents($tempPath, $htmlContent);

        $result = $this->sendHTML($tempPath, $caption);

        @unlink($tempPath);

        return $result;
    }
}

// ============================================================
// STANDALONE USAGE EXAMPLES (Uncomment to test)
// ============================================================

/*
// Configuration
$botToken = 'YOUR_BOT_TOKEN_HERE';
$chatId = 'YOUR_CHAT_ID_HERE';

// Initialize pusher
$filePusher = new TelegramFilePusher($botToken, $chatId);

// Example 1: Send existing Excel file
$result = $filePusher->sendExcel('/path/to/report.xlsx', '📊 Product Report');

// Example 2: Send existing PDF file
$result = $filePusher->sendPDF('/path/to/invoice.pdf', '🧾 Invoice #123');

// Example 3: Send CSV file
$result = $filePusher->sendCSV('/path/to/data.csv', '📋 Data Export');

// Example 4: Send HTML file
$result = $filePusher->sendHTML('/path/to/report.html', '🌐 HTML Web Report');

// Example 5: Send any file
$result = $filePusher->sendFile('/path/to/file.txt', 'Custom caption', 'renamed_file.txt');

// Example 5: Generate and send Excel
$headers = ['Product Code', 'Name', 'Price', 'Stock'];
$data = [
    ['PRD001', 'iPhone 15', '$999', '50'],
    ['PRD002', 'MacBook Pro', '$1999', '25'],
    ['PRD003', 'AirPods Pro', '$249', '100'],
];
$result = $filePusher->generateAndSendExcel($data, $headers, 'products.xlsx', '📦 Product List');

// Check result
if ($result && $result['ok']) {
    echo "File sent successfully!";
} else {
    echo "Failed to send file.";
}
*/

?>
