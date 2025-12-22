<?php

declare(strict_types=1);



// echo "Starting authentication process...<br>";
// exit();
// ------------------------------------
// 1. DbInfo Class (Credentials)
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
            echo "Database connection failed. Please contact administrator.". $e->getMessage();

            // ✅ VERY IMPORTANT RETURN NULL
            return null;
        }
    }

    public function getConnection(): ?PDO {
        return $this->connect();
    }
}

/**
 * Professional PHP Authentication and Security Script
 * * This script handles user authentication via email, generates a cryptographically secure 
 * CSRF token, updates the token in the database, and manages session state and redirection 
 * based on the user's role.
 * * NOTE: The 'Dbh' class (Database Handler) is assumed to exist and provide the connect() method 
 * for establishing a PDO connection.
 * * WARNING: Using $_GET for authentication parameters (like email) is highly insecure for 
 * production. This should be changed to $_POST and combined with password verification.
 */

// Start session immediately to ensure it's available for the entire script.
// This is handled via the static CSRF method call below.

// --- Configuration/Environment Setup ---

// Set the default timezone for the application
date_default_timezone_set('Asia/Dhaka');
$defaultDateTime = date('Y-m-d H:i:s');

// Define constants for configuration paths and URLs for maintainability
const BASE_URL = '127.0.0.1/gs'; // http://127.0.0.1/gs/super_admin/index.php
const FOLDER_PATH = '/gs'; // /gs/super_admin/index.php
const USER_REDIRECT_PATH = '/store/layout/start/';
const SUPERADMIN_REDIRECT_PATH = '/super_admin/index.php';

// --- Database Interaction Classes ---

/**
 * Class Auth
 * Handles user authentication by retrieving user and associated database information.
 * Assumes 'Dbh' provides the database connection.
 */
class Auth extends Dbh
{
    /**
     * Retrieves a user's data by their email address.
     *
     * @param string $email The email address of the user.
     * @return array|false The user's data as an associative array on success, or false on failure.
     */
    public function getUserByEmail(string $email): array|false
    {
        $pdo = $this->connect();

        $query = "
            SELECT 
                u.*, d.db_name , d.short_name
            FROM 
                [inventoryuser].[dbo].[user] u
            INNER JOIN 
                [inventoryuser].[dbo].[dbinfo] d ON u.site = d.db_name
            WHERE 
                u.email = :email";

        try {
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            
            // Fetch a single row
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            // Return the result array if found, otherwise false
            return $result ?: false;

        } catch (PDOException $e) {
            // Log the error for administrator review
            error_log("Database Error in getUserByEmail: " . $e->getMessage());
            return false;
        }
    }
}

/**
 * Class Security
 * Handles security-related tasks, such as CSRF token generation and database management.
 * Assumes 'Dbh' provides the database connection.
 */
class Security extends Dbh
{
    /**
     * Ensures a session is active and generates a new CSRF token if one does not exist.
     *
     * @return string The current or newly generated CSRF token.
     */
    public static function generateCSRFToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            try {
                // Use random_bytes for cryptographically secure random number generation
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            } catch (Exception $e) {
                // Fallback for very specific, older environments
                $_SESSION['csrf_token'] = bin2hex(openssl_random_pseudo_bytes(32));
                error_log("CSRF token generated with openssl_random_pseudo_bytes: " . $e->getMessage());
            }
        }

        return $_SESSION['csrf_token'];
    }

    /**
     * Updates the CSRF token for a specific user in the database.
     *
     * @param int $userId The ID of the user.
     * @param string $token The new CSRF token.
     * @return bool True on successful update, false otherwise.
     */
    public function updateCsrfToken(int $userId, string $token): bool
    {
        $pdo = $this->connect();

        $query = "
            UPDATE 
                user_token 
            SET 
                csrf = :token, 
                active = 1 
            WHERE 
                u_id = :uid";
        
        try {
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':token', $token, PDO::PARAM_STR);
            $stmt->bindParam(':uid', $userId, PDO::PARAM_INT);

            $success = $stmt->execute();

            // Check if the query executed successfully AND at least one row was affected (upsert logic might vary)
            return $success && $stmt->rowCount() > 0;

        } catch (PDOException $e) {
            error_log("Database Error in updateCsrfToken: " . $e->getMessage());
            return false;
        }
    }
}


// --- Main Execution Logic ---

// Ensure session is started for CSRF token handling
Security::generateCSRFToken();


// --- Input Validation and Authentication ---

// WARNING: Replace $_GET['email'] with $_POST['email'] and include password validation 
// in a secure, production environment.
if (!isset($_GET['email']) || empty($_GET['email'])) {
    header("Location: index.php?error=Missing email address");
    exit();
}

$email = filter_var($_GET['email'], FILTER_SANITIZE_EMAIL);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: index.php?error=Invalid email format");
    exit();
}

$auth = new Auth();

$userData = $auth->getUserByEmail($email);

if ($userData) {

    // print_r($userData);
    // exit();
    // --- Successful Authentication and Session Setup ---
    
    // 1. Generate and update CSRF Token
    $csrfToken = Security::generateCSRFToken();
    $securityHandler = new Security();

    if ($securityHandler->updateCsrfToken((int)$userData['u_id'], $csrfToken)) {
        
        // 2. Set necessary session variables
        $_SESSION['uid'] = (int)$userData['u_id'];
        $_SESSION['username'] = $userData['username'];
        $_SESSION['company'] = $userData['location'] ?? null;
        $_SESSION['role'] = $userData['role'];
        $_SESSION['section'] = $userData['section'];
        $_SESSION['email'] = $userData['email'];
        $_SESSION['po'] = $userData['short_name']; // use to create invoice first name 
        $_SESSION['folder_path'] = FOLDER_PATH;
        $_SESSION['base_url'] = BASE_URL;
        $_SESSION['is_logged_in'] = true;
        $_SESSION['Asia_Dhaka_time'] = $defaultDateTime;

        $_SESSION['table']='short'; // default table view setting to short or fetch all data 
        
        // 3. Redirection based on user role
        $role = $userData['role'];
        $redirectUrl = '';

        if ($role === 'super_admin') {
            $_SESSION['company'] = null; // Enforce null company for super admin
            $redirectUrl = "http://" . BASE_URL . SUPERADMIN_REDIRECT_PATH;
        } elseif (in_array($role, ['admin', 'user', 'group_admin'], true)) {
            $redirectUrl = "http://" . BASE_URL . USER_REDIRECT_PATH;
        }

        if (!empty($redirectUrl)) {
            // Echoing log-in message is removed, replaced by silent redirection
            header("Location: $redirectUrl");
            exit();
        } else {
            // Handle unknown role
            error_log("Unknown user role: " . $role . " for user ID: " . $userData['u_id']);
            header("Location: index.php?error=User role not recognized");
            exit();
        }

    } else {
        // CSRF Token update failed
        error_log("Failed to update CSRF token for user ID: " . $userData['u_id']);
        header("Location: index.php?error=Security initialization failed");
        exit();
    }
} else {
    // --- Authentication Failed ---
    header("Location: index.php?error=Invalid username or password");
    exit();
}