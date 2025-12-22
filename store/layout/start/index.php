
<?php
include '../template/header.php';
?>

<?php
// ----------------------------------------------------
// 4. Helper Function
// ----------------------------------------------------
$db = new Database();

// Function to safely fetch a counter value using the Database object
function get_count(Database $db, string $sql): int {
    // Handle potential null return from fetchSingleColumn if connection/query fails 
    $value = $db->fetchSingleColumn($sql);
    return (int)($value ?? 0); 
}

// --- Data Fetching (Counters) ---
$total_employees = get_count($db, "SELECT COUNT(e_name) FROM employee");

$sql_uncomplete_receive = "
    SELECT COUNT(ip.p_id)
    FROM item_purchase ip
    INNER JOIN (
        SELECT p_id, SUM(p_recive_qty) AS total_received_qty
        FROM tem_purchase_recive
        GROUP BY p_id
    ) AS tpr ON ip.p_id = tpr.p_id
    WHERE ip.p_req_qty > tpr.total_received_qty
";
$uncomplete_receive_count = get_count($db, $sql_uncomplete_receive);

$sql_unreceived_items = "
    SELECT COUNT(ip.p_id)
    FROM item_purchase ip                                
    WHERE ip.p_request = 1 AND ip.p_recive = 0
";
$unreceived_items_count = get_count($db, $sql_unreceived_items);

$sql_stock_out_items = "
    SELECT COUNT(i.i_id)
    FROM item i 
    LEFT JOIN balance b ON i.i_id = b.i_id             
    WHERE b.qty_balance < i.stock_out_reminder_qty
";
$stock_out_items_count = get_count($db, $sql_stock_out_items);

$sql_unaccept_items = "
    SELECT COUNT(ip.p_id)
    FROM item_purchase ip                                
    WHERE ip.p_request = 0
";
$unaccept_items_count = get_count($db, $sql_unaccept_items);


// --- Data Fetching (Main Table) ---
$table_data = [];
$section = $_SESSION['section'] ?? null; 

$base_query = "
    SELECT 
        b.c_name, b.i_name, b.total_item_purchase, b.total_item_issue, 
        b.total_item_purchase_price, b.total_item_issue_price, 
        b.qty_balance, b.item_issue_avg_price
    FROM balance b
    INNER JOIN item i ON i.i_id = b.i_id 
    INNER JOIN item_purchase ip ON ip.i_id = i.i_id
    WHERE i.section = ?
    ORDER BY ip.p_request_accept_datetime DESC
";

if (isset($_SESSION['table']) && $_SESSION['table'] === 'short') {
    $query = "SELECT TOP(50) T.* FROM (" . $base_query . ") AS T";
} elseif (isset($_SESSION['table']) && $_SESSION['table'] === 'all') {
    $query = $base_query;
} else {
    $query = null; 
}

$pdo = $db->getConnection(); 

if ($query && $section && $pdo) {
    try {
        $stmt = $pdo->prepare($query);
        $stmt->execute([$section]);
        $table_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Table Data Query Error: " . $e->getMessage());
        echo "<p class='alert alert-danger'>Could not load table data due to a database error. Please check the logs.</p>";
    }
} elseif (!$pdo) {
    // The Database class already outputted an error, but a final check here is fine.
    // The counters would already be 0.
}


// ----------------------------------------------------
// 5. HTML Output Starts
// ----------------------------------------------------
?>



<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<?php echo "total employee is: " . $total_employees; ?>


        <div class="container-fluid px-4">
            <h1 class="mt-4">Dashboard</h1>
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item active"><marquee> 
                Established in 1992, we have been providing quality fashion lingerie and swimwear to major global retailers for over two decades.
                We take pride in our excellence in offering reliable services from product design to manufacturing and in achieving the best quality 
                at a competitive price. Today, Hop Lun employs more than 23,000 people. We have 12 manufacturing 
                facilities located across 3 countries together with a centralized pre-production office and logistics centre.
                </marquee></li>
            </ol>

            <div class="row">
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-primary text-white mb-4">
                        <div class="card-body">Total Uncomplete Receive Items:</div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a class="small text-white stretched-link" href="#">
                                <?php echo $uncomplete_receive_count; ?>
                            </a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-warning text-white mb-4">
                        <div class="card-body">Total Unreceived Items:</div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a class="small text-white stretched-link" href="#">
                                <?php echo $unreceived_items_count; ?>
                            </a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-success text-white mb-4">
                        <div class="card-body">Total Stock Out Items</div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a class="small text-white stretched-link" href="#">
                                <?php echo $stock_out_items_count; ?>
                            </a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card bg-danger text-white mb-4">
                        <div class="card-body">Total Unaccept Item : </div>
                        <div class="card-footer d-flex align-items-center justify-content-between">
                            <a class="small text-white stretched-link" href="#">
                                <?php echo $unaccept_items_count; ?>
                            </a>
                            <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <style>
                th{ font-size: 12px; }
                td{ font-size: 10px; }
            </style>

            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-table me-1"></i>
                    DataTable Example
                </div>
                <div class="card-body">
                    <table id="datatablesSimple">
                        <thead>
                            <tr>
                                <th>Category </th>
                                <th>Item</th>
                                <th>Total Purchase</th>
                                <th>Total Issue</th>
                                <th>Total Purchase Price</th>
                                <th>Total Issue Price</th>
                                <th>Balance</th>
                                <th>Issue Avg Price</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th>Category </th>
                                <th>Item</th>
                                <th>Total Purchase</th>
                                <th>Total Issue</th>
                                <th>Total Purchase Price</th>
                                <th>Total Issue Price</th>
                                <th>Balance</th>
                                <th>Issue Avg Price</th>
                            </tr>
                        </tfoot>
                        <tbody>
                        <?php
                        foreach ($table_data as $row) {
                            echo '<tr>';
                            foreach ($row as $cell) {
                                echo '<td>' . htmlspecialchars($cell) . '</td>';
                            }
                            echo '</tr>';
                        }
                        ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <hr>
    <?php
    print_r($_SESSION);
    ?>

    <?php
    // Include the main template footer (assuming relative path is correct)
    include '../template/footer.php';
    ?>
</div>

<?php 
// End of file
?>