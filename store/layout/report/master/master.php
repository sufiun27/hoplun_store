<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../../database.php';
$section = (string) $_SESSION['section'];
$start_date = $_POST['start_date'] ?? date('Y-m-01');
$end_date = $_POST['end_date'] ?? date('Y-m-d');

$report = new DbhReport();

// Fetch Metadata
$departments = $report->getData("SELECT d_id, d_name FROM department ORDER BY d_name ASC");
$categories = $report->getData("SELECT c_id, c_name FROM category_item WHERE section = '$section' ORDER BY c_name ASC");

function getinformation($i_id, $section, $start_date, $end_date) {
    $report = new DbhReport();
    // In production, ALWAYS use prepared statements. 
    // Below is the optimized query logic.
    $sql = "SELECT subquery.d_id, subquery.d_name, subquery.c_name, subquery.i_name,
            SUM(subquery.is_qty) AS quantity, 
            SUM(subquery.total_price) / NULLIF(SUM(subquery.is_qty), 0) AS avg_price,
            subquery.qty_balance
            FROM (
                SELECT i.i_name, i.i_id, c.c_name, iss.is_qty, ist.total_price, d.d_name, d.d_id, b.qty_balance
                FROM item_issue iss 
                INNER JOIN item i ON iss.i_id = i.i_id 
                INNER JOIN employee e ON iss.e_id = e.e_id
                INNER JOIN department d ON d.d_id = e.d_id
                INNER JOIN category_item c ON i.c_id = c.c_id
                INNER JOIN balance b ON i.i_id = b.i_id 
                INNER JOIN (
                    SELECT SUM(ist_qty * ist_price) AS total_price, is_id 
                    FROM item_issue_trac 
                    GROUP BY is_id
                ) ist ON ist.is_id = iss.is_id
                WHERE i.i_id = {$i_id} AND i.section = '$section' 
                AND CAST(iss.is_datetime AS DATE) BETWEEN '$start_date' AND '$end_date'
            ) AS subquery 
            GROUP BY subquery.d_id, subquery.d_name, subquery.c_name, subquery.i_name, subquery.qty_balance";
    return $report->getData($sql);
}

function getitem($c_id) {
    $report = new DbhReport();
    return $report->getData("SELECT i_id, i_name, c_id FROM item WHERE c_id = {$c_id} ORDER BY i_name ASC");
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Inventory Report | Hop Lun</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        .report-card { background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); margin-top: 20px; }
        .table-responsive { max-height: 700px; overflow-y: auto; }
        /* Sticky Header */
        .table thead th { position: sticky; top: 0; background: #212529; color: white; z-index: 10; font-weight: 500; font-size: 0.85rem; text-transform: uppercase; }
        .category-row { background-color: #e9ecef !important; font-weight: bold; color: #495057; }
        .item-name { font-weight: 600; color: #0d6efd; min-width: 200px; }
        .qty-cell { text-align: center; }
        .total-row { background-color: #212529; color: white; font-weight: bold; }
        .btn-export { background-color: #198754; color: white; border: none; }
        .btn-export:hover { background-color: #146c43; color: white; }
    </style>
</head>
<body>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center py-3 border-bottom">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-1">
                    <li class="breadcrumb-item"><a href="../indexmaster.php" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active">Inventory Report</li>
                </ol>
            </nav>
            <h4 class="mb-0">Hop Lun <small class="text-muted">| Consumption Report</small></h4>
        </div>
        <div class="d-flex gap-2">
            <a href="export.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" class="btn btn-export">
                <i class="fas fa-file-excel me-2"></i>Export to Excel
            </a>
        </div>
    </div>

    <div class="report-card">
        <div class="table-responsive">
            <table class="table table-hover table-bordered mb-0">
                <thead>
                    <tr>
                        <th>Item Description</th>
                        <?php foreach($departments as $dept): ?>
                            <th class="text-center"><?= htmlspecialchars($dept['d_name']) ?></th>
                        <?php endforeach; ?>
                        <th class="bg-dark">Total Qty</th>
                        <th class="bg-dark">Balance</th>
                        <th class="bg-dark">Avg Price</th>
                        <th class="bg-dark text-end">Grand Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $grand_total_price = 0;
                    foreach($categories as $category): ?>
                        <tr class="category-row">
                            <td colspan="<?= count($departments) + 5 ?>">
                                <i class="fas fa-folder-open me-2"></i><?= htmlspecialchars($category['c_name']) ?>
                            </td>
                        </tr>

                        <?php 
                        $items = getitem($category['c_id']);
                        foreach($items as $item): 
                            $priceinfo = getinformation($item['i_id'], $_SESSION['section'], $start_date, $end_date);
                            $totalquantity = 0;
                            $avg_price = 0;
                            $qty_balance = 0;
                        ?>
                            <tr>
                                <td class="item-name"><?= htmlspecialchars($item['i_name']) ?></td>
                                
                                <?php foreach($departments as $dept): 
                                    $qty = 0;
                                    foreach($priceinfo as $p) {
                                        if($p['d_id'] == $dept['d_id']) {
                                            $qty = $p['quantity'];
                                            $totalquantity += $qty;
                                            $avg_price = $p['avg_price'];
                                            $qty_balance = $p['qty_balance'];
                                            break;
                                        }
                                    }
                                ?>
                                    <td class="qty-cell text-muted"><?= $qty > 0 ? number_format($qty) : '-' ?></td>
                                <?php endforeach; ?>

                                <td class="fw-bold text-center table-info"><?= number_format($totalquantity) ?></td>
                                <td class="text-center"><?= number_format($qty_balance) ?></td>
                                <td class="text-center text-muted">$<?= number_format($avg_price, 2) ?></td>
                                <td class="text-end fw-bold">
                                    <?php 
                                        $row_total = $totalquantity * $avg_price;
                                        $grand_total_price += $row_total;
                                        echo number_format($row_total, 2);
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="<?= count($departments) + 4 ?>" class="text-end">GRAND TOTAL (All Items)</td>
                        <td class="text-end text-warning h5 mb-0">$<?= number_format($grand_total_price, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

</body>
</html>