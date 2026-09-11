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

$product_code = preg_replace('/\s+/', ' ', trim($_POST["product_code"] ?? ''));
$product_name = preg_replace('/\s+/', ' ', trim($_POST["product_name"] ?? ''));
$category_id = isset($_POST["category_id"]) ? (int)$_POST["category_id"] : 0;
$price = isset($_POST["price"]) ? (float)$_POST["price"] : 0.00;
$quantity = isset($_POST["quantity"]) ? (int)$_POST["quantity"] : 0;

if ($product_code === "" || $product_name === "" || $category_id <= 0) {
    http_response_code(400);
    echo json_encode(["message" => "Product Code, Product Name, and Category are required."]);
    exit;
}

// Check if Product Code already exists for this user
$check_code_stmt = db_prepare($conn, "SELECT id FROM product WHERE user_id = ? AND LOWER(TRIM(product_code)) = LOWER(?)");
if ($check_code_stmt) {
    db_stmt_bind_param($check_code_stmt, "is", $userId, $product_code);
    db_stmt_execute($check_code_stmt);
    db_stmt_store_result($check_code_stmt);
    if (db_stmt_num_rows($check_code_stmt) > 0) {
        db_stmt_close($check_code_stmt);
        http_response_code(400);
        echo json_encode(["message" => "Product Code '$product_code' already exists."]);
        exit;
    }
    db_stmt_close($check_code_stmt);
}

$stmt = db_prepare($conn, "INSERT INTO product (user_id, product_code, product_name, category_id, price, quantity) VALUES (?, ?, ?, ?, ?, ?)");
if ($stmt) {
    db_stmt_bind_param($stmt, "issidi", $userId, $product_code, $product_name, $category_id, $price, $quantity);
    if (db_stmt_execute($stmt)) {
        $newId = db_insert_id($conn);
        db_stmt_close($stmt);

        $sel = db_prepare($conn, "SELECT product.*, category.category_name FROM product LEFT JOIN category ON product.category_id = category.id WHERE product.id = ?");
        db_stmt_bind_param($sel, "i", $newId);
        db_stmt_execute($sel);
        $row = db_stmt_get_result($sel);
        $data = db_fetch_assoc($row);
        db_stmt_close($sel);

        $catName = $data['category_name'] ?? 'N/A';
        $msg = "<b>📦 New Product Created</b>\n"
             . "<b>Code:</b> " . htmlspecialchars($product_code) . "\n"
             . "<b>Name:</b> " . htmlspecialchars($product_name) . "\n"
             . "<b>Category:</b> " . htmlspecialchars($catName) . "\n"
             . "<b>Price:</b> $" . number_format($price, 2) . "\n"
             . "<b>Quantity:</b> " . $quantity;
        echo json_encode($data);
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        if (isAutoTelegramEnabled($conn)) {
            sendTelegramNotification($msg, $conn, $userId);
        }
        exit;
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . db_error($conn)]);
        exit;
    }
} else {
    http_response_code(500);
    echo json_encode(["message" => "Database preparation failed: " . db_error($conn)]);
    exit;
}
