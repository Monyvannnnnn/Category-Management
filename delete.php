<?php

require_once "database.php";
require_once "includes/auth_helper.php";
require_once "notify_bot.php";

header("Content-Type: application/json");

$user = getCurrentUser();
$userId = (int)($user['id'] ?? 1);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed"]);
    exit;
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid or missing Category ID."]);
    exit;
}

$id = (int)$_GET["id"];

// Fetch category info before deletion for notification
$cat_info = null;
$sel_stmt = db_prepare($conn, "SELECT category_code, category_name FROM category WHERE id = ?");
if ($sel_stmt) {
    db_stmt_bind_param($sel_stmt, "i", $id);
    db_stmt_execute($sel_stmt);
    $res = db_stmt_get_result($sel_stmt);
    $cat_info = db_fetch_assoc($res);
    db_stmt_close($sel_stmt);
}

$stmt = db_prepare($conn, "DELETE FROM category WHERE id = ?");
if ($stmt) {
    db_stmt_bind_param($stmt, "i", $id);
    if (db_stmt_execute($stmt)) {
        db_stmt_close($stmt);

        $catCode = $cat_info['category_code'] ?? "N/A";
        $catName = $cat_info['category_name'] ?? "N/A";
        $msg = "<b>🗑️ Category Deleted</b>\n"
             . "<b>ID:</b> #{$id}\n"
             . "<b>Code:</b> " . htmlspecialchars($catCode) . "\n"
             . "<b>Name:</b> " . htmlspecialchars($catName);
        echo json_encode(["success" => true]);
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        if (isAutoTelegramEnabled($conn)) {
            sendTelegramNotification($msg, $conn, $userId);
        }
        exit;
    } else {
        db_stmt_close($stmt);
        http_response_code(500);
        echo json_encode(["message" => "Delete failed: " . db_error($conn)]);
        exit;
    }
} else {
    http_response_code(500);
    echo json_encode(["message" => "Database query preparation failed: " . db_error($conn)]);
    exit;
}