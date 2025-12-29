<?php
// 1. Capture and Decode POST Data
// Assuming the form sends 'excel_data' as a JSON string and 'section', 'company' as regular strings
$excel_raw = isset($_POST['excel_data']) ? json_decode($_POST['excel_data'], true) : [];
$section   = $_POST['section'] ?? 'GEN';
$company   = $_POST['company'] ?? 'demo';

// 2. Database Credentials (Host and Auth remain same, DB is dynamic)
$host = "10.3.13.87";
$user = "sa";
$pass = "sa@123";

try {
    // Establish Connection using the dynamic 'company' name from POST
    $conn = new PDO("sqlsrv:Server=$host;Database=$company", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $currentUser = "System_Upload"; 
    $now = date('Y-m-d H:i:s');

    // 3. Process the Data
    if (!empty($excel_raw) && is_array($excel_raw)) {
        
        // Remove Header Row (the first array inside the excel_data array)
        array_shift($excel_raw);

        foreach ($excel_raw as $row) {
            // Basic validation: ensure row isn't empty
            if (empty($row[0])) continue;

            $conn->beginTransaction();

            // Map Row Data based on your Excel structure
            // Index: 0:Cat, 1:Item, 2:Mfg, 3:Unit, 4:Size, 5:SellPrice, 6:Reminder, 7:Supplier, 8:Qty, 9:PurchPrice, 10:PO
            $catName    = $row[0];
            $itemName   = $row[1];
            $mfgBy      = $row[2];
            $unit       = $row[3];
            $size       = $row[4];
            $sellPrice  = (float)$row[5];
            $reminder   = (int)$row[6];
            $supName    = $row[7];
            $qty        = (int)$row[8];
            $purchPrice = (float)$row[9];
            $poNo       = $row[10];

            // --- STEP 1: CATEGORY ---
            $stmt = $conn->prepare("SELECT c_id FROM category_item WHERE c_name = ? AND section = ?");
            $stmt->execute([$catName, $section]);
            $c_id = $stmt->fetchColumn();

            if (!$c_id) {
                $stmt = $conn->prepare("INSERT INTO category_item (c_name, section, c_add_date_time, c_add_by, c_active) VALUES (?, ?, ?, ?, 1)");
                $stmt->execute([$catName, $section, $now, $currentUser]);
                $c_id = $conn->lastInsertId();
            }

            // --- STEP 2: SUPPLIER ---
            $stmt = $conn->prepare("SELECT s_id FROM supplier WHERE s_name = ?");
            $stmt->execute([$supName]);
            $s_id = $stmt->fetchColumn();

            if (!$s_id) {
                $stmt = $conn->prepare("INSERT INTO supplier (s_name, s_add_datetime, s_add_by, s_active, section) VALUES (?, ?, ?, 1, ?)");
                $stmt->execute([$supName, $now, $currentUser, $section]);
                $s_id = $conn->lastInsertId();
            }

            // --- STEP 3: ITEM ---
            // Check existence based on your unique constraint logic
            $stmt = $conn->prepare("SELECT i_id FROM item WHERE i_name = ? AND c_id = ? AND i_manufactured_by = ? AND i_size = ? AND section = ?");
            $stmt->execute([$itemName, $c_id, $mfgBy, $size, $section]);
            $i_id = $stmt->fetchColumn();

            if (!$i_id) {
                $stmt = $conn->prepare("INSERT INTO item (i_name, i_add_datetime, c_id, i_unit, i_size, i_price, stock_out_reminder_qty, i_add_by, i_active, i_manufactured_by, section) 
                                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)");
                $stmt->execute([$itemName, $now, $c_id, $unit, $size, $sellPrice, $reminder, $currentUser, $mfgBy, $section]);
                $i_id = $conn->lastInsertId();
            }

            // --- STEP 4: PURCHASE ---
            $profit = $sellPrice - $purchPrice;
            $stmt = $conn->prepare("INSERT INTO item_purchase (i_id, s_id, p_req_qty, p_unit_price, p_request_datetime, p_purchase_by, p_profit, p_request, p_recive, p_po_no) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, 1, 1, ?)");
            $stmt->execute([$i_id, $s_id, $qty, $purchPrice, $now, $currentUser, $profit, $poNo]);
            $p_id = $conn->lastInsertId();

            // --- STEP 5: RECEIVE ---
            $expiry = date('Y-m-d H:i:s', strtotime('+2 years')); 
            $stmt = $conn->prepare("INSERT INTO tem_purchase_recive (p_id, p_recive_by, p_recive_datetime, p_expaired_datetime, p_recive_qty, p_stock, cash1_creadit0) 
                                    VALUES (?, ?, ?, ?, ?, ?, 1)");
            $stmt->execute([$p_id, $currentUser, $now, $expiry, $qty, $qty]);

            $conn->commit();
            echo "Successfully Inserted: $itemName (PO: $poNo)<br>";
        }
    } else {
        echo "No valid data found in excel_data.";
    }

} catch (Exception $e) {
    if (isset($conn) && $conn->inTransaction()) { 
        $conn->rollBack(); 
    }
    echo "Error: " . $e->getMessage();
}
?>