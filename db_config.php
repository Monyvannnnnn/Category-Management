<?php
// db_config.php - Multi-Environment Database Configuration

// 1. Check if running inside Docker container with local MariaDB or external MySQL host
$db_host_env = getenv('DB_HOST');

if (!empty($db_host_env) && strpos($db_host_env, 'postgres') === false && strpos($db_host_env, 'dpg-') === false) {
    // External MySQL host specified (e.g. PlanetScale, Clever Cloud, docker-compose mysql container)
    return [
        "host" => $db_host_env,
        "user" => getenv('DB_USER') ?: "root",
        "pass" => getenv('DB_PASS') ?: "",
        "name" => getenv('DB_NAME') ?: "inventory",
        "port" => (int)(getenv('DB_PORT') ?: 3306)
    ];
}

// 2. Check if inside Docker container (entrypoint.sh exists)
if (file_exists('/usr/local/bin/entrypoint.sh') || file_exists('/var/www/html/entrypoint.sh')) {
    return [
        "host" => "127.0.0.1",
        "user" => "root",
        "pass" => "",
        "name" => "inventory",
        "port" => 3306
    ];
}

// 3. Local Environment Check (XAMPP on Windows)
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
    // 4. Remote Production Hosting (InfinityFree / Web Host fallback)
    return [
        "host" => "sql310.infinityfree.com",
        "user" => "if0_42693065",
        "pass" => "Munyvann3103094",
        "name" => "if0_42693065_inventory",
        "port" => 3306
    ];
}

