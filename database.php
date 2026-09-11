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
    // SUPABASE (POSTGRESQL) PDO DRIVER & AUTO-RECOVERY ADAPTER
    // ============================================================
    $pdo = null;
    $lastException = null;

    $host = $cfg['host'] ?? 'aws-0-ap-northeast-2.pooler.supabase.com';
    $port = (int)($cfg['port'] ?? 6543);
    $user = $cfg['user'] ?? 'postgres.wpzaeloeqsiacehkxvgq';
    $pass = !empty($cfg['pass']) ? $cfg['pass'] : 'Monyvann310394';
    $dbname = $cfg['name'] ?? 'postgres';

    // Extract tenant ref if available (defaulting to project ref)
    $tenantRef = 'wpzaeloeqsiacehkxvgq';
    if (preg_match('/postgres\.([a-z0-9]+)/i', $user, $m)) {
        $tenantRef = $m[1];
    }

    // Ensure pooler username includes tenant ref (e.g. postgres.wpzaeloeqsiacehkxvgq)
    if (strpos($host, 'pooler.supabase.com') !== false && strpos($user, '.') === false) {
        $user = $user . '.' . $tenantRef;
    }

    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode=require";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_TIMEOUT => 5
        ]);
    } catch (PDOException $ex) {
        $lastException = $ex;

        // Try alternative ports if initial connection fails
        $altPorts = array_diff([6543, 5432], [$port]);
        foreach ($altPorts as $p) {
            try {
                $altDsn = "pgsql:host={$host};port={$p};dbname={$dbname};sslmode=require";
                $pdo = new PDO($altDsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => true,
                    PDO::ATTR_TIMEOUT => 4
                ]);
                if ($pdo) break;
            } catch (PDOException $e) {
                // preserve primary exception if it contained an authentication error
                if (strpos($lastException->getMessage(), 'password authentication') === false) {
                    $lastException = $e;
                }
            }
        }
    }

    if (!$pdo) {
        $errMsg = $lastException ? $lastException->getMessage() : 'Unknown error';
        if (strpos($errMsg, 'password authentication failed') !== false) {
            die("Supabase Connection Failed: Password authentication failed for user '{$user}'. Please check DB_PASS in .env or reset your database password in the Supabase Dashboard.");
        } elseif (strpos($errMsg, 'ENOIDENTIFIER') !== false || strpos($errMsg, 'no tenant identifier') !== false) {
            die("Supabase Connection Failed: No tenant identifier provided. When using Supabase Pooler ({$host}), DB_USER must be formatted as 'postgres.[project_ref]' (e.g. postgres.wpzaeloeqsiacehkxvgq).");
        } else {
            die("Supabase Connection Failed: " . $errMsg);
        }
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
                // Strip MySQL-specific CREATE TABLE parameters
                $sql = preg_replace('/DEFAULT\s+CHARSET\s*=\s*\w+/i', '', $sql);
                $sql = preg_replace('/ENGINE\s*=\s*\w+/i', '', $sql);
                $sql = preg_replace('/COLLATE\s*=\s*\w+/i', '', $sql);
                // Convert INSERT IGNORE INTO -> INSERT INTO ... ON CONFLICT DO NOTHING
                if (preg_match('/INSERT\s+IGNORE\s+INTO/i', $sql)) {
                    $sql = preg_replace('/INSERT\s+IGNORE\s+INTO/i', 'INSERT INTO', $sql) . ' ON CONFLICT DO NOTHING';
                }
                // Convert REPLACE INTO -> INSERT INTO ... ON CONFLICT DO NOTHING / UPDATE
                if (preg_match('/REPLACE\s+INTO/i', $sql)) {
                    $sql = preg_replace('/REPLACE\s+INTO/i', 'INSERT INTO', $sql) . ' ON CONFLICT DO NOTHING';
                }
                // Convert MySQL DATE_ADD(NOW(), INTERVAL X MINUTE) -> (NOW() + INTERVAL 'X minute')
                if (preg_match('/DATE_ADD\s*\(\s*NOW\(\)\s*,\s*INTERVAL\s+([0-9]+)\s+MINUTE\s*\)/i', $sql, $m)) {
                    $sql = preg_replace('/DATE_ADD\s*\(\s*NOW\(\)\s*,\s*INTERVAL\s+[0-9]+\s+MINUTE\s*\)/i', "NOW() + INTERVAL '{$m[1]} minute'", $sql);
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
                    try {
                        $this->connWrapper->insertId = (int)$this->connWrapper->pdo->lastInsertId();
                    } catch (Exception $ign) {
                        $this->connWrapper->insertId = 0;
                    }
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

// Universal database helper functions (supporting both MySQLi and PostgreSQL PDO Wrapper)
if (!function_exists('db_query')) {
    function db_query($c, $sql) {
        if ($c instanceof PgSqlConnWrapper) return $c->query($sql);
        return mysqli_query($c, $sql);
    }
}
if (!function_exists('db_fetch_assoc')) {
    function db_fetch_assoc($res) {
        if ($res instanceof PgSqlResultWrapper) return $res->fetch_assoc();
        return (is_object($res) || is_resource($res)) ? mysqli_fetch_assoc($res) : false;
    }
}
if (!function_exists('db_fetch_array')) {
    function db_fetch_array($res) {
        if ($res instanceof PgSqlResultWrapper) return $res->fetch_array();
        return (is_object($res) || is_resource($res)) ? mysqli_fetch_array($res) : false;
    }
}
if (!function_exists('db_num_rows')) {
    function db_num_rows($res) {
        if ($res instanceof PgSqlResultWrapper) return $res->num_rows();
        return (is_object($res) || is_resource($res)) ? mysqli_num_rows($res) : 0;
    }
}
if (!function_exists('db_insert_id')) {
    function db_insert_id($c) {
        if ($c instanceof PgSqlConnWrapper) return $c->insertId;
        return mysqli_insert_id($c);
    }
}
if (!function_exists('db_error')) {
    function db_error($c) {
        if ($c instanceof PgSqlConnWrapper) return $c->lastError;
        return mysqli_error($c);
    }
}
if (!function_exists('db_errno')) {
    function db_errno($c) {
        if ($c instanceof PgSqlConnWrapper) return $c->lastErrno;
        return mysqli_errno($c);
    }
}
if (!function_exists('db_real_escape_string')) {
    function db_real_escape_string($c, $str) {
        if ($c instanceof PgSqlConnWrapper) return addslashes($str);
        return mysqli_real_escape_string($c, $str);
    }
}
if (!function_exists('db_prepare')) {
    function db_prepare($c, $sql) {
        if ($c instanceof PgSqlConnWrapper) return $c->prepare($sql);
        return mysqli_prepare($c, $sql);
    }
}
if (!function_exists('db_stmt_bind_param')) {
    function db_stmt_bind_param($s, $types, &...$params) {
        if ($s instanceof PgSqlStmtWrapper) return $s->bind_param($types, ...$params);
        return mysqli_stmt_bind_param($s, $types, ...$params);
    }
}
if (!function_exists('db_stmt_execute')) {
    function db_stmt_execute($s) {
        if ($s instanceof PgSqlStmtWrapper) return $s->execute();
        return mysqli_stmt_execute($s);
    }
}
if (!function_exists('db_stmt_store_result')) {
    function db_stmt_store_result($s) {
        if ($s instanceof PgSqlStmtWrapper) return $s->store_result();
        return mysqli_stmt_store_result($s);
    }
}
if (!function_exists('db_stmt_num_rows')) {
    function db_stmt_num_rows($s) {
        if ($s instanceof PgSqlStmtWrapper) return $s->num_rows();
        return mysqli_stmt_num_rows($s);
    }
}
if (!function_exists('db_stmt_close')) {
    function db_stmt_close($s) {
        if ($s instanceof PgSqlStmtWrapper) return $s->close();
        return mysqli_stmt_close($s);
    }
}
if (!function_exists('db_stmt_get_result')) {
    function db_stmt_get_result($s) {
        if ($s instanceof PgSqlStmtWrapper) return $s->get_result();
        return mysqli_stmt_get_result($s);
    }
}
