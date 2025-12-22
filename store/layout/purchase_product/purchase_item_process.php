<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include '../template/header.php'; // Bootstrap, icons, jQuery included
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Highlight the current menu item
        $("#collapseLayouts2").addClass("show");
        $("#collapseLayouts2_add").addClass("active bg-success");
        
        // Hide success/error message after 5 seconds
        setTimeout(function() {
            $("#status-message").fadeOut('slow');
        }, 5000);
    });
</script>

<main class="container py-5">
    <h3 class="mb-5 border-bottom pb-2 text-primary fw-bold">🛍️ Create New Purchase Order</h3>

    

    <form action="purchase_item_submit_store.php" method="POST">
        <input type="hidden" name="supplier_id" id="form_supplier_id">

        <div class="row mb-5 g-4"> <!-- g-4 adds spacing between columns -->

<!-- PO Number -->
<div class="col-lg-6">
    <label for="po_number" class="form-label fw-bold">Purchase Order Number</label>
    <div class="input-group input-group-lg">
        <!-- <span class="input-group-text bg-light text-primary">
            <i class="bi bi-receipt"></i>
        </span> -->
        <input type="text" 
               name="po_number" 
               id="po_number" 
               class="form-control border-primary" 
               placeholder="Enter unique PO number (e.g., PO-2025-001)" 
               required>
    </div>
    <div class="form-text text-muted">
        Unique identifier for this purchase order.
    </div>
</div>

<!-- Supplier Selection -->
<div class="col-lg-6">
    <section class="card shadow-sm border-success h-100">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <h5 class="m-0">Common Supplier for this PO</h5>
            <span class="badge bg-light text-dark fs-6" id="global_supplier_selected_text">
                No supplier selected
            </span>
        </div>
        <div class="card-body position-relative">
            <div class="input-group mb-2">
                <input type="text" id="supplier_search_global" class="form-control" placeholder="Search supplier (min 2 chars)">
                <button class="btn btn-outline-secondary" type="button" id="clear_supplier" title="Clear selection">Clear</button>
            </div>
            <input type="hidden" name="global_supplier_id" id="global_supplier_id">
            <div id="supplier_result_global" class="list-group position-absolute w-100 shadow-sm" style="z-index:1050;"></div>
        </div>
    </section>
</div>

</div>


        <!-- Item Search -->
        <section class="card shadow-sm mb-5 border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="m-0">Search & Add Items</h5>
            </div>
            <div class="card-body position-relative">
                <input type="text" id="item_search" class="form-control form-control-lg" placeholder="Search item by name or code...">
                <div id="item_result" class="list-group mt-2 position-absolute w-100 shadow-sm" style="z-index:1040;"></div>
            </div>
        </section>

        <!-- Purchase Table -->
        <h4 class="mb-3 text-secondary">Items to Purchase</h4>
        <div class="table-responsive mb-4 shadow-sm">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark text-center">
                    <tr>
                        <th>Item Name</th>
                        <th class="col-2">Quantity</th>
                        <th class="col-2">Unit Price</th>
                        <th class="col-2 text-end">Total</th>
                        <th class="col-1">Action</th>
                    </tr>
                </thead>
                <tbody id="purchase_list"></tbody>
                <tfoot>
                    <tr class="table-info">
                        <th colspan="3" class="text-end">Grand Total:</th>
                        <th id="grand_total" class="text-end fw-bold fs-5">0.00</th>
                        <th></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <button type="submit" class="btn btn-success btn-lg w-100 mt-3" id="submit_purchase" disabled>
            <i class="bi bi-box-arrow-in-right"></i> Submit Purchase Order
        </button>
    </form>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<script>
$(document).ready(function() {

    function updateSubmitButton() {
        const supplierId = $("#global_supplier_id").val();
        const itemCount = $("#purchase_list tr").length;
        $("#submit_purchase").prop('disabled', !(supplierId && itemCount > 0));
    }

    function calculateRowTotal(row) {
        let qty = parseFloat(row.find(".qty").val()) || 0;
        let price = parseFloat(row.find(".price").val()) || 0;
        let total = qty * price;
        row.find(".total").text(total.toFixed(2));
        return total;
    }

    function calculateGrandTotal() {
        let grandTotal = 0;
        $("#purchase_list tr").each(function() {
            grandTotal += calculateRowTotal($(this));
        });
        $("#grand_total").text(grandTotal.toFixed(2));
        updateSubmitButton();
    }

    updateSubmitButton();

    // Supplier Search & Select
    $("#supplier_search_global").on('keyup', function() {
        let q = $(this).val();
        if(q.length < 2) { $("#supplier_result_global").html(""); return; }
        $.post("search_supplier.php", { query: q }, function(data) {
            $("#supplier_result_global").html(data);
        });
    });

    $(document).on("click", "#supplier_result_global .supplier_result_item", function(e) {
        e.preventDefault();
        let id = $(this).data("supplier-id");
        let name = $(this).data("supplier-name");
        $("#global_supplier_id").val(id);
        $("#form_supplier_id").val(id);
        $("#global_supplier_selected_text")
            .text(name)
            .removeClass("bg-danger text-dark")
            .addClass("bg-success text-white");
        $("#supplier_result_global").html("");
        $("#supplier_search_global").val(name).prop('disabled', true);
        updateSubmitButton();
    });

    $("#clear_supplier").on("click", function() {
        $("#global_supplier_id, #form_supplier_id").val("");
        $("#supplier_search_global").val("").prop('disabled', false);
        $("#global_supplier_selected_text")
            .text("No supplier selected")
            .removeClass("bg-success text-white")
            .addClass("bg-danger text-dark");
        $("#supplier_result_global").html("");
        updateSubmitButton();
    });

    // Item Search & Add
    $("#item_search").on('keyup', function() {
        let q = $(this).val();
        if(q.length < 2) { $("#item_result").html(""); return; }
        if(!$("#global_supplier_id").val()) {
            $("#item_result").html('<div class="list-group-item list-group-item-warning">Please select a supplier first.</div>');
            return;
        }
        $.post("search_item.php", { query: q }, function(data) {
            $("#item_result").html(data);
        });
    });

    window.addItem = function(id, name, price) {
        if($(`input[name="item_id[]"][value="${id}"]`).length > 0) { alert('This item is already in the list.'); return; }
        let initialPrice = parseFloat(price).toFixed(2);
        let row = `
            <tr>
                <td><input type="hidden" name="item_id[]" value="${id}"><span class="fw-bold text-dark">${name}</span></td>
                <td><input type="number" name="qty[]" class="form-control form-control-sm qty" min="1" value="1" required></td>
                <td><div class="input-group input-group-sm"><span class="input-group-text">$</span><input type="number" name="unit_price[]" class="form-control price" value="${initialPrice}" step="0.01" min="0.01" required></div></td>
                <td class="total text-end fw-bold">${initialPrice}</td>
                <td class="text-center"><button type="button" class="btn btn-danger btn-sm remove" title="Remove Item"><i class="bi bi-trash"></i></button></td>
            </tr>
        `;
        $("#purchase_list").append(row);
        $("#item_search").val("");
        $("#item_result").html("");
        calculateGrandTotal();
    }

    $(document).on("click", ".remove", function() { $(this).closest("tr").remove(); calculateGrandTotal(); });
    $(document).on("input", ".qty, .price", function() { calculateGrandTotal(); });
});
</script>

<?php
include '../template/footer.php';
?>
