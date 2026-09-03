<?php

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

$cfg = require __DIR__ . "/db_config.php";

$conn = mysqli_connect($cfg["host"], $cfg["user"], $cfg["pass"], $cfg["name"], $cfg["port"]);

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
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_code` (`category_code`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

mysqli_query($conn, $create_table_sql);

// Check if lastupdate column exists, if not, add it
$column_check = mysqli_query($conn, "SHOW COLUMNS FROM category LIKE 'lastupdate'");
if ($column_check && mysqli_num_rows($column_check) == 0) {
    mysqli_query($conn, "ALTER TABLE category ADD COLUMN lastupdate timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()");
}

// ----------------------------------------------------
// DATABASE MIGRATION: Rename table categories -> category if needed
// ----------------------------------------------------
$check_old_table = mysqli_query($conn, "SHOW TABLES LIKE 'categories'");
if ($check_old_table && mysqli_num_rows($check_old_table) > 0) {
    $check_new_table = mysqli_query($conn, "SHOW TABLES LIKE 'category'");
    if ($check_new_table && mysqli_num_rows($check_new_table) == 0) {
        // Simple rename if category table doesn't exist
        mysqli_query($conn, "RENAME TABLE `categories` TO `category`");
    } else {
        // Merge data if both tables exist, then drop old table
        mysqli_query($conn, "INSERT IGNORE INTO `category` SELECT * FROM `categories`");
        mysqli_query($conn, "DROP TABLE `categories`");
    }
}

// ----------------------------------------------------
// DATABASE MIGRATION: Clean duplicates & enforce Unique constraint if missing
// ----------------------------------------------------
$index_check = mysqli_query($conn, "SHOW INDEX FROM category WHERE Key_name = 'category_name'");
if ($index_check && mysqli_num_rows($index_check) == 0) {
    // 1. Delete duplicate rows, keeping only the lowest ID per name
    $delete_dups = "DELETE c1 FROM category c1
                    INNER JOIN category c2 
                    WHERE c1.id > c2.id 
                      AND LOWER(TRIM(c1.category_name)) = LOWER(TRIM(c2.category_name))";
    mysqli_query($conn, $delete_dups);

    // 2. Enforce unique constraint
    mysqli_query($conn, "ALTER TABLE category ADD UNIQUE KEY `category_name` (`category_name`)");
}

// ----------------------------------------------------
// AUTO-INITIALIZE SYSTEM SETTINGS TABLE
// ----------------------------------------------------
$create_settings_sql = "CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

mysqli_query($conn, $create_settings_sql);
mysqli_query($conn, "INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`) VALUES ('auto_telegram_notify', '1')");