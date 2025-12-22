<?php
session_start();

// Include the centralized database class
// include('../../../database.php');
require_once '../database.php';

$user_name = $_SESSION['username'] ?? '';
$user_company = $_SESSION['company'] ?? '';
$user_role = $_SESSION['role'] ?? '';

if (empty($user_company)) {
    die("❌ Company database not set in session.");
}

// Initialize Database object
$db = new Database();

$emp_main_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$emp_update_by = $user_name;

// Set timezone and datetime
date_default_timezone_set('Asia/Dhaka');
$defaultDateTime = date('Y-m-d H:i:s');
$active = 0; // Inactive

try {
    $conn = $db->getConnection();

    $sql = "UPDATE supplier SET 
            s_active = ?,
            s_inactive_datetime = ?,
            s_inactive_by = ?
            WHERE s_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bindParam(1, $active, PDO::PARAM_INT);
    $stmt->bindParam(2, $defaultDateTime, PDO::PARAM_STR);
    $stmt->bindParam(3, $emp_update_by, PDO::PARAM_STR);
    $stmt->bindParam(4, $emp_main_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $message = "Update successfully";
        header("Location: supplier_search.php?success=" . urlencode($message) . "&id=" . urlencode($emp_main_id));
        exit();
    } else {
        $message = "Don't found employee!";
        header("Location: supplier_search.php?error=" . urlencode($message));
        exit();
    }

} catch (PDOException $e) {
    die("❌ Database Error: " . $e->getMessage());
}
