<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Ensure this path is correct for your environment
include '../database.php';

// Check if the query parameter is set
if (!isset($_POST['query'])) {
    echo "<p class='text-danger p-2'>Invalid search query</p>";
    exit;
}

$query = $_POST['query'];

$sql = "SELECT s_id, s_name, s_email 
        FROM supplier 
        WHERE s_active = 1 
        AND (s_name LIKE ? OR s_email LIKE ?)";

try {
    $stmt = $conn->prepare($sql);
    $search = "%$query%";
    $stmt->execute([$search, $search]);

    $data = "";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // --- FIX APPLIED HERE ---
        // Changed to use CSS class 'supplier_result_item' and data attributes
        // to work with the delegated jQuery handler in the main page.
        $data .= "
            <a href='#' class='list-group-item list-group-item-action supplier_result_item'
                data-supplier-id='{$row['s_id']}' 
                data-supplier-name='{$row['s_name']}'>
                {$row['s_name']} ({$row['s_email']})
            </a>";
    }

    echo $data ?: "<p class='text-danger p-2'>No supplier found</p>";

} catch (PDOException $e) {
    // Log error in a real application
    // error_log("Supplier Search Error: " . $e->getMessage());
    echo "<p class='text-danger p-2'>Database error during search</p>";
}

?>