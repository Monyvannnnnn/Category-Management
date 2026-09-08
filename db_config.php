<?php
// db_config.php - Multi-Environment Database Configuration

$db_host_env = getenv('DB_HOST');
$db_user_env = getenv('DB_USER');
$db_pass_env = getenv('DB_PASS');
$db_name_env = getenv('DB_NAME');
$db_port_env = getenv('DB_PORT');

// 1. External MySQL host specified via environment variables (excluding Postgres)
if (!empty($db_host_env) && strpos($db_host_env, 'postgres') === false && strpos($db_host_env, 'dpg-') === false) {
    return [
        "host" => $db_host_env,
        "user" => $db_user_env ?: "root",
        "pass" => $db_pass_env ?: "",
        "name" => $db_name_env ?: "inventory",
        "port" => (int)($db_port_env ?: 3306)
    ];
}

// 2. Local environment / Docker Container check (try local 3306 and 3307)
$local_ports = [3306, 3307];
foreach ($local_ports as $p) {
    $c = @mysqli_connect("127.0.0.1", "root", "", "", $p);
    if ($c) {
        mysqli_close($c);
        return [
            "host" => "127.0.0.1",
            "user" => "root",
            "pass" => "",
            "name" => "inventory",
            "port" => $p
        ];
    }
}

// 3. Fallback for Docker / container environments if service hasn't finished opening socket yet
if (file_exists('/usr/local/bin/entrypoint.sh') || file_exists('/var/www/html/entrypoint.sh')) {
    return [
        "host" => "127.0.0.1",
        "user" => "root",
        "pass" => "",
        "name" => "inventory",
        "port" => 3306
    ];
}

// 4. Remote Production Hosting (InfinityFree / Web Host fallback)
return [
    "host" => "sql310.infinityfree.com",
    "user" => "if0_42693065",
    "pass" => "Munyvann3103094",
    "name" => "if0_42693065_inventory",
    "port" => 3306
];
