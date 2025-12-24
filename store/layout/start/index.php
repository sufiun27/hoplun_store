<?php
include '../template/header.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    header("Location: ../../logout/logout.php");
    exit();
}

$db = new Database();
$pdo = $db->getConnection();

/* ======================================================
   1. HELPER FUNCTION
====================================================== */
function get_count(Database $db, string $sql, array $params = []): int {
    $pdo = $db->getConnection();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $val = $stmt->fetchColumn();
    return (int)($val ?: 0);
}

/* ======================================================
   2. PAGINATION LOGIC
====================================================== */
$limit = 10; // Records per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

/* ======================================================
   3. DASHBOARD COUNTS
====================================================== */
$total_employees = get_count($db, "SELECT COUNT(e_name) FROM employee");

$uncomplete_receive_count = get_count($db, "
    SELECT COUNT(ip.p_id)
    FROM item_purchase ip
    INNER JOIN (
        SELECT p_id, SUM(p_recive_qty) AS total_received_qty
        FROM tem_purchase_recive
        GROUP BY p_id
    ) tpr ON ip.p_id = tpr.p_id
    WHERE ip.p_req_qty > tpr.total_received_qty
");

$unreceived_items_count = get_count($db, "SELECT COUNT(p_id) FROM item_purchase WHERE p_request = 1 AND p_recive = 0");
$stock_out_items_count = get_count($db, "SELECT COUNT(i.i_id) FROM item i LEFT JOIN balance b ON i.i_id = b.i_id WHERE b.qty_balance < i.stock_out_reminder_qty");
$unaccept_items_count = get_count($db, "SELECT COUNT(p_id) FROM item_purchase WHERE p_request = 0");

/* ======================================================
   4. DATA FETCHING (MS-SQL COMPLIANT)
====================================================== */
$section = $_SESSION['section'] ?? null;
$table_data = [];
$total_pages = 0;
$search1 = null;
$search2 = null;
if (isset($_GET['search'])) {
    $search1 = $_GET['search'];
    $search2 = $_GET['search'];

    $search1 = '%' . $search1 . '%';
    $search2 = '%' . $search2 . '%';
}

if ($section && $pdo) {
    // Get total count for this section
    $count_sql = "
    SELECT COUNT(1)
    FROM balance b
    INNER JOIN item i ON i.i_id = b.i_id
    WHERE i.section = :section
";

$params = [':section' => $section];

// Add search condition if needed
if (!empty($search1) || !empty($search2)) {
    $count_sql .= " AND (b.c_name LIKE :search1 OR b.i_name LIKE :search2)";
    $params[':search1'] = '%' . $search1 . '%';
    $params[':search2'] = '%' . $search2 . '%';
}

$total_records = get_count($db, $count_sql, $params);
$total_pages = ceil($total_records / $limit);


    // MS SQL Server Pagination Query
    $query = "
        SELECT 
            ip.p_id, ip.p_request_accept_datetime,
            b.c_name, b.i_name, b.total_item_purchase, b.total_item_issue,
            b.qty_balance, b.item_issue_avg_price
        FROM balance b
        INNER JOIN item i ON i.i_id = b.i_id
        INNER JOIN item_purchase ip ON ip.i_id = i.i_id
        WHERE i.section = :section ";

    if ($search1 && $search2) {
        $query .= "AND (b.c_name LIKE :search1 OR b.i_name LIKE :search2)";
    }


     $query .=   "ORDER BY ip.p_request_accept_datetime DESC
        OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY
    ";

    $stmt = $pdo->prepare($query);

    if ($search1 && $search2) {
        $stmt->bindValue(':search1', $search1, PDO::PARAM_STR);
        $stmt->bindValue(':search2', $search2, PDO::PARAM_STR);
    }
    $stmt->bindValue(':section', $section, PDO::PARAM_STR);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $table_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<style>
    .stat-card { border: none; border-radius: 12px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .stat-card:hover { transform: translateY(-5px); box-shadow: 0 8px 20px rgba(0,0,0,0.12); }
    .bg-grad-blue { background: linear-gradient(135deg, #1e3a8a, #3b82f6); }
    .bg-grad-orange { background: linear-gradient(135deg, #ea580c, #fb923c); }
    .bg-grad-green { background: linear-gradient(135deg, #15803d, #22c55e); }
    .bg-grad-red { background: linear-gradient(135deg, #b91c1c, #ef4444); }
    .pagination .page-link { border-radius: 5px; margin: 0 2px; color: #444; }
    .pagination .page-item.active .page-link { background-color: #1e3a8a; border-color: #1e3a8a; color: #fff; }
</style>

<div class="container-fluid px-4 py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold m-0">Operations Dashboard</h2>
        <span class="badge bg-secondary p-2">Total Employees: <?= $total_employees ?></span>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card stat-card bg-grad-blue text-white">
                <div class="card-body">
                    <small class="text-white-50">Incomplete Receives</small>
                    <h2 class="mb-0"><?= number_format($uncomplete_receive_count) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-grad-orange text-white">
                <div class="card-body">
                    <small class="text-white-50">Unreceived Items</small>
                    <h2 class="mb-0"><?= number_format($unreceived_items_count) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-grad-green text-white">
                <div class="card-body">
                    <small class="text-white-50">Stock Out Alerts</small>
                    <h2 class="mb-0"><?= number_format($stock_out_items_count) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stat-card bg-grad-red text-white">
                <div class="card-body">
                    <small class="text-white-50">Unaccepted Items</small>
                    <h2 class="mb-0"><?= number_format($unaccept_items_count) ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold"><i class="fas fa-boxes text-primary me-2"></i>Inventory Status</h5>
            <form action="?" method="GET" class="d-flex align-items-center">
                <input type="text" name="search" placeholder="Item Name or Category" class="form-control form-control-sm" value="<?= $_GET['search'] ?? '' ?>">
                <button type="submit" class="btn btn-primary btn-sm ms-2">Search</button>
            </form>
            <div class="text-muted small">Page <?= $page ?> of <?= max(1, $total_pages) ?></div>
        </div>

        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle border-top">
                    <thead class="table-light">
                        <tr>
                            <th>Category</th>
                            <th>Item Name</th>
                            <th class="text-center">Total Purchase</th>
                            <th class="text-center">Total Issue</th>
                            <th class="text-center">Balance</th>
                            <th class="text-end">Avg Price</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!empty($table_data)): ?>
                        <?php foreach ($table_data as $row): ?>
                            <tr>
                                <td><span class="text-muted small"><?= htmlspecialchars($row['c_name']) ?></span></td>
                                <td class="fw-semibold"><?= htmlspecialchars($row['i_name']) ?></td>
                                <td class="text-center"><?= number_format($row['total_item_purchase']) ?></td>
                                <td class="text-center"><?= number_format($row['total_item_issue']) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $row['qty_balance'] < 10 ? 'bg-danger' : 'bg-success' ?> rounded-pill">
                                        <?= number_format($row['qty_balance']) ?>
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-primary">
                                    <?= number_format($row['item_issue_avg_price'], 2) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                No records found for this section.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page - 1 ?>"><i class="fas fa-chevron-left"></i></a>
                    </li>

                    <?php
                    $window = 2; // Pages to show around current page
                    for ($i = 1; $i <= $total_pages; $i++):
                        if($i == 1 || $i == $total_pages || ($i >= $page - $window && $i <= $page + $window)): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php elseif($i == $page - $window - 1 || $i == $page + $window + 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif;
                    endfor; ?>

                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?page=<?= $page + 1 ?>"><i class="fas fa-chevron-right"></i></a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../template/footer.php'; ?>