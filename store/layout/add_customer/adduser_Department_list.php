<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Include the Database class and Header

include '../template/header.php';

$db = new Database();
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>

    $(document).ready(function() {

        $("#collapseLayouts").addClass("show");

        $("#collapseLayouts_department").addClass("active bg-success");

        

        setTimeout(function() {

            $("#statusMessage").fadeOut("slow");

        }, 3000);

    });

</script>
<div class="container-fluid px-4 py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Department Management</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="../dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Departments</li>
                </ol>
            </nav>
        </div>
        <a href="http://<?php echo $_SESSION['base_url']; ?>/store/layout/add_customer/adddepartment.php" 
           class="btn btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50 me-1"></i> Add New Department
        </a>
    </div>

    <?php if (isset($_GET['status'])): ?>
        <div id="statusMessage" class="alert alert-<?php echo $_GET['status'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas <?php echo $_GET['status'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> me-2"></i>
            <?php echo $_GET['status'] === 'success' ? 'Operation completed successfully!' : 'An error occurred during the operation.'; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center bg-light">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-table me-2"></i>Department List</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle" id="departmentTable" width="100%" cellspacing="0">
                    <thead class="table-light text-secondary">
                        <tr>
                            <th>Short Name</th>
                            <th>Full Name</th>
                            <th class="text-center">Employees</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $sql = "SELECT d.d_active, d.d_id, d.d_full_name, d.d_name, 
                                           ISNULL(e.total_employee, 0) as total_employee
                                    FROM department d 
                                    LEFT JOIN (
                                        SELECT d_id, COUNT(e_id) as total_employee 
                                        FROM employee GROUP BY d_id
                                    ) e ON e.d_id = d.d_id";

                            $conn = $db->getConnection();
                            $stmt = $conn->prepare($sql);
                            $stmt->execute();
                            $result = $stmt->fetchAll();

                            if ($result):
                                foreach ($result as $row):
                                    $d_id = $row['d_id'];
                                    $isActive = $row['d_active'] == 1;
                        ?>
                            <tr>
                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($row['d_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['d_full_name']); ?></td>
                                <td class="text-center">
                                    <span class="badge bg-info text-dark rounded-pill">
                                        <?php echo $row['total_employee']; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <?php if ($isActive): ?>
                                        <span class="badge bg-success-subtle text-success border border-success px-3">
                                            <i class="fas fa-check me-1"></i> Active
                                        </span>
                                        <a href="adduser_Department_list_deactive.php?id=<?php echo $d_id; ?>" class="ms-2 text-warning" title="Deactivate">
                                            <i class="fas fa-toggle-on fa-lg"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-danger-subtle text-danger border border-danger px-3">
                                            <i class="fas fa-times me-1"></i> Inactive
                                        </span>
                                        <a href="adduser_Department_list_active.php?id=<?php echo $d_id; ?>" class="ms-2 text-secondary" title="Activate">
                                            <i class="fas fa-toggle-off fa-lg"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="adduser_list_department_edit.php?id=<?php echo $d_id; ?>" class="btn btn-outline-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="adduser_dep_delete.php?id=<?php echo $d_id; ?>" 
                                           class="btn btn-outline-danger" 
                                           onclick="return confirm('Are you sure you want to delete this department?');" 
                                           title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php 
                                endforeach;
                            else: 
                        ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No department records found.</td>
                            </tr>
                        <?php endif; 
                        } catch (Exception $e) {
                            echo '<tr><td colspan="5" class="text-danger text-center">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // UI Navigation Highlighting
        $("#collapseLayouts").addClass("show");
        $("#collapseLayouts_department").addClass("active bg-primary text-white");
        
        // Auto-hide alerts
        setTimeout(function() {
            $(".alert").fadeOut("slow");
        }, 4000);
    });
</script>

<?php include '../template/footer.php'; ?>