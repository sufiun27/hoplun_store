<?php
// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database class
include '../database.php';

// Check for required parameters
if (!isset($_GET['is_id']) || !isset($_GET['return_qty']) || !isset($_GET['e_id'])) {
    echo "No record found.";
    exit;
}

$is_id = $_GET['is_id'];
$return_qty = $_GET['return_qty'];
$e_id = $_GET['e_id'];

echo "item id : " . $is_id . "<br>";
echo "return qty : " . $return_qty . "<br>";
echo "employee id : " . $e_id . "<br>";

try {
    // Create database instance
    $db = new Database();
    $conn = $db->getConnection();

    if ($conn === null) {
        throw new Exception("Database connection failed.");
    }

    // Helper functions
    function executeSql(PDO $conn, string $sql) {
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        return $stmt;
    }

    function fetchSql(PDO $conn, string $sql) {
        $stmt = $conn->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Fetch issued items
    $sql = "SELECT *
            FROM item_issue_trac AS ir
            INNER JOIN item_issue AS iss ON iss.is_id = ir.is_id
            WHERE ir.is_id = $is_id;";
    $result = fetchSql($conn, $sql);

    // Step 1: Process return quantity
    $remaining_qty = $return_qty;
    foreach ($result as $row) {
        echo "<br> is_is:" . $row['is_id'] . " - is_qty:" . $row['is_qty'] . " - r_id:" . $row['r_id'] . " - ist_qty:" . $row['ist_qty'] . "<br>";

        if ($remaining_qty > $row['ist_qty']) {
            // Update purchase stock
            $sql = "UPDATE tem_purchase_recive SET p_stock = p_stock + " . $row['ist_qty'] . " WHERE r_id = " . $row['r_id'];
            executeSql($conn, $sql);

            $remaining_qty -= $row['ist_qty'];
            echo "1 remaining qty : " . $remaining_qty . "<br>";

            // Delete issue record
            $sql = "DELETE FROM item_issue_trac WHERE ist_id = " . $row['ist_id'];
            executeSql($conn, $sql);
        } else {
            // Update purchase stock with remaining_qty
            $sql = "UPDATE tem_purchase_recive SET p_stock = p_stock + " . $remaining_qty . " WHERE r_id = " . $row['r_id'];
            executeSql($conn, $sql);

            $remaining_isse_qty = $row['ist_qty'] - $remaining_qty;
            $remaining_qty = 0;

            echo "2 remaining issue qty : " . $remaining_isse_qty . "<br>";
            echo "2 remaining qty : " . $remaining_qty . "<br>";

            if ($remaining_isse_qty == 0) {
                $sql = "DELETE FROM item_issue_trac WHERE ist_id = " . $row['ist_id'];
                executeSql($conn, $sql);
            } elseif ($remaining_isse_qty > 0) {
                $sql = "UPDATE item_issue_trac SET ist_qty = " . $remaining_isse_qty . " WHERE ist_id = " . $row['ist_id'];
                executeSql($conn, $sql);
            }
        }
    }

    // Check if any remaining issue qty
    $sql = "SELECT COALESCE(SUM(ist_qty), 0) as qty FROM item_issue_trac WHERE is_id=$is_id";
    $result = fetchSql($conn, $sql);

    if ($result[0]['qty'] == 0) {
        $sql = "SELECT * FROM item_issue WHERE is_id = $is_id";
        $return = fetchSql($conn, $sql);

        $sqlToExecuteReturn = "INSERT INTO item_return (
            [is_po_no], [is_datetime], [i_id], [is_qty], [i_price], [e_id], 
            [emp_dep], [is_item_issue_by], [is_avg_price], [is_profit], [remarks], 
            [return_by], [return_datetime]
        ) VALUES (
            '{$return[0]['is_po_no']}', '{$return[0]['is_datetime']}', '{$return[0]['i_id']}', 
            '{$return_qty}', '{$return[0]['i_price']}', '{$return[0]['e_id']}', 
            '{$return[0]['emp_dep']}', '{$return[0]['is_item_issue_by']}', 
            '{$return[0]['is_avg_price']}', '{$return[0]['is_profit']}', 'test', 
            '{$_SESSION['email']}', GETDATE()
        )";
        executeSql($conn, $sqlToExecuteReturn);

        $sql = "DELETE FROM item_issue WHERE is_id = $is_id";
        executeSql($conn, $sql);
    } else {
        $sql = "UPDATE item_issue SET is_qty = " . $result[0]['qty'] . " WHERE is_id = $is_id";
        executeSql($conn, $sql);

        $sql = "SELECT * FROM item_issue WHERE is_id = $is_id";
        $return = fetchSql($conn, $sql);

        $sqlToExecuteReturn = "INSERT INTO item_return (
            [is_po_no], [is_datetime], [i_id], [is_qty], [i_price], [e_id], 
            [emp_dep], [is_item_issue_by], [is_avg_price], [is_profit], [remarks], 
            [return_by], [return_datetime]
        ) VALUES (
            '{$return[0]['is_po_no']}', '{$return[0]['is_datetime']}', '{$return[0]['i_id']}', 
            '{$return_qty}', '{$return[0]['i_price']}', '{$return[0]['e_id']}', 
            '{$return[0]['emp_dep']}', '{$return[0]['is_item_issue_by']}', 
            '{$return[0]['is_avg_price']}', '{$return[0]['is_profit']}', 'test', 
            '{$_SESSION['email']}', GETDATE()
        )";
        executeSql($conn, $sqlToExecuteReturn);
    }

    // Redirect after success
    header("Location: transfer_process1.php?id=$e_id&value=Success");

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
