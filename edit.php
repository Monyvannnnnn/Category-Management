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

// First get existing category owned by this user
$stmt = db_prepare($conn, "SELECT * FROM category WHERE id = ? AND user_id = ?");
if ($stmt) {
    db_stmt_bind_param($stmt, "ii", $id, $userId);
    db_stmt_execute($stmt);
    $result = db_stmt_get_result($stmt);
    $category = db_fetch_assoc($result);
    db_stmt_close($stmt);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Database query preparation failed: " . db_error($conn)]);
    exit;
}

if (!$category) {
    http_response_code(404);
    echo json_encode(["message" => "Category not found."]);
    exit;
}

// Extract new values or fallback to original values
$categoryCode = isset($_POST["category_code"]) ? preg_replace('/\s+/', ' ', trim($_POST["category_code"])) : $category["category_code"];
$categoryName = isset($_POST["category_name"]) ? preg_replace('/\s+/', ' ', trim($_POST["category_name"])) : $category["category_name"];

// Trim and normalize original database values
$origCategoryCode = preg_replace('/\s+/', ' ', trim($category["category_code"]));
$origCategoryName = preg_replace('/\s+/', ' ', trim($category["category_name"]));

if ($categoryCode === "" || $categoryName === "") {
    http_response_code(400);
    echo json_encode(["message" => "Category Code and Category Name cannot be empty."]);
    exit;
}

// Check if the new Category Code already exists for another category owned by this user
if (strcasecmp($categoryCode, $origCategoryCode) !== 0) {
    $check_code_stmt = db_prepare($conn, "SELECT id FROM category WHERE user_id = ? AND LOWER(TRIM(category_code)) = LOWER(?) AND id != ?");
    if ($check_code_stmt) {
        db_stmt_bind_param($check_code_stmt, "isi", $userId, $categoryCode, $id);
        db_stmt_execute($check_code_stmt);
        db_stmt_store_result($check_code_stmt);
        if (db_stmt_num_rows($check_code_stmt) > 0) {
            db_stmt_close($check_code_stmt);
            http_response_code(400);
            echo json_encode(["message" => "Category Code '$categoryCode' already exists."]);
            exit;
        }
        db_stmt_close($check_code_stmt);
    }
}

// Check if the new Category Name already exists for another category owned by this user
if (strcasecmp($categoryName, $origCategoryName) !== 0) {
    $check_name_stmt = db_prepare($conn, "SELECT id FROM category WHERE user_id = ? AND LOWER(TRIM(category_name)) = LOWER(?) AND id != ?");
    if ($check_name_stmt) {
        db_stmt_bind_param($check_name_stmt, "isi", $userId, $categoryName, $id);
        db_stmt_execute($check_name_stmt);
        db_stmt_store_result($check_name_stmt);
        if (db_stmt_num_rows($check_name_stmt) > 0) {
            db_stmt_close($check_name_stmt);
            http_response_code(400);
            echo json_encode(["message" => "Category Name '$categoryName' already exists."]);
            exit;
        }
        db_stmt_close($check_name_stmt);
    }
}

$stmt = db_prepare($conn, "UPDATE category SET category_code = ?, category_name = ? WHERE id = ? AND user_id = ?");
if ($stmt) {
    db_stmt_bind_param($stmt, "ssii", $categoryCode, $categoryName, $id, $userId);
    if (db_stmt_execute($stmt)) {
        db_stmt_close($stmt);

        $msg = "<b>✏️ Category Updated</b> (ID: #{$id})\n"
             . "<b>Code:</b> " . htmlspecialchars($categoryCode) . "\n"
             . "<b>Name:</b> " . htmlspecialchars($categoryName);
        echo json_encode(["success" => true]);
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        if (isAutoTelegramEnabled($conn)) {
            sendTelegramNotification($msg, $conn, $userId);
        }
        exit;
    } else {
        $errno = db_errno($conn);
        db_stmt_close($stmt);
        if ($errno === 1062) {
            http_response_code(400);
            echo json_encode(["message" => "Category Code '$categoryCode' already exists."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Database error: " . db_error($conn)]);
        }
        exit;
    }
} else {
    http_response_code(500);
    echo json_encode(["message" => "Database update preparation failed: " . db_error($conn)]);
    exit;
}