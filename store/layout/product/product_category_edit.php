<?php
include '../template/header.php';
if (session_status() == PHP_SESSION_NONE) { session_start(); }
include '../database.php'; 

// Fetch category
$depid = intval($_GET['id']);
$sql = "SELECT * FROM category_item WHERE c_id = :c_id";
$stmt = $conn->prepare($sql);
$stmt->execute(['c_id' => $depid]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $("#collapseLayouts1").addClass("show");
    $("#collapseLayouts1_category").addClass("active bg-success");

    // Auto-hide message
    setTimeout(() => { $("#messageBox").fadeOut(); }, 3000);
});
</script>

<style>
/* Card Layout */
.card-custom {
    background: #ffffff;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
    transition: transform 0.2s ease;
}
.card-custom:hover {
    transform: translateY(-2px);
}

/* Typography */
h1, h4 {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    font-weight: 600;
}
h1 { color: #1f3c88; margin-bottom: 20px; }
h4 { color: #3b3b3b; margin-bottom: 25px; }

/* Labels & Inputs */
label {
    font-weight: 600;
}
input.form-control {
    border-radius: 8px;
    border: 1px solid #ced4da;
    padding: 10px 12px;
    transition: 0.3s;
}
input.form-control:focus {
    border-color: #1f3c88;
    box-shadow: 0 0 5px rgba(31,60,136,0.2);
}

/* Buttons */
button.btn-primary {
    background-color: #1f3c88;
    border-color: #1f3c88;
    padding: 10px 20px;
    font-weight: 600;
    border-radius: 8px;
    transition: 0.3s;
}
button.btn-primary:hover {
    background-color: #162b61;
    border-color: #162b61;
}

/* Messages */
#messageBox .alert {
    border-radius: 8px;
    font-weight: 600;
    text-align: center;
    margin-top: 15px;
}
</style>


    <div class="container-fluid px-4">
        <div class="row justify-content-center">
            <div class="col-md-6">

                <div class="card-custom">

                    <h4>Current Category: <?php echo htmlspecialchars($row['c_name']); ?></h4>
                    <h1>Update Category</h1>

                    <!-- Message Box -->
                    <div id="messageBox">
                        <?php
                        if(isset($_GET['value_dep'])){
                            $msg = htmlspecialchars($_GET['value_dep']);
                            $class = ($msg == "Record update successfully") ? "alert-success" : "alert-danger";
                            echo "<div class='alert $class'>$msg</div>";
                        }
                        ?>
                    </div>

                    <form action="product_category_update.php" method="POST">

                        <div class="mb-3">
                            <label for="c_name">Category Name</label>
                            <input type="text" class="form-control" name="c_name" id="c_name" 
                                   value="<?php echo htmlspecialchars($row['c_name']); ?>" required>
                        </div>

                        <input type="hidden" name="c_id" value="<?php echo $row['c_id']; ?>">

                        <button type="submit" class="btn btn-primary w-100 mt-3">Update Category</button>

                    </form>

                </div>
            </div>
        </div>
    </div>
</main>

<?php include '../template/footer.php'; ?>
</div>
