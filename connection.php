<?php

declare(strict_types=1);

// ------------------------------------
// 1. DbInfo Class (Credentials)
// ------------------------------------
class DbInfo {
    protected string $host;
    protected string $user;
    protected string $pass;

    public function __construct() {
        // ✅ FIXED INSTANCE FORMAT
        $this->host = "10.3.13.87"; // BEST for Laragon
        $this->user = "sa";
        $this->pass = "sa@123";
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
}

// ------------------------------------
// 2. Session Start
// ------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------
// 3. Dbh Class (Connection Handler)
// ------------------------------------
class Dbh extends DbInfo {
    private string $db_name = "inventoryuser";

    protected function connect(): ?PDO {

        $dsn = "sqlsrv:Server=" . $this->getHost() . ";Database=" . $this->db_name;

        try {
            $pdo = new PDO(
                $dsn,
                $this->getUser(),
                $this->getPass(),
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );

            return $pdo;

        } catch (PDOException $e) {

            // ✅ LOG ERROR
            error_log("DB Connection Error: " . $e->getMessage());

            // ✅ SHOW USER FRIENDLY MESSAGE
            echo "Database connection failed. Please contact administrator.";

            // ✅ VERY IMPORTANT RETURN NULL
            return null;
        }
    }

    public function getConnection(): ?PDO {
        return $this->connect();
    }
}
