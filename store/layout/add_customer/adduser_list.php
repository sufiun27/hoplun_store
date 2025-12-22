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
// 1. Setup Pagination and Search
$limit = 25;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;
$search = isset($_GET['search_id']) ? $_GET['search_id'] : '';

// 2. Build Query with Filters
$where_clause = "";
$params = [];
if (!empty($search)) {
    $where_clause = " WHERE employee.e_com_id LIKE :search ";
    $params[':search'] = "%$search%";
}

// Get total for pagination
$count_sql = "SELECT COUNT(*) FROM employee $where_clause";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->execute($params);
$total_records = $count_stmt->fetchColumn();
$total_pages = ceil($total_records / $limit);

// Fetch main data
$sql = "SELECT * FROM employee 
        INNER JOIN department ON employee.d_id = department.d_id 
        $where_clause
        ORDER BY e_add_date_time DESC 
        OFFSET $start ROWS FETCH NEXT $limit ROWS ONLY";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
?>

<style>
    .card { border: none; border-radius: 10px; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); }
    .card-header { background-color: #f8f9fa; border-bottom: 1px solid #eee; font-weight: bold; }
    .search-box { max-width: 400px; }
    .table thead th { background-color: #f8f9fa; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; border-bottom: 2px solid #dee2e6; }
    .table td { vertical-align: middle; font-size: 13px; }
    .badge-status { width: 80px; }
</style>

<div class="container-fluid px-4 py-4">
    <?php if(isset($_GET['value'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($_GET['value']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card mb-4">
    
    <div class="card-header d-flex justify-content-between align-items-center py-3">

<span class="fw-bold flex-shrink-0">
    <i class="fas fa-users me-2"></i>Employee Directory
    <a id="collapseLayouts_add"
   href="http://<?php echo $_SESSION['base_url']; ?>/store/layout/add_customer/adduser.php"
   class="btn btn-success btn-sm d-inline-flex align-items-center gap-1"
   role="button"
   aria-label="Add new employee">
    <i class="fas fa-user-plus"></i>
    <span>Add New Employee</span>
</a>

</span>

<form method="GET" class="d-flex align-items-end gap-2">

    <div style="width: 320px;">
        <label class="form-label small fw-bold mb-1">Search ID</label>
        <div class="position-relative">
            <i class="fas fa-search position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>

            <input type="text"
                   name="search_id"
                   class="form-control ps-5"
                   placeholder="Search ID..."
                   value="<?= htmlspecialchars($search ?? '') ?>">
        </div>
    </div>

    <button class="btn btn-success" type="submit">
        Apply
    </button>

    <?php if (!empty($search)): ?>
        <a href="?"
           class="btn btn-outline-secondary btn-sm d-flex align-items-center">
            <i class="fas fa-times me-1"></i> Clear
        </a>
    <?php endif; ?>

</form>

</div>




        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Department</th>
                            <th>Designation</th>
                            <th>Type</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr>
                            <td class="fw-bold text-primary"><?php echo $row["e_com_id"]; ?></td>
                            <td><?php echo $row["e_name"]; ?></td>
                            <td><?php echo $row["d_name"]; ?></td>
                            <td><?php echo $row["e_designation"]; ?></td>
                            <td><span class="text-muted small"><?php echo $row["user_type"]; ?></span></td>
                            <td>
                                <?php if($row["e_active"] == 1): ?>
                                    <span class="badge bg-success text-white border border-success px-2 py-1 badge-status">Active</span>
                                    <a href="adduser_list_status_deactive.php?id=<?php echo $row['e_id']; ?>" class="btn btn-link btn-sm text-warning p-0 ms-1" title="Deactivate"><i class="fas fa-user-slash"></i></a>
                                <?php else: ?>
                                    <span class="badge bg-danger text-white border border-danger px-2 py-1 badge-status">Inactive</span>
                                    <a href="adduser_list_status_active.php?id=<?php echo $row['e_id']; ?>" class="btn btn-link btn-sm text-success p-0 ms-1" title="Activate"><i class="fas fa-user-check"></i></a>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="adduser_list_edit.php?id=<?php echo $row["e_id"]; ?>&name=<?php echo $row["e_name"]; ?>&department=<?php echo $row["d_name"]; ?>&designation=<?php echo $row["e_designation"]; ?>&comid=<?php echo $row["e_com_id"]; ?>" 
                                       class="btn btn-outline-primary" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="adduser_list_delete.php?id=<?php echo $row["e_id"]; ?>" 
                                       class="btn btn-outline-danger" onclick="return confirm('Are you sure?')" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination pagination-sm justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page-1; ?>&search_id=<?php echo $search; ?>">Previous</a>
                    </li>
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo ($page == $i) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search_id=<?php echo $search; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page+1; ?>&search_id=<?php echo $search; ?>">Next</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $("#collapseLayouts").addClass("show");
        $("#collapseLayouts_list").addClass("active bg-success");
    });
</script>

<?php include '../template/footer.php'; ?>