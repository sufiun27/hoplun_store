<?php
include '../template/header.php';
require 'vendor/autoload.php';
?>

<style>
    td { font-size: 12px; }
    .table-total { font-weight: bold; background-color: #f8f9fa; }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(function () {
    $("#collapsePages_report").addClass("show");
    $("#pagesCollapseStoreReport").addClass("show");
    $("#Invoice_wise_Balance_Items").addClass("active bg-success text-white");
});
</script>

<div class="container-fluid px-4">

<?php
$filename   = "";
$reportData = [];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    require 'Invoice_wise_Balance_Items_Class.php';

    $start_date = $_POST['start_date'] ?? '';
    $end_date   = $_POST['end_date'] ?? '';
    $item_name  = $_POST['item_name'] ?? '';

    $obj = new DateWiseReceive();
    $reportData = $obj->generateReport($start_date, $end_date, $item_name);
    
   
    if (!empty($reportData)) {

        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
    
        // Headers
        $headers = [
            'PO No','Category','Item','Size','Unit',
            'System Price','Purchase Price',
            'Request Qty','Receive Qty','Total Cost'
        ];
    
        $sheet->fromArray($headers, NULL, 'A1');
    
        $rowNo = 2;
    
        foreach ($reportData as $row) {
    
            $sheet->fromArray([
                $row['po_no'],
                $row['category'],
                $row['item'],
                $row['size'],
                $row['unit'],
                $row['system_price'],
                $row['purchase_price'],
                $row['request_qty'],
                $row['receive_qty'],
                $row['cost']
            ], null, "A{$rowNo}");
    
            $rowNo++;
        }
    
        // Auto-size columns
        $filename = 'Date_Wise_Issue_' . date('Ymd_His') . '.xlsx';
        $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filename);
    }
    
}
?>

<!-- =================== FILTER FORM =================== -->

<div class="mt-3">
    <span class="text-success fw-bold fs-5">Invoice Wise Balance Items</span>
</div>

<form method="post" class="card p-3 mb-4 mt-2">
    <div class="row align-items-end">
        <div class="col-md-3">
            <label>Start Date</label>
            <input type="date" name="start_date" class="form-control form-control-sm" required value="<?= $_POST['start_date'] ?? '' ?>">
        </div>
        <div class="col-md-3">
            <label>End Date</label>
            <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $_POST['end_date'] ?? '' ?>">
        </div>
        <div class="col-md-3">
            <label>Invoice / PO No</label>
            <input type="text" name="item_name" class="form-control form-control-sm" value="<?= $_POST['item_name'] ?? '' ?>">
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary btn-sm">Generate</button>
            <?php if ($filename): ?>
                <a href="<?= $filename ?>" class="btn btn-success btn-sm">
                    <i class="fas fa-file-excel"></i> Export
                </a>
            <?php endif; ?>
        </div>
    </div>
</form>

<!-- =================== TABLE =================== -->

<?php
if (!empty($reportData)): ?>
    
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table"></i> Invoice Wise Balance Items
    </div>
    <div class="card-body">
        <table class="table table-bordered table-hover">
            <thead class="table-light">
                <tr>
                    <th>PO</th>
                    <th>Category</th>
                    <th>Item</th>
                    <th>Size</th>
                    <th>Unit</th>
                    <th>Sys Price</th>
                    <th>Pur Price</th>
                    <th>Req</th>
                    <th>Rec</th>
                    <th>Bal</th>
                    <th>Bal Cost</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($reportData as $row): 
                $balQty  = $row['request_qty'] - $row['receive_qty'];
                $balCost = $balQty * $row['purchase_price'];
            ?>
                <tr>
                    <td><?= htmlspecialchars($row['po_no']) ?></td>
                    <td><?= htmlspecialchars($row['category']) ?></td>
                    <td><?= htmlspecialchars($row['item']) ?></td>
                    <td><?= htmlspecialchars($row['size']) ?></td>
                    <td><?= htmlspecialchars($row['unit']) ?></td>
                    <td class="text-end"><?= number_format($row['system_price'], 2) ?></td>
                    <td class="text-end"><?= number_format($row['purchase_price'], 2) ?></td>
                    <td class="text-center"><?= $row['request_qty'] ?></td>
                    <td class="text-center"><?= $row['receive_qty'] ?></td>
                    <td class="text-center"><?= $balQty ?></td>
                    <td class="text-end"><?= number_format($balCost, 2) ?></td>
                    <td class="text-end"><?= number_format($row['cost'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>


</div>

<?php include '../template/footer.php'; ?>
