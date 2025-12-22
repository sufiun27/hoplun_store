<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}




// ✅ ENABLE FULL ERROR DISPLAY (DEBUG MODE)
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// ✅ Set secure timezone
date_default_timezone_set('Asia/Dhaka');


// ------------------------
// ✅ DATABASE CONNECTION CLASS
// ------------------------
class Dbh
{
    private string $host = "10.3.13.87";   // ✅ SQL Server IP
    private string $user = "sa";
    private string $pass = "sa@123";
    private string $db_name = "inventoryuser";

    public function connect(): PDO
    {
        $dsn = "sqlsrv:Server=" . $this->host . ";Database=" . $this->db_name;

        try {
            $pdo = new PDO($dsn, $this->user, $this->pass);
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            echo "<pre style='color:red; font-weight:bold;'>
❌ DATABASE CONNECTION FAILED:
" . $e->getMessage() . "
</pre>";
            exit;
        }
    }
}

// ------------------------
// ✅ CSRF AUTHENTICATION CLASS
// ------------------------
class AuthCsrf extends Dbh
{
    public function authenticate(?string $token, ?int $uid): bool
    {
        if (!$token || !$uid) {
            echo "<pre style='color:red;'>❌ TOKEN OR UID MISSING</pre>";
            return false;
        }

        try {
            $pdo = $this->connect();

            $query = "SELECT csrf FROM user_token WHERE u_id = :uid";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':uid', $uid, PDO::PARAM_INT);
            $stmt->execute();

            $row = $stmt->fetch();

            if (!$row) {
                echo "<pre style='color:red;'>❌ NO CSRF FOUND FOR USER</pre>";
                return false;
            }

            return hash_equals($row['csrf'], $token);

        } catch (PDOException $e) {
            echo "<pre style='color:red;'>
❌ CSRF QUERY FAILED:
" . $e->getMessage() . "
</pre>";
            exit;
        }
    }
}

// ----------------------------------------------------
// ✅ AUTHORIZATION & SECURITY CHECKS
// ----------------------------------------------------

// ✅ Base redirect URL
$baseUrl = $_SESSION['base_url'] ?? "/";

// ✅ LOGIN CHECK
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo "<pre style='color:red;'>❌ USER NOT LOGGED IN</pre>";
    header("Location: " . $baseUrl);
    exit();
}

// ✅ SESSION DEBUG CHECK
if (!isset($_SESSION['csrf_token'], $_SESSION['uid'])) {
    echo "<pre style='color:red;'>
❌ SESSION VALUES MISSING:
";
    print_r($_SESSION);
    echo "</pre>";
    exit();
}

// ✅ CSRF CHECK
$token = $_SESSION['csrf_token'];
$uid   = (int) $_SESSION['uid'];

$auth = new AuthCsrf();

if ($auth->authenticate($token, $uid)) {
    $_SESSION['csrf_valid'] = true;
    echo "<pre style='color:green;'>✅ CSRF VALIDATED SUCCESSFULLY</pre>";
} else {
    $_SESSION['csrf_valid'] = false;
    echo "<pre style='color:red;'>❌ CSRF VALIDATION FAILED</pre>";
    header("Location: " . $baseUrl);
    exit();
}

// ------------------------
// ✅ DEFAULT DATETIME SETUP
// ------------------------
$defaultDateTime = date('Y-m-d H:i:s');
echo "<pre style='color:blue;'>✅ CURRENT SERVER TIME: {$defaultDateTime}</pre>";

// ✅ EXECUTION CONTINUES SAFELY AFTER ALL CHECKS PASSED
?>
