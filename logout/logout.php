
<?php  

// Clear session variables
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
//$base_url = $_SESSION['base_url'];
session_unset();
session_destroy();

// Close database connection
$pdo = null; // Assuming $pdo is your active database connection object

// Redirect to the login page or any other appropriate page
//localhost:8080/storehl
$logout_url = "http://127.0.0.1/gs";
header("Location:$logout_url");
//header("Location:http://$base_url");
exit();


//
?>


