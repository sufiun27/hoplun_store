<?php
// search_item.php
session_start();
include '../database.php'; // Assuming this provides a PDO connection in $conn

if (isset($_POST['input'])) {
    $input = filter_input(INPUT_POST, 'input', FILTER_SANITIZE_STRING);

    $sql = "SELECT TOP 10 i.i_id, i.i_name, i.i_unit, i.i_size, i.i_price, i.i_code, 
               b.qty_balance, b.item_issue_avg_price
            FROM item i
            LEFT JOIN balance b ON i.i_id = b.i_id
            WHERE i.i_name LIKE ? OR i.i_code LIKE ?
            "; // Limit for performance

    $stmt = $conn->prepare($sql);
    $searchInput = "%{$input}%";

    if ($stmt->execute([$searchInput, $searchInput])) {
        echo '<table class="table table-sm table-bordered table-hover">
        <thead>
            <tr>
                <th>ID/Code</th>
                <th>Item Name</th>
                <th>Unit/Size</th>
                <th>Stock</th>
                <th>Avg. Price</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>';

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $currentPrice = $row["item_issue_avg_price"] ?? $row["i_price"];
            $stock = $row["qty_balance"] ?? 0;
            
            echo '<tr>
            <td>' . htmlspecialchars($row["i_code"] ?? $row["i_id"]) . '</td>
            <td>' . htmlspecialchars($row["i_name"]) . '</td>
            <td>' . htmlspecialchars($row["i_unit"]) . ' / ' . htmlspecialchars($row["i_size"]) . '</td>
            <td class="' . ($stock <= 0 ? 'text-danger fw-bold' : '') . '">' . $stock . '</td>
            <td>' . number_format($currentPrice, 2) . '</td>
            <td>
            <button class="btn btn-success btn-sm btn-add-item" 
                data-iid="' . $row["i_id"] . '" 
                data-iname="' . htmlspecialchars($row["i_name"]) . '"
                data-iunit="' . htmlspecialchars($row["i_unit"]) . '"
                data-isize="' . htmlspecialchars($row["i_size"]) . '"
                data-icode="' . htmlspecialchars($row["i_code"]) . '"
                data-iprice="' . $currentPrice . '"
                ' . ($stock <= 0 ? 'disabled' : '') . '"
                data-istock="' . $stock . '">
                
                Add
            </button>
            </td>
            </tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<div class="alert alert-warning">No items found matching the criteria.</div>';
    }
    unset($conn);
    exit;
}
?>