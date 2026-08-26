<?php
// diag2.php — better INSERT diagnostic. DELETE after use.
header("Content-Type: text/plain; charset=utf-8");
error_reporting(E_ALL);
ini_set('display_errors', 1);

$cfg = require __DIR__ . "/db_config.php";
$conn = @mysqli_connect($cfg["host"], $cfg["user"], $cfg["pass"], $cfg["name"], $cfg["port"]);
if (!$conn) { echo "CONNECT FAILED: " . mysqli_connect_error() . "\n"; exit; }
echo "CONNECT OK\n";

// Show real columns of the live table
$cols = mysqli_query($conn, "SHOW COLUMNS FROM category");
echo "\n=== Live 'category' columns ===\n";
while ($c = mysqli_fetch_assoc($cols)) {
    echo "- " . $c["Field"] . " (" . $c["Type"] . ") null=" . $c["Null"] . " default=" . $c["Default"] . "\n";
}

// Show keys (to confirm unique constraints)
$keys = mysqli_query($conn, "SHOW INDEX FROM category");
echo "\n=== Live indexes ===\n";
while ($k = mysqli_fetch_assoc($keys)) {
    echo "- " . $k["Key_name"] . " on " . $k["Column_name"] . "\n";
}

// Simulate exactly what create.php does
echo "\n=== Simulated create.php INSERT ===\n";
$category_code = "DIAG_" . time();
$category_name = "Diag Test " . time();

$check = mysqli_prepare($conn, "SELECT id FROM category WHERE LOWER(TRIM(category_name)) = LOWER(?)");
mysqli_stmt_bind_param($check, "s", $category_name);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);
echo "Duplicate-name check rows: " . mysqli_stmt_num_rows($check) . "\n";
mysqli_stmt_close($check);

$stmt = mysqli_prepare($conn, "INSERT INTO category (category_code, category_name) VALUES (?, ?)");
if (!$stmt) { echo "PREPARE FAILED: " . mysqli_error($conn) . "\n"; exit; }
mysqli_stmt_bind_param($stmt, "ss", $category_code, $category_name);
if (mysqli_stmt_execute($stmt)) {
    $newId = mysqli_insert_id($conn);
    echo "INSERT OK -> new id = " . $newId . "\n";
    // cleanup
    mysqli_query($conn, "DELETE FROM category WHERE category_code = '" . mysqli_real_escape_string($conn, $category_code) . "'");
    echo "Cleanup done.\n";
} else {
    echo "INSERT FAILED: errno=" . mysqli_errno($conn) . " msg=" . mysqli_error($conn) . "\n";
}

// Count rows now
$count = mysqli_query($conn, "SELECT COUNT(*) AS c FROM category");
echo "Total rows in category: " . mysqli_fetch_assoc($count)["c"] . "\n";
echo "\n=== DONE ===\n";
