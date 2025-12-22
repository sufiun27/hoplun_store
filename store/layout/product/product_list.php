<?php
include '../template/header.php';
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Highlight the current menu item
        $("#collapseLayouts").addClass("show");
        $("#collapseLayouts_add").addClass("active bg-success");
        
        // Hide success/error message after 5 seconds
        setTimeout(function() {
            $("#status-message").fadeOut('slow');
        }, 5000);
    });
</script>
<?php

// ======================
// 1. Pagination & Search
// ======================
$limit = 25;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$section = $_SESSION['section'];

// ======================
// 2. Build WHERE clause
// ======================
$where_sql = "WHERE i.section = :section";
$params = [':section' => $section];

if ($search !== '') {
    $where_sql .= " AND (i.i_name LIKE :search_name OR i.i_code LIKE :search_code)";
$params[':search_name'] = "%$search%";
$params[':search_code'] = "%$search%";

}

// ======================
// 3. Total Records
// ======================
try {
    $count_sql = "SELECT COUNT(*) AS total_count FROM item i $where_sql";
    $count_stmt = $conn->prepare($count_sql);
    $count_stmt->execute($params);
    $total_records = (int)$count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);
} catch (PDOException $e) {
    die("Count Error: " . $e->getMessage());
}

// ======================
// 4. Fetch Items
// ======================
try {
    $sql = "SELECT i.*, c.c_name, b.qty_balance, b.total_item_purchase, b.total_item_issue
            FROM item i
            LEFT JOIN balance b ON i.i_id = b.i_id
            INNER JOIN category_item c ON c.c_id = i.c_id
            $where_sql
            ORDER BY i.i_add_datetime DESC
            OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY";

    $stmt = $conn->prepare($sql);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

    // Bind other params (section, search)
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Data Load Error: " . $e->getMessage());
}

?>

<style>
:root { --success-soft: #eafaf1; --warning-soft: #fff9e6; }
.card { border: none; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,0.075); border-radius: 0.75rem; }
.table thead th { background-color: #f8f9fa; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; border-bottom: 2px solid #dee2e6; vertical-align: middle; }
.table td { vertical-align: middle; font-size: 13px; font-weight: 500; }
.search-group { max-width: 450px; }
.status-badge { padding: 0.4em 0.8em; border-radius: 50rem; font-size: 11px; font-weight: 700; display: inline-block; min-width: 80px; text-align: center; }
.stock-low { background-color: #fff5f5; color: #e53e3e; border: 1px solid #feb2b2; }
.stock-ok { background-color: #f0fff4; color: #38a169; border: 1px solid #9ae6b4; }
.action-btn { width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 6px; margin: 0 2px; transition: all 0.2s; }
.action-btn:hover { transform: scale(1.1); }
</style>

<div class="container-fluid px-4 py-4">
    <div class="card mb-4">
        <!-- Card Header -->
        <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold text-dark">
                <i class="fas fa-boxes me-2 text-success"></i>Product Inventory
                <a id="collapseLayouts1_add" 
   class="btn btn-primary" 
   href="http://<?= $_SESSION['base_url']; ?>/store/layout/product/product_add.php">
   Add New
</a>

            </h5>
            
            <div class="filter-card">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-8">
            <label class="form-label small fw-bold">Item Name or Code</label>
            <div class="position-relative">
                <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                <input type="text"
                       name="search"
                       class="form-control ps-5"
                       placeholder="Enter Item Name or Code..."
                       value="<?= htmlspecialchars($search ?? '') ?>">
            </div>
        </div>

        <?php if(!empty($search)): ?>
        <div class="col-md-2 d-grid">
            <a href="?" class="btn btn-outline-secondary d-flex align-items-center justify-content-center">
                <i class="fas fa-times me-1"></i> Clear
            </a>
        </div>
        <?php endif; ?>

        <div class="col-md-2 d-grid">
            <button type="submit" class="btn btn-success">Search</button>
        </div>
    </form>
</div>


        </div>

        <!-- Table Body -->
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Product Detail</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock / ROL</th>
                            <th>Status</th>
                            <th>Dates</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(!empty($items)): ?>
                            <?php foreach($items as $p): ?>
                                <?php $stock_class = ($p['qty_balance'] <= $p['stock_out_reminder_qty']) ? 'stock-low' : 'stock-ok'; ?>
                                <tr>
                                    <td class="text-primary fw-bold"><?= $p['i_code']; ?></td>
                                    <td>
                                        <div class="fw-bold"><?= $p['i_name']; ?></div>
                                        <small class="text-muted"><?= $p['i_manufactured_by']; ?> | <?= $p['i_size']; ?></small>
                                    </td>
                                    <td><span class="badge bg-light text-dark border"><?= $p['c_name']; ?></span></td>
                                    <td><?= number_format($p['i_price'], 2); ?> <small class="text-muted"><?= $p['i_unit']; ?></small></td>
                                    <td>
                                        <span class="status-badge <?= $stock_class; ?>">
                                            <?= $p['qty_balance'] ?? 0; ?> / <?= $p['stock_out_reminder_qty']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($p['i_active'] == 1): ?>
                                            <a href="status_deactive.php?id=<?= $p['i_id']; ?>" class="badge bg-success text-decoration-none">Active <i class="fas fa-toggle-on ms-1"></i></a>
                                        <?php else: ?>
                                            <a href="status_active.php?id=<?= $p['i_id']; ?>" class="badge bg-danger text-decoration-none">Inactive <i class="fas fa-toggle-off ms-1"></i></a>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted">
                                        Add: <?= date('d-m-Y', strtotime($p['i_add_datetime'])); ?><br>
                                        Upd: <?= $p['i_update_datetime'] ? date('d-m-Y', strtotime($p['i_update_datetime'])) : '-'; ?>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center">
                                            <a href="product_edit.php?p_id=<?= $p['i_id']; ?>" class="action-btn bg-primary text-white" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($p['total_item_purchase'] == 0 && $p['total_item_issue'] == 0): ?>
                                                <a href="product_delete.php?p_id=<?= $p['i_id']; ?>" class="action-btn bg-danger text-white" onclick="return confirm('Delete this item?')" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            <?php else: ?>
                                                <span class="action-btn bg-secondary text-white" title="Locked: Item has transactions"><i class="fas fa-lock"></i></span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">No items found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Card Footer -->
        <div class="card-footer bg-white border-top py-3 d-flex justify-content-between align-items-center">
            <small class="text-muted">Showing page <?= $page; ?> of <?= $total_pages; ?> (Total: <?= $total_records; ?> items)</small>
            <nav aria-label="Page navigation">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?= $page-1; ?>&search=<?= urlencode($search); ?>">Previous</a>
                    </li>
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?= $i; ?>&search=<?= urlencode($search); ?>"><?= $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?= $page+1; ?>&search=<?= urlencode($search); ?>">Next</a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $("#collapseLayouts1").addClass("show");
    $("#collapseLayouts1_list").addClass("active bg-success text-white");
});
</script>

<?php include '../template/footer.php'; ?>
