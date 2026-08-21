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

$stmt = mysqli_prepare($conn, "DELETE FROM categories WHERE id = ?");
if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $id);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
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