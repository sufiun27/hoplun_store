<!--### Header Part ##################################################-->
<?php include '../template/header.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $("#collapseLayouts").addClass("show");
        $("#collapseLayouts_department").addClass("active bg-success text-white");
    });
</script>
<!--#####################################################-->



<?php
// Fetch Department
$depid = $_GET['id'];
$sql = "SELECT * FROM department WHERE d_id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $depid, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
?>


    <div class="container-fluid px-4 mt-4">

        <div class="row justify-content-center">
            <div class="col-lg-6">

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Update Department</h4>
                    </div>

                    <div class="card-body">

                        <p class="text-muted mb-4">
                            Current Department: 
                            <span class="fw-bold text-dark">
                                <?= htmlspecialchars($row['d_name']); ?>
                            </span>
                        </p>

                        <form action="adduser_dep_update.php" method="POST">

                            <!-- Short Name -->
                            <div class="mb-3">
                                <label class="form-label">Department Name (Short)</label>
                                <input 
                                    type="text" 
                                    class="form-control form-control-lg"
                                    name="department_short_name"
                                    value="<?= htmlspecialchars($row['d_name']); ?>"
                                    required
                                >
                            </div>

                            <!-- Full Name -->
                            <div class="mb-3">
                                <label class="form-label">Department Name (Full)</label>
                                <input 
                                    type="text" 
                                    class="form-control form-control-lg"
                                    name="department_full_name"
                                    value="<?= htmlspecialchars($row['d_full_name']); ?>"
                                    required
                                >
                            </div>

                            <input type="hidden" name="depid" value="<?= $row['d_id']; ?>">

                            <button type="submit" class="btn btn-success btn-lg w-100">
                                Save Changes
                            </button>

                        </form>

                        <!-- Status Message -->
                        <?php if (isset($_GET['value_dep'])): ?>
                            <div class="alert mt-4 
                                <?= $_GET['value_dep'] === 'Record updated successfully' ? 'alert-success' : 'alert-danger'; ?>
                                text-center fw-bold">
                                <?= htmlspecialchars($_GET['value_dep']); ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info mt-4 text-center">
                                Update department details and click Save.
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>

    </div>
</main>

<!--###### Footer Part ###############################################-->
<?php include '../template/footer.php'; ?>
