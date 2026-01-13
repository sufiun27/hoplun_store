<?php
// Validate p_id
if (!isset($_GET['p_id']) || !is_numeric($_GET['p_id'])) {
    die("Invalid purchase ID.");
}
$p_is = (int) $_GET['p_id'];
?>

<!--### Header Part ##################################################-->
<?php include '../template/header.php'; ?>

<style>
    td, th {
        font-size: 10px;
    }
    th {
        font-weight: bold;
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
    $("#collapseLayouts2").addClass("show");
    $("#collapseLayouts2_list").addClass("active bg-success");
});
</script>

<div class="container-fluid px-4">
    <h4 class="bg-success text-light p-1">
        <?php echo $_GET['value'] ?? ''; ?>
    </h4>

<?php
try {
    /* ---------- COUNT ISSUED PO ---------- */
    $sqlCount = "
        SELECT COUNT(DISTINCT iss.is_po_no) AS total_po_qty
        FROM item_issue iss
        INNER JOIN item_issue_trac ist ON ist.is_id = iss.is_id
        INNER JOIN tem_purchase_recive ir ON ir.r_id = ist.r_id
        INNER JOIN item_purchase ip ON ip.p_id = ir.p_id
        WHERE ip.p_id = :p_id
    ";
    $stmt = $conn->prepare($sqlCount);
    $stmt->execute([':p_id' => $p_is]);
    $total_po_qty = $stmt->fetch(PDO::FETCH_ASSOC)['total_po_qty'];

    /* ---------- DATA LIST ---------- */
    $sql = "
        SELECT DISTINCT
            iss.is_po_no,
            e.e_name,
            e.e_com_id,
            i.i_name,
            iss.is_qty
        FROM item_issue iss
        INNER JOIN item i ON iss.i_id = i.i_id
        INNER JOIN employee e ON e.e_id = iss.e_id
        INNER JOIN item_issue_trac ist ON ist.is_id = iss.is_id
        INNER JOIN tem_purchase_recive ir ON ir.r_id = ist.r_id
        INNER JOIN item_purchase ip ON ip.p_id = ir.p_id
        WHERE ip.p_id = :p_id
    ";
    $stmt = $conn->prepare($sql);
    $stmt->execute([':p_id' => $p_is]);
?>

<div class="card mb-4">
    <div class="card-header">
        <i class="fas fa-table me-1"></i>
        Need to return those issued  <?php echo $total_po_qty; ?> item first

        <?php if ($total_po_qty == 0): ?>
            <a href="purchase_list_process_delete_return_step2.php?p_id=<?php echo $p_is; ?>"
               class="btn btn-danger float-end">
               Return To Supplier
            </a>
        <?php endif; ?>
    </div>

    <div class="card-body">
        <table id="datatablesSimple" class="table table-bordered">
            <thead>
                <tr>
                    <th>PO NO</th>
                    <th>ID</th>
                    <th>Employee</th>
                    <th>Item</th>
                    <th>Quantity</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <tr>
                    <td><?= htmlspecialchars($row['is_po_no']) ?></td>
                    <td><?= htmlspecialchars($row['e_com_id']) ?></td>
                    <td><?= htmlspecialchars($row['e_name']) ?></td>
                    <td><?= htmlspecialchars($row['i_name']) ?></td>
                    <td><?= htmlspecialchars($row['is_qty']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
} catch (PDOException $e) {
    echo "Database Error: " . $e->getMessage();
}

$conn = null;
?>

</div>

<?php include '../template/footer.php'; ?>

<style>
.table tbody tr:nth-child(even) {
    background-color: #f9f9f9;
}
</style>
