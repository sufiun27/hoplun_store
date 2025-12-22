<?php
// Database configuration for different stores
$user_name = $_SESSION['username'];
$user_company = $_SESSION['company'];
$user_role = $_SESSION['role'];

// Include the hostingDBinfo.php file for connection details
$servername = "10.3.13.87";
$username = "sa";
$password = "sa@123";

$database = $user_company;

try {
        $conn = new PDO("sqlsrv:Server=$servername;Database=$database", $username, $password);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
    
// Close the database connection when you're done with your operations
//$conn = null;
?>
