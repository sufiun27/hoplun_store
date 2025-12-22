<?php
// ----------------------------------------------------
// 2. DbInfo Class (Database Credentials)
// ----------------------------------------------------
class DbInfo {
    protected string $host;
    protected string $user;
    protected string $pass;

    public function __construct() {
        $this->host = "10.3.13.87"; 
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

    public function getDbName(): string {
        return $_SESSION['company'];
    }
}

// ----------------------------------------------------
// 3. Database Class (Connection + Queries)
// ----------------------------------------------------
class Database extends DbInfo {

    private ?PDO $pdo_connection = null;

    // ✅ MUST call parent constructor
    public function __construct() {
        parent::__construct();
    }

    protected function connect(): ?PDO {

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
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );

            return $this->pdo_connection;

        } catch (PDOException $e) {
            error_log("DB Connection Failed: " . $e->getMessage());

            echo "<pre style='color:red'>";
            echo "❌ DATABASE CONNECTION FAILED\n";
            echo "HOST: " . $this->getHost() . "\n";
            echo "DB: " . $this->getDbName() . "\n\n";
            echo $e->getMessage();
            echo "</pre>";
            exit;
        }
    }

    public function getConnection(): ?PDO {
        return $this->connect();
    }

    public function fetchSingleColumn(string $sql, array $params = []): string|int|null {

        $pdo = $this->getConnection();
        if ($pdo === null) {
            return null;
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchColumn();
            return $result !== false ? $result : null;

        } catch (PDOException $e) {
            error_log("Query Error: " . $e->getMessage() . " | SQL: " . $sql);
            return null;
        }
    }
}

$serverInfo = new DbInfo();
$servername = $serverInfo->getHost();
$username = $serverInfo->getUser();
$password = $serverInfo->getPass();
$database = $serverInfo->getDbName();

try {
    $conn = new PDO("sqlsrv:Server=$servername;Database=$database", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}