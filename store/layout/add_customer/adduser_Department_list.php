<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include '../template/header.php';
?>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $("#collapseLayouts").addClass("show");
        $("#collapseLayouts_department").addClass("active bg-success");
        
        setTimeout(function() {
            $("#statusMessage").fadeOut("slow");
        }, 3000);
    });
</script>


    <div style="padding:20px;">
        <h1 style="margin-top:20px;">Department List
        <a id="collapseLayouts_add_department"
   href="http://<?php echo $_SESSION['base_url']; ?>/store/layout/add_customer/adddepartment.php"
   class="btn btn-success btn-sm d-inline-flex align-items-center gap-1"
   role="button"
   aria-label="Add new department">
    <i class="fas fa-building"></i>
    <span>Add New Departments</span>
</a>

        </h1>
        <ol style="margin-bottom:20px;padding-left:15px;list-style:decimal;">
            <li style="display:inline;margin-right:10px;"><a href="../dashboard.php" style="text-decoration:none;color:#007bff;">Dashboard</a></li>
            <li style="display:inline;color:#6c757d;"> / Department List</li>
        </ol>
        
        <?php
        if (isset($_GET['status'])) {
            $status = $_GET['status'];
            $message = '';
            $bgColor = '';
            $textColor = '';

            if ($status === 'success') {
                $message = 'Operation completed successfully!';
                $bgColor = '#d4edda';
                $textColor = '#155724';
            } elseif ($status === 'error') {
                $message = 'An error occurred during the operation.';
                $bgColor = '#f8d7da';
                $textColor = '#721c24';
            }

            if (!empty($message)) {
                echo '<div id="statusMessage" style="
                        background-color:' . $bgColor . ';
                        color:' . $textColor . ';
                        padding:15px 20px;
                        border-radius:4px;
                        margin-bottom:15px;
                        position:relative;
                        border:1px solid ' . $textColor . ';">
                        ' . htmlspecialchars($message) . '
                        <button type="button" onclick="this.parentElement.style.display=\'none\'" style="
                            position:absolute;
                            top:5px;
                            right:10px;
                            background:none;
                            border:none;
                            font-size:16px;
                            font-weight:bold;
                            cursor:pointer;
                            color:' . $textColor . ';">&times;</button>
                      </div>';
            }
        }

        if (!isset($_SESSION['company'])) {
            echo '<div style="background-color:#f8d7da;color:#721c24;padding:15px 20px;border-radius:4px;margin-bottom:15px;">Company session variable not set. Cannot connect to database.</div>';
        } else {
            $user_company = $_SESSION['company'];
            $database = $user_company;
            $conn = null;

            try {
                $conn = new PDO("sqlsrv:Server=$servername;Database=$database", $username, $password);
                $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                
                $sql = "SELECT d.d_active, d.d_id, d.d_full_name, d.d_name , ISNULL(e.total_employee, 0) as total_employee
                        FROM department d 
                        LEFT JOIN (
                            SELECT d_id, COUNT(e_id) as total_employee from employee GROUP BY d_id
                        ) e 
                        ON e.d_id = d.d_id";

                $stmt = $conn->prepare($sql);
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if ($result) {
                    echo '<div style="border:1px solid #ddd;border-radius:4px;margin-bottom:20px;">
                            <div style="padding:10px 15px;font-weight:bold;background-color:#f5f5f5;">
                                Department Data List
                            </div>
                            <div style="padding:15px;overflow-x:auto;">
                                <table style="width:100%;border-collapse:collapse;">
                                    <thead>
                                        <tr>
                                            <th style="border:1px solid #ddd;padding:8px;font-size:12px;font-weight:bold;">Short Name</th>
                                            <th style="border:1px solid #ddd;padding:8px;font-size:12px;font-weight:bold;">Full Name</th>
                                            <th style="border:1px solid #ddd;padding:8px;font-size:12px;font-weight:bold;">Total Employees</th>
                                            <th style="border:1px solid #ddd;padding:8px;font-size:12px;font-weight:bold;">Status</th>
                                            <th style="border:1px solid #ddd;padding:8px;font-size:12px;font-weight:bold;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>';

                    foreach ($result as $row) {
                        $department_id = htmlspecialchars($row["d_id"]);
                        echo '<tr>
                                <td style="border:1px solid #ddd;padding:8px;font-size:12px;">' . htmlspecialchars($row["d_name"]) . '</td>
                                <td style="border:1px solid #ddd;padding:8px;font-size:12px;">' . htmlspecialchars($row["d_full_name"]) . '</td>
                                <td style="border:1px solid #ddd;padding:8px;font-size:12px;text-align:center;">' . htmlspecialchars($row["total_employee"]) . '</td>';

                        if ($row["d_active"] == 1) {
                            echo '<td style="border:1px solid #ddd;padding:8px;font-size:12px;text-align:center;color:#28a745;">
                                    &#10004; Active
                                    <a href="adduser_Department_list_deactive.php?id=' . $department_id . '" style="display:inline-block;background-color:#ffc107;color:#212529;padding:2px 5px;font-size:10px;border-radius:3px;text-decoration:none;margin-left:5px;">&#10006;</a>
                                  </td>';
                        } else {
                            echo '<td style="border:1px solid #ddd;padding:8px;font-size:12px;text-align:center;color:#dc3545;">
                                    &#10006; Inactive
                                    <a href="adduser_Department_list_active.php?id=' . $department_id . '" style="display:inline-block;background-color:#28a745;color:#fff;padding:2px 5px;font-size:10px;border-radius:3px;text-decoration:none;margin-left:5px;">&#10004;</a>
                                  </td>';
                        }

                        echo '<td style="border:1px solid #ddd;padding:8px;font-size:12px;text-align:center;">
                                <a href="adduser_list_department_edit.php?id=' . $department_id . '" style="display:inline-block;background-color:#007bff;color:#fff;padding:2px 5px;font-size:10px;border-radius:3px;text-decoration:none;margin-right:3px;">&#9998;</a>
                                <a href="adduser_dep_delete.php?id=' . $department_id . '" onclick="return confirm(\'Are you sure you want to delete department: ' . htmlspecialchars($row["d_full_name"]) . '?\');" style="display:inline-block;background-color:#dc3545;color:#fff;padding:2px 5px;font-size:10px;border-radius:3px;text-decoration:none;">&#128465;</a>
                              </td>
                            </tr>';
                    }

                    echo '</tbody>
                        </table>
                        </div>
                        </div>';
                } else {
                    echo '<div style="background-color:#d1ecf1;color:#0c5460;padding:15px 20px;border-radius:4px;">No department records found.</div>';
                }
            } catch (PDOException $e) {
                echo '<div style="background-color:#f8d7da;color:#721c24;padding:15px 20px;border-radius:4px;">Database Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            } catch (Exception $e) {
                echo '<div style="background-color:#f8d7da;color:#721c24;padding:15px 20px;border-radius:4px;">General Error: ' . htmlspecialchars($e->getMessage()) . '</div>';
            } finally {
                $conn = null;
            }
        }
        ?>
    </div>


<?php
include '../template/footer.php';
?>
