<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Assuming this path is correct for your project structure
require_once '../database.php'; 

// $user_name, $user_company, $user_role are not used here, but kept for context if needed later
// $user_name = $_SESSION['username'];
// $user_company = $_SESSION['company'];
// $user_role = $_SESSION['role'];

// Removed $database = $user_company; as database.php should handle the connection to the correct DB

try {
    // Assuming $conn is available from database.php or a class instance method
    // If you are using the procedural style from the original search, keep $conn.
    // If you are using the OOP style from the original list, adjust to get $conn.
    
    // For consistency, let's establish the connection here if it wasn't done globally
    if (!isset($conn)) {
        $db = new Database();
        $conn = $db->getConnection();
    }


    if (isset($_POST['input'])) {
        $input = $_POST['input'];
        // Use a wildcard only for partial matching
        $param = "%$input%"; 
        
        // Prepare the SQL statement for searching by Name, Email, or Phone
        $sql = "SELECT * FROM supplier WHERE s_name LIKE ? OR s_email LIKE ? OR s_phone LIKE ?";
        $stmt = $conn->prepare($sql);
        
        if ($stmt->execute([$param, $param, $param])) {
            
            // Start the table structure
            echo '<table class="table table-bordered table-striped bg-white">
                <thead class="table-dark">
                    <tr>
                        <th>Name</th>
                        <th>Contract</th>
                        <th>Email</th>
                        <th>Address</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>';

            $results_found = false;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $results_found = true;
                echo '<tr>
                        <td>' . htmlspecialchars($row["s_name"]) . '</td>
                        <td>' . htmlspecialchars($row["s_phone"]) . '</td>
                        <td>' . htmlspecialchars($row["s_email"]) . '</td>
                        <td>' . htmlspecialchars($row["s_address"]) . '</td>';

                // Status switch logic
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

            if (!$results_found) {
                // If no rows were returned, display a message within the container
                echo '<div class="alert alert-info">No supplier found matching your search criteria.</div>';
            }

        } else {
            echo '<div class="alert alert-danger">Query failed to execute.</div>';
        }

        // Close connection if explicitly opened here (optional, depending on database.php)
        // $conn = null;
        exit;
    }
} catch (Exception $e) {
    // Return an error message to the AJAX call
    echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
    exit;
}
?>