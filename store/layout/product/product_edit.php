<?php
include '../template/header.php';

$p_id = $_GET['p_id'] ?? 0;

// Fetch item with its category
$sql = "SELECT * FROM item
        INNER JOIN category_item ON item.c_id = category_item.c_id
        WHERE i_id = :p_id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':p_id', $p_id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
?>

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-7">

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h2 class="mb-4 text-center text-primary"><b>Update Item</b></h2>

                        <form id="submitForm" action="product_item_process_update.php" method="POST">
                            <input type="hidden" name="pid" value="<?= htmlspecialchars($p_id) ?>">

                            <!-- Category Live Search -->
                            <div class="mb-3">
                                <label class="form-label"><strong>Select Category</strong></label>
                                <input type="text" class="form-control" id="live_search" 
                                       placeholder="Search categories..." autocomplete="off"
                                       value="<?= htmlspecialchars($row['c_name']) ?>">
                                <input type="hidden" name="c_id" id="selected_category" value="<?= $row['c_id'] ?>">
                                <div id="search_result" class="mt-2"></div>
                            </div>

                            <!-- Item Fields -->
                            <?php 
                            $fields = [
                                'i_name' => 'Item Name',
                                'i_code' => 'Item Code',
                                'i_manufactured_by' => 'Brand',
                                'i_unit' => 'Unit',
                                'i_size' => 'Size',
                                'i_price' => 'Price',
                                'stock_out_reminder_qty' => 'Stock Out Reminder Quantity'
                            ];

                            foreach ($fields as $key => $label) {
                                $value = htmlspecialchars($row[$key] ?? '');
                                echo '<div class="mb-3">
                                        <label class="form-label">' . $label . '</label>
                                        <input type="text" class="form-control" name="' . $key . '" value="' . $value . '" required>
                                      </div>';
                            }
                            ?>

                            <button type="submit" class="btn btn-success w-100">Update Item</button>

                            <?php if(isset($_GET['value_emp'])): ?>
                                <div class="alert mt-3 <?= $_GET['value_emp'] == 'Record inserted successfully' ? 'alert-success' : 'alert-danger' ?>" role="alert">
                                    <?= htmlspecialchars($_GET['value_emp']) ?>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</main>

<!-- Live Search JS -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script>
$(document).ready(function() {
    let currentCategoryId = $('#selected_category').val();

    // Live search
    $("#live_search").on("keyup", function() {
        var input = $(this).val();
        if(input !== "") {
            $.post("livesearch.php", {input: input, current: currentCategoryId}, function(data) {
                $("#search_result").html(data);
            });
        } else {
            $("#search_result").empty();
        }
    });

    // Select category from search result
    $(document).on('click', '.search-item-hover', function(){
        var radio = $(this).find('input[type=radio]');
        radio.prop('checked', true);
        $('#selected_category').val(radio.val()); // Update hidden field
        $('#live_search').val($(this).text().trim()); // Show selection in input
        $("#search_result").empty(); // Clear search results
    });

    // Prevent submission without category
    $('#submitForm').on('submit', function() {
        if($('#selected_category').val() === '') {
            alert('Please select a category.');
            return false;
        }
    });
});
</script>

<?php include '../template/footer.php'; ?>
