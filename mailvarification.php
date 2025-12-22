
<?php

// echo "LDAP authentication process started...<br>";
// exit();



$verification = false;


// LDAP server settings
$ldap_server = "ldap://hoplun.com";
$ldap_port = 389;
$ldap_user_base_dn = "ou=users,dc=hoplun,dc=com";

// Get mail and password from the login form
$mail = $_POST['email'];
$password = $_POST['password'];

if (empty($mail) || empty($password)) {
    header("Location:index.php?error=Please enter both mail and password");
    exit();
}

if($verification == false){
    echo "LDAP verification is disabled. Bypassing LDAP authentication.<br>";
    // For testing purposes, bypass LDAP authentication
     header("Location: login.php?email=$mail");
     exit();
}

// Attempt to connect to the LDAP server
$ldap_conn = ldap_connect($ldap_server, $ldap_port);

if ($ldap_conn) {
    // Bind to the LDAP server with the provided mail and password
    $ldap_bind = ldap_bind($ldap_conn, $mail, $password);

    if ($ldap_bind) {
        // Authentication successful

        // Generate a random token (you may want to implement a more secure method)
        $token = bin2hex(random_bytes(16));

        // Store the token in a session or database, depending on your application
        session_start();
        //$_SESSION['token'] = $token;

        // Close the LDAP connection
        ldap_close($ldap_conn);

        echo "You are logged in.";

        // Redirect to the success page with the token
        header("Location: login.php?email=$mail");
        exit();
    } else {
        // Authentication failed
        header("Location:index.php?error=Invalid mail or password");
    }
} else {
    // Unable to connect to the LDAP server
    echo "Unable to connect to the LDAP server.";
}
?>


