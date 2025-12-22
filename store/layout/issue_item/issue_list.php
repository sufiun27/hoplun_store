<?php
include '../template/header.php';
?>  
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Highlight the current menu item
        $("#collapseLayouts3").addClass("show");
        $("#collapseLayouts4_add").addClass("active bg-success");
        
        // Hide success/error message after 5 seconds
        setTimeout(function() {
            $("#status-message").fadeOut('slow');
        }, 5000);
    });
</script>
<?php

// 1. Initialize Dates and Search Params
$default_start = date('Y-m-d', strtotime('-15 days'));
$default_end = date('Y-m-d', strtotime('+15 days'));

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : $default_start;
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : $default_end;
$search_po = isset($_GET['search_po']) ? $_GET['search_po'] : '';
$section = $_SESSION['section'];
?>

<style>
    :root { --primary-green: #28a745; --light-green: #eafaf1; }
    .filter-card { background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); margin-bottom: 25px; padding: 20px; border-left: 5px solid var(--primary-green); }
    .table-container { background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.08); padding: 15px; }
    .thead-custom th { background-color: var(--light-green) !important; color: #155724; font-weight: 600; text-transform: uppercase; font-size: 0.85rem; vertical-align: middle; }
    .custom-info-row { display: none; background-color: #fdfdfd; }
    .btn-expand { border-radius: 50%; width: 30px; height: 30px; padding: 0; line-height: 1; transition: all 0.3s; }
    .inner-table { margin: 10px 0; border: 1px solid #dee2e6; background: #fff !important; }
</style>

<div class="container-fluid px-4 py-4">
    <h2 class="mb-4">Item Issue Records</h2>

    <div class="filter-card">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-4">
    <label class="form-label small fw-bold">Search PO Number</label>
    <div class="position-relative">
        <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
        <input type="text"
               name="search_po"
               class="form-control ps-5"
               placeholder="Enter PO No..."
               value="<?php echo htmlspecialchars($search_po ?? ''); ?>">
    </div>
</div>

            <div class="col-md-2 d-grid">
                <button type="submit" class="btn btn-success">Apply Filters</button>
            </div>
        </form>
    </div>

    <div class="table-container shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="thead-custom">
                    <tr>
                        <th>PO No</th>
                        <th>Item Details</th>
                        <th>Category</th>
                        <th>Brand</th>
                        <th>Pricing (Sys/Avg)</th>
                        <th>Qty</th>
                        <th>Total</th>
                        <th>Issue Date</th>
                        <th>Issued By</th>
                        <th>Employee</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                // Construct dynamic SQL
                $conditions = ["iss.is_active = 1", "i.section = :section"];
                $params = [':section' => $section];

                if ($start_date && $end_date) {
                    $conditions[] = "CAST(iss.is_datetime AS DATE) BETWEEN :start AND :end";
                    $params[':start'] = $start_date;
                    $params[':end'] = $end_date;
                }

                if (!empty($search_po)) {
                    $conditions[] = "iss.is_po_no LIKE :po";
                    $params[':po'] = "%$search_po%";
                }

                $where_clause = implode(" AND ", $conditions);
                $limit = ($_SESSION['table'] == 'short') ? "TOP (50)" : "";

                $sql = "SELECT $limit iss.is_po_no, iss.is_id, i.i_name, i.i_price, c.c_name, 
                        i.i_manufactured_by, i.i_size, i.i_unit, ist.is_avg_price, 
                        iss.is_qty, ist.total_price, iss.is_datetime, 
                        iss.is_item_issue_by, e.e_com_id, e.e_name
                        FROM item_issue iss 
                        INNER JOIN item i ON iss.i_id = i.i_id 
                        INNER JOIN employee e ON iss.e_id = e.e_id
                        INNER JOIN category_item c ON i.c_id = c.c_id
                        INNER JOIN (
                            SELECT SUM(ist_qty*ist_price) as total_price, is_id, 
                            SUM(ist_qty*ist_price)/NULLIF(SUM(ist_qty), 0) as is_avg_price
                            FROM item_issue_trac GROUP BY is_id
                        ) ist ON ist.is_id = iss.is_id
                        WHERE $where_clause
                        ORDER BY iss.is_datetime DESC";

                $stmt = $conn->prepare($sql);
                foreach ($params as $key => &$val) { $stmt->bindParam($key, $val); }

                if ($stmt->execute()) {
                    while ($product = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        $is_id = $product['is_id'];
                        ?>
                        <tr>
                            <td class="fw-bold text-primary"><?php echo $product['is_po_no']; ?></td>
                            <td>
                                <div class="fw-bold"><?php echo $product['i_name']; ?></div>
                                <small class="text-muted"><?php echo $product['i_size']; ?></small>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?php echo $product['c_name']; ?></span></td>
                            <td><?php echo $product['i_manufactured_by']; ?></td>
                            <td>
                                <div class="small">Sys: <?php echo number_format($product['i_price'], 2); ?></div>
                                <div class="small fw-bold">Avg: <?php echo number_format($product['is_avg_price'], 2); ?></div>
                            </td>
                            <td><?php echo $product['is_qty']; ?> <small><?php echo $product['i_unit']; ?></small></td>
                            <td class="fw-bold"><?php echo number_format($product['total_price'], 2); ?></td>
                            <td class="small"><?php echo date('d M Y h:i A', strtotime($product['is_datetime'])); ?></td>
                            <td><?php echo $product['is_item_issue_by']; ?></td>
                            <td>
                                <div class="small fw-bold"><?php echo $product['e_name']; ?></div>
                                <div class="text-muted small">ID: <?php echo $product['e_com_id']; ?></div>
                            </td>
                            <td>
                                <button class="btn btn-outline-success btn-sm btn-expand" onclick="toggleDetails(this)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </td>
                        </tr>
                        <tr class="custom-info-row">
                            <td colspan="11" class="px-4 py-3 bg-light">
                                <h6 class="fw-bold mb-2">Lot Traceability</h6>
                                <table class="table table-sm inner-table shadow-sm">
                                    <thead>
                                        <tr class="table-secondary">
                                            <th>Lot No / ID</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $sql1 = "SELECT r_id, ist_qty, ist_price FROM item_issue_trac WHERE is_id = :id";
                                        $stmt1 = $conn->prepare($sql1);
                                        $stmt1->execute([':id' => $is_id]);
                                        while ($row = $stmt1->fetch(PDO::FETCH_ASSOC)) {
                                            echo "<tr><td>{$row['r_id']}</td><td>{$row['ist_qty']}</td><td>".number_format($row['ist_price'],2)."</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <?php
                    }
                }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function toggleDetails(btn) {
    const row = btn.closest('tr').nextElementSibling;
    const icon = btn.querySelector('i');
    
    if (row.style.display === "table-row") {
        row.style.display = "none";
        icon.classList.replace('fa-minus', 'fa-plus');
        btn.classList.replace('btn-success', 'btn-outline-success');
    } else {
        row.style.display = "table-row";
        icon.classList.replace('fa-plus', 'fa-minus');
        btn.classList.replace('btn-outline-success', 'btn-success');
    }
}

$(document).ready(function() {
    $("#collapseLayouts3").addClass("show");
    $("#collapseLayouts3_list").addClass("active bg-success");
});
</script>

<?php include '../template/footer.php'; ?>