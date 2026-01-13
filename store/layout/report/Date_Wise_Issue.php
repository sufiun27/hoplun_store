<!--################ Header ################-->
<?php include '../template/header.php'; ?>

<style>
    td { font-size: 12px; }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        $("#collapsePages_report").addClass("show");
        $("#pagesCollapseStoreReport").addClass("show");
        $("#dateWiseIssue").addClass("active bg-success text-white");
    });
</script>

<div class="container-fluid px-4">

<?php
require 'vendor/autoload.php';
include 'Date_Wise_Issue_Class.php';

$reportData = [];
$filename = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $start_date = str_replace('T', ' ', $_POST['start_date']);
    $end_date   = str_replace('T', ' ', $_POST['end_date']);
    $item_name  = trim($_POST['item_name']);

    $obj = new DateWiseReceive();
    //generateReport
    $reportData = $obj->generateReport($start_date, $end_date, $item_name);

    // if (!empty($item_name)) {
    //     $reportData = $obj->DateWiseNameReceiveReport($start_date, $end_date, $item_name);
    // } else {
    //     $reportData = $obj->DateWiseReceiveReport($start_date, $end_date);
    // }

    /* ================= Excel Export ================= */
    if (!empty($reportData)) {

        $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [
            'PO No', 'Department', 'Issue Date', 'Category',
            'Item', 'Brand', 'Size', 'Avg Issue Price',
            'Quantity', 'Units', 'Total Price'
        ];

        $sheet->fromArray($headers, NULL, 'A1');

        $rowNum = 2;
        foreach ($reportData as $row) {
            $sheet->fromArray([
                $row['po_no'],
                $row['department'],
                $row['issue_date'],
                $row['category'],
                $row['item'],
                $row['brand'],
                $row['size'],
                $row['purchase_price'],
                $row['quantity'],
                $row['units'],
                $row['total_price']
            ], NULL, 'A' . $rowNum++);
        }

        $filename = 'Date_Wise_Issue_' . date('Ymd_His') . '.xlsx';
        $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($filename);
    }
}
?>

<!--################ Title ################-->
<b class="text-success">Date Wise Issue</b>

<!--################ Form ################-->
<form method="post" class="mb-3">
    <label>Start Date:</label>
    <input type="date" name="start_date" required>

    <label>End Date:</label>
    <input type="date" name="end_date" >

    <label>Item Name:</label>
    <input type="text" name="item_name">

    <button type="submit" class="btn btn-primary btn-sm">Generate Report</button>

    <?php if (!empty($filename)): ?>
        <a href="<?= $filename ?>" class="btn btn-success btn-sm">
            <i class="fas fa-file-export"></i> Export
        </a>
    <?php endif; ?>
</form>

<!--################ Table ################-->
<?php if (!empty($reportData)): ?>
<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table me-1"></i> Date Wise Issue
    </div>
    <div class="card-body">
        <table id="datatablesSimple" class="table table-bordered table-sm">
            <thead class="table-success">
                <tr>
                    <th>ITRF No</th>
                    <th>Department</th>
                    <th>Date</th>
                    <th>Category</th>
                    <th>Item</th>
                    <th>Brand</th>
                    <th>Size</th>
                    <th>Avg Issue Price</th>
                    <th>Quantity</th>
                    <th>Units</th>
                    <th>Total Price</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($reportData as $row): ?>
                <tr>
                    <td><?= $row['po_no'] ?></td>
                    <td><?= $row['department'] ?></td>
                    <td><?= date('Y-m-d', strtotime($row['issue_date'])) ?></td>
                    <td><?= $row['category'] ?></td>
                    <td><?= $row['item'] ?></td>
                    <td><?= $row['brand'] ?></td>
                    <td><?= $row['size'] ?></td>
                    <td><?= number_format($row['purchase_price'], 2) ?></td>
                    <td><?= $row['quantity'] ?></td>
                    <td><?= $row['units'] ?></td>
                    <td><?= number_format($row['total_price'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

</div>

<!--################ Footer ################-->
<?php include '../template/footer.php'; ?>
