<?php
// db_config.php - Multi-Environment Database Configuration

// 1. Docker Environment Override
if (getenv('DB_HOST')) {
    return [
        "host" => getenv('DB_HOST'),
        "user" => getenv('DB_USER') ?: "inventory_user",
        "pass" => getenv('DB_PASS') ?: "inventory_pass",
        "name" => getenv('DB_NAME') ?: "inventory_db",
        "port" => (int)(getenv('DB_PORT') ?: 3306)
    ];
}

// 2. Local Environment Check
$is_local = (php_sapi_name() === 'cli')
    || (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', 'localhost:8080']))
    || (isset($_SERVER['SERVER_ADDR']) && in_array($_SERVER['SERVER_ADDR'], ['127.0.0.1', '::1']));

if ($is_local) {
    return [
        "host" => "127.0.0.1",
        "user" => "root",
        "pass" => "",
        "name" => "inventory",
        "port" => 3307
    ];
} else {
    // 3. Remote Production Hosting
    return [
        "host" => "sql310.infinityfree.com",
        "user" => "if0_42693065",
        "pass" => "Munyvann3103094",
        "name" => "if0_42693065_inventory",
        "port" => 3306
    ];
}
