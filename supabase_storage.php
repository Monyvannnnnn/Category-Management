<?php
/**
 * Supabase Storage Helper
 * Handles uploading, deleting, and validating product images in Supabase Storage.
 */

require_once __DIR__ . '/database.php';

/**
 * Get MIME type of a file with graceful fallbacks
 *
 * @param string $filePath
 * @return string MIME type
 */
function getFileMimeType($filePath) {
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $mime = finfo_file($finfo, $filePath);
            finfo_close($finfo);
            if ($mime) return $mime;
        }
    }
    
    if (function_exists('mime_content_type')) {
        $mime = @mime_content_type($filePath);
        if ($mime) return $mime;
    }

    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimes = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'gif'  => 'image/gif',
        'webp' => 'image/webp'
    ];

    return $mimes[$ext] ?? 'application/octet-stream';
}

/**
 * Resize image to standard dimensions (default 300x300) maintaining aspect ratio
 *
 * @param string $sourcePath Path to source image file
 * @param int $maxWidth Max target width (default 300)
 * @param int $maxHeight Max target height (default 300)
 * @return string Path to resized image or original path if GD unavailable/fails
 */
function resizeImage($sourcePath, $maxWidth = 300, $maxHeight = 300) {
    if (!file_exists($sourcePath)) {
        return $sourcePath;
    }

    // Check if GD library is available
    if (!function_exists('imagecreatetruecolor') || !function_exists('getimagesize')) {
        return $sourcePath;
    }

    $info = @getimagesize($sourcePath);
    if ($info === false) {
        return $sourcePath;
    }

    list($origWidth, $origHeight, $type) = $info;

    // If original is already smaller than or equal to max dimensions, keep original
    if ($origWidth <= $maxWidth && $origHeight <= $maxHeight) {
        return $sourcePath;
    }

    // Calculate new dimensions (maintain aspect ratio)
    $ratio = $origWidth / $origHeight;

    if ($maxWidth / $maxHeight > $ratio) {
        $newWidth = (int)round($maxHeight * $ratio);
        $newHeight = $maxHeight;
    } else {
        $newWidth = $maxWidth;
        $newHeight = (int)round($maxWidth / $ratio);
    }

    // Create new canvas
    $newImage = @imagecreatetruecolor($newWidth, $newHeight);
    if (!$newImage) {
        return $sourcePath;
    }

    // Load original based on type
    $origImage = null;
    switch ($type) {
        case IMAGETYPE_JPEG:
            if (function_exists('imagecreatefromjpeg')) $origImage = @imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            if (function_exists('imagecreatefrompng')) {
                $origImage = @imagecreatefrompng($sourcePath);
                imagealphablending($newImage, false);
                imagesavealpha($newImage, true);
                $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
                imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
            }
            break;
        case IMAGETYPE_GIF:
            if (function_exists('imagecreatefromgif')) $origImage = @imagecreatefromgif($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagewebp') && function_exists('imagecreatefromwebp')) $origImage = @imagecreatefromwebp($sourcePath);
            break;
    }

    if (!$origImage) {
        @imagedestroy($newImage);
        return $sourcePath;
    }

    // Resample / Resize
    imagecopyresampled(
        $newImage, $origImage,
        0, 0, 0, 0,
        $newWidth, $newHeight,
        $origWidth, $origHeight
    );

    // Save resized image to temporary location
    $resizedPath = $sourcePath . '_resized';

    $saved = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            if (function_exists('imagejpeg')) $saved = @imagejpeg($newImage, $resizedPath, 90);
            break;
        case IMAGETYPE_PNG:
            if (function_exists('imagepng')) $saved = @imagepng($newImage, $resizedPath, 8);
            break;
        case IMAGETYPE_GIF:
            if (function_exists('imagegif')) $saved = @imagegif($newImage, $resizedPath);
            break;
        case IMAGETYPE_WEBP:
            if (function_exists('imagewebp')) $saved = @imagewebp($newImage, $resizedPath, 90);
            break;
    }

    // Clean up memory resources
    @imagedestroy($origImage);
    @imagedestroy($newImage);

    return ($saved && file_exists($resizedPath)) ? $resizedPath : $sourcePath;
}

/**
 * Upload a file to Supabase Storage bucket
 *
 * @param string $filePath Local temp file path
 * @param string $destinationName Unique filename in the bucket
 * @return string|false Public URL on success, false on failure
 */
function uploadToSupabase($filePath, $destinationName) {
    if (!file_exists($filePath)) {
        return false;
    }

    // Resize image to standard dimensions (300x300 max) before uploading
    $uploadPath = resizeImage($filePath, 300, 300);

    $supabaseUrl = rtrim(SUPABASE_URL, '/');
    $bucket = SUPABASE_BUCKET;
    $cleanName = basename($destinationName);
    $url = "{$supabaseUrl}/storage/v1/object/{$bucket}/{$cleanName}";

    $mimeType = getFileMimeType($uploadPath);
    $fileData = file_get_contents($uploadPath);

    // Clean up temp resized file if created
    if ($uploadPath !== $filePath && file_exists($uploadPath)) {
        @unlink($uploadPath);
    }

    if ($fileData === false) {
        return false;
    }

    $keysToTry = [
        defined('SUPABASE_SERVICE_KEY') ? SUPABASE_SERVICE_KEY : null,
        defined('SUPABASE_ANON_KEY') ? SUPABASE_ANON_KEY : null
    ];

    foreach (array_filter($keysToTry) as $apiKey) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$apiKey}",
            "apiKey: {$apiKey}",
            "Content-Type: {$mimeType}",
            "x-upsert: true"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 201) {
            return getPublicUrl($cleanName);
        }
    }

    return false;
}

/**
 * Delete a file from Supabase Storage bucket
 *
 * @param string $fileName Filename or full public URL
 * @return bool True on success, false on failure
 */
function deleteFromSupabase($fileName) {
    if (empty($fileName)) {
        return true;
    }

    $cleanName = basename(parse_url($fileName, PHP_URL_PATH));
    if (empty($cleanName)) {
        return false;
    }

    $supabaseUrl = rtrim(SUPABASE_URL, '/');
    $bucket = SUPABASE_BUCKET;
    $url = "{$supabaseUrl}/storage/v1/object/{$bucket}/{$cleanName}";

    $keysToTry = [
        defined('SUPABASE_SERVICE_KEY') ? SUPABASE_SERVICE_KEY : null,
        defined('SUPABASE_ANON_KEY') ? SUPABASE_ANON_KEY : null
    ];

    foreach (array_filter($keysToTry) as $apiKey) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer {$apiKey}",
            "apiKey: {$apiKey}"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200 || $httpCode === 204) {
            return true;
        }
    }

    return false;
}

/**
 * Get public URL for a file in Supabase Storage
 *
 * @param string $fileName
 * @return string Full public URL
 */
function getPublicUrl($fileName) {
    $supabaseUrl = rtrim(SUPABASE_URL, '/');
    $bucket = SUPABASE_BUCKET;
    $cleanName = ltrim(basename($fileName), '/');
    return "{$supabaseUrl}/storage/v1/object/public/{$bucket}/{$cleanName}";
}

/**
 * Validate an uploaded file from $_FILES
 *
 * @param array $file $_FILES['product_image'] array
 * @return bool True if valid
 * @throws Exception If validation fails
 */
function validateUploadedFile($file) {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new Exception("Invalid upload parameters.");
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new Exception("No file was uploaded.");
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new Exception("Exceeded file size limit.");
        default:
            throw new Exception("Unknown upload error.");
    }

    // File size check: Max 5MB
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception("File size exceeds 5MB limit.");
    }

    // File extension check
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowedExts, true)) {
        throw new Exception("Invalid file type '.{$ext}'. Only JPG, PNG, GIF, and WEBP images are allowed.");
    }

    // Check image integrity using getimagesize if available
    if (function_exists('getimagesize')) {
        $imgSize = @getimagesize($file['tmp_name']);
        if ($imgSize === false) {
            throw new Exception("The uploaded file is not a valid image.");
        }
    }

    // MIME type check
    $mime = getFileMimeType($file['tmp_name']);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $allowedMimes, true) && strpos($mime, 'image/') !== 0) {
        throw new Exception("Invalid image format '{$mime}'.");
    }

    return true;
}
