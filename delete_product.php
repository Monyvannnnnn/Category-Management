<?php

require_once "database.php";
require_once "notify_bot.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed"]);
    exit;
}

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    http_response_code(400);
    echo json_encode(["message" => "Invalid or missing Product ID."]);
    exit;
}

$id = (int)$_GET["id"];

// Fetch product info before deletion for notification
$prod_info = null;
$sel_stmt = mysqli_prepare($conn, "SELECT product_code, product_name FROM product WHERE id = ?");
if ($sel_stmt) {
    mysqli_stmt_bind_param($sel_stmt, "i", $id);
    mysqli_stmt_execute($sel_stmt);
    $res = mysqli_stmt_get_result($sel_stmt);
    $prod_info = mysqli_fetch_assoc($res);
    mysqli_stmt_close($sel_stmt);
}

$stmt = mysqli_prepare($conn, "DELETE FROM product WHERE id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);

        $prodCode = $prod_info['product_code'] ?? "N/A";
        $prodName = $prod_info['product_name'] ?? "N/A";
        $msg = "<b>🗑️ Product Deleted</b>\n"
             . "<b>ID:</b> #{$id}\n"
             . "<b>Code:</b> " . htmlspecialchars($prodCode) . "\n"
             . "<b>Name:</b> " . htmlspecialchars($prodName);
        if (isAutoTelegramEnabled($conn)) {
            sendTelegramNotification($msg);
        }

        echo json_encode(["success" => true]);
        exit;
    } else {
        mysqli_stmt_close($stmt);
        http_response_code(500);
        echo json_encode(["message" => "Delete failed: " . mysqli_error($conn)]);
        exit;
    }
} else {
    http_response_code(500);
    echo json_encode(["message" => "Database query preparation failed: " . mysqli_error($conn)]);
    exit;
}
