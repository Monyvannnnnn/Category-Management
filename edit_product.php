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
    echo json_encode(["message" => "Invalid or missing Product ID."]);
    exit;
}

$id = (int)$_GET["id"];

// First get existing product owned by this user
$stmt = db_prepare($conn, "SELECT * FROM product WHERE id = ? AND user_id = ?");
if ($stmt) {
    db_stmt_bind_param($stmt, "ii", $id, $userId);
    db_stmt_execute($stmt);
    $result = db_stmt_get_result($stmt);
    $product = db_fetch_assoc($result);
    db_stmt_close($stmt);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Database query preparation failed: " . db_error($conn)]);
    exit;
}

if (!$product) {
    http_response_code(404);
    echo json_encode(["message" => "Product not found."]);
    exit;
}

// Extract new values or fallback to original values
$productCode = isset($_POST["product_code"]) ? preg_replace('/\s+/', ' ', trim($_POST["product_code"])) : $product["product_code"];
$productName = isset($_POST["product_name"]) ? preg_replace('/\s+/', ' ', trim($_POST["product_name"])) : $product["product_name"];
$categoryId = isset($_POST["category_id"]) ? (int)$_POST["category_id"] : (int)$product["category_id"];
$price = isset($_POST["price"]) ? (float)$_POST["price"] : (float)$product["price"];
$quantity = isset($_POST["quantity"]) ? (int)$_POST["quantity"] : (int)$product["quantity"];

$origProductCode = preg_replace('/\s+/', ' ', trim($product["product_code"]));

if ($productCode === "" || $productName === "" || $categoryId <= 0) {
    http_response_code(400);
    echo json_encode(["message" => "Product Code, Product Name, and Category cannot be empty."]);
    exit;
}

// Check if the new Product Code already exists for another product owned by this user
if (strcasecmp($productCode, $origProductCode) !== 0) {
    $check_code_stmt = db_prepare($conn, "SELECT id FROM product WHERE user_id = ? AND LOWER(TRIM(product_code)) = LOWER(?) AND id != ?");
    if ($check_code_stmt) {
        db_stmt_bind_param($check_code_stmt, "isi", $userId, $productCode, $id);
        db_stmt_execute($check_code_stmt);
        db_stmt_store_result($check_code_stmt);
        if (db_stmt_num_rows($check_code_stmt) > 0) {
            db_stmt_close($check_code_stmt);
            http_response_code(400);
            echo json_encode(["message" => "Product Code '$productCode' already exists."]);
            exit;
        }
        db_stmt_close($check_code_stmt);
    }
}

$stmt = db_prepare($conn, "UPDATE product SET product_code = ?, product_name = ?, category_id = ?, price = ?, quantity = ? WHERE id = ? AND user_id = ?");
if ($stmt) {
    db_stmt_bind_param($stmt, "ssidiii", $productCode, $productName, $categoryId, $price, $quantity, $id, $userId);
    if (db_stmt_execute($stmt)) {
        db_stmt_close($stmt);

        $msg = "<b>✏️ Product Updated</b> (ID: #{$id})\n"
             . "<b>Code:</b> " . htmlspecialchars($productCode) . "\n"
             . "<b>Name:</b> " . htmlspecialchars($productName) . "\n"
             . "<b>Price:</b> $" . number_format($price, 2) . "\n"
             . "<b>Quantity:</b> " . $quantity;
        echo json_encode(["success" => true]);
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
    echo json_encode(["message" => "Database update preparation failed: " . db_error($conn)]);
    exit;
}
