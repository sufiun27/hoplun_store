<?php
// search_employee.php
session_start();
include '../database.php'; // Assuming this provides a PDO connection in $conn

if (isset($_POST['input'])) {
    $input = filter_input(INPUT_POST, 'input', FILTER_SANITIZE_STRING);

    $stmt = $conn->prepare("SELECT e_id, e_com_id, e_name, d_name 
    FROM employee 
    INNER JOIN department ON department.d_id = employee.d_id
    WHERE e_com_id LIKE ? OR e_name LIKE ?
    ");

    $searchInput = "%{$input}%";

    if ($stmt->execute([$searchInput, $searchInput])) {
        echo '<table class="table table-striped table-hover">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Department</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>';

        // Display the options
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // *** IMPORTANT: Pass all necessary data attributes to the button ***
            echo '<tr>
            <td>' . htmlspecialchars($row["e_com_id"]) . '</td>
            <td>' . htmlspecialchars($row["e_name"]) . '</td>
            <td>' . htmlspecialchars($row["d_name"]) . '</td>
            <td>
            <button class="btn btn-primary btn-sm btn-select-employee" 
                data-eid="' . $row["e_id"] . '" 
                data-ecomid="' . htmlspecialchars($row["e_com_id"]) . '"
                data-ename="' . htmlspecialchars($row["e_name"]) . '"
                data-dname="' . htmlspecialchars($row["d_name"]) . '">
                Select
            </button>
            </td>
            </tr>';
        }

        echo '</tbody></table>';
    } else {
        echo '<div class="alert alert-warning">No employees found matching the criteria.</div>';
    }
    unset($conn);
    exit;
}
?>