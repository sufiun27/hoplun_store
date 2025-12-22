<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
error_reporting(E_ALL & ~E_NOTICE);

if (!isset($_GET['id'])) {
    header("Location: supplier_search.php?value=");
    exit;
}

require_once '../database.php';

$s_id = $_GET['id'];

// --------------------------------------------------
// Supplier Edit Class using Database.php
// --------------------------------------------------
class SupplierEdit extends Database
{
    public function viewSupplier($id)
    {
        try {
            $sql = "SELECT * FROM supplier WHERE s_id = :id";
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute([":id" => $id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            die("Query Error: " . $e->getMessage());
        }
    }
}

$supp = new SupplierEdit();
$suppinfo = $supp->viewSupplier($s_id);
?>

<?php include '../template/header.php'; ?>

<style>
    .card-style {
        background: #ffffff;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        padding: 25px;
        border-left: 5px solid #0d6efd;
    }

    .form-label {
        font-weight: 600;
        color: #333;
    }

    .page-title {
        font-weight: 700;
        font-size: 24px;
        color: #0d6efd;
    }

    .fade-alert {
        animation: fadeOut 1s ease-in-out 3s forwards;
    }

    @keyframes fadeOut {
        to { opacity: 0; visibility: hidden; }
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        $("#collapseLayouts0").addClass("show");
        $("#collapseLayouts0_search").addClass("active bg-success");
    });
</script>


        <div class="container-fluid px-4">

            <div class="row justify-content-center mt-4">
                <div class="col-md-7">

                    <div class="card-style">

                        <div class="mb-4 d-flex justify-content-between align-items-center">
                            <h3 class="page-title">Edit Supplier</h3>

                            <a href="supplier_list.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fa fa-arrow-left"></i> Back
                            </a>
                        </div>

                        <!-- ALERT MESSAGES -->
                        <?php if (isset($_GET['value_dep'])): ?>
                            <?php
                                $msg = $_GET['value_dep'];
                                $alertClass = ($msg === "Record updated successfully") ? "alert-success" : "alert-danger";
                            ?>
                            <div class="alert <?php echo $alertClass; ?> fade-alert">
                                <?php echo $msg; ?>
                            </div>
                        <?php endif; ?>

                        <form action="update_supplier_process.php" method="POST">

                            <input type="hidden" name="s_id" value="<?php echo htmlspecialchars($s_id); ?>">

                            <div class="mb-3">
                                <label class="form-label">Supplier Name</label>
                                <input type="text" class="form-control form-control-lg"
                                    name="supplier_name"
                                    value="<?php echo htmlspecialchars($suppinfo[0]['s_name']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Address</label>
                                <input type="text" class="form-control form-control-lg"
                                    name="address"
                                    value="<?php echo htmlspecialchars($suppinfo[0]['s_address']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone Number</label>
                                <input type="text" class="form-control form-control-lg"
                                    name="phone"
                                    value="<?php echo htmlspecialchars($suppinfo[0]['s_phone']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control form-control-lg"
                                    name="email"
                                    value="<?php echo htmlspecialchars($suppinfo[0]['s_email']); ?>" required>
                            </div>

                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-primary px-4 py-2">
                                    <i class="fa fa-save"></i> Update Supplier
                                </button>
                            </div>

                        </form>

                    </div>

                </div>
            </div>

        </div>
    </main>

    <?php include '../template/footer.php'; ?>
</div>
