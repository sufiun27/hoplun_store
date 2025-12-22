<?php
// Start the session if it hasn't been started already
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Set the correct base path as a root-relative path (e.g., /gs)
//    This should be set elsewhere, but we'll ensure it's correct here for demonstration.
$_SESSION['base_url'] = $_SESSION['folder_path'] ?? '/gs'; 

// 2. Start session variable (if needed)
$_SESSION['company'] = 'legend';

// 3. Ensure base_url is set and clean it
$baseUrl = $_SESSION['base_url'];

// Ensure the base URL starts with a slash but doesn't end with one,
// so we can reliably add the next path component.
$baseUrl = '/' . trim($baseUrl, '/'); 

// Build redirect URL
// Result: /gs/store/layout/start/
$redirectUrl = $baseUrl . '/store/layout/start/'; 

// --- Debugging Output (To be removed in production) ---
echo "Session Base URL: " . $_SESSION['base_url'] . "<br>";
echo "Cleaned Base URL: " . $baseUrl . "<br>";
echo "Redirecting to: " . $redirectUrl . "<br>";
// -----------------------------------------------------

// 4. Redirect using the absolute path (status code 302 Found)
//    The browser will prepend http://127.0.0.1 (or your actual domain)
//    to form the complete URL: http://127.0.0.1/gs/store/layout/start/
header("Location: $redirectUrl", true, 302);

// 5. Always call exit after header redirect to stop script execution
exit;
?>