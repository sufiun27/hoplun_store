<!--### Header Part ##################################################-->
<?php include '../template/header.php'; ?>
<!--#####################################################-->

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

 
    <div class="container-fluid px-4">

        <div class="row justify-content-center">

            <!-- Add Department Form -->
            <div class="col-lg-6 col-md-8">

                <div class="card shadow-lg border-0 rounded-3 mb-4">
                    <div class="card-header bg-primary text-white py-3">
                        <h4 class="mb-0 text-center">Add New Department</h4>
                    </div>

                    <div class="card-body bg-light">

                        <form action="adduser_dep_process.php" method="POST">

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Department Name (Short)</label>
                                <input type="text" class="form-control" name="department_short_name"
                                       placeholder="e.g., HR">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Department Name (Full)</label>
                                <input type="text" class="form-control"
                                       name="department_full_name"
                                       placeholder="e.g., Human Resource">
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-success px-4 py-2 fw-bold">
                                    Submit
                                </button>
                            </div>

                        </form>

                        <!-- Display response message -->
                        <?php if (isset($_GET['value_dep'])): ?>
                            <div class="alert mt-4 text-center 
                                <?php echo $_GET['value_dep'] == 'Record inserted successfully' ? 'alert-success' : 'alert-danger'; ?>">
                                <?php echo $_GET['value_dep']; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-muted text-center mt-3">Please add a new department.</p>
                        <?php endif; ?>

                    </div>
                </div>

            </div>


            <!-- Department List -->
            <div class="col-lg-6 col-md-8">

                <div class="card shadow-sm border-0 rounded-3">
                    <div class="card-header bg-secondary text-white py-3">
                        <h4 class="mb-0 text-center">Department List</h4>
                    </div>

                    <div class="card-body">

                        <?php
                       

                        $sql = "SELECT d_name FROM department ORDER BY d_name DESC";
                        $result = $conn->query($sql);
                        $rowno = 0;

                        echo "<ul class='list-group list-group-flush'>";

                        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                            echo "<li class='list-group-item d-flex justify-content-between align-items-center'>
                                    <span class='fw-semibold'>" . $row['d_name'] . "</span>
                                  </li>";
                            $rowno++;
                        }
                        echo "</ul>";

                        echo "<p class='mt-3 fw-bold text-center'>Total Departments: $rowno</p>";

                        $conn = null;
                        ?>

                    </div>
                </div>

            </div>

        </div>

    </div>
</main>

<?php include '../template/footer.php'; ?>
</div>
