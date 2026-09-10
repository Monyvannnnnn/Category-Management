<?php
// Configuration Constants
define("DB_HOST", "127.0.0.1");
define("DB_PORT", "3307"); // XAMPP MariaDB/MySQL port
define("DB_NAME", "telegram_test");
define("DB_USER", "root");
define("DB_PASS", "");

define("DEFAULT_BOT_TOKEN", "8736337451:AAEtwDgtwUpWGnV4cIrMNKwNjHaAV8J18jc");
define("DEFAULT_BOT_USERNAME", "reportpush_bot");

/**
 * Returns a PDO MySQL Connection.
 * Tries XAMPP port 3307 first, then default port 3306.
 */
function getDBConnection() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $ports = [3307, 3306];

    foreach ($ports as $port) {
        try {
            // Try connecting directly to target DB
            $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            return $pdo;
        } catch (PDOException $e) {
            // Try creating DB if missing on this port
            try {
                $pdoHost = new PDO("mysql:host=" . DB_HOST . ";port=" . $port . ";charset=utf8mb4", DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                ]);
                $pdoHost->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` DEFAULT CHARACTER SET utf8mb4");
                $pdoHost->exec("USE `" . DB_NAME . "`");
                
                $schema = file_get_contents(__DIR__ . "/../database/schema.sql");
                $pdoHost->exec($schema);

                $pdo = new PDO("mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                return $pdo;
            } catch (PDOException $ex) {
                // Try next port
            }
        }
    }

    // Fallback to SQLite if MySQL is unreachable
    $sqlitePath = __DIR__ . "/../database/database.sq3";
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
    $pdo->exec("CREATE TABLE IF NOT EXISTS telegram_push_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        chat_id TEXT NOT NULL,
        message TEXT NOT NULL,
        status TEXT NOT NULL,
        error_message TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    return $pdo;
}
