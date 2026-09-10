<?php
// db_config.php - Multi-Environment Database Configuration

// Load .env file automatically if present
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $val) = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            putenv("{$key}={$val}");
            $_ENV[$key] = $val;
            $_SERVER[$key] = $val;
        }
    }
}

// ============================================================
// LOCAL MYSQL CONFIGURATION (ACTIVE FOR XAMPP / MYSQLI)
// ============================================================
$db_host_env = getenv('DB_HOST');

if (!empty($db_host_env) && strpos($db_host_env, 'postgres') === false && strpos($db_host_env, 'dpg-') === false) {
    return [
        "host" => $db_host_env,
        "user" => getenv('DB_USER') ?: "root",
        "pass" => getenv('DB_PASS') ?: "",
        "name" => getenv('DB_NAME') ?: "inventory",
        "port" => (int)(getenv('DB_PORT') ?: 3306)
    ];
}

// Check if inside Docker container (entrypoint.sh exists)
if (file_exists('/usr/local/bin/entrypoint.sh') || file_exists('/var/www/html/entrypoint.sh')) {
    return [
        "host" => "127.0.0.1",
        "user" => "root",
        "pass" => "",
        "name" => "inventory",
        "port" => 3306
    ];
}

// Local Environment Check (XAMPP on Windows)
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
    // Remote Production Hosting fallback
    return [
        "host" => "sql310.infinityfree.com",
        "user" => "if0_42693065",
        "pass" => "Munyvann3103094",
        "name" => "if0_42693065_inventory",
        "port" => 3306
    ];
}

/*
// ============================================================
// SUPABASE (POSTGRESQL) CONFIGURATION (COMMENTED OUT)
// Note: Your current PHP code uses `mysqli_connect()`, which is 
// MySQL-only. Supabase requires PostgreSQL (PDO pgsql).
// ============================================================
return [
    "driver"   => "pgsql",
    "host"     => "aws-0-ap-northeast-2.pooler.supabase.com",
    "port"     => 6543,
    "name"     => "postgres",
    "user"     => "postgres.wpzaeloeqsiacehkxvgq",
    "pass"     => "Munyvann.310394",
    "url"      => "postgresql://postgres.wpzaeloeqsiacehkxvgq:Munyvann.310394@aws-0-ap-northeast-2.pooler.supabase.com:6543/postgres"
];
*/



