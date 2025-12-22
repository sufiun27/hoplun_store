<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include '../database.php';

if (isset($_POST['input'])) {
    $input = $_POST['input'];
    $input = "%$input%";
    $section = $_SESSION['section'];

    $sql = "SELECT
    i.i_code,
    i.i_id,
    i.i_name,
    c.c_name,
    i.i_manufactured_by,
    i.i_size,
    i.i_unit,
    i.i_price,
    i.i_add_datetime,
    i.i_update_datetime,
    i.i_active,
    i.stock_out_reminder_qty,
    b.qty_balance,
    b.item_issue_avg_price,
    b.total_item_purchase,
    b.total_item_issue
FROM
    item i
LEFT JOIN
    balance b ON i.i_id = b.i_id
INNER JOIN
    category_item c ON c.c_id = i.c_id
WHERE
    i.section = :section AND
    (i.i_name LIKE :input1 OR i.i_manufactured_by LIKE :input2 OR c.c_name LIKE :input3)
ORDER BY
    i.i_add_datetime DESC";

$stmt = $conn->prepare($sql);
$stmt->execute([
'section' => $section,
'input1' => $input,
'input2' => $input,
'input3' => $input
]);


    echo '<table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Item Code</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Brand</th>
                    <th>Size</th>
                    <th>Unit</th>
                    <th>Unit Price</th>
                    <th>Stock</th>
                    <th>ROL</th>
                    <th>Added DateTime</th>
                    <th>Update DateTime</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>';

    while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($product['i_code']) . "</td>";
        echo "<td>" . htmlspecialchars($product['i_name']) . "</td>";
        echo "<td>" . htmlspecialchars($product['c_name']) . "</td>";
        echo "<td>" . htmlspecialchars($product['i_manufactured_by']) . "</td>";
        echo "<td>" . htmlspecialchars($product['i_size']) . "</td>";
        echo "<td>" . htmlspecialchars($product['i_unit']) . "</td>";
        echo "<td>" . htmlspecialchars($product['i_price']) . "</td>";

        // Stock highlight
        if ($product['stock_out_reminder_qty'] > $product['qty_balance']) {
            echo '<td>(' . htmlspecialchars($product['qty_balance']) . ')</td>';
        } else {
            echo '<td style="background: rgba(91, 255, 102, 0.40);">' . htmlspecialchars($product['qty_balance']) . '</td>';
        }

        echo "<td>" . htmlspecialchars($product['stock_out_reminder_qty']) . "</td>";
        echo "<td>" . date('Y-m-d', strtotime($product['i_add_datetime'])) . "</td>";
        echo "<td>" . ($product['i_update_datetime'] ? date('Y-m-d', strtotime($product['i_update_datetime'])) : '') . "</td>";

        // Status with styled links
        if ($product["i_active"] == 1) {
            echo '<td class="text-success">&#10003; <a id="active" href="product_list_status_deactive.php?id=' . $product["i_id"] . '">X</a></td>';
        } else {
            echo '<td class="text-danger">&#10007; <a id="deactive" href="product_list_status_active.php?id=' . $product["i_id"] . '">A</a></td>';
        }

        // Actions
        echo '<td><div class="btn-group">';
        if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'super_admin') {
            echo '<a id="edit" href="product_edit.php?p_id=' . $product['i_id'] . '">E</a>';
            if ($product['total_item_purchase'] == 0 && $product['total_item_issue'] == 0) {
                echo '<a id="delete" href="product_delete.php?p_id=' . $product['i_id'] . '">X</a>';
            } else {
                echo '<span>L</span>';
            }
        }
        echo '</div></td>';

        echo "</tr>";
    }

    echo '</tbody></table>';

    unset($conn);
    exit;
}
?>
