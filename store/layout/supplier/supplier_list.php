<?php
include '../template/header.php';
require_once '../database.php';
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function () {
        $("#collapseLayouts0").addClass("show");
        $("#collapseLayouts0_list").addClass("active bg-success");
    });
</script>


        <div class="container-fluid px-4">
            <div class="bg-light p-3">

                <!-- Show top message -->
                <?php
                if (isset($_GET['value'])) {
                    echo '<div id="message" class="bg-warning p-2">' . $_GET['value'] . '</div>';
                    echo '<script>
                        setTimeout(() => document.getElementById("message").style.display = "none", 3000);
                    </script>';
                }
                ?>

                <h5 class="mt-5 mb-4 text-primary">Supplier List</h5>

                <?php
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }

                try {
                    // Use database.php PDO connection
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
                            <td>' . $row["s_name"] . '</td>
                            <td>' . $row["s_phone"] . '</td>
                            <td>' . $row["s_email"] . '</td>
                            <td>' . $row["s_address"] . '</td>';

                        // Supplier active/inactive switch
                        if ($row["s_active"] == 1) {
                            echo '<td class="text-success fw-bold">
                                    <i class="fa-solid fa-check"></i>
                                    <a href="supplier_deactive.php?id=' . $row["s_id"] . '" class="btn btn-warning btn-sm ms-2">
                                        <i class="fa-solid fa-xmark"></i>
                                    </a>
                                </td>';
                        } else {
                            echo '<td class="text-danger fw-bold">
                                    <i class="fa-solid fa-xmark"></i>
                                    <a href="supplier_active.php?id=' . $row["s_id"] . '" class="btn btn-success btn-sm ms-2">
                                        <i class="fa-solid fa-check"></i>
                                    </a>
                                </td>';
                        }

                        // Edit / Delete buttons
                        echo '<td>
                                <a href="supplier_edit.php?id=' . $row["s_id"] . '" class="btn btn-primary btn-sm">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="supplier_delete.php?id=' . $row["s_id"] . '" class="btn btn-danger btn-sm ms-1">
                                    <i class="fa-solid fa-xmark"></i>
                                </a>
                              </td>
                        </tr>';
                    }

                    echo '</tbody></table>';

                } catch (PDOException $e) {
                    echo "<div class='alert alert-danger'>Connection failed: " . $e->getMessage() . "</div>";
                }
                ?>

                <!-- bottom message -->
                <div id="valueDiv" class="bg-success text-white h4 p-2 mt-3"></div>
                <script>
                    const v = new URLSearchParams(window.location.search).get('value');
                    if (v) {
                        document.getElementById('valueDiv').innerText = v;
                        setTimeout(() => document.getElementById('valueDiv').innerText = '', 3000);
                    }
                </script>

                <?php include '../template/footer.php'; ?>
            </div>
        </div>
    </main>
</div>
