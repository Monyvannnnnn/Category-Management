<?php

require_once "database.php";

header("Content-Type: application/json");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["message" => "Method Not Allowed"]);
    exit;
}

$category_code = trim($_POST["category_code"] ?? '');
$category_name = trim($_POST["category_name"] ?? '');

if ($category_code === "" || $category_name === "") {
    http_response_code(400);
    echo json_encode(["message" => "Both Category Code and Category Name are required."]);
    exit;
}

$stmt = mysqli_prepare($conn, "INSERT INTO category (category_code, category_name) VALUES (?, ?)");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $category_code, $category_name);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        echo json_encode(["success" => true]);
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