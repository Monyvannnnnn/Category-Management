<?php
// database.php - Dual MySQL / PostgreSQL Database Connection Layer

if (file_exists(__DIR__ . "/db_config.php")) {
    $cfg = require __DIR__ . "/db_config.php";
} else {
    $cfg = [
        "driver" => getenv('DB_DRIVER') ?: "mysql",
        "host"   => getenv('DB_HOST') ?: "127.0.0.1",
        "user"   => getenv('DB_USER') ?: "root",
        "pass"   => getenv('DB_PASS') ?: "",
        "name"   => getenv('DB_NAME') ?: "inventory",
        "port"   => (int)(getenv('DB_PORT') ?: 3306)
    ];
}

$driver = $cfg['driver'] ?? 'mysql';

if ($driver === 'pgsql') {
    // ============================================================
    // SUPABASE (POSTGRESQL) PDO DRIVER & MYSQLI ADAPTER
    // ============================================================
    try {
        $dsn = "pgsql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};sslmode=require";
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
    } catch (PDOException $e) {
        die("Supabase Connection Failed: " . $e->getMessage());
    }

    if (!class_exists('PgSqlResultWrapper')) {
        class PgSqlResultWrapper {
            private $stmt;
            public function __construct($stmt) {
                $this->stmt = $stmt;
            }
            public function fetch_assoc() {
                return $this->stmt ? $this->stmt->fetch(PDO::FETCH_ASSOC) : false;
            }
            public function fetch_array() {
                return $this->stmt ? $this->stmt->fetch(PDO::FETCH_BOTH) : false;
            }
            public function num_rows() {
                return $this->stmt ? $this->stmt->rowCount() : 0;
            }
        }
    }

    if (!class_exists('PgSqlConnWrapper')) {
        class PgSqlConnWrapper {
            public $pdo;
            public $lastError = "";
            public $lastErrno = 0;
            public $insertId = 0;

            public function __construct($pdo) {
                $this->pdo = $pdo;
            }

            private function normalizeSql($sql) {
                // Strip MySQL backticks for PostgreSQL compatibility
                $sql = str_replace('`', '"', $sql);
                // Convert INSERT IGNORE INTO -> INSERT INTO ... ON CONFLICT DO NOTHING
                if (preg_match('/INSERT\s+IGNORE\s+INTO/i', $sql)) {
                    $sql = preg_replace('/INSERT\s+IGNORE\s+INTO/i', 'INSERT INTO', $sql) . ' ON CONFLICT DO NOTHING';
                }
                return $sql;
            }

            public function query($sql) {
                try {
                    $sql = $this->normalizeSql($sql);
                    $stmt = $this->pdo->query($sql);
                    $this->lastError = "";
                    $this->lastErrno = 0;
                    return new PgSqlResultWrapper($stmt);
                } catch (Exception $e) {
                    $this->lastError = $e->getMessage();
                    $this->lastErrno = $e->getCode();
                    return false;
                }
            }

            public function prepare($sql) {
                try {
                    $sql = $this->normalizeSql($sql);
                    $stmt = $this->pdo->prepare($sql);
                    return new PgSqlStmtWrapper($stmt, $this);
                } catch (Exception $e) {
                    $this->lastError = $e->getMessage();
                    return false;
                }
            }
        }
    }

    if (!class_exists('PgSqlStmtWrapper')) {
        class PgSqlStmtWrapper {
            private $stmt;
            private $connWrapper;
            private $params = [];

            public function __construct($stmt, $connWrapper) {
                $this->stmt = $stmt;
                $this->connWrapper = $connWrapper;
            }

            public function bind_param($types, &...$params) {
                $this->params = &$params;
                return true;
            }

            public function execute() {
                try {
                    $res = $this->stmt->execute($this->params);
                    $this->connWrapper->insertId = (int)$this->connWrapper->pdo->lastInsertId();
                    return $res;
                } catch (Exception $e) {
                    $this->connWrapper->lastError = $e->getMessage();
                    return false;
                }
            }

            public function get_result() {
                return new PgSqlResultWrapper($this->stmt);
            }

            public function num_rows() {
                return $this->stmt->rowCount();
            }

            public function store_result() {
                return true;
            }

            public function close() {
                return true;
            }
        }
    }

    $conn = new PgSqlConnWrapper($pdo);

    // Provide polyfill wrapper functions for MySQLi if running under PostgreSQL
    if (!function_exists('mysqli_query')) {
        function mysqli_query($c, $sql) { return $c instanceof PgSqlConnWrapper ? $c->query($sql) : false; }
    }
    if (!function_exists('mysqli_fetch_assoc')) {
        function mysqli_fetch_assoc($res) { return $res instanceof PgSqlResultWrapper ? $res->fetch_assoc() : false; }
    }
    if (!function_exists('mysqli_fetch_array')) {
        function mysqli_fetch_array($res) { return $res instanceof PgSqlResultWrapper ? $res->fetch_array() : false; }
    }
    if (!function_exists('mysqli_num_rows')) {
        function mysqli_num_rows($res) { return $res instanceof PgSqlResultWrapper ? $res->num_rows() : 0; }
    }
    if (!function_exists('mysqli_insert_id')) {
        function mysqli_insert_id($c) { return $c instanceof PgSqlConnWrapper ? $c->insertId : 0; }
    }
    if (!function_exists('mysqli_error')) {
        function mysqli_error($c) { return $c instanceof PgSqlConnWrapper ? $c->lastError : ''; }
    }
    if (!function_exists('mysqli_errno')) {
        function mysqli_errno($c) { return $c instanceof PgSqlConnWrapper ? $c->lastErrno : 0; }
    }
    if (!function_exists('mysqli_real_escape_string')) {
        function mysqli_real_escape_string($c, $str) { return addslashes($str); }
    }
    if (!function_exists('mysqli_prepare')) {
        function mysqli_prepare($c, $sql) { return $c instanceof PgSqlConnWrapper ? $c->prepare($sql) : false; }
    }
    if (!function_exists('mysqli_stmt_bind_param')) {
        function mysqli_stmt_bind_param($s, $types, &...$params) { return $s instanceof PgSqlStmtWrapper ? $s->bind_param($types, ...$params) : false; }
    }
    if (!function_exists('mysqli_stmt_execute')) {
        function mysqli_stmt_execute($s) { return $s instanceof PgSqlStmtWrapper ? $s->execute() : false; }
    }
    if (!function_exists('mysqli_stmt_store_result')) {
        function mysqli_stmt_store_result($s) { return $s instanceof PgSqlStmtWrapper ? $s->store_result() : true; }
    }
    if (!function_exists('mysqli_stmt_num_rows')) {
        function mysqli_stmt_num_rows($s) { return $s instanceof PgSqlStmtWrapper ? $s->num_rows() : 0; }
    }
    if (!function_exists('mysqli_stmt_close')) {
        function mysqli_stmt_close($s) { return $s instanceof PgSqlStmtWrapper ? $s->close() : true; }
    }
    if (!function_exists('mysqli_stmt_get_result')) {
        function mysqli_stmt_get_result($s) { return $s instanceof PgSqlStmtWrapper ? $s->get_result() : false; }
    }

} else {
    // ============================================================
    // STANDARD MYSQLI DRIVER (FOR LOCAL XAMPP / MYSQL)
    // ============================================================
    if (function_exists('mysqli_report')) {
        mysqli_report(MYSQLI_REPORT_OFF);
    }
    $conn = mysqli_connect($cfg["host"], $cfg["user"], $cfg["pass"], $cfg["name"], $cfg["port"]);
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }
}
