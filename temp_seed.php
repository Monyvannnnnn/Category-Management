<?php
// temp_seed.php - seed Khmer category rows directly via database.php connection.
require_once __DIR__ . "/database.php";

$cats = [
    ["TECH001", "បច្ចេកវិទ្យា"],      // Technology
    ["FOOD002", "អាហារ"],            // Food
    ["EDU003",  "ការអប់រំ"],         // Education
    ["HEAL004", "សុខភាព"],           // Health
    ["BIZ005",  "អាជីវកម្ម"],         // Business
    ["SPORT006","កីឡា"],             // Sports
    ["MUSIC007","តន្ត្រី"],            // Music
    ["TRAVEL008","ការធ្វើដំណើរ"],      // Travel
];

$stmt = mysqli_prepare($conn, "INSERT INTO category (category_code, category_name) VALUES (?, ?)");
foreach ($cats as $c) {
    mysqli_stmt_bind_param($stmt, "ss", $c[0], $c[1]);
    if (mysqli_stmt_execute($stmt)) {
        echo "OK  {$c[0]} -> {$c[1]} (id=" . mysqli_insert_id($conn) . ")\n";
    } else {
        $errno = mysqli_errno($conn);
        if ($errno === 1062) {
            echo "SKIP {$c[0]} -> {$c[1]} (already exists)\n";
        } else {
            echo "ERR {$c[0]}: " . mysqli_error($conn) . "\n";
        }
    }
}
echo "Done.\n";
