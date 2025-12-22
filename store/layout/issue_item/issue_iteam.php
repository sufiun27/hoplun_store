
<?php
// store/layout/issue_item/issue_iteam.php
include '../template/header.php';
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Highlight the current menu item
        $("#collapseLayouts3").addClass("show");
        $("#collapseLayouts3_add").addClass("active bg-success");
        
        // Hide success/error message after 5 seconds
        setTimeout(function() {
            $("#status-message").fadeOut('slow');
        }, 5000);
    });
</script>
<!-- <!DOCTYPE html>
<html lang="en"> -->
<!-- <head> -->
    <!-- <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> -->
    <!-- <title>Stock Item Issuance System</title> -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">  -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .step-content { display: none; }
        .step-active { display: block; }
        .progress-bar-container { margin-bottom: 20px; }
        /* Simple style to make search results clearly selectable */
        .select-employee-btn-container, .select-item-btn-container {
            padding: 10px;
            margin-bottom: 5px;
            border-radius: 5px;
        }
    </style>
<!-- </head> -->
<!-- <body> -->

    <div class="container-fluid my-5">
        <h1 class="text-center mb-4">📦 Stock Item Issue</h1>

        <div class="progress-bar-container">
            <div class="progress" role="progressbar" aria-label="Issuance Steps" aria-valuenow="33" aria-valuemin="0" aria-valuemax="100">
                <div id="step-progress" class="progress-bar" style="width: 33%">Step 1: Employee</div>
            </div>
        </div>
        
        <div id="step-1" class="step-content step-active card p-4 mb-4">
            <h3 class="card-title">1. Select Employee</h3>
            <div class="mb-3">
                <label for="employee-search" class="form-label">Search by ID or Name:</label>
                <input type="text" class="form-control" id="employee-search" placeholder="E.g., 1001 or John Doe">
            </div>
            <div id="employee-results" class="mt-3">
                <p class="text-muted">Start typing to search for an employee...</p>
            </div>
            <button id="next-step-2" class="btn btn-primary mt-3" disabled>Next: Invoice</button>
        </div>

        <div id="step-2" class="step-content card p-4 mb-4">
            <h3 class="card-title">2. Provide Invoice Number</h3>
            <div class="alert alert-info" id="selected-employee-info" role="alert">
                Employee Selected: **Waiting for selection...**
            </div>
            <div class="mb-3">
                <label for="invoice-number" class="form-label">Invoice/Requisition Number:</label>
                <input type="text" class="form-control" id="invoice-number" placeholder="Enter Invoice Number" required>
            </div>
            <button id="prev-step-1" class="btn btn-secondary me-2">Previous</button>
            <button id="next-step-3" class="btn btn-primary" disabled>Next: Select Items</button>
        </div>

        <div id="step-3" class="step-content card p-4 mb-4">
            <h3 class="card-title">3. Select Item(s)</h3>
            <div class="mb-3">
                <label for="item-search" class="form-label">Search Item by Name or Code:</label>
                <input type="text" class="form-control" id="item-search" placeholder="E.g., Pen, Monitor, or Code 456">
            </div>

            <div id="item-search-results" class="table-responsive mt-3 mb-4">
                <p class="text-muted">Start typing at least 3 characters to search for items.</p>
            </div>

            <hr>
            <h4>Items to Issue (<span id="item-count">0</span>)</h4>
            <div class="table-responsive">
                <table class="table table-bordered align-middle" id="selected-items-table">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name (Unit/Size)</th>
                            <th>Issue Price (Min: 0)</th>
                            <th>Quantity (Min: 1)</th>
                            <th>Stock</th>
                            <th>Replacement</th> 
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        </tbody>
                </table>
            </div>

            <button id="prev-step-2" class="btn btn-secondary me-2">Previous</button>
            
            <form id="issuance-form" action="submit_issuance.php" method="POST" class="d-inline">
                <input type="hidden" name="issuance_data" id="issuance-data" value="">
                <button id="submit-issuance" type="submit" class="btn btn-success" disabled>Submit Issuance (<span id="submit-item-count">0</span> Items)</button>
            </form>
            
        </div>

        <div class="container mt-4">
            <div class="card shadow-sm rounded-4">
                <div class="card-body">
                    <h5 class="card-title fw-bold text-primary mb-4">
                        <i class="bi bi-info-circle"></i> Issuance Summary
                    </h5>

                    <div class="d-flex mb-3 align-items-baseline">
                        <div class="fw-semibold text-secondary me-2">
                            Employee : 
                        </div>
                        <div class="">
                            <span id="employee-data" class="badge bg-light text-dark px-3 py-2">
                                —
                            </span>
                        </div>
                    </div>

                    <div class="d-flex align-items-baseline">
                        <div class="fw-semibold text-secondary me-2">
                            Invoice : 
                        </div>
                        <div class="">
                            <span id="invoice-data" class="badge bg-light text-dark px-3 py-2">
                                —
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card p-4 mt-5 bg-light" id="final-data-output" style="display: none;">
            <h3 class="card-title text-success">✅ Submission Object</h3>
            <pre id="final-data-json"></pre>
        </div>

    </div>

    <script>
        // Global object to store data throughout the steps
        let issuanceData = {
            employee_id: null,
            employee_name: null,
            invoice_number: null,
            items: [] // Array of { item_id, quantity, price, item_name, ... }
        };

        let currentStep = 1;

        // Function to control step visibility and progress bar
        function showStep(step) {
            $('.step-content').removeClass('step-active').hide();
            $('#step-' + step).addClass('step-active').show();
            currentStep = step;

            let progressWidth = (step / 3) * 100;
            let progressText = "Step " + step + ": ";
            if (step === 1) progressText += "Employee";
            else if (step === 2) progressText += "Invoice";
            else if (step === 3) progressText += "Items";

            $('#step-progress').css('width', progressWidth + '%').text(progressText);
            window.scrollTo(0, 0); // Scroll to top on step change
        }

        // --- Step 1: Employee Search & Selection Logic ---
        $('#employee-search').on('keyup', function() {
            const input = $(this).val();
            // Reset selection indicator if user starts typing again
            $('#next-step-2').prop('disabled', true).text('Next: Invoice');
            
            if (input.length > 2) {
                // AJAX call to search_employee.php (assumed to return HTML with .btn-select-employee)
                $.post('search_employee.php', { input: input }, function(data) {
                    $('#employee-results').html(data);
                }).fail(function() {
                    $('#employee-results').html('<p class="text-danger">Error searching for employees.</p>');
                });
            } else {
                $('#employee-results').html('<p class="text-muted">Start typing at least 3 characters to search.</p>');
            }
        });

        // Delegate click handler for 'Select' button (which is dynamically loaded)
        $(document).on('click', '.btn-select-employee', function(e) {
            e.preventDefault();
            const e_id = $(this).data('eid');
            const e_name = $(this).data('ename');
            const e_com_id = $(this).data('ecomid');
            const d_name = $(this).data('dname');

            // Store the data
            issuanceData.employee_id = e_id;
            issuanceData.employee_name = e_name;

            // Update Step 2 Info Card
            const employeeInfoHtml = `Employee Selected: **${e_name}** (${e_com_id}) from Department **${d_name}**`;
            $('#selected-employee-info').html(employeeInfoHtml);
            $('#employee-data').html(employeeInfoHtml);

            // Enable Next button and visually indicate selection
            $('#next-step-2').prop('disabled', false).text('Next: Invoice (Selected)');
            
            // Optional: Highlight the selected result (if applicable)
            $('.select-employee-btn-container').removeClass('bg-success bg-opacity-10').addClass('bg-light');
            $(this).closest('.select-employee-btn-container').addClass('bg-success bg-opacity-10').removeClass('bg-light');
        });

        $('#next-step-2').on('click', function() {
            showStep(2);
        });

        // --- Step 2: Invoice Number Logic ---
        $('#invoice-number').on('input', function() {
            const input = $(this).val().trim();
            if (input.length > 0) {
                $('#next-step-3').prop('disabled', false);
            } else {
                $('#next-step-3').prop('disabled', true);
            }
        });

        $('#next-step-3').on('click', function() {
            issuanceData.invoice_number = $('#invoice-number').val().trim();
            if (issuanceData.invoice_number) {
                // Update summary data
                $('#invoice-data').html(`Invoice Number: **${issuanceData.invoice_number}**`);
                showStep(3);
            } else {
                alert('Please provide an Invoice/Requisition Number.');
            }
        });
        
        $('#prev-step-1').on('click', function() {
            showStep(1);
        });

        // --- Step 3: Item Search and Selection Logic ---

        // Function to refresh the selected items table and update count/submit button
        function renderSelectedItems() {
            const $tbody = $('#selected-items-table tbody');
            $tbody.empty();

            issuanceData.items.forEach((item, index) => { // Crucial: use index for data updates
                const uid = item.item_id; 
                
                // Determine if stock is available
                const stock = parseInt(item.stock) || 0;
                const stockDisplay = stock > 0 ? `<span class="badge text-bg-success">${stock}</span>` : `<span class="badge text-bg-danger">0</span>`;

                $tbody.append(`
                    <tr id="row-${uid}-${index}" data-id="${uid}" data-index="${index}">
                        <td>${item.item_code || 'N/A'}</td>

                        <td>
                            <strong>${item.item_name}</strong>
                            <small class="text-muted">
                                (${item.item_unit} / ${item.item_size})
                            </small>
                        </td>

                        <td>
                            <input type="number"
                                class="form-control item-price"
                                data-index="${index}"
                                value="${parseFloat(item.price).toFixed(2)}"
                                step="0.01"
                                min="0"
                                required>
                        </td>

                        <td>
                            <input type="number"
                                class="form-control item-qty"
                                data-index="${index}"
                                value="${item.quantity}"
                                min="1"
                                max="${stock}"
                                required>
                        </td>

                        <td>${stockDisplay}</td>
                        
                        <td>
                            <select class="form-select item-replacement"
                                data-index="${index}">
                                <option value="0" ${item.replacement === 0 ? 'selected' : ''}>No</option>
                                <option value="1" ${item.replacement === 1 ? 'selected' : ''}>Yes</option>
                            </select>
                        </td>

                        <td>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-item"
                                data-index="${index}">
                                Remove
                            </button>
                        </td>
                    </tr>
                `);
            });

            // Update item count and submit button state
            $('#item-count').text(issuanceData.items.length);
            $('#submit-item-count').text(issuanceData.items.length);
            $('#submit-issuance').prop('disabled', issuanceData.items.length === 0);
        }


        // Remove item from the issuanceData.items array (FIXED: uses data-index)
        $(document).on('click', '.remove-item', function() {
            const index = $(this).data('index');
            issuanceData.items.splice(index, 1);
            renderSelectedItems(); // Re-render to update indexes and table rows
        });

        // Update quantity in issuanceData.items array (FIXED: uses data-index)
        $(document).on('change', '.item-qty', function() {
            const index = $(this).data('index');
            const maxQty = parseInt($(this).attr('max'));
            let newQty = parseInt($(this).val());
            
            if (isNaN(newQty) || newQty < 1) {
                newQty = 1;
            }
            if (maxQty > 0 && newQty > maxQty) { // Prevent issuing more than stock
                 alert(`Cannot issue ${newQty}. Only ${maxQty} in stock.`);
                 newQty = maxQty; 
            }

            $(this).val(newQty); // Update input field
            issuanceData.items[index].quantity = newQty;
        });

        // Update price in issuanceData.items array (FIXED: uses data-index)
        $(document).on('change', '.item-price', function() {
            const index = $(this).data('index');
            let newPrice = parseFloat($(this).val());
            
            if (isNaN(newPrice) || newPrice < 0) {
                newPrice = 0;
            }
            
            $(this).val(newPrice.toFixed(2)); // Update input field
            issuanceData.items[index].price = newPrice.toFixed(2);
        });

        // Update replacement status in issuanceData.items array (NEW HANDLER)
        $(document).on('change', '.item-replacement', function() {
            const index = $(this).data('index');
            const replacementValue = parseInt($(this).val());
            issuanceData.items[index].replacement = replacementValue;
        });


        // Search Item Logic
        $('#item-search').on('keyup', function() {
            const input = $(this).val();
            if (input.length > 2) {
                // AJAX call to search_item.php (assumed to return HTML with .btn-add-item)
                $.post('search_item.php', { input: input }, function(data) {
                    $('#item-search-results').html(data);
                }).fail(function() {
                    $('#item-search-results').html('<p class="text-danger">Error searching for items.</p>');
                });
            } else {
                $('#item-search-results').html('<p class="text-muted">Start typing at least 3 characters to search for items.</p>');
            }
        });

        // Delegate click handler for 'Add' button (dynamically loaded)
        $(document).on('click', '.btn-add-item', function(e) {
            e.preventDefault();
            const i_id = $(this).data('iid');
            const i_name = $(this).data('iname');
            const i_unit = $(this).data('iunit');
            const i_size = $(this).data('isize');
            const i_price = parseFloat($(this).data('iprice')) || 0; // Use 0 if not set
            const i_code = $(this).data('icode');
            const i_stock = parseInt($(this).data('istock')) || 0;

            if (i_stock <= 0) {
                alert(`Cannot add ${i_name}. Item is out of stock (0 available).`);
                return;
            }

            // Check if item is already added
            const existingItem = issuanceData.items.find(item => item.item_id === i_id);

            if (existingItem) {
                if (existingItem.quantity < i_stock) {
                    existingItem.quantity++; // Increment quantity if it exists and there's stock
                    alert(`Quantity for ${i_name} incremented!`);
                } else {
                    alert(`Maximum stock (${i_stock}) reached for ${i_name}.`);
                }
            } else {
                // Add new item
                issuanceData.items.push({
                    item_id: i_id,
                    item_name: i_name,
                    item_unit: i_unit,
                    item_size: i_size,
                    item_code: i_code,
                    price: i_price.toFixed(2), 
                    stock: i_stock,
                    quantity: 1,
                    replacement: 0 // Default replacement to 'No'
                });
            }

            renderSelectedItems();
        });

        $('#prev-step-2').on('click', function() {
            showStep(2);
        });

        // --- Final Submission Logic ---
        $('#issuance-form').on('submit', function(e) {
            
            // 1. Gather the latest data from the input fields in the table
            let finalItems = [];
            let isValid = true;
            let firstInvalidInput = null;

            $('#selected-items-table tbody tr').each(function() {
                const index = $(this).data('index'); // Use the stored index
                const qtyInput = $(this).find('.item-qty').val();
                const priceInput = $(this).find('.item-price').val();
                const replacement = $(this).find('.item-replacement').val();
                
                const quantity = parseInt(qtyInput);
                const price = parseFloat(priceInput);

                // --- Validation ---
                if (isNaN(quantity) || quantity <= 0) {
                    isValid = false;
                    alert('Please ensure all item quantities are valid (greater than 0).');
                    firstInvalidInput = $(this).find('.item-qty');
                    return false; // Break out of the .each() loop
                }
                if (isNaN(price) || price < 0) {
                    isValid = false;
                    alert('Please ensure all item prices are valid (not negative).');
                    firstInvalidInput = $(this).find('.item-price');
                    return false;
                }
                // Check if quantity exceeds stock (optional, but good practice)
                const stock = parseInt(issuanceData.items[index].stock);
                if (quantity > stock) {
                    isValid = false;
                    alert(`Error: Quantity for ${issuanceData.items[index].item_name} (${quantity}) exceeds available stock (${stock}).`);
                    firstInvalidInput = $(this).find('.item-qty');
                    return false;
                }
                // --- End Validation ---

                // Copy the base item details and update quantity/price
                const baseItem = issuanceData.items[index];
                finalItems.push({
                    item_id: baseItem.item_id,
                    quantity: quantity,
                    price: price.toFixed(2),
                    replacement: parseInt(replacement) // Ensure replacement is an integer (0 or 1)
                });
            });

            if (!isValid) {
                e.preventDefault(); // Stop form submission
                if (firstInvalidInput) {
                    firstInvalidInput.focus(); // Focus on the problematic input
                }
                return; 
            }
            
            if (finalItems.length === 0) {
                e.preventDefault();
                alert('Please add at least one item before submitting.');
                return;
            }

            // 2. Build the final submission object
            const finalIssuanceObject = {
                employee_id: issuanceData.employee_id,
                invoice_number: issuanceData.invoice_number,
                items: finalItems
            };

            // 3. Set the hidden input value
            document.getElementById('issuance-data').value = JSON.stringify(finalIssuanceObject);
            
            // Optional: Display the final object for inspection before submission
            // $('#final-data-json').text(JSON.stringify(finalIssuanceObject, null, 4));
            // $('#final-data-output').show();
            // You may still want to prevent the default submit if you plan to use AJAX instead of a full form post.
            // If using AJAX, you would add e.preventDefault() here and use $.post().
            // Otherwise, letting the form submit normally will send data to submit_issuance.php
        });

        // Initialize the view
        showStep(1); 
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- </body> -->
<!-- </html> -->

<?php
include '../template/footer.php';
?>