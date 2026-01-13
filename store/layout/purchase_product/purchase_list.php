<?php
ob_start(); 

include '../template/header.php';

/* ===================== PAGINATION ===================== */
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// --- 1. Date Range Logic (Kept existing logic) ---
// Calculate default dates
$defaultEndDate = date('Y-m-d');
$defaultStartDate = date('Y-m-d', strtotime('-15 days'));

// Check if form was submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['view'])) {
    $startDate = $_POST['startDate'] ?? $defaultStartDate;
    $endDate = $_POST['endDate'] ?? $defaultEndDate;

    $extra_url = 'store/layout/purchase_product/purchase_list.php';
    $base_url = $_SESSION['base_url'] ?? '';

    // Build query parameters safely
    $query_params = [
        'page' => $_GET['page'] ?? 1,
        'section' => $_GET['section'] ?? '',
        'startDate' => $startDate,
        'endDate' => $endDate,
        'p_po_no' => $_POST['p_po_no'] ?? ''
    ];

    // Include DataTables state if captured
    if (isset($_POST['page_number'])) {
        $query_params['page_number'] = $_POST['page_number'];
    }
    if (isset($_POST['search_term'])) {
        $query_params['search_term'] = $_POST['search_term'];
    }

    $query = http_build_query($query_params);

    header("Location: http://$base_url/$extra_url?$query");
    exit(); // Always exit after a header redirect!
} elseif (isset($_GET['startDate']) && isset($_GET['endDate'])) {
    // Use dates from session if available
    $startDate = $_GET['startDate'];
    $endDate = $_GET['endDate'];
} else {
    // Use default dates
    $startDate = $defaultStartDate;
    $endDate = $defaultEndDate;
}

?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Assuming you are using jQuery DataTables, this function needs to be run 
    // after DataTables is initialized (usually in footer.php or elsewhere).
    $(document).ready(function() {
        $("#collapseLayouts2").addClass("show");
        $("#collapseLayouts2_list").addClass("active bg-success text-white");

        // FIX 1: Capture DataTables state before submitting View form
        $('form.view-form, form.filter-form').submit(function() {
            try {
                // Get the DataTables API object (Assuming it's initialized via $('#datatablesSimple').DataTable())
                const dataTableApi = $('#datatablesSimple').DataTable();
                
                // Get current page (DataTables is 0-indexed, we pass 1-indexed to PHP)
                const currentPage = dataTableApi.page.info().page + 1; 
                const searchTerm = dataTableApi.search();
                
                // Append the current page and search term to the submission form
                $(this).append('<input hidden type="number" name="page_number" value="' + currentPage + '">');
                $(this).append('<input hidden type="text" name="search_term" value="' + searchTerm + '">');
            } catch (e) {
                console.warn("DataTables API not available to capture state.", e);
            }
            return true;
        });

        // FIX 2: Restore DataTables state from URL parameters on page load
        const urlParams = new URLSearchParams(window.location.search);
        const pageNumber = urlParams.get('page_number');
        const searchTerm = urlParams.get('search_term');
        
        if (pageNumber || searchTerm) {
            // We use a small delay to ensure DataTables is fully loaded/rendered
            setTimeout(() => {
                try {
                    const dataTableApi = $('#datatablesSimple').DataTable(); // Re-get the DataTables instance
                    
                    if (searchTerm) {
                        dataTableApi.search(searchTerm).draw(false);
                    }
    
                    if (pageNumber && parseInt(pageNumber) > 0) {
                        // Subtract 1 because DataTables is 0-indexed
                        dataTableApi.page(parseInt(pageNumber) - 1).draw(false);
                    }
                    
                } catch (e) {
                    console.error("Failed to restore DataTables state:", e);
                }
            }, 100); // 100ms delay should be safe for most environments
        }
    });
</script>

<style>
    /* Production-grade small text for table readability */
    th, td {
        font-size: 13px !important;
        vertical-align: middle;
    }
    .card-header-view {
        background: #f8f9fa; /* Light background for the view details section */
        border-bottom: 1px solid #e9ecef;
    }
    .detail-row b {
        font-weight: 600;
        color: #007bff; /* Primary color for details */
    }
    .detail-item {
        margin-right: 20px;
        display: inline-block;
    }
</style>


<div class="container-fluid px-4">

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-calendar-alt me-1"></i>
            Date Range Filter
        </div>
        <div class="card-body">
          
              <form method="POST" action="" class="filter-form">
                <div class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label for="startDate" class="form-label fw-bold">Start Date (Request Date)</label>
                        <input type="date" class="form-control" id="startDate" name="startDate" value="<?php echo htmlspecialchars($startDate); ?>" required>
                    </div>
                    <div class="col-md-3">
                        <label for="endDate" class="form-label fw-bold">End Date (Request Date)</label>
                        <input type="date" class="form-control" id="endDate" name="endDate" value="<?php echo htmlspecialchars($endDate); ?>" required>
                    </div>

                    <!-- // p_po_no -->
                    <div class="col-md-3">
                        <label for="p_po_no" class="form-label fw-bold">PO No.</label>
                        <input type="text" class="form-control" id="p_po_no" name="p_po_no" value="<?php echo htmlspecialchars($_GET['p_po_no'] ?? ''); ?>">
                    </div>


                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-search me-1"></i> Search
                        </button>
                    </div>
                </div>
              </form>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header card-header-view">
            <?php
                // Check if 'view' was posted either directly, or via redirection query parameters (if you implement that)
                if (isset($_GET['view']) && $_GET['view'] == 'true') {

                    // PHP logic for fetching item name for header view
                    $p_id   = $_GET['p_id'] ;// FIXED variable name

                    $item_name = 'N/A'; // Default value

                    try {
                        $sqlpo = "
                            SELECT 
                            ip.p_id,
                                ip.p_po_no,
                                i.i_name,
                                i.i_manufactured_by,
                                c.c_name,
                                ip.p_unit_price,
                                ip.p_req_qty,
                                ip.p_request_datetime,
                                s.s_name,
                                ip.p_purchase_by,
                                ip.p_request_accept_by,
                                ip.p_request
                            FROM item i
                            INNER JOIN item_purchase ip ON ip.i_id = i.i_id
                            INNER JOIN category_item c ON i.c_id = c.c_id
                            INNER JOIN supplier s ON ip.s_id = s.s_id
                            WHERE ip.p_id = :p_id
                        ";

                        $stmt = $conn->prepare($sqlpo);
                        $stmt->bindParam(':p_id', $p_id, PDO::PARAM_INT);

                        if ($stmt->execute()) {
                            $purchase_data = $stmt->fetch(PDO::FETCH_ASSOC);
                        }

                        if (!$purchase_data) {
                            echo "<div class='alert alert-danger'>Purchase order not found.</div>";
                            
                        }
                    } catch (PDOException $e) {
                        // Error handling
                         echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
                    }
                    
                    // Display Purchase Details (Improved UI/UX)
                    ?>
                    <h5 class="mb-3 text-dark fw-bold"><i class="fas fa-eye me-2 text-primary"></i> Purchase Order Details (PO: <?php echo htmlspecialchars($purchase_data['p_po_no']); ?>)</h5>
                    <p>purchase id : <?php echo $purchase_data['p_id']; ?></p>
                    <div class="row detail-row mb-3">
                        <div class="col-lg-3 detail-item"><b>Item:</b> <?php echo htmlspecialchars($purchase_data['i_name']); ?></div>
                        <div class="col-lg-2 detail-item"><b>Brand:</b> <?php echo htmlspecialchars($purchase_data['i_manufactured_by']); ?></div>
                        <div class="col-lg-2 detail-item"><b>Category:</b> <?php echo htmlspecialchars($purchase_data['c_name']); ?></div>
                        <div class="col-lg-2 detail-item"><b>Unit Price:</b> $<?php echo number_format($purchase_data['p_unit_price'], 2); ?></div>
                        <div class="col-lg-2 detail-item"><b>Requested Qty:</b> <?php echo htmlspecialchars($purchase_data['p_req_qty']); ?></div>
                    </div>

                    <div class="row detail-row mb-3">
                        <div class="col-lg-3 detail-item"><b>Request Date:</b> <?php echo htmlspecialchars($purchase_data['p_request_datetime']); ?></div>
                        <div class="col-lg-2 detail-item"><b>Supplier:</b> <?php echo htmlspecialchars($purchase_data['s_name']); ?></div>
                        <div class="col-lg-2 detail-item"><b>Purchased By:</b> <?php echo htmlspecialchars($purchase_data['p_purchase_by']); ?></div>
                        <div class="col-lg-2 detail-item"><b>Accepted By:</b> <?php echo htmlspecialchars($purchase_data['p_request_accept_by'] ?? 'N/A'); ?></div>
                    </div>

                    <hr class="my-3">

                    <h6 class="mb-2 text-dark fw-bold"><i class="fas fa-boxes me-2 text-success"></i> Received Inventory</h6>
                    
                    <?php
                    
                    $sql_receive = "
                                SELECT * 
                                FROM tem_purchase_recive 
                                WHERE p_id = :p_id 
                                ORDER BY p_recive_datetime DESC
                            ";
                    $stmt = $conn->prepare($sql_receive);
                    $stmt->bindParam(':p_id', $p_id, PDO::PARAM_INT);

                    if ($stmt->execute()) {
                        $receive_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if (count($receive_products) > 0) {
                            echo '<table class="table table-bordered table-sm mt-3">';
                            echo '<thead class="table-light"><tr>
                                <th>No</th>
                                <th>Quantity</th>
                                <th>Receive Date</th>
                                <th>Expiry Date</th>
                                <th>Payment Type</th>
                                <th>Received By</th>
                            </tr></thead><tbody>';

                            $count = 0;
                            foreach ($receive_products as $receive_product) {
                                $count++;
                                $payment_type = ($receive_product['cash1_creadit0'] == '1') ? 'Cash' : 'Credit';
                                echo '<tr>
                                    <td>' . $count . '</td>
                                    <td>' . htmlspecialchars($receive_product['p_recive_qty']) . '</td>
                                    <td>' . htmlspecialchars($receive_product['p_recive_datetime']) . '</td>
                                    <td>' . htmlspecialchars($receive_product['p_expaired_datetime'] ?? 'N/A') . '</td>
                                    <td>' . $payment_type . '</td>
                                    <td>' . htmlspecialchars($receive_product['p_recive_by']) . '</td>
                                </tr>';
                            }
                            echo '</tbody></table>';
                        } else {
                            echo "<div class='alert alert-info py-2'>No partial receipts recorded yet.</div>";
                        }
                    }

                    // Remaining Qty Summary (Improved UI/UX)
                    $total_received = array_sum(array_column($receive_products, 'p_recive_qty'));
                    $remaining_qty  = $purchase_data['p_req_qty'] - $total_received;

                    ?>
                    <div class="row mt-4">
                        <div class="col-md-4">
                            <div class="alert alert-info py-2 text-center">
                                <b>Received Quantity:</b> <?php echo htmlspecialchars($total_received); ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert <?php echo $remaining_qty > 0 ? 'alert-warning' : 'alert-success'; ?> py-2 text-center">
                                <b>Remaining Quantity:</b> <?php echo $remaining_qty; ?>
                            </div>
                        </div>
                    </div>


                    <?php
                    ////////////////////////////////////////////////////////////////////////
                    // Receive Form Logic (Improved UI/UX)
                    if($purchase_data['p_request']=='1' && $remaining_qty > 0){
                        ?>
                        <hr class="my-4">
                        <h6 class="mb-3 text-dark fw-bold"><i class="fas fa-truck-loading me-2 text-success"></i> Record New Delivery</h6>
                       
                        <form action="purchase_list_process_recive.php" method="post" class="needs-validation" >

    <div class="row g-3">
        <!-- Receive Quantity -->
        <div class="col-md-3">
            <label for="receive_qty" class="form-label">Receive Quantity</label>
            <input
                name="receive_qty"
                id="receive_qty"
                type="number"
                class="form-control"
                placeholder="Quantity"
                min="1"
                max="<?= (int)$remaining_qty ?>"
                required
            >
            <div class="invalid-feedback">
                Please enter a valid quantity (1–<?= (int)$remaining_qty ?>).
            </div>
        </div>

        <!-- Expiry Date -->
        <div class="col-md-3">
            <label for="expired_datetime" class="form-label">Expiry Date (Optional)</label>
            <input
                name="expired_datetime"
                id="expired_datetime"
                type="date"
                class="form-control"
            >
        </div>

        <!-- Payment Status -->
        <div class="col-md-3">
            <label for="cash" class="form-label">Payment Status</label>
            <select name="cash" id="cash" class="form-select" required>
                <option value="1">Cash</option>
                <option value="0">Credit</option>
            </select>
        </div>
        <!-- Required -->
    <input type="hidden" name="p_id" value="<?= (int)$_GET['p_id'] ?>">

    <?php if (isset($_GET['page'], $_GET['section'], $_GET['startDate'], $_GET['endDate'])): ?>
        <input type="hidden" name="page" value="<?= (int)$_GET['page'] ?>">
        <input type="hidden" name="section" value="<?= htmlspecialchars($_GET['section']) ?>">
        <input type="hidden" name="startDate" value="<?= htmlspecialchars($_GET['startDate']) ?>">
        <input type="hidden" name="endDate" value="<?= htmlspecialchars($_GET['endDate']) ?>">
    <?php endif; ?>

        <!-- Submit -->
        <div class="col-md-3 d-flex align-items-end">
            <button id="submitButton" type="submit" class="btn btn-success w-100" >
                <i class="fas fa-check me-1"></i> Submit Receipt
            </button>
        </div>
    </div>

    

    

    <div id="message" class="alert alert-danger mt-3 d-none"></div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    validate();
    const inputNumber = document.getElementById('receive_qty');
    const submitButton = document.getElementById('submitButton');
    const message = document.getElementById('message');
    const maxQty = <?= (int)$remaining_qty ?>;

    function validate() {
        const value = parseInt(inputNumber.value, 10);

        if (!Number.isInteger(value) || value < 1 || value > maxQty) {
            submitButton.disabled = true;
            message.classList.remove('d-none');
            message.textContent = `Input must be between 1 and ${maxQty}.`;
            inputNumber.classList.add('is-invalid');
            inputNumber.classList.remove('is-valid');
        } else {
            submitButton.disabled = false;
            message.classList.add('d-none');
            message.textContent = '';
            inputNumber.classList.remove('is-invalid');
            inputNumber.classList.add('is-valid');
        }
    }

    inputNumber.addEventListener('input', validate);
});
</script>


                        <?php
                    } elseif ($purchase_data['p_request']=='1' && $remaining_qty <= 0) {
                        echo "<div class='alert alert-success py-2 mt-3'>This purchase order has been **Fully Received**.</div>";
                    } else {
                        echo "<div class='alert alert-info py-2 mt-3'>This request must be **Accepted** before inventory can be received.</div>";
                    }

                } // end if (view true)
            ?>
        </div>


        <div class="card-body">
            <h5 class="mb-3 text-dark fw-bold"><i class="fas fa-list me-2 text-secondary"></i> Purchase Order Requests |  <?php echo $startDate; ?> to <?php echo $endDate; ?></h5>
            <table id="datatablesSimple" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Po No</th>
                        <th>Item Name</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th>Unit Price</th>
                        <th>Req/Recv Qty</th>
                        <th>Total Price</th>
                        <th>Request Date</th>
                        <th>Status</th>
                        <th>Action</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tfoot>
                    <tr>
                        <th>Po No</th>
                        <th>Item Name</th>
                        <th>Brand</th>
                        <th>Category</th>
                        <th>Unit Price</th>
                        <th>Req/Recv Qty</th>
                        <th>Total Price</th>
                        <th>Request Date</th>
                        <th>Status</th>
                        <th>Action</th>
                        <th>Details</th>
                    </tr>
                </tfoot>

                <tbody>
                    <?php
// Retrieve user input, using defaults if not provided in GET
$section   = $_SESSION['section'] ?? '';

// Pagination variables
$page     = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$pageSize = 10;
$offset   = ($page - 1) * $pageSize;

// --- 2. Construct SQL Query ---

$sql = "SELECT 
            COALESCE(r.total_recive, 0) AS total_recive,
            ip.p_req_qty,
            ip.p_id,
            ip.p_po_no,
            i.i_name,
            c.c_name,
            i.i_id,
            i.i_unit,
            i.i_size,
            i.i_manufactured_by,
            ip.p_unit_price,
            ip.p_unit_price * ip.p_req_qty AS total_price,
            ip.p_profit,
            ip.p_request_datetime,
            s.s_name,
            ip.p_purchase_by,
            ip.p_request,
            ip.p_recive,
            ip.p_request_accept_by
        FROM item_purchase ip
        INNER JOIN item i 
            ON ip.i_id = i.i_id
        INNER JOIN supplier s 
            ON ip.s_id = s.s_id
        INNER JOIN category_item c 
            ON i.c_id = c.c_id
        LEFT JOIN (
            SELECT p_id, SUM(p_recive_qty) AS total_recive
            FROM tem_purchase_recive -- CHECK THIS TABLE NAME
            GROUP BY p_id
        ) r ON ip.p_id = r.p_id
        WHERE i.section = :section
        AND CAST(ip.p_request_datetime AS DATE) BETWEEN :startDate AND :endDate";
        
// --- 3. Append Conditional WHERE Clause ---

if (!empty($_GET['p_po_no'])) {
    $sql .= " AND ip.p_po_no LIKE :p_po_no";
}

// --- 4. Append Pagination and Ordering (T-SQL syntax) ---

$sql .= " ORDER BY ip.p_request_datetime DESC OFFSET :offset ROWS FETCH NEXT :limit ROWS ONLY;";
// NOTE: If using MySQL/PostgreSQL, change the line above to:
// $sql .= " ORDER BY ip.p_request_datetime DESC LIMIT :limit OFFSET :offset;";


// --- 5. Prepare and Bind Parameters ---

$stmt = $conn->prepare($sql);

$stmt->bindParam(':section', $section, PDO::PARAM_STR);
$stmt->bindParam(':startDate', $startDate, PDO::PARAM_STR);
$stmt->bindParam(':endDate', $endDate, PDO::PARAM_STR);

if(!empty($_GET['p_po_no'])) {
    $searchPoNo = '%' . $_GET['p_po_no'] . '%';
    $stmt->bindParam(':p_po_no', $searchPoNo, PDO::PARAM_STR);
}

// Bind pagination parameters
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);

// --- 6. Execution (Example) ---
// $stmt->execute();
// $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ... rest of your code to execute and display results

                    

                    if ($stmt->execute()) {
                        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($products as $product) {
                            $total_recive = $product['total_recive'];
                            $p_req_qty = $product['p_req_qty'];
                            $p_request = $product['p_request'];
                            $p_recive = $product['p_recive'];

                            // Determine Status Badge
                            $status_badge = '';
                            $action_button = '';

                            if ($total_recive == $p_req_qty) {
                                $status_badge = '<span class="badge bg-success">Completed</span>';
                            } elseif ($p_request == '1') {
                                if ($total_recive > 0) {
                                    $status_badge = '<span class="badge bg-primary">Partial Receive</span>';
                                } else {
                                    $status_badge = '<span class="badge bg-info">Accepted</span>';
                                }
                            } else {
                                $status_badge = '<span class="badge bg-warning text-dark">Pending Request</span>';
                            }
                            
                            // Build query params for action links
                            $query_params = [
                                'page' => $page,
                                'section' => $section,
                                'startDate' => $startDate,
                                'endDate' => $endDate,
                                'p_po_no' => $_GET['p_po_no'] ?? '',
                                'page_number' => $_GET['page_number'] ?? '',
                                'search_term' => $_GET['search_term'] ?? ''
                            ];
                            $query_string = http_build_query($query_params);

                            // Determine Action Button (Accept/Unaccept/Delete)
                            if ($total_recive == $p_req_qty) {
                                $action_button = '<span class="text-success">N/A</span>';
                            } elseif ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'super_admin' || $_SESSION['role'] == 'group_admin') {
                                if ($p_request == '0') {

                                    // Action button with query params
                                    $action_button = '<a href="purchase_list_process_request.php?p_id=' . $product['p_id'] 
                                        . ($query_string ? '&' . $query_string : '') 
                                        . '" class="btn btn-success btn-sm me-1" title="Accept Request"><i class="fas fa-check"></i> Accept</a>';
                                } else {
                                    // Unaccept (only if not fully received)
                                    if ($total_recive == 0) {
                                        $action_button = '<a href="purchase_list_process_request_unaccept.php?p_id=' . $product['p_id'] .($query_string ? '&' . $query_string : ''). '" class="btn btn-warning btn-sm me-1" title="Unaccept Request"><i class="fas fa-times"></i> Unaccept</a>';
                                    } else {
                                        $action_button = '<span class="text-primary">Recv in Progress</span>';
                                    }
                                }
                            }
                            
                            // Delete/Return Button
                            if ($p_request == '0' && $p_recive == '0') {
                                $action_button .= ' <a href="purchase_list_process_delete.php?p_id=' . $product['p_id'] . ($query_string ? '&' . $query_string : '') . '" class="btn btn-danger btn-sm" title="Delete Request"><i class="fas fa-trash"></i> Delete</a>';
                            } elseif ($p_recive != '0' && $p_request != '0') {
                                $action_button .= ' <a href="purchase_list_process_delete_return_step1.php?p_id=' . $product['p_id'] . ($query_string ? '&' . $query_string : '') . '" class="btn btn-secondary btn-sm" title="Initiate Return"><i class="fas fa-undo"></i> Return</a>';
                            }


                            echo "<tr>";
                            echo "<td>" . htmlspecialchars($product['p_po_no']) . "</td>";
                            echo "<td>" . htmlspecialchars($product['i_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($product['i_manufactured_by']) . "</td>";
                            echo "<td>" . htmlspecialchars($product['c_name']) . "</td>";
                            echo "<td>" . number_format($product['p_unit_price'], 2) . "</td>";
                            echo "<td><b>" . $p_req_qty . "</b> / " . $total_recive . "</td>";
                            echo "<td>$" . number_format($product['total_price'], 2) . "</td>";
                            echo "<td>" . date('Y-m-d', strtotime($product['p_request_datetime'])) . "</td>"; // Display only date
                            echo "<td>" . $status_badge . "</td>";
                            echo "<td>" . $action_button . "</td>";

                            // Details/Select button (Form for post-back view)
echo '<td>
<form method="get" action="" style="display:inline;" class="view-form">
    <input type="hidden" name="view" value="true">
    <input type="hidden" name="p_id" value="' . htmlspecialchars($product['p_id'] ?? '', ENT_QUOTES, 'UTF-8') . '">
    <button type="submit" class="btn btn-primary btn-sm">
        <i class="fas fa-eye"></i> View
    </button>
</form>
</td>';
echo "</tr>";
                        }
                    } else {
                        // Error executing query
                        echo "<tr><td colspan='11'><div class='alert alert-danger'>Error fetching data.</div></td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php
    $countSql = "
    SELECT COUNT(*) 
    FROM item_purchase ip 
    INNER JOIN item i ON ip.i_id = i.i_id
    WHERE i.section = :section
    AND CAST(ip.p_request_datetime AS DATE) BETWEEN :startDate AND :endDate
    ";
    
    if (!empty($_GET['p_po_no'])) {
        $countSql .= " AND ip.p_po_no LIKE :p_po_no";
    }

    $countStmt = $conn->prepare($countSql);
    $countStmt->bindParam(':section', $section);
    $countStmt->bindParam(':startDate', $startDate);
    $countStmt->bindParam(':endDate', $endDate);

    if (!empty($_GET['p_po_no'])) {
        $searchPoNo = '%' . $_GET['p_po_no'] . '%';
        $countStmt->bindParam(':p_po_no', $searchPoNo);
    }

    $countStmt->execute();
    
    $totalRecords = $countStmt->fetchColumn();
    $totalPages   = ceil($totalRecords / $pageSize);
    
    ?>
















<div style="margin:20px 0; text-align:center;">

<?php
$range = 2; // how many pages before & after current
$start = max(1, $page - $range);
$end   = min($totalPages, $page + $range);
?>

<!-- Previous -->
<?php if ($page > 1): ?>
    <a href="?page=<?= $page-1 ?>&section=<?= $section ?>&startDate=<?= $startDate ?>&endDate=<?= $endDate ?>&p_po_no=<?= urlencode($_GET['p_po_no'] ?? '') ?>&page_number=<?= $_GET['page_number'] ?? '' ?>&search_term=<?= urlencode($_GET['search_term'] ?? '') ?>"
       style="padding:6px 12px;margin:2px;text-decoration:none;border:1px solid #ccc;border-radius:4px;">
       ⬅ Prev
    </a>
<?php endif; ?>

<!-- First Page -->
<?php if ($start > 1): ?>
    <a href="?page=1&section=<?= $section ?>&startDate=<?= $startDate ?>&endDate=<?= $endDate ?>&p_po_no=<?= urlencode($_GET['p_po_no'] ?? '') ?>&page_number=<?= $_GET['page_number'] ?? '' ?>&search_term=<?= urlencode($_GET['search_term'] ?? '') ?>"
       style="padding:6px 12px;margin:2px;border:1px solid #ccc;border-radius:4px;text-decoration:none;">
       1
    </a>
    <span style="margin:0 5px;">...</span>
<?php endif; ?>

<!-- Page Numbers -->
<?php for ($i = $start; $i <= $end; $i++): ?>
    <a href="?page=<?= $i ?>&section=<?= $section ?>&startDate=<?= $startDate ?>&endDate=<?= $endDate ?>&p_po_no=<?= urlencode($_GET['p_po_no'] ?? '') ?>&page_number=<?= $_GET['page_number'] ?? '' ?>&search_term=<?= urlencode($_GET['search_term'] ?? '') ?>"
       style="
            padding:6px 12px;
            margin:2px;
            text-decoration:none;
            border:1px solid <?= $i == $page ? '#007bff' : '#ccc' ?>;
            border-radius:4px;
            background:<?= $i == $page ? '#007bff' : '#fff' ?>;
            color:<?= $i == $page ? '#fff' : '#333' ?>;
            font-weight:<?= $i == $page ? 'bold' : 'normal' ?>;
       ">
       <?= $i ?>
    </a>
<?php endfor; ?>

<!-- Last Page -->
<?php if ($end < $totalPages): ?>
    <span style="margin:0 5px;">...</span>
    <a href="?page=<?= $totalPages ?>&section=<?= $section ?>&startDate=<?= $startDate ?>&endDate=<?= $endDate ?>&p_po_no=<?= urlencode($_GET['p_po_no'] ?? '') ?>&page_number=<?= $_GET['page_number'] ?? '' ?>&search_term=<?= urlencode($_GET['search_term'] ?? '') ?>"
       style="padding:6px 12px;margin:2px;border:1px solid #ccc;border-radius:4px;text-decoration:none;">
       <?= $totalPages ?>
    </a>
<?php endif; ?>

<!-- Next -->
<?php if ($page < $totalPages): ?>
    <a href="?page=<?= $page+1 ?>&section=<?= $section ?>&startDate=<?= $startDate ?>&endDate=<?= $endDate ?>&p_po_no=<?= urlencode($_GET['p_po_no'] ?? '') ?>&page_number=<?= $_GET['page_number'] ?? '' ?>&search_term=<?= urlencode($_GET['search_term'] ?? '') ?>"
       style="padding:6px 12px;margin:2px;text-decoration:none;border:1px solid #ccc;border-radius:4px;">
       Next ➡
    </a>
<?php endif; ?>

</div>




    <?php
    include '../template/footer.php';
    ?>
    </div>

<?php
    ob_end_flush(); // Send buffered output
?>