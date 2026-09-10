<?php

/**
 * Send JSON response and terminate script
 */
function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Generate a secure, unique one-time connection code
 * Example: CONNECT-A8F2K9
 */
function generateConnectionCode($prefix = "CONNECT-") {
    $bytes = random_bytes(4);
    return $prefix . strtoupper(bin2hex($bytes));
}

/**
 * Mask Chat ID for UI privacy (e.g. 7892****36)
 */
function maskChatId($chatId) {
    if (empty($chatId)) return "Not Connected";
    $len = strlen($chatId);
    if ($len <= 4) return "****";
    return substr($chatId, 0, 4) . '****' . substr($chatId, -2);
}
