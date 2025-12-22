<?php
// purchase_list_process_delete.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$emp_id = filter_input(INPUT_GET, 'p_id', FILTER_SANITIZE_NUMBER_INT);
$page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_NUMBER_INT);
$section = filter_input(INPUT_GET, 'section', FILTER_SANITIZE_STRING);
$startDate = filter_input(INPUT_GET, 'startDate', FILTER_SANITIZE_STRING);
$endDate = filter_input(INPUT_GET, 'endDate', FILTER_SANITIZE_STRING);

$extra_url = 'store/layout/purchase_product/purchase_list.php';
$base_url = $_SESSION['base_url'] ?? '';

// Redirect if p_id is invalid
if ($emp_id === false || $emp_id === null) {
    header("Location: http://$base_url/$extra_url");
    exit;
}

include '../database.php'; // Make sure $conn is a PDO connection using sqlsrv

try {
    // MS SQL DELETE using PDO
    $stmt = $conn->prepare("DELETE FROM item_purchase WHERE p_id = :p_id");
    $stmt->bindValue(':p_id', $emp_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        // Redirect with query parameters
        $query = http_build_query([
            'page' => $page,
            'section' => $section,
            'startDate' => $startDate,
            'endDate' => $endDate
        ]);
        header("Location: http://$base_url/$extra_url?$query");
    } else {
        header("Location: http://$base_url/$extra_url");
    }
} catch (PDOException $e) {
    // Optional: log $e->getMessage() for debugging
    header("Location: http://$base_url/$extra_url");
}

// Close the connection
$conn = null;
exit;
?>
