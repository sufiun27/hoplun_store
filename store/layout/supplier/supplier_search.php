<?php 
// Assuming header.php handles session start and basic HTML structure up to the content area
include '../template/header.php'; 
require_once '../database.php';
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        // Activate the relevant navigation links (Adjust IDs as needed)
        $("#collapseLayouts0").addClass("show");
        // You might want to create a new ID for the combined page
        $("#collapseLayouts0_management").addClass("active bg-success text-white"); 

        // Initial setup: The list will be visible, the search result container is hidden initially
        // but will receive the list's content, so we ensure the list is displayed.
        $("#search_result_container").show(); 
    });
</script>


        <div class="container-fluid px-4 mt-4">

            <div class="row justify-content-center">
                <div class="col-lg-8">

                    <div class="card shadow border-0 mb-4">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">Supplier Management (Search and List)</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="live_search" class="form-label fw-semibold">Type Supplier Name, Contact, or Email</label>
                                <input required 
                                    type="text" 
                                    class="form-control form-control-lg" 
                                    id="live_search" 
                                    autocomplete="off" 
                                    placeholder="Start typing to search...">
                            </div>

                            <div id="valueDiv" class="alert alert-success mt-3 text-center fw-bold d-none"></div>

                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                <a id="collapseLayouts0_add"
   href="http://<?php echo $_SESSION['base_url']; ?>/store/layout/supplier/supplier_add.php"
   class="btn btn-success">
   <i class="fas fa-plus me-1"></i> Add New
</a>

                </div>
            </div>

            <div class="row">
                <div class="col-lg-12">
                    <div id="search_result_container" class="bg-light p-3">
                        <h5 class="mt-2 mb-4 text-primary" id="list_title">Full Supplier List</h5>

                        <?php
                        // PHP code to display the full supplier list (from supplier_list.php)
                        if (session_status() === PHP_SESSION_NONE) {
                            session_start();
                        }

                        try {
                            $db = new Database();
                            $conn = $db->getConnection();

                            $sql = "SELECT * FROM supplier ORDER BY s_add_datetime DESC";
                            $query = $conn->query($sql);

                            echo '<table class="table table-bordered table-striped bg-white">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Name</th>
                                        <th>Contact</th>
                                        <th>Email</th>
                                        <th>Address</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>';

                            while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
                                echo '<tr>
                                    <td>' . htmlspecialchars($row["s_name"]) . '</td>
                                    <td>' . htmlspecialchars($row["s_phone"]) . '</td>
                                    <td>' . htmlspecialchars($row["s_email"]) . '</td>
                                    <td>' . htmlspecialchars($row["s_address"]) . '</td>';

                                // Status switch
                                $status_icon = $row["s_active"] == 1 ? '<i class="fa-solid fa-check"></i>' : '<i class="fa-solid fa-xmark"></i>';
                                $status_class = $row["s_active"] == 1 ? 'text-success' : 'text-danger';
                                $switch_btn_class = $row["s_active"] == 1 ? 'btn-warning' : 'btn-success';
                                $switch_link = $row["s_active"] == 1 ? 'supplier_deactive.php?id=' : 'supplier_active.php?id=';
                                $switch_icon = $row["s_active"] == 1 ? '<i class="fa-solid fa-xmark"></i>' : '<i class="fa-solid fa-check"></i>';

                                echo '<td class="' . $status_class . ' fw-bold">
                                    ' . $status_icon . '
                                    <a href="' . $switch_link . $row["s_id"] . '" class="btn ' . $switch_btn_class . ' btn-sm ms-2">' . $switch_icon . '</a>
                                </td>';

                                // Edit / Delete buttons
                                echo '<td>
                                    <a href="supplier_edit.php?id=' . $row["s_id"] . '" class="btn btn-primary btn-sm">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <a href="supplier_delete.php?id=' . $row["s_id"] . '" class="btn btn-danger btn-sm ms-1">
                                        <i class="fa-solid fa-trash"></i>
                                    </a>
                                </td>
                                </tr>';
                            }

                            echo '</tbody></table>';

                        } catch (PDOException $e) {
                            echo "<div class='alert alert-danger'>Database query failed: " . $e->getMessage() . "</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>

        </div>


<script>
$(document).ready(function () {
    const $search_input = $("#live_search");
    const $search_result_container = $("#search_result_container");
    const $list_title = $("#list_title");
    const $full_list_html = $search_result_container.html(); // Store the original full list HTML

    $search_input.keyup(function () {
        let input = $(this).val().trim();

        if (input.length > 0) {
            // Change title for search mode
            $list_title.text("Search Results"); 
            
            $.ajax({
                url: "livesearch_supplier.php",
                method: "POST",
                data: { input: input },
                success: function (data) {
                    $search_result_container.html(data);
                },
                error: function() {
                    $search_result_container.html('<div class="alert alert-danger">Error fetching search results.</div>');
                }
            });

        } else {
            // Restore full list when search input is empty
            $list_title.text("Full Supplier List");
            $search_result_container.html($full_list_html); 
        }
    });

    // Handle messages from URL parameters (for Create/Edit/Delete actions)
    const params = new URLSearchParams(window.location.search);
    const value = params.get('value');

    if (value) {
        const messageDiv = document.getElementById('valueDiv');
        messageDiv.textContent = value;
        messageDiv.classList.remove("d-none");
        messageDiv.classList.add("alert-success"); // Ensure it's a success alert

        setTimeout(() => messageDiv.classList.add("d-none"), 3000);
    }
});
</script>

<?php include '../template/footer.php'; ?>