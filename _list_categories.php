<?php
// READ-ONLY: lists all categories. No deletes.
require_once __DIR__ . '/db_config.php';
$cfg = require __DIR__ . '/db_config.php';

$conn = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name'], $cfg['port']);
if ($conn->connect_error) {
    die("CONNECT FAIL: " . $conn->connect_error . "\n");
}

$res = $conn->query("SELECT id, category_code, category_name, created_at FROM categories ORDER BY id ASC");
if (!$res) die("QUERY FAIL: " . $conn->error . "\n");

echo "TOTAL ROWS: " . $res->num_rows . "\n";
echo str_pad("id", 5) . str_pad("code", 14) . str_pad("name", 30) . "created_at\n";
echo str_repeat("-", 70) . "\n";
while ($row = $res->fetch_assoc()) {
    echo str_pad($row['id'], 5)
       . str_pad(substr($row['category_code'], 0, 12), 14)
       . str_pad(substr($row['category_name'], 0, 28), 30)
       . $row['created_at'] . "\n";
}
$conn->close();
