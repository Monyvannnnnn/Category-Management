<?php

require_once "database.php";

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
$categoryCode = isset($_POST["category_code"]) ? trim($_POST["category_code"]) : $category["category_code"];
$categoryName = isset($_POST["category_name"]) ? trim($_POST["category_name"]) : $category["category_name"];

if ($categoryCode === "" || $categoryName === "") {
    http_response_code(400);
    echo json_encode(["message" => "Category Code and Category Name cannot be empty."]);
    exit;
}

$stmt = mysqli_prepare($conn, "UPDATE category SET category_code = ?, category_name = ? WHERE id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ssi", $categoryCode, $categoryName, $id);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
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