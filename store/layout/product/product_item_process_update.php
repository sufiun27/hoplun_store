<?php
if(session_status() == PHP_SESSION_NONE){
    session_start();
}

include_once '../database.php';

// Retrieve POST data (use names that match your form)
$p_id = $_POST['pid'] ?? '';
$c_id = $_POST['c_id'] ?? '';
$i_name = $_POST['i_name'] ?? '';
$i_code = $_POST['i_code'] ?? '';
$i_manufactured_by = $_POST['i_manufactured_by'] ?? '';
$i_unit = $_POST['i_unit'] ?? '';
$i_size = $_POST['i_size'] ?? '';
$i_price = $_POST['i_price'] ?? '';
$stock_out_reminder_qty = $_POST['stock_out_reminder_qty'] ?? '';
$update_by = $_SESSION['username'] ?? '';
$update_datetime = $_SESSION['Asia_Dhaka_time'] ?? date('Y-m-d H:i:s'); // fallback if session not set

try {
    // Prepare the SQL statement
    $stmt = $conn->prepare("
        UPDATE item SET
            i_name = :i_name,
            i_code = :i_code,
            i_manufactured_by = :i_manufactured_by,
            c_id = :c_id,
            i_unit = :i_unit,
            i_size = :i_size,
            i_price = :i_price,
            stock_out_reminder_qty = :stock_out_reminder_qty,
            i_update_datetime = :update_datetime,
            i_update_by = :update_by
        WHERE i_id = :p_id
    ");

    // Bind parameters
    $stmt->bindParam(':i_name', $i_name, PDO::PARAM_STR);
    $stmt->bindParam(':i_code', $i_code, PDO::PARAM_STR);
    $stmt->bindParam(':i_manufactured_by', $i_manufactured_by, PDO::PARAM_STR);
    $stmt->bindParam(':c_id', $c_id, PDO::PARAM_INT);
    $stmt->bindParam(':i_unit', $i_unit, PDO::PARAM_STR);
    $stmt->bindParam(':i_size', $i_size, PDO::PARAM_STR);
    $stmt->bindParam(':i_price', $i_price, PDO::PARAM_STR);
    $stmt->bindParam(':stock_out_reminder_qty', $stock_out_reminder_qty, PDO::PARAM_INT);
    $stmt->bindParam(':update_datetime', $update_datetime, PDO::PARAM_STR);
    $stmt->bindParam(':update_by', $update_by, PDO::PARAM_STR);
    $stmt->bindParam(':p_id', $p_id, PDO::PARAM_INT);

    // Execute
    if ($stmt->execute()) {
        redirectToPage('product_list.php', 'Record updated successfully');
    } else {
        redirectToPage('product_list.php', 'Failed to update record');
    }

} catch (PDOException $e) {
    // Handle SQL error
    redirectToPage('product_list.php', 'Error: ' . $e->getMessage());
}

// Close connection
$stmt = null;
$conn = null;

// Redirect function
function redirectToPage($page, $message) {
    $url = $page . '?value_emp=' . urlencode($message);
    header("Location: $url");
    exit();
}
?>
