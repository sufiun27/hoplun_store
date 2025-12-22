<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../database.php';

if (!isset($_POST['issuance_data'])) {
    http_response_code(400);
    die('No issuance data received');
}

$issuanceData = json_decode($_POST['issuance_data'], true);
if (!$issuanceData) {
    http_response_code(400);
    die('Invalid JSON');
}

$employeeId    = (int)$issuanceData['employee_id'];
$invoiceNumber = $issuanceData['invoice_number'];
$items         = $issuanceData['items'];
$username      = $_SESSION['username'] ?? 'system';

$db   = new Database();
$conn = $db->getConnection();

try {
    $conn->beginTransaction();

    /** Employee */
    $empStmt = $conn->prepare("
        SELECT e.e_id, d.d_name
        FROM employee e
        JOIN department d ON d.d_id = e.d_id
        WHERE e.e_id = :eid
    ");
    $empStmt->execute(['eid' => $employeeId]);
    $emp = $empStmt->fetch(PDO::FETCH_ASSOC);
    if (!$emp) throw new Exception('Employee not found');

    /** Prepared statements */
    $fifoSql = "
        SELECT ir.r_id, ir.p_id, ir.p_stock, ip.p_unit_price
        FROM tem_purchase_recive ir
        JOIN item_purchase ip ON ip.p_id = ir.p_id
        WHERE ip.i_id = :item_id AND ir.p_stock > 0
        ORDER BY ir.p_recive_datetime ASC
    ";

    $insertIssue = $conn->prepare("
        INSERT INTO item_issue
        (is_po_no, is_datetime, i_id, is_qty, i_price, e_id, emp_dep,
         is_item_issue_by, is_avg_price, is_profit, replacement, reason)
         OUTPUT INSERTED.is_id
        VALUES
        (:po, GETDATE(), :iid, :qty, :price, :eid, :dep,
         :user, :avg, :profit, :rep, :reason)
    ");

    $insertTrac = $conn->prepare("
        INSERT INTO item_issue_trac (is_id, r_id, ist_qty, ist_price)
        VALUES (:is_id, :r_id, :qty, :price)
    ");

    $updateStock = $conn->prepare("
        UPDATE tem_purchase_recive
        SET p_stock = :stock
        WHERE r_id = :r_id
    ");

    foreach ($items as $item) {

        $itemId      = (int)$item['item_id'];
        $issueQty    = (int)$item['quantity'];
        $replacement = (int)$item['replacement'];
        $reason      = $item['reason'] ?? null;

        /** FIFO rows */
        $stmt = $conn->prepare($fifoSql);
        $stmt->execute(['item_id' => $itemId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $totalStock = array_sum(array_column($rows, 'p_stock'));
        if ($totalStock < $issueQty) {
            throw new Exception("Insufficient stock for item ID $itemId");
        }

        /** FIFO calculation */
        $remaining = $issueQty;
        $issueRows = [];
        $totalCost = 0;

        foreach ($rows as $row) {
            if ($remaining <= 0) break;

            $useQty = min($row['p_stock'], $remaining);

            $issueRows[] = [
                'r_id'   => $row['r_id'],
                'qty'    => $useQty,
                'price'  => $row['p_unit_price'],
                'stock'  => $row['p_stock'] - $useQty
            ];

            $totalCost += $useQty * $row['p_unit_price'];
            $remaining -= $useQty;
        }

        /** Prices */
        $priceStmt = $conn->prepare("SELECT i_price FROM item WHERE i_id = :id");
        $priceStmt->execute(['id' => $itemId]);
        $sellPrice = (float)$priceStmt->fetchColumn();

        $avgPrice = $totalCost / $issueQty;
        $profit   = ($sellPrice - $avgPrice) * $issueQty;

        /** Insert item_issue */
        $insertIssue->execute([
            'po'     => $invoiceNumber,
            'iid'    => $itemId,
            'qty'    => $issueQty,
            'price'  => $sellPrice,
            'eid'    => $employeeId,
            'dep'    => $emp['d_name'],
            'user'   => $username,
            'avg'    => $avgPrice,
            'profit' => $profit,
            'rep'    => $replacement,
            'reason' => $reason
        ]);

        $isId = $insertIssue->fetchColumn();
        

        /** Track & update stock */
        foreach ($issueRows as $row) {
            $insertTrac->execute([
                'is_id' => $isId,
                'r_id'  => $row['r_id'],
                'qty'   => $row['qty'],
                'price' => $row['price']
            ]);

            $updateStock->execute([
                'stock' => $row['stock'],
                'r_id'  => $row['r_id']
            ]);
        }
    }

    $conn->commit();

    header("Location: issue_iteam.php?success=" . urlencode($invoiceNumber)." issued successfully");
    exit();

} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    header("Location: issue_iteam.php?error=" . urlencode($invoiceNumber)." issued failed |" .$e->getMessage());
    exit();
}
