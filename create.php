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

$category_code = preg_replace('/\s+/', ' ', trim($_POST["category_code"] ?? ''));
$category_name = preg_replace('/\s+/', ' ', trim($_POST["category_name"] ?? ''));

if ($category_code === "" || $category_name === "") {
    http_response_code(400);
    echo json_encode(["message" => "Both Category Code and Category Name are required."]);
    exit;
}

// Check if Category Code already exists for this user
$check_code_stmt = mysqli_prepare($conn, "SELECT id FROM category WHERE user_id = ? AND LOWER(TRIM(category_code)) = LOWER(?)");
if ($check_code_stmt) {
    mysqli_stmt_bind_param($check_code_stmt, "is", $userId, $category_code);
    mysqli_stmt_execute($check_code_stmt);
    mysqli_stmt_store_result($check_code_stmt);
    if (mysqli_stmt_num_rows($check_code_stmt) > 0) {
        mysqli_stmt_close($check_code_stmt);
        http_response_code(400);
        echo json_encode(["message" => "Category Code '$category_code' already exists."]);
        exit;
    }
    mysqli_stmt_close($check_code_stmt);
}

// Check if Category Name already exists for this user
$check_name_stmt = mysqli_prepare($conn, "SELECT id FROM category WHERE user_id = ? AND LOWER(TRIM(category_name)) = LOWER(?)");
if ($check_name_stmt) {
    mysqli_stmt_bind_param($check_name_stmt, "is", $userId, $category_name);
    mysqli_stmt_execute($check_name_stmt);
    mysqli_stmt_store_result($check_name_stmt);
    if (mysqli_stmt_num_rows($check_name_stmt) > 0) {
        mysqli_stmt_close($check_name_stmt);
        http_response_code(400);
        echo json_encode(["message" => "Category Name '$category_name' already exists."]);
        exit;
    }
    mysqli_stmt_close($check_name_stmt);
}

$stmt = mysqli_prepare($conn, "INSERT INTO category (user_id, category_code, category_name) VALUES (?, ?, ?)");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "iss", $userId, $category_code, $category_name);
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        $sel = mysqli_prepare($conn, "SELECT * FROM category WHERE id = ?");
        mysqli_stmt_bind_param($sel, "i", $newId);
        mysqli_stmt_execute($sel);
        $row = mysqli_stmt_get_result($sel);
        $data = mysqli_fetch_assoc($row);
        mysqli_stmt_close($sel);

        $msg = "🏷️ <b>NEW CATEGORY CREATED</b>\n"
             . "═════════════════════════════\n"
             . "🆔 <b>ID:</b> #{$newId}\n"
             . "🏷️ <b>Code:</b> <code>" . htmlspecialchars($category_code) . "</code>\n"
             . "📦 <b>Name:</b> <b>" . htmlspecialchars($category_name) . "</b>";
        if (isAutoTelegramEnabled($conn)) {
            sendTelegramNotification($msg, $conn, $userId);
        }

        echo json_encode($data);
        exit;
    } else {
        $errno = mysqli_errno($conn);
        mysqli_stmt_close($stmt);
        if ($errno === 1062) {
            http_response_code(400);
            echo json_encode(["message" => "Category Code '$category_code' already exists."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Database error: " . mysqli_error($conn)]);
        }
        exit;
    }
} else {
    http_response_code(500);
    echo json_encode(["message" => "Database preparation failed: " . mysqli_error($conn)]);
    exit;
}