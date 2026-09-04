<?php

if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

if (file_exists(__DIR__ . "/db_config.php")) {
    $cfg = require __DIR__ . "/db_config.php";
} else {
    $cfg = [
        "host" => getenv('DB_HOST') ?: "127.0.0.1",
        "user" => getenv('DB_USER') ?: "root",
        "pass" => getenv('DB_PASS') ?: "",
        "name" => getenv('DB_NAME') ?: "inventory",
        "port" => (int)(getenv('DB_PORT') ?: 3306)
    ];
}

$conn = mysqli_connect($cfg["host"], $cfg["user"], $cfg["pass"], $cfg["name"], $cfg["port"]);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// ----------------------------------------------------
// AUTO-INITIALIZE CATEGORY TABLE & SAMPLE SEED DATA
// ----------------------------------------------------
$create_category_sql = "CREATE TABLE IF NOT EXISTS `category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_code` varchar(50) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `category_code` (`category_code`),
  UNIQUE KEY `category_name` (`category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

mysqli_query($conn, $create_category_sql);

// Check if lastupdate column exists, if not, add it
$column_check = mysqli_query($conn, "SHOW COLUMNS FROM category LIKE 'lastupdate'");
if ($column_check && mysqli_num_rows($column_check) == 0) {
    mysqli_query($conn, "ALTER TABLE category ADD COLUMN lastupdate timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()");
}

// Seed default categories if table is empty
$cat_count = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM category");
if ($cat_count && ($r = mysqli_fetch_assoc($cat_count)) && (int)$r['cnt'] === 0) {
    mysqli_query($conn, "INSERT IGNORE INTO `category` (`id`, `category_code`, `category_name`) VALUES
        (1, 'CAT-10', 'Electronics'),
        (2, 'CAT-20', 'Computer Accessories'),
        (3, 'CAT-30', 'Office Furniture'),
        (4, 'CAT-40', 'Smart Home Gadgets'),
        (5, 'CAT-50', 'Mobile Devices')");
}

// ----------------------------------------------------
// AUTO-INITIALIZE PRODUCT TABLE & SAMPLE SEED DATA
// ----------------------------------------------------
$create_product_sql = "CREATE TABLE IF NOT EXISTS `product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_code` varchar(50) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `category_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `product_code` (`product_code`),
  KEY `category_id` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

mysqli_query($conn, $create_product_sql);

// Seed default products if table is empty
$prod_count = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM product");
if ($prod_count && ($r = mysqli_fetch_assoc($prod_count)) && (int)$r['cnt'] === 0) {
    mysqli_query($conn, "INSERT IGNORE INTO `product` (`product_code`, `product_name`, `category_id`, `price`, `quantity`) VALUES
        ('PRD-101', 'Wireless Ergonomic Mouse', 2, 29.99, 45),
        ('PRD-102', 'RGB Mechanical Keyboard', 2, 79.50, 18),
        ('PRD-103', '27-inch 4K UHD Monitor', 1, 349.00, 8),
        ('PRD-104', 'Ergonomic Mesh Office Chair', 3, 199.99, 12),
        ('PRD-105', 'Smart Wi-Fi LED Light Strip', 4, 24.95, 60),
        ('PRD-106', 'Flagship Smartphone 128GB', 5, 899.00, 4)");
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