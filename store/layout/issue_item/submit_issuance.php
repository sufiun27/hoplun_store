<?php
include '../template/header.php';
if (session_status() == PHP_SESSION_NONE) {
  session_start();
}


// -----------------------------
// 1. Decode POST data
// -----------------------------
if (!isset($_POST['issuance_data']) || empty($_POST['issuance_data'])) {
    die("Error: No issuance data received.");
}

$issuance_data = json_decode($_POST['issuance_data'], true);

if ($issuance_data === null) {
    die("Error: Invalid JSON data received.");
}

$employee_id = $issuance_data['employee_id'];
$invoice_number = $issuance_data['invoice_number'];
$items = $issuance_data['items'];

// -----------------------------
// 2. Fetch Employee Info
// -----------------------------
$stmt_emp = $conn->prepare("
    SELECT e.e_name, e.e_com_id, d.d_name, e.e_designation
    FROM employee e
    JOIN department d ON e.d_id = d.d_id
    WHERE e.e_id = :eid
");
$stmt_emp->execute([':eid' => $employee_id]);
$employee = $stmt_emp->fetch();

if (!$employee) {
    die("Error: Employee not found.");
}

// print_r($employee);
// exit();

$employee_name = $employee['e_name'] ?? "Unknown Employee";
$employee_department = $employee['d_name'] ?? "N/A";
$employee_designation = $employee['e_designation'] ?? "N/A";
$employee_com_id = $employee['e_com_id'] ?? "N/A";


// -----------------------------
// 3. Fetch Item Info
// -----------------------------
$full_items = [];
$total_amount = 0;

foreach ($items as $item) {
    $stmt_item = $conn->prepare("
        SELECT i_name, i_unit, i_size, i_code
        FROM item
        WHERE i_id = :iid
    ");
    $stmt_item->execute([':iid' => $item['item_id']]);
    $row = $stmt_item->fetch();

    $item_name = $row ? $row['i_name'] . " ({$row['i_unit']} / {$row['i_size']})" : "Item {$item['item_id']}";
    $item_code = $row['i_code'] ?? '';

    $total = $item['quantity'] * $item['price'];
    $total_amount += $total;

    $full_items[] = [
        'name' => $item_name,
        'code' => $item_code,
        'quantity' => $item['quantity'],
        'price' => $item['price'],
        'replacement' => $item['replacement'],
        'total' => $total
    ];
}
?>

<!-- <!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Issuance Memo | <?php echo htmlspecialchars($invoice_number); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body> -->
<style>
.memo-container { max-width: 900px; margin: 40px auto; padding: 30px; border: 1px solid #ccc; box-shadow: 0 0 15px rgba(0,0,0,0.1); background: #fff; }
.table th, .table td { vertical-align: middle; }
@media print { .no-print { display: none; } .memo-container { border: none; box-shadow: none; } }
</style>

<div class="memo-container">
    <div class="text-center mb-5">
        <h1 class="text-success">✅ Stock Issuance Memo</h1>
        <p class="lead">Receipt for Item Issue</p>
        <hr>
    </div>

    <div class="row mb-4">
        <div class="col-md-6">
            <p><strong>Invoice Number:</strong> <?php echo htmlspecialchars($invoice_number); ?></p>
            <p><strong>Employee Name:</strong> <?php echo htmlspecialchars($employee_name); ?></p>
            <p><strong>Employee ID:</strong> <?php echo htmlspecialchars($employee_com_id); ?></p>
            <p><strong>Department:</strong> <?php echo htmlspecialchars($employee_department); ?></p>
            <p><strong>Designation:</strong> <?php echo htmlspecialchars($employee_designation); ?></p>
        </div>
        <div class="col-md-6 text-md-end">
            <p><strong>Date Issued:</strong> <?php echo date('F j, Y, g:i a'); ?></p>
            <p><strong>Issued By:</strong> Admin</p>
        </div>
    </div>

    <h5 class="mb-3">Issued Items</h5>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Item Code</th>
                    <th>Item Name (Unit/Size)</th>
                    <th class="text-end">Qty Issued</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-center">Replacement</th>
                    <th class="text-end">Total Value</th>
                </tr>
            </thead>
            <tbody>
                <?php $count = 1; foreach ($full_items as $item): ?>
                <tr>
                    <td><?php echo $count++; ?></td>
                    <td><?php echo htmlspecialchars($item['code']); ?></td>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td class="text-end"><?php echo $item['quantity']; ?></td>
                    <td class="text-end"><?php echo number_format($item['price'],2); ?></td>
                    <td class="text-center">
                        <span class="badge <?php echo $item['replacement'] == 1 ? 'bg-warning text-dark' : 'bg-secondary'; ?>">
                            <?php echo $item['replacement'] == 1 ? 'YES' : 'NO'; ?>
                        </span>
                    </td>
                    <td class="text-end"><?php echo number_format($item['total'],2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="6" class="text-end">Grand Total:</th>
                    <th class="text-end"><?php echo number_format($total_amount,2); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="row mt-5">
        <div class="col-6 text-center">
            <hr class="w-75 mx-auto">
            <p>Receiver's Signature</p>
            <p>(<?php echo htmlspecialchars($employee_name); ?>)</p>
        </div>
        <div class="col-6 text-center">
            <hr class="w-75 mx-auto">
            <p>Issuer's Signature</p>
            <p>(Admin)</p>
        </div>
    </div>

    <div class="text-center mt-5 no-print">
        <button class="btn btn-primary" onclick="window.print()">🖨️ Print Memo</button>
        <form action="submit_issuance_processed.php" method="post">
        <input type="hidden" id="issuance-data" name="issuance_data" />
            <button type="submit" class="btn btn-success">✅ Submit</button>
        </form>
    </div>
</div>

<script>
  // Pass PHP array/object to JS safely
  const finalIssuanceObject = <?php echo json_encode($issuance_data, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
  
  // Put the JSON string into a hidden input if needed
  document.getElementById('issuance-data').value = JSON.stringify(finalIssuanceObject);

  // Example: log to console to verify
  console.log(finalIssuanceObject);
</script>
<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> -->

<?php
    include '../template/footer.php';
?>