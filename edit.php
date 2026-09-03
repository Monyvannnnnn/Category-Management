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
    echo json_encode(["message" => "Invalid or missing Category ID."]);
    exit;
}

$id = (int)$_GET["id"];

// First get existing category
$stmt = mysqli_prepare($conn, "SELECT * FROM category WHERE id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $category = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
} else {
    http_response_code(500);
    echo json_encode(["message" => "Database query preparation failed: " . mysqli_error($conn)]);
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

// Trim and normalize original database values just in case
$origCategoryName = preg_replace('/\s+/', ' ', trim($category["category_name"]));

if ($categoryCode === "" || $categoryName === "") {
    http_response_code(400);
    echo json_encode(["message" => "Category Code and Category Name cannot be empty."]);
    exit;
}

// Check if the new Category Name already exists for another category (case-insensitive & trimmed)
if (strcasecmp($categoryName, $origCategoryName) !== 0) {
    $check_name_stmt = mysqli_prepare($conn, "SELECT id FROM category WHERE LOWER(TRIM(category_name)) = LOWER(?) AND id != ?");
    if ($check_name_stmt) {
        mysqli_stmt_bind_param($check_name_stmt, "si", $categoryName, $id);
        mysqli_stmt_execute($check_name_stmt);
        mysqli_stmt_store_result($check_name_stmt);
        if (mysqli_stmt_num_rows($check_name_stmt) > 0) {
            mysqli_stmt_close($check_name_stmt);
            http_response_code(400);
            echo json_encode(["message" => "Category Name '$categoryName' already exists."]);
            exit;
        }
        mysqli_stmt_close($check_name_stmt);
    }
}

$stmt = mysqli_prepare($conn, "UPDATE category SET category_code = ?, category_name = ? WHERE id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ssi", $categoryCode, $categoryName, $id);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);

        $msg = "<b>✏️ Category Updated</b> (ID: #{$id})\n"
             . "<b>Code:</b> " . htmlspecialchars($categoryCode) . "\n"
             . "<b>Name:</b> " . htmlspecialchars($categoryName);
        if (isAutoTelegramEnabled($conn)) {
            sendTelegramNotification($msg);
        }

        echo json_encode(["success" => true]);
        exit;
    } else {
        $errno = mysqli_errno($conn);
        mysqli_stmt_close($stmt);
        if ($errno === 1062) {
            http_response_code(400);
            echo json_encode(["message" => "Category Code '$categoryCode' already exists."]);
        } else {
            http_response_code(500);
            echo json_encode(["message" => "Database error: " . mysqli_error($conn)]);
        }
        exit;
    }
} else {
    http_response_code(500);
    echo json_encode(["message" => "Database update preparation failed: " . mysqli_error($conn)]);
    exit;
}