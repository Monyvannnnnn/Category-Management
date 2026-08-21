<?php

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

$is_local = (php_sapi_name() === 'cli') 
            || (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', 'localhost:8080']))
            || (isset($_SERVER['SERVER_ADDR']) && in_array($_SERVER['SERVER_ADDR'], ['127.0.0.1', '::1']));

if ($is_local) {
    // ----------------------------------------------------
    // LOCAL DEV CONFIGURATION (XAMPP)
    // ----------------------------------------------------
    $db_host = "127.0.0.1";
    $db_user = "root";
    $db_pass = "";
    $db_name = "inventory";
    $db_port = 3307; // Your XAMPP MySQL port
} else {
    // ----------------------------------------------------
    // LIVE DEPLOYMENT CONFIGURATION (InfinityFree)
    // ----------------------------------------------------
    $db_host = "sql310.infinityfree.com";
    $db_user = "if0_42693065";
    $db_pass = "Munyvann3103094";
    $db_name = "if0_42693065_inventory";
    $db_port = 3306;                      // Default MySQL port
}

$conn = mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// ----------------------------------------------------
// AUTO-INITIALIZE SCHEMA: Create table if it doesn't exist
// ----------------------------------------------------
$create_table_sql = "CREATE TABLE IF NOT EXISTS `category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_code` varchar(50) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_code` (`category_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

mysqli_query($conn, $create_table_sql);