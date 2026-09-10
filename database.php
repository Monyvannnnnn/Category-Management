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
// AUTO-INITIALIZE CATEGORY TABLE
// ----------------------------------------------------
$create_category_sql = "CREATE TABLE IF NOT EXISTS `category` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `category_code` varchar(50) NOT NULL,
  `category_name` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  UNIQUE KEY `user_cat_code` (`user_id`, `category_code`),
  UNIQUE KEY `user_cat_name` (`user_id`, `category_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

mysqli_query($conn, $create_category_sql);

// Check if user_id column exists in category table
$cat_uid_check = mysqli_query($conn, "SHOW COLUMNS FROM category LIKE 'user_id'");
if ($cat_uid_check && mysqli_num_rows($cat_uid_check) == 0) {
    mysqli_query($conn, "ALTER TABLE category ADD COLUMN user_id int(11) NOT NULL AFTER id, ADD KEY (user_id)");
}

// Check if lastupdate column exists, if not, add it
$column_check = mysqli_query($conn, "SHOW COLUMNS FROM category LIKE 'lastupdate'");
if ($column_check && mysqli_num_rows($column_check) == 0) {
    mysqli_query($conn, "ALTER TABLE category ADD COLUMN lastupdate timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()");
}

// Migration: Drop legacy single-column UNIQUE keys and ensure composite per-user UNIQUE keys exist
$cat_idx = mysqli_query($conn, "SHOW INDEX FROM category");
$cat_keys = [];
if ($cat_idx) {
    while ($r = mysqli_fetch_assoc($cat_idx)) {
        $cat_keys[$r['Key_name']] = true;
    }
}
if (isset($cat_keys['category_code'])) {
    @mysqli_query($conn, "DROP INDEX `category_code` ON `category`");
}
if (isset($cat_keys['category_name'])) {
    @mysqli_query($conn, "DROP INDEX `category_name` ON `category`");
}
if (!isset($cat_keys['user_cat_code'])) {
    @mysqli_query($conn, "ALTER TABLE `category` ADD UNIQUE KEY `user_cat_code` (`user_id`, `category_code`)");
}
if (!isset($cat_keys['user_cat_name'])) {
    @mysqli_query($conn, "ALTER TABLE `category` ADD UNIQUE KEY `user_cat_name` (`user_id`, `category_name`)");
}

// ----------------------------------------------------
// AUTO-INITIALIZE PRODUCT TABLE
// ----------------------------------------------------
$create_product_sql = "CREATE TABLE IF NOT EXISTS `product` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `product_code` varchar(50) NOT NULL,
  `product_name` varchar(100) NOT NULL,
  `category_id` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `quantity` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `lastupdate` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `category_id` (`category_id`),
  UNIQUE KEY `user_prod_code` (`user_id`, `product_code`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

mysqli_query($conn, $create_product_sql);

// Check if user_id column exists in product table
$prod_uid_check = mysqli_query($conn, "SHOW COLUMNS FROM product LIKE 'user_id'");
if ($prod_uid_check && mysqli_num_rows($prod_uid_check) == 0) {
    mysqli_query($conn, "ALTER TABLE product ADD COLUMN user_id int(11) NOT NULL AFTER id, ADD KEY (user_id)");
}

// Migration: Drop legacy single-column UNIQUE keys and ensure composite per-user UNIQUE key exists
$prod_idx = mysqli_query($conn, "SHOW INDEX FROM product");
$prod_keys = [];
if ($prod_idx) {
    while ($r = mysqli_fetch_assoc($prod_idx)) {
        $prod_keys[$r['Key_name']] = true;
    }
}
if (isset($prod_keys['product_code'])) {
    @mysqli_query($conn, "DROP INDEX `product_code` ON `product`");
}
if (!isset($prod_keys['user_prod_code'])) {
    @mysqli_query($conn, "ALTER TABLE `product` ADD UNIQUE KEY `user_prod_code` (`user_id`, `product_code`)");
}

// ----------------------------------------------------
// AUTO-INITIALIZE SYSTEM SETTINGS & SUBSCRIBERS TABLE
// ----------------------------------------------------
$create_settings_sql = "CREATE TABLE IF NOT EXISTS `system_settings` (
  `setting_key` varchar(50) NOT NULL,
  `setting_value` varchar(255) NOT NULL,
  PRIMARY KEY (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

mysqli_query($conn, $create_settings_sql);
mysqli_query($conn, "INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`) VALUES ('auto_telegram_notify', '1')");

$create_subscribers_sql = "CREATE TABLE IF NOT EXISTS `telegram_subscribers` (
  `chat_id` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`chat_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

mysqli_query($conn, $create_subscribers_sql);

// ----------------------------------------------------
// AUTO-INITIALIZE USERS TABLE
// ----------------------------------------------------
$create_users_sql = "CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'user',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

mysqli_query($conn, $create_users_sql);

// Check if role column exists in users table, add if missing
$role_check = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'role'");
if ($role_check && mysqli_num_rows($role_check) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN `role` varchar(50) NOT NULL DEFAULT 'user' AFTER `name`");
}

// ----------------------------------------------------
// AUTO-INITIALIZE USER TELEGRAM BOTS TABLE
// ----------------------------------------------------
$create_user_bots_sql = "CREATE TABLE IF NOT EXISTS `user_telegram_bots` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `bot_token` varchar(255) NOT NULL,
  `bot_username` varchar(100) NOT NULL,
  `chat_id` varchar(100) DEFAULT NULL,
  `connection_code` varchar(50) DEFAULT NULL,
  `code_expires_at` datetime DEFAULT NULL,
  `connected_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";

mysqli_query($conn, $create_user_bots_sql);
