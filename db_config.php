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

// 1. Check if running on Vercel or explicitly requested PostgreSQL
$driver = getenv('DB_DRIVER');
$is_vercel = !empty(getenv('VERCEL')) || !empty(getenv('VERCEL_ENV'));

if ($driver === 'pgsql' || $is_vercel || (getenv('DB_HOST') && strpos(getenv('DB_HOST'), 'supabase') !== false)) {
    return [
        "driver"   => "pgsql",
        "host"     => getenv('DB_HOST') ?: "aws-0-ap-northeast-2.pooler.supabase.com",
        "port"     => (int)(getenv('DB_PORT') ?: 6543),
        "name"     => getenv('DB_NAME') ?: "postgres",
        "user"     => getenv('DB_USER') ?: "postgres.wpzaeloeqsiacehkxvgq",
        "pass"     => getenv('DB_PASS') ?: "Munyvann.310394",
        "url"      => getenv('DATABASE_URL') ?: "postgresql://postgres.wpzaeloeqsiacehkxvgq:Munyvann.310394@aws-0-ap-northeast-2.pooler.supabase.com:6543/postgres"
    ];
}

// 2. Check if inside Docker container (entrypoint.sh exists)
if (file_exists('/usr/local/bin/entrypoint.sh') || file_exists('/var/www/html/entrypoint.sh')) {
    return [
        "driver" => "mysql",
        "host"   => "127.0.0.1",
        "user"   => "root",
        "pass"   => "",
        "name"   => "inventory",
        "port"   => 3306
    ];
}

// 3. Local Environment Check (XAMPP on Windows)
$is_local = (php_sapi_name() === 'cli')
    || (isset($_SERVER['HTTP_HOST']) && in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', 'localhost:8080']))
    || (isset($_SERVER['SERVER_ADDR']) && in_array($_SERVER['SERVER_ADDR'], ['127.0.0.1', '::1']));

if ($is_local) {
    return [
        "driver" => "mysql",
        "host"   => "127.0.0.1",
        "user"   => "root",
        "pass"   => "",
        "name"   => "inventory",
        "port"   => 3307
    ];
} else {
    // Remote Production Hosting fallback
    return [
        "driver" => "mysql",
        "host"   => "sql310.infinityfree.com",
        "user"   => "if0_42693065",
        "pass"   => "Munyvann3103094",
        "name"   => "if0_42693065_inventory",
        "port"   => 3306
    ];
}
