<?php
include '../template/header.php';
?>
<style>td { font-size: 12px; }</style>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $("#collapsePages_report").addClass("show");
        $("#pagesCollapseStoreReport").addClass("show");
        $("#Invoice_wise_Balance_Items").addClass("active bg-success text-white");
    });
</script>

<div class="container-fluid px-4">
    <?php
    $filename = ""; 
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        include 'Invoice_wise_Balance_Items_Class.php';
        
        $start_date = $_POST['start_date'] ?? '';
        $end_date   = $_POST['end_date'] ?? '';
        $item_name  = $_POST['item_name'] ?? '';
        
        $obj = new DateWiseReceive();
        $reportData = [];

        if (!empty($item_name) && empty($start_date)) {
            $reportData = $obj->DateWiseNameReceiveReport($item_name);
        } elseif (!empty($start_date) && !empty($end_date)) {
            $reportData = $obj->DateWiseReceiveReport($start_date, $end_date);
        }

        if (!empty($reportData)) {
            require 'vendor/autoload.php';
            $spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Initialize Totals to avoid Undefined Variable errors
            $totalPurchasePrice = 0;
            $totalRequestQty = 0;
            $totalReceiveQty = 0;
            $totalCost = 0;

            // Set headers
            $headers = ['PO No', 'Category', 'Item', 'Size', 'Unit', 'System Price', 'Purchase Price', 'Request Qty', 'Receive Qty', 'Cost'];
            foreach ($headers as $index => $header) {
                $sheet->setCellValueByColumnAndRow($index + 1, 1, $header);
            }

            $rowNumber = 2;
            foreach ($reportData as $row) {
                // Map data explicitly to ensure correct column alignment
                $sheet->setCellValueByColumnAndRow(1, $rowNumber, $row['po_no']);
                $sheet->setCellValueByColumnAndRow(2, $rowNumber, $row['category']);
                $sheet->setCellValueByColumnAndRow(3, $rowNumber, $row['item']);
                $sheet->setCellValueByColumnAndRow(4, $rowNumber, $row['size']);
                $sheet->setCellValueByColumnAndRow(5, $rowNumber, $row['unit']);
                $sheet->setCellValueByColumnAndRow(6, $rowNumber, $row['system_price']);
                $sheet->setCellValueByColumnAndRow(7, $rowNumber, $row['purchase_price']);
                $sheet->setCellValueByColumnAndRow(8, $rowNumber, $row['request_qty']);
                $sheet->setCellValueByColumnAndRow(9, $rowNumber, $row['receive_qty']);
                $sheet->setCellValueByColumnAndRow(10, $rowNumber, $row['cost']);

                // Calculate Totals
                $totalPurchasePrice += (float)$row['purchase_price'];
                $totalRequestQty    += (float)$row['request_qty'];
                $totalReceiveQty    += (float)$row['receive_qty'];
                $totalCost          += (float)$row['cost'];
                
                $rowNumber++;
            }

            // Total Row
            $sheet->setCellValueByColumnAndRow(1, $rowNumber, 'TOTAL');
            $sheet->setCellValueByColumnAndRow(7, $rowNumber, $totalPurchasePrice);
            $sheet->setCellValueByColumnAndRow(8, $rowNumber, $totalRequestQty);
            $sheet->setCellValueByColumnAndRow(9, $rowNumber, $totalReceiveQty);
            $sheet->setCellValueByColumnAndRow(10, $rowNumber, $totalCost);

            $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $filename = 'Invoice_wise_Balance_Items.xlsx';
            $writer->save($filename);
        }
    }
    ?>

    <b><span class="text-success">Invoice Wise Balance Items</span></b>
    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" class="mb-3">
        <label>Start Date:</label>
        <input type="datetime-local" name="start_date">
        <label>End Date:</label>
        <input type="datetime-local" name="end_date">
        <label>Invoice No:</label>
        <input type="text" name="item_name">
        <input type="submit" value="Generate Report" class="btn btn-primary btn-sm">
        <?php if ($filename): ?>
            <span><a href="<?php echo $filename; ?>" class="btn btn-success btn-sm"><i class="fas fa-file-export"></i> Export</a></span>
        <?php endif; ?>
    </form>

    <?php if (!empty($reportData)): ?>
    <div class="card mb-4">
        <div class="card-header"><i class="fas fa-table me-1"></i> Report Results</div>
        <div class="card-body">
            <table id="datatablesSimple" class="table table-bordered">
                <thead>
                    <tr>
                        <th>PO No</th>
                        <th>Category</th>
                        <th>Item</th>
                        <th>Size</th>
                        <th>Unit</th>
                        <th>System Price</th>
                        <th>Purchase Price</th>
                        <th>Request Qty</th>
                        <th>Receive Qty</th>
                        <th>Cost</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reportData as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['po_no']); ?></td>
                        <td><?php echo htmlspecialchars($row['category']); ?></td>
                        <td><?php echo htmlspecialchars($row['item']); ?></td>
                        <td><?php echo htmlspecialchars($row['size']); ?></td>
                        <td><?php echo htmlspecialchars($row['unit']); ?></td>
                        <td><?php echo number_format($row['system_price'], 2); ?></td>
                        <td><?php echo number_format($row['purchase_price'], 2); ?></td>
                        <td><?php echo $row['request_qty']; ?></td>
                        <td><?php echo $row['receive_qty']; ?></td>
                        <td><?php echo number_format($row['cost'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
<?php include '../template/footer.php'; ?>