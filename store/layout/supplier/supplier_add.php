<!--### Header Part ##################################################-->
<?php include '../template/header.php'; ?>
<!--#####################################################-->

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $("#collapseLayouts0").addClass("show");
        $("#collapseLayouts0_add").addClass("active bg-success text-white");
    });
</script>


    <div class="container-fluid px-4 mt-4">

        <div class="row justify-content-center">
            <div class="col-lg-6">

                <!-- Card -->
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Add New Supplier</h4>
                    </div>

                    <div class="card-body">

                        <form action="add_supplier_process.php" method="POST">

                            <!-- Supplier Name -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Supplier Name</label>
                                <input 
                                    type="text" 
                                    class="form-control form-control-lg"
                                    name="supplier_name" 
                                    id="supplier_name" 
                                    placeholder="Enter supplier name"
                                    required>
                            </div>

                            <!-- Address -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Address</label>
                                <input 
                                    type="text" 
                                    class="form-control form-control-lg"
                                    name="address" 
                                    id="address" 
                                    placeholder="Enter address"
                                    required>
                            </div>

                            <!-- Phone -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Phone Number</label>
                                <input 
                                    type="tel" 
                                    class="form-control form-control-lg"
                                    name="phone" 
                                    id="phone" 
                                    placeholder="Enter phone number"
                                    required>
                            </div>

                            <!-- Email -->
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Email</label>
                                <input 
                                    type="email" 
                                    class="form-control form-control-lg"
                                    name="email" 
                                    id="email" 
                                    placeholder="example@example.com"
                                    required>
                            </div>

                            <button type="submit" class="btn btn-success btn-lg w-100 mt-2">
                                Save Supplier
                            </button>

                        </form>

                        <!-- Status message -->
                        <?php if (isset($_GET['value_dep'])): ?>
                            <div id="statusMsg"
                                class="alert mt-4 text-center fw-bold 
                                    <?= $_GET['value_dep'] === 'Record inserted successfully' ? 'alert-success' : 'alert-danger'; ?>">
                                <?= htmlspecialchars($_GET['value_dep']); ?>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>

    </div>
</main>

<!-- Auto Hide Status Message -->
<script>
    setTimeout(() => {
        let msg = document.getElementById("statusMsg");
        if (msg) msg.style.display = "none";
    }, 3500);
</script>

<?php include '../template/footer.php'; ?>
