<!--### Header Part ##################################################-->
<?php include '../template/header.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        $("#collapseLayouts").addClass("show");
        $("#collapseLayouts_list").addClass("active bg-success");
    });
</script>

<!--#####################################################-->
<div id="layoutSidenav_content">

<?php
$empid = $_GET['id'];
$sql = "SELECT * FROM employee e 
        INNER JOIN department d ON e.d_id = d.d_id 
        WHERE e.e_id = $empid";
$result = $conn->query($sql);
$row = $result->fetch(PDO::FETCH_ASSOC);
?>


    <div class="container-fluid px-4">

        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-9 col-sm-12">
                
                <div class="card shadow-lg border-0 rounded-3">
                    <div class="card-header bg-primary text-white py-3">
                        <h4 class="mb-0 text-center">Update Employee Information</h4>
                    </div>

                    <div class="card-body bg-light">

                        <form action="adduser_list_edit_process.php" method="POST">
                            <input type="hidden" name="e_id" value="<?php echo $empid; ?>">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Name</label>
                                <input type="text" class="form-control" name="name" 
                                       value="<?php echo $row['e_name']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">ID</label>
                                <input type="text" class="form-control" name="id" 
                                       value="<?php echo $row['e_com_id']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Designation</label>
                                <input type="text" class="form-control" name="designation" 
                                       value="<?php echo $row['e_designation']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Management Type</label>
                                <select class="form-select" name="mn" required>
                                    <option value="Management" <?php echo ($row['e_type'] ?? '') == 'Management' ? 'selected' : ''; ?>>Management</option>
                                    <option value="Non-Management" <?php echo ($row['e_type'] ?? '') == 'Non-Management' ? 'selected' : ''; ?>>Non-Management</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Department</label>
                                <select class="form-select" name="department" required>
                                    <option selected disabled>Select Department</option>

                                    <?php
                                    $selectedDep = $row['d_name'];
                                    $depQuery = $conn->query("SELECT d_id, d_name FROM department");

                                    while ($d = $depQuery->fetch(PDO::FETCH_ASSOC)) {
                                        $isSelected = ($d['d_name'] == $selectedDep) ? "selected" : "";
                                        echo "<option value='{$d['d_id']}' $isSelected>{$d['d_name']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-success px-4 py-2 fw-bold">
                                    Update Employee
                                </button>
                            </div>
                        </form>

                        <!-- Show Messages -->
                        <?php if (isset($_GET['value'])): ?>
                            <div class="alert mt-4 text-center 
                                <?php echo ($_GET['value'] == 'Update successfully') ? 'alert-success' : 'alert-danger'; ?>">
                                <?php echo $_GET['value']; ?>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>

    </div>
</main>

<?php include '../template/footer.php'; ?>
</div>
