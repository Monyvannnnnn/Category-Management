<?php

require_once "database.php";

header("Content-Type: application/json");

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

// Check if Category Name already exists (case-insensitive & trimmed)
$check_name_stmt = mysqli_prepare($conn, "SELECT id FROM category WHERE LOWER(TRIM(category_name)) = LOWER(?)");
if ($check_name_stmt) {
    mysqli_stmt_bind_param($check_name_stmt, "s", $category_name);
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

$stmt = mysqli_prepare($conn, "INSERT INTO category (category_code, category_name) VALUES (?, ?)");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $category_code, $category_name);
    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);
        // Return the full inserted row so DevExtreme's store stays consistent
        // and the new row shows immediately with correct id/created_at.
        $sel = mysqli_prepare($conn, "SELECT * FROM category WHERE id = ?");
        mysqli_stmt_bind_param($sel, "i", $newId);
        mysqli_stmt_execute($sel);
        $row = mysqli_stmt_get_result($sel);
        $data = mysqli_fetch_assoc($row);
        mysqli_stmt_close($sel);
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