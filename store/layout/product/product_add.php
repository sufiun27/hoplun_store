<?php
include '../template/header.php';
?>
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
    $(document).ready(function() {
        $("#collapseLayouts1").addClass("show");
        $("#collapseLayouts1_add").addClass("active bg-success");
    });
</script>


    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-7">

                <!-- Card Layout for Modern UI -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="text-center text-primary mb-4"><b>Add New Item</b></h2>

                        <form id="submitForm" action="product_item_process.php" method="POST">
                            
                            <!-- Category Live Search -->
                            <div class="mb-4 form-floating">
                                <input type="text" class="form-control" id="live_search" placeholder="Search categories..." autocomplete="off" required>
                                <label for="live_search">Select Category</label>
                                <div id="search_result" class="mt-2"></div>
                            </div>

                            <!-- Item Fields with Floating Labels -->
                            <div class="form-floating  p-5">
                                <input type="text" class="form-control " id="item_name" name="item_name" required>
                                <label for="item_name">Item Name</label>
                            </div>

                            <div class="form-floating p-5">
                                <input type="text" class="form-control" id="item_code" name="item_code" required>
                                <label for="item_code">Item Code</label>
                            </div>

                            <div class="form-floating p-5">
                                <input type="text" class="form-control" id="brand" name="brand" required>
                                <label for="brand">Brand</label>
                            </div>

                            <div class="form-floating p-5">
                                <input type="text" class="form-control" id="unit" name="Unit" required>
                                <label for="unit">Unit</label>
                            </div>

                            <div class="form-floating p-5">
                                <input type="text" class="form-control" id="size" name="size" required>
                                <label for="size">Size</label>
                            </div>

                            <div class="form-floating p-5">
                                <input type="text" class="form-control" id="price" name="price" required>
                                <label for="price">Price</label>
                            </div>

                            <div class="form-floating mb-4">
                                <input type="number" class="form-control" id="stock_out_reminder_qty" name="stock_out_reminder_qty" required>
                                <label for="stock_out_reminder_qty">Stock Out Reminder Quantity</label>
                            </div>

                            <button type="submit" class="btn btn-success w-100 py-2">Submit</button>

                            <!-- Feedback Message -->
                            <?php if(isset($_GET['value_emp'])): ?>
                                <div class="alert mt-3 <?= $_GET['value_emp']=="Record inserted successfully" ? 'alert-success' : 'alert-danger' ?>" role="alert">
                                    <?= htmlspecialchars($_GET['value_emp']) ?>
                                </div>
                            <?php endif; ?>

                        </form>
                    </div>
                </div>
                <!-- End Card -->

            </div>
        </div>
    </div>
</main>

<!-- Live Search Script -->
<script>
$(document).ready(function() {
    $("#live_search").on("keyup", function() {
        const input = $(this).val();
        if(input !== "") {
            $.ajax({
                url: "livesearch.php",
                method: "POST",
                data: { input: input },
                success: function(data) {
                    $("#search_result").html(data);
                }
            });
        } else {
            $("#search_result").empty();
        }
    });

    // Click category to auto-select radio
    $(document).on('click', '.search-item-hover', function(){
        $(this).find('input[type=radio]').prop('checked', true);
    });
});
</script>

<?php include '../template/footer.php'; ?>
