<?php
// TEMPORARY cleanup script — DELETE ALL rows from `categories`, then REMOVE THIS FILE.
// SAFETY: requires ?confirm=YES in the URL. Without it, only shows a count.
require_once __DIR__ . '/db_config.php';
$cfg = require __DIR__ . '/db_config.php';

header('Content-Type: text/plain; charset=utf-8');

$conn = new mysqli($cfg['host'], $cfg['user'], $cfg['pass'], $cfg['name'], $cfg['port']);
if ($conn->connect_error) {
    die("CONNECT FAIL: " . $conn->connect_error);
}

// 1) Show current count (always)
$res = $conn->query("SELECT COUNT(*) AS c FROM categories");
$count = $res ? (int)$res->fetch_assoc()['c'] : -1;
echo "Current row count in `categories`: $count\n";

if (!isset($_GET['confirm']) || $_GET['confirm'] !== 'YES') {
    echo "Nothing deleted. To DELETE ALL ROWS, open this URL with ?confirm=YES\n";
    echo "Example: _delete_all_categories.php?confirm=YES\n";
    $conn->close();
    exit;
}

// 2) Delete all rows
if ($conn->query("DELETE FROM categories")) {
    $remaining = (int)$conn->query("SELECT COUNT(*) AS c FROM categories")->fetch_assoc()['c'];
    echo "DELETED. Rows remaining: $remaining\n";
    echo "Now re-add your real categories via the UI, then DELETE THIS SCRIPT.\n";
} else {
    echo "DELETE FAILED: " . $conn->error . "\n";
}
$conn->close();
