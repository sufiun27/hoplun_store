<?php
include '../template/header.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include '../layoutdbconnection.php';
$section = $_SESSION['section'];
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $("#collapseLayouts1").addClass("show");
    $("#collapseLayouts1_add_category").addClass("active bg-success");

    // Hide alert after 3 sec
    setTimeout(() => { $("#message").fadeOut(); }, 3000);
});
</script>

<style>
    td, th { font-size: 12px; font-weight: bold; }
    .card-custom {
        background: #ffffff;
        border-radius: 10px;
        box-shadow: 0px 2px 10px rgba(0,0,0,0.1);
    }
</style>


    <div class="container-fluid px-4">

        <!-- Page Title -->
        <div class="d-flex justify-content-between align-items-center my-4">
            <h3 class="text-dark">Category Management</h3>
        </div>

        <!-- Success/Error Message -->
        <div id="message">
            <?php if (isset($_GET['value'])) echo "<div class='alert alert-info'>".$_GET['value']."</div>"; ?>
        </div>

        <!-- Card: Add Category + Category Table -->
        <div class="row">

            <!-- LEFT: Add Category -->
            <div class="col-md-4">
                <div class="card card-custom p-3">
                    <h5 class="mb-3">Add Category</h5>

                    <form action="product_add_catagory.php" method="POST">

                        <div class="mb-3">
                            <label class="form-label">Category Name</label>
                            <input type="text" name="category_name" class="form-control" placeholder="Enter category name" required>
                        </div>

                        <input type="hidden" name="section" value="<?php echo htmlspecialchars($section); ?>">

                        <button type="submit" class="btn btn-success w-100">Add Category</button>
                    </form>
                </div>
            </div>

            <!-- RIGHT: Category List -->
            <div class="col-md-8">
                <div class="card card-custom mb-4">
                    <div class="card-header">
                        <i class="fas fa-table me-1"></i> Category List
                    </div>

                    <?php
                    $sql = "SELECT c.c_active, c.c_id, c.c_name, COALESCE(i.total_item, 0) as total_item
                            FROM category_item c
                            LEFT JOIN (
                                SELECT c_id, COUNT(i_id) as total_item FROM item GROUP BY c_id
                            ) i ON c.c_id = i.c_id
                            WHERE c.section = :section
                            ORDER BY c.c_name ASC";

                    $stmt = $conn->prepare($sql);
                    $stmt->execute(['section' => $section]);
                    ?>

                    <div class="card-body">
                        <table id="datatablesSimple" class="table table-striped table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Category</th>
                                    <th>Total Item</th>
                                    <th>Status</th>
                                    <th width="150">Action</th>
                                </tr>
                            </thead>
                            <tbody>

                            <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { ?>
                                <tr>
                                    <td><?= $row["c_name"] ?></td>
                                    <td><?= $row["total_item"] ?></td>

                                    <td>
                                        <?php if ($row["c_active"] == 1) { ?>
                                            <span class="text-success">Active</span>
                                            <a href="product_category_deactive.php?id=<?= $row['c_id'] ?>"
                                               class="btn btn-warning btn-sm">Deactivate</a>
                                        <?php } else { ?>
                                            <span class="text-danger">Inactive</span>
                                            <a href="product_category_active.php?id=<?= $row['c_id'] ?>"
                                               class="btn btn-success btn-sm">Activate</a>
                                        <?php } ?>
                                    </td>

                                    <td>
                                        <a href="product_category_edit.php?id=<?= $row['c_id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                                        <a href="product_category_delete.php?id=<?= $row['c_id'] ?>" class="btn btn-danger btn-sm"
                                           onclick="return confirm('Are you sure?')">Delete</a>
                                    </td>
                                </tr>
                            <?php } ?>

                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div> <!-- Row end -->

    </div>
</main>

<?php include '../template/footer.php'; ?>
</div>
