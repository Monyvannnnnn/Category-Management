<?php
// Configuration Constants

/*
// ============================================================
// OLD DATABASE CONFIGURATION (COMMENTED OUT)
// ============================================================
define("DB_HOST", "127.0.0.1");
define("DB_PORT", "3307"); // XAMPP MariaDB/MySQL port
define("DB_NAME", "telegram_test");
define("DB_USER", "root");
define("DB_PASS", "");
*/

// ============================================================
// NEW SUPABASE (POSTGRESQL) CONFIGURATION
// ============================================================
define("DB_HOST", getenv('DB_HOST') ?: "db.wpzaeloeqsiacehkxvgq.supabase.co");
define("DB_PORT", getenv('DB_PORT') ?: "5432");
define("DB_NAME", getenv('DB_NAME') ?: "postgres");
define("DB_USER", getenv('DB_USER') ?: "postgres");
define("DB_PASS", getenv('DB_PASS') ?: "Monyvann310394");
define("DB_DRIVER", "pgsql");
define("DB_URL", getenv('DATABASE_URL') ?: "postgresql://postgres:Monyvann310394@db.wpzaeloeqsiacehkxvgq.supabase.co:5432/postgres");

define("DEFAULT_BOT_TOKEN", "8736337451:AAEtwDgtwUpWGnV4cIrMNKwNjHaAV8J18jc");
define("DEFAULT_BOT_USERNAME", "reportpush_bot");

/**
 * Returns a PDO Connection for Supabase (PostgreSQL) or fallback.
 */
function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    try {
        $host = DB_HOST;
        $port = DB_PORT;
        $user = DB_USER;
        $pass = DB_PASS;
        $dbname = DB_NAME;

        if (strpos($host, 'pooler.supabase.com') !== false && strpos($user, '.') === false) {
            $user = $user . '.wpzaeloeqsiacehkxvgq';
        }

        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true
        ]);
        return $pdo;
    } catch (PDOException $e) {
        // Fallback to MySQL if PDO pgsql connection fails
        try {
            $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            return $pdo;
        } catch (PDOException $ex) {
            // Fallback to SQLite if MySQL/PgSQL unreachable
            $sqlitePath = __DIR__ . "/../database/database.sq3";
            @mkdir(dirname($sqlitePath), 0777, true);
            $pdo = new PDO("sqlite:" . $sqlitePath);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            $pdo->exec("CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT NOT NULL UNIQUE,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                name TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");
            $pdo->exec("CREATE TABLE IF NOT EXISTS user_telegram_bots (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL UNIQUE,
                bot_token TEXT NOT NULL,
                bot_username TEXT NOT NULL,
                chat_id TEXT DEFAULT NULL,
                connection_code TEXT DEFAULT NULL,
                code_expires_at DATETIME DEFAULT NULL,
                connected_at DATETIME DEFAULT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )");

            return $pdo;
        }
    }
}
