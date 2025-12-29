<?php 
// super_admin/database.php
class Dbh
{
    // Use environment variables or a configuration file in a real application
    private string $host = "10.3.13.87"; 
    private string $user = "sa";
    private string $pass = "sa@123";
    private string $db_name = "inventoryuser";

    public function connect(): PDO
    {
        // 🚨 FIX: Added 'sqlsrv:Encrypt=0' to prevent potential issues with default SSL/TLS settings
        $dsn = "sqlsrv:Server=" . $this->host . ";Database=" . $this->db_name . ";Encrypt=0";

        try {
            // Added charset for better encoding handling, though less critical for SQL Server than MySQL
            $pdo = new PDO($dsn, $this->user, $this->pass); 
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (PDOException $e) {
            // In a production environment, avoid echoing $e->getMessage() directly
            error_log("DATABASE CONNECTION FAILED: " . $e->getMessage()); 
            echo "<pre style='color:red; font-weight:bold;'>
❌ DATABASE CONNECTION FAILED. Check logs.
</pre>";
            exit;
        }
    }
}
?>