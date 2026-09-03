<?php

require_once "database.php";
require_once "notify_bot.php";

header("Content-Type: application/json");

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

// Check if Product Code already exists
$check_code_stmt = mysqli_prepare($conn, "SELECT id FROM product WHERE LOWER(TRIM(product_code)) = LOWER(?)");
if ($check_code_stmt) {
    mysqli_stmt_bind_param($check_code_stmt, "s", $product_code);
    mysqli_stmt_execute($check_code_stmt);
    mysqli_stmt_store_result($check_code_stmt);
    if (mysqli_stmt_num_rows($check_code_stmt) > 0) {
        mysqli_stmt_close($check_code_stmt);
        http_response_code(400);
        echo json_encode(["message" => "Product Code '$product_code' already exists."]);
        exit;
    }
    mysqli_stmt_close($check_code_stmt);
}

$stmt = mysqli_prepare($conn, "INSERT INTO product (product_code, product_name, category_id, price, quantity) VALUES (?, ?, ?, ?, ?)");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ssidi", $product_code, $product_name, $category_id, $price, $quantity);
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        
        $sel = mysqli_prepare($conn, "SELECT product.*, category.category_name FROM product LEFT JOIN category ON product.category_id = category.id WHERE product.id = ?");
        mysqli_stmt_bind_param($sel, "i", $newId);
        mysqli_stmt_execute($sel);
        $row = mysqli_stmt_get_result($sel);
        $data = mysqli_fetch_assoc($row);
        mysqli_stmt_close($sel);
        
        $catName = $data['category_name'] ?? 'N/A';
        $msg = "<b>📦 New Product Created</b>\n"
             . "<b>Code:</b> " . htmlspecialchars($product_code) . "\n"
             . "<b>Name:</b> " . htmlspecialchars($product_name) . "\n"
             . "<b>Category:</b> " . htmlspecialchars($catName) . "\n"
             . "<b>Price:</b> $" . number_format($price, 2) . "\n"
             . "<b>Quantity:</b> " . $quantity;
        if (isAutoTelegramEnabled($conn)) {
            sendTelegramNotification($msg);
        }

        echo json_encode($data);
        exit;
    } else {
        http_response_code(500);
        echo json_encode(["message" => "Database error: " . mysqli_error($conn)]);
        exit;
    }
} else {
    http_response_code(500);
    echo json_encode(["message" => "Database preparation failed: " . mysqli_error($conn)]);
    exit;
}
