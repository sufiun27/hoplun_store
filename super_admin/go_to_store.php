<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['company'] = $_GET['company'] ?? '';

// Build absolute base URL correctly
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST']; // 10.3.13.87

$redirectUrl = $protocol . $host . '/store/layout/start/';

// 🚀 Redirect
header("Location: $redirectUrl", true, 302);
exit;
