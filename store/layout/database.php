<?php

/**
 * Production-Grade Database Management
 * Using PDO for SQL Server (sqlsrv)
 */

class DbInfo {
    protected string $host;
    protected string $user;
    protected string $pass;

    public function __construct() {
        /**
         * PRODUCTION BEST PRACTICE: 
         * Load credentials from Environment Variables or a secure config file.
         * Defaulting to your provided values if env is not set.
         */
        $this->host = getenv('DB_HOST') ?: "10.3.13.87";
        $this->user = getenv('DB_USER') ?: "sa";
        $this->pass = getenv('DB_PASS') ?: "sa@123";
    }

    public function getHost(): string {
        return $this->host;
    }

    public function getUser(): string {
        return $this->user;
    }

    public function getPass(): string {
        return $this->pass;
    }

    public function getDbName(): string {
        // Ensure session is started before calling
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['company'] ?? 'default_db';
    }
}

class Database extends DbInfo {
    private ?PDO $pdo_connection = null;

    public function __construct() {
        parent::__construct();
    }

    /**
     * Establishes a singleton PDO connection
     */
    protected function connect(): PDO {
        if ($this->pdo_connection !== null) {
            return $this->pdo_connection;
        }

        $dsn = "sqlsrv:Server=" . $this->getHost() . ";Database=" . $this->getDbName();

        try {
            $this->pdo_connection = new PDO(
                $dsn,
                $this->getUser(),
                $this->getPass(),
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    // SQL Server specific timeout if needed
                    PDO::SQLSRV_ATTR_QUERY_TIMEOUT => 30 
                ]
            );

            return $this->pdo_connection;

        } catch (PDOException $e) {
            // Log the detailed error for developers
            error_log("DB Connection Failed: " . $e->getMessage());

            // Generic message for users (Security: Hide host/IP/credentials)
            die("Error: A database connection error occurred. Please try again later.");
        }
    }

    public function getConnection(): PDO {
        return $this->connect();
    }

    /**
     * Executes a query and returns a single value
     */
    public function fetchSingleColumn(string $sql, array $params = []): string|int|null {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchColumn();
            return $result !== false ? $result : null;

        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage() . " | SQL: " . $sql);
            return null;
        }
    }
}

/**
 * REFACTOR: DbhReport should likely inherit from Database to reuse 
 * the connection logic rather than duplicating it.
 */
class DbhReport extends Database {
    public function __construct() {
        parent::__construct();
    }

    // This class now inherits getConnection() and fetchSingleColumn()
    // and shares the same PDO instance.
}

// ----------------------------------------------------
// Usage Example (Clean Implementation)
// ----------------------------------------------------

$db = new Database();
$conn = $db->getConnection(); 

// No need for separate try-catch blocks here as the class handles it.