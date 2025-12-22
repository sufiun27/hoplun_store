<?php
if(session_status() == PHP_SESSION_NONE){
    session_start();
}
$baseurl = $_SESSION['baseurl'];
// Use the header function to perform the redirect
header("Location: $baseurl");
exit(); // Make sure to call exit() after the header to stop further script execution
?>