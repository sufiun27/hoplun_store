<?php
// 1. Session and Database Initialization
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include the necessary database class (assuming '../db/Database.php' is the path)
// I've adjusted the path to be relative to the expected location of Database.php
include '../database.php'; 
// NOTE: I'm assuming the actual database connection file in the original adduser.php was using a procedural style 
// that is now being replaced/integrated with the OOP style from Database.php.
// The original was 'include '../layoutdbconnection.php';' - I will use the OOP style now.

// Helper function to get DB connection
function getDbConnection() {
    // We assume the Database class exists in the included file
    $db = new Database();
    return $db->getConnection();
}

// Default date/time for hidden input (assuming $defaultDateTime was set elsewhere, setting a default here)
$defaultDateTime = date('Y-m-d H:i:s');
$username = $_SESSION['username'] ?? 'System'; // Use session username or 'System' fallback

$adduser_process_message = "add new user";

// 2. Handle Form Submission (The logic from adduser_emp_process.php)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emp_name = $_POST['name'] ?? null;
    $mn = $_POST['mn'] ?? null;
    $emp_id = $_POST['id'] ?? null;
    $emp_designation = $_POST['designation'] ?? null;
    $emp_department_id = $_POST['department'] ?? null;
    $emp_add_datetime = $_POST['emp_add_datetime'] ?? $defaultDateTime;
    $emp_add_by = $_POST['emp_add_by'] ?? $username;

    if ($emp_name && $emp_id && $emp_designation && $emp_department_id && $mn) {
        $conn = getDbConnection();

        if ($conn) {
            // Prepare the SQL statement with named parameters
            $sql = "INSERT INTO employee (e_com_id, e_name, d_id, e_designation, e_add_date_time, e_add_by, user_type) 
                    VALUES (:emp_id, :emp_name, :emp_department_id, :emp_designation, :emp_add_datetime, :emp_add_by, :mn)";
            
            try {
                $stmt = $conn->prepare($sql);

                // Bind parameters
                $stmt->bindParam(':emp_id', $emp_id, PDO::PARAM_STR);
                $stmt->bindParam(':emp_name', $emp_name, PDO::PARAM_STR);
                $stmt->bindParam(':emp_department_id', $emp_department_id, PDO::PARAM_INT);
                $stmt->bindParam(':emp_designation', $emp_designation, PDO::PARAM_STR);
                $stmt->bindParam(':emp_add_datetime', $emp_add_datetime, PDO::PARAM_STR);
                $stmt->bindParam(':emp_add_by', $emp_add_by, PDO::PARAM_STR);
                $stmt->bindParam(':mn', $mn, PDO::PARAM_STR);

                // Execute the prepared statement
                if ($stmt->execute()) {
                    $adduser_process_message = "✅ Employee record inserted successfully!";
                } else {
                    $adduser_process_message = "❌ Error occurred during insertion.";
                }
            } catch (PDOException $e) {
                // Check for common error like unique constraint violation (duplicate ID)
                if (strpos($e->getMessage(), 'unique constraint') !== false || strpos($e->getMessage(), 'duplicate') !== false) {
                    $adduser_process_message = "❌ Duplicate Employee ID found! Could not register.";
                } else {
                    // Log the error for debugging
                    error_log("DB Error on INSERT: " . $e->getMessage());
                    $adduser_process_message = "❌ An unexpected database error occurred.";
                }
            }
            // Close the connection (or let PDO handle it via object destruction)
            $conn = null;
        } else {
            $adduser_process_message = "❌ Could not connect to the database.";
        }
    } else {
        $adduser_process_message = "❌ All required fields must be submitted.";
    }
}
// Get message from GET parameter if it exists (for redirects, though self-posting handles it better)
if (isset($_GET['value'])) {
    $adduser_process_message = htmlspecialchars($_GET['value']);
}


// 3. UI Template Structure
include '../template/header.php';
?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Highlight the current menu item
        $("#collapseLayouts").addClass("show");
        $("#collapseLayouts_add").addClass("active bg-success");
        
        // Hide success/error message after 5 seconds
        setTimeout(function() {
            $("#status-message").fadeOut('slow');
        }, 5000);
    });
</script>

<!-- <div id="layoutSidenav_content">
    <main> -->
        <div class="container-fluid px-4">
            <h1 class="mt-4">👤 Employee Registration</h1>
            <ol class="breadcrumb mb-4">
                <li class="breadcrumb-item active">Add New Employee</li>
            </ol>
            
            <div class="row">
                <div class="col-lg-6 col-md-8">
                    <div class="card shadow mb-4">
                        <div class="card-header bg-primary text-white">
                            <i class="fas fa-user-plus me-1"></i>
                            New Employee Details
                        </div>
                        <div class="card-body">
                            <?php 
                                $message_class = '';
                                if (strpos($adduser_process_message, 'successfully') !== false || strpos($adduser_process_message, '✅') !== false) {
                                    $message_class = 'alert-success';
                                } elseif (strpos($adduser_process_message, 'add new user') === false) {
                                    $message_class = 'alert-danger';
                                }
                            ?>
                            <?php if ($message_class): ?>
                                <div id="status-message" class="alert <?php echo $message_class; ?> text-center p-3 mb-4" role="alert">
                                    <strong><?php echo htmlspecialchars($adduser_process_message); ?></strong>
                                </div>
                            <?php endif; ?>

                            <form action="" method="POST"> <div class="mb-3">
                                    <label for="name" class="form-label">Employee Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required placeholder="e.g., John Doe">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="id" class="form-label">Employee ID <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="id" name="id" required placeholder="Unique Company ID">
                                </div>
                                
                                <div class="mb-3">
                                    <label for="designation" class="form-label">Designation <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="designation" name="designation" required placeholder="e.g., Software Engineer">
                                </div>

                                <div class="mb-3">
                                    <label for="mn" class="form-label">User Type <span class="text-danger">*</span></label>
                                    <select class="form-select" id="mn" name="mn" required>
                                        <option value="" disabled selected>Select User Type</option>
                                        <option value="Management">Management</option>
                                        <option value="Non-Management">Non-Management</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="department" class="form-label">Department <span class="text-danger">*</span></label>
                                    <select class="form-select" id="department" name="department" required>
                                        <option value="" disabled selected>Select Department</option>
                                        <?php
                                            $conn = getDbConnection();
                                            if ($conn) {
                                                // Fetch department names from the database
                                                $sql = "SELECT d_id, d_name FROM department WHERE d_active = 1 ORDER BY d_name";
                                                $stmt = $conn->prepare($sql);
                                                $stmt->execute();
                                                
                                                // Display department names as options
                                                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                                    echo "<option value='" . htmlspecialchars($row['d_id']) . "'>" . htmlspecialchars($row['d_name']) . "</option>";
                                                }
                                                $conn = null; // Close connection
                                            } else {
                                                echo "<option value='' disabled>Error fetching departments</option>";
                                            }
                                        ?>
                                    </select>
                                </div>
                                
                                <input hidden type="datetime-local" id="emp_add_datetime" name="emp_add_datetime" value="<?php echo htmlspecialchars($defaultDateTime); ?>" >
                                <input hidden type="text" id="emp_add_by" name="emp_add_by" value="<?php echo htmlspecialchars($username); ?>" >
                                
                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-check-circle me-2"></i> Register Employee
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div></div></main>

<?php
// 4. Footer Part
include '../template/footer.php';
?>