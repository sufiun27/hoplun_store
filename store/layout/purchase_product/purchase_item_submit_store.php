<?php
// Start session immediately
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug POST
print_r($_POST);

// Validate POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: purchase_item_process.php?value=' . urlencode('Invalid access method.'));
    exit;
}

try {

    // ---- Input Arrays ----
    $po_number    = $_POST['po_number'] ?? null;
    $supplier_id  = $_POST['supplier_id'] ?? null;

    $item_ids     = $_POST['item_id'] ?? [];        
    $quantities   = $_POST['qty'] ?? [];            
    $unit_prices  = $_POST['unit_price'] ?? [];     

    // Basic validation
    if (empty($po_number) || empty($supplier_id) || empty($item_ids)) {
        header('Location: purchase_item_process.php?value=' . urlencode("Invalid or missing required fields."));
        exit;
    }

    // PO prefix from session
    //$po_prefix  = $_SESSION['po'] ?? 'N/A';
    $section    = $_SESSION['section'] ?? '';

    // Section codes
    // $section_code = match ($section) {
    //     'GEN111' => 'G-',
    //     'ELE111' => 'M-',
    //     'ELE222' => 'E-',
    //     default  => '',
    // };

    // // Final PO number
    // $full_po_no = trim($po_prefix . '-' . $section_code . $po_number, '-');

    // Include MS SQL connection file
    include '../database.php'; 

    if (!isset($conn)) {
        throw new Exception("Database connection failed.");
    }

    // --- MS SQL Insert Query ---
    // NOTE: sqlsrv requires SELECT SCOPE_IDENTITY() if you want last ID; but here insert only.
    $sql = "
        INSERT INTO item_purchase (
            i_id, 
            s_id, 
            p_po_no, 
            p_req_qty, 
            p_unit_price,
            p_request_datetime, 
            p_purchase_by, 
            p_profit
           
        )
        VALUES (
            ?, ?, ?, ?, ?, ?, ?, ?
        )
    ";

    $stmt = $conn->prepare($sql);

    // Common values
    $username = $_SESSION['username'] ?? 'SYSTEM';
    $request_dt = date("Y-m-d H:i:s");
    $profit = 0;

    // Multiple item loop
    foreach ($item_ids as $index => $item_id) {

        $qty = $quantities[$index] ?? 0;
        $unit_price = $unit_prices[$index] ?? 0;

        if (!$item_id || !$qty || !$unit_price) {
            continue;
        }

        $stmt->execute([
            $item_id,
            $supplier_id,
            $po_number ,
            $qty,
            $unit_price,
            $request_dt,
            $username,
            $profit
        ]);
    }

    // Success redirect
    $success_message = "All items added successfully! PO: " . $po_number;
    header("Location: purchase_item_process.php?success=" . urlencode($success_message) . "&supplier_id=" . urlencode($supplier_id));
    exit;

} catch (PDOException $e) {
    $error_message = "Database Error: " . $e->getMessage();
    header('Location: purchase_item_process.php?error=' . urlencode($error_message));
    exit;

} catch (Exception $e) {
    $error_message = "System Error: " . $e->getMessage();
    header('Location: purchase_item_process.php?error=' . urlencode($error_message));
    exit;
}
?>
