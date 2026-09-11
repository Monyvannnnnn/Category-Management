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
$driver = getenv('DB_DRIVER') ?: ($_ENV['DB_DRIVER'] ?? ($_SERVER['DB_DRIVER'] ?? ''));
$host_header = $_SERVER['HTTP_HOST'] ?? '';
$is_vercel = !empty(getenv('VERCEL')) 
          || !empty(getenv('VERCEL_ENV')) 
          || !empty($_ENV['VERCEL']) 
          || !empty($_SERVER['VERCEL']) 
          || strpos($host_header, 'vercel.app') !== false;

if ($driver === 'pgsql' || $is_vercel || (getenv('DB_HOST') && strpos(getenv('DB_HOST'), 'supabase') !== false)) {
    $host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? ($_SERVER['DB_HOST'] ?? "aws-0-ap-northeast-2.pooler.supabase.com"));
    $user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? ($_SERVER['DB_USER'] ?? "postgres.wpzaeloeqsiacehkxvgq"));
    
    // Ensure pooler username includes the Supabase tenant project ref
    if (strpos($host, 'pooler.supabase.com') !== false && strpos($user, '.') === false) {
        $user = $user . ".wpzaeloeqsiacehkxvgq";
    }

    $pass = getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? ($_SERVER['DB_PASS'] ?? "Monyvann310394"));

    return [
        "driver"   => "pgsql",
        "host"     => $host,
        "port"     => (int)(getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? ($_SERVER['DB_PORT'] ?? 6543))),
        "name"     => getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? ($_SERVER['DB_NAME'] ?? "postgres")),
        "user"     => $user,
        "pass"     => $pass,
        "url"      => getenv('DATABASE_URL') ?: "postgresql://{$user}:{$pass}@{$host}:6543/postgres"
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
