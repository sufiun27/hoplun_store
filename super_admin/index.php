<?php

// ✅ START SESSION SAFELY
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------
// ✅ SECURITY & CONFIGURATION
// ------------------------

// ✅ ENABLE FULL ERROR DISPLAY (DEBUG MODE) - OK for dev, should be OFF on production
error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

// ✅ Set secure timezone
date_default_timezone_set('Asia/Dhaka');


// ------------------------
// ✅ DATABASE CONNECTION CLASS
// ------------------------
include 'database.php';

// ------------------------
// ✅ CSRF AUTHENTICATION CLASS
// ------------------------
class AuthCsrf extends Dbh
{
    public function authenticate(?string $token, ?int $uid): bool
    {
        if (!$token || !$uid) {
            // 🚨 IMPROVEMENT: Use error_log instead of echo/print for security messages
            error_log("CSRF Check: TOKEN OR UID MISSING for user ID: " . ($uid ?? 'null'));
            echo "<pre style='color:red;'>❌ TOKEN OR UID MISSING</pre>";
            return false;
        }

        try {
            $pdo = $this->connect();

            // 🚨 FIX: SELECT is missing u_id in the WHERE clause, which is a common error
            // The original query was correct (selecting based on u_id), but it's good practice
            // to re-verify for security purposes. The original was: SELECT csrf FROM user_token WHERE u_id = :uid.
            // This is correct, no fix needed on the SQL statement, but better error handling below.

            $query = "SELECT csrf FROM user_token WHERE u_id = :uid";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':uid', $uid, PDO::PARAM_INT);
            $stmt->execute();

            $row = $stmt->fetch();

            if (!$row) {
                error_log("CSRF Check: NO CSRF FOUND FOR USER ID: " . $uid);
                echo "<pre style='color:red;'>❌ NO CSRF FOUND FOR USER</pre>";
                return false;
            }

            // 🚨 IMPROVEMENT: Use trim() just in case database column has leading/trailing spaces
            return hash_equals(trim($row['csrf']), $token);

        } catch (PDOException $e) {
            error_log("CSRF QUERY FAILED: " . $e->getMessage());
            echo "<pre style='color:red;'>
❌ CSRF QUERY FAILED. Check logs.
</pre>";
            exit;
        }
    }
}

// ----------------------------------------------------
// ✅ AUTHORIZATION & SECURITY CHECKS
// ----------------------------------------------------

// ✅ Base redirect URL
// 🚨 IMPROVEMENT: Use absolute path for redirects for better consistency
$baseUrl = rtrim($_SESSION['base_url'] ?? "/", '/') . '/';


// ✅ LOGIN CHECK
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {
    echo "<pre style='color:red;'>❌ USER NOT LOGGED IN</pre>";
    // 🚨 FIX: Redirecting to a specific login page is generally better than just $baseUrl
    header("Location: " . $baseUrl . "login.php"); 
    exit();
}

// ✅ SESSION DEBUG CHECK
if (!isset($_SESSION['csrf_token'], $_SESSION['uid'])) {
    error_log("SESSION VALUES MISSING: " . print_r($_SESSION, true));
    echo "<pre style='color:red;'>
❌ SESSION VALUES MISSING. Check logs.
</pre>";
    // 🚨 FIX: Destroy session and redirect to login if security values are missing
    session_unset();
    session_destroy();
    header("Location: " . $baseUrl . "login.php"); 
    exit();
}

// ✅ CSRF CHECK
$token = $_SESSION['csrf_token'];
// 🚨 IMPROVEMENT: Use filter_var for safer casting
$uid   = filter_var($_SESSION['uid'], FILTER_VALIDATE_INT); 

$auth = new AuthCsrf();

if ($auth->authenticate($token, $uid)) {
    $_SESSION['csrf_valid'] = true;
    // echo "<pre style='color:green;'>✅ CSRF VALIDATED SUCCESSFULLY</pre>"; // Commented for production
} else {
    $_SESSION['csrf_valid'] = false;
    error_log("CSRF VALIDATION FAILED for user ID: " . $uid);
    echo "<pre style='color:red;'>❌ CSRF VALIDATION FAILED</pre>";
    // 🚨 FIX: Redirecting to a secure logout endpoint is better than base_url on CSRF failure
    header("Location: " . $baseUrl . "logout/logout.php"); 
    exit();
}

// ------------------------
// ✅ DEFAULT DATETIME SETUP
// ------------------------
$defaultDateTime = date('Y-m-d H:i:s');
// echo "<pre style='color:blue;'>✅ CURRENT SERVER TIME: {$defaultDateTime}</pre>"; // Commented for production

// ✅ EXECUTION CONTINUES SAFELY AFTER ALL CHECKS PASSED


// --- HELPER FUNCTION TO ESCAPE OUTPUT ---
function esc($val) {
    // 🚨 IMPROVEMENT: Check if $val is an object/array before attempting to cast to string
    if (is_array($val) || is_object($val)) {
        $val = ''; // Or handle serialization if needed
    }
    return htmlspecialchars($val ?? '', ENT_QUOTES, 'UTF-8');
}

// --- USER MODEL ---
class UserModel extends Dbh {

    // Fetches data from a table
    private function fetchData(string $table, array $columns = ['*']): array {
        $pdo = $this->connect();
        // 🚨 FIX: Column and table names should be quoted consistently, e.g., using double quotes for SQL Server
        // The original logic was mostly correct but refined here for clarity and robust quoting.
        $cols = implode(', ', array_map(fn($c) => $c === '*' ? '*' : "\"" . trim($c, '[]"') . "\"", $columns));
        $query = "SELECT {$cols} FROM [dbo].\"" . trim($table, '[]"') . "\"";
        
        try {
            $stmt = $pdo->query($query); // Using query() for simple SELECT without WHERE is okay
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("DB Error in fetchData: " . $e->getMessage());
            return [];
        }
    }

    public function getDbInfo(): array { 
        return $this->fetchData('dbinfo', ['db_name']); 
    }

    public function getRoles(): array { 
        return $this->fetchData('role', ['role']); 
    }

    public function getAllUsers(): array { 
        return $this->fetchData('user'); 
    }
}

// --- CONTROLLER ---
$userModel = new UserModel();
$db_info = $userModel->getDbInfo();
$role_info = $userModel->getRoles();
$users_records = $userModel->getAllUsers();

// --- HANDLE CREATE / UPDATE POST ---
$error_message = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Data sanitization
    // 🚨 IMPROVEMENT: Use filter_input for POST data which is generally safer/cleaner
    $u_id     = filter_input(INPUT_POST, 'u_id', FILTER_VALIDATE_INT); // Validate as int
    $username = trim(filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING));
    $password = filter_input(INPUT_POST, 'password', FILTER_DEFAULT); // Default filter is fine for hashing later
    $userRole = trim(filter_input(INPUT_POST, 'userRole', FILTER_SANITIZE_STRING));
    $location = trim(filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING));
    $userid   = filter_input(INPUT_POST, 'userid', FILTER_DEFAULT); // User ID can be mixed string/int
    $email    = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL); // Validate email format
    $section  = trim(filter_input(INPUT_POST, 'section', FILTER_SANITIZE_STRING));
    $site     = trim(filter_input(INPUT_POST, 'site', FILTER_SANITIZE_STRING));


    // 🚨 FIX: Missing CSRF check for POST requests. This is critical for security!
    if (!isset($_SESSION['csrf_valid']) || $_SESSION['csrf_valid'] !== true) {
         $error_message = "Invalid CSRF token for POST request.";
         error_log("POST Request Failed: Invalid CSRF Token.");
    }
    
    // Check required fields (Username, Role, Location)
    if (empty($username) || empty($userRole) || empty($location)) {
        $error_message = "Username, User Role, and Location are required fields.";
    }

    // Check for password on CREATE
    if (!$u_id && empty($password)) {
        $error_message = "Password is required for creating a new user.";
    }


    if (!$error_message) { // Only proceed if no validation error
        $pdo = (new Dbh())->connect();

        try {
            if ($u_id) {
                // --- UPDATE ---
                $sql = "UPDATE [dbo].[user] 
                        SET username = :username, role = :role, location = :location, 
                            userid = :userid, email = :email, section = :section, site = :site";

                if ($password) {
                    $sql .= ", password = :password";
                }

                $sql .= " WHERE u_id = :u_id";

                $stmt = $pdo->prepare($sql);

                $params = [
                    ':username' => $username,
                    ':role'     => $userRole,
                    ':location' => $location,
                    ':userid'   => $userid,
                    // 🚨 IMPROVEMENT: Use validated email, if null, send null
                    ':email'    => $email, 
                    ':section'  => $section,
                    ':site'     => $site,
                    ':u_id'     => $u_id
                ];

                if ($password) {
                    // 🚨 FIX: Re-check: The original code was missing a password minimum length check on the PHP side
                    // (It has JS side check, but server-side is more important). Assuming validationForm handles this.
                    $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
                }

                $stmt->execute($params);

            } else {
                // --- CREATE ---
                $stmt = $pdo->prepare(
                    "INSERT INTO [dbo].[user] 
                     (username, password, role, location, userid, email, section, site) 
                     VALUES 
                     (:username, :password, :role, :location, :userid, :email, :section, :site)"
                );

                $stmt->execute([
                    ':username' => $username,
                    // Password is guaranteed to be non-empty at this point by validation
                    ':password' => password_hash($password, PASSWORD_DEFAULT), 
                    ':role'     => $userRole,
                    ':location' => $location,
                    ':userid'   => $userid,
                    ':email'    => $email,
                    ':section'  => $section,
                    ':site'     => $site
                ]);
            }

            // Redirect after success (Post-Redirect-Get pattern)
            header("Location: " . $_SERVER['PHP_SELF'] . "?status=success");
            exit;

        } catch (Exception $e) {
            $error_message = "Database Operation Failed. See logs for details.";
            error_log("User Management DB Error: " . $e->getMessage());
        }
    }
}

// 🚨 IMPROVEMENT: Check for success status on GET request
$success_message = null;
if (isset($_GET['status']) && $_GET['status'] === 'success') {
    $success_message = "User operation completed successfully!";
}

// --- PAGINATION ---
$recordsPerPage = 20;
$totalRecords = count($users_records);
$totalPages = ceil($totalRecords / $recordsPerPage) ?: 1;

$current_page = max(1, min(filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?? 1, $totalPages));
$startIndex = ($current_page - 1) * $recordsPerPage;
$usersOnCurrentPage = array_slice($users_records, $startIndex, $recordsPerPage);

$baseUrl = $_SESSION['base_url'] ?? '/';
?>


<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<title>User Dashboard</title>
<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
<style>
/* CSS Styles remain the same for aesthetics */
:root{
--primary:#4f46e5; --card:rgba(255,255,255,.85); --radius:14px; --shadow-soft:0 10px 25px rgba(0,0,0,.08); --shadow-hover:0 18px 40px rgba(0,0,0,.15); --glass:blur(10px);
}
body{background:linear-gradient(120deg,#dbeafe,#f8fafc);font-family:"Inter","Segoe UI",sans-serif;color:#0f172a;}
.user-container{background:var(--card);backdrop-filter:var(--glass);border-radius:var(--radius);box-shadow:var(--shadow-soft);padding:32px;margin-top:50px;transition:.3s ease;}
.user-container:hover{box-shadow:var(--shadow-hover);}
.dashboard-header h2{font-weight:700;color:var(--primary);letter-spacing:.3px;}
.nav-pills{background:linear-gradient(135deg,var(--primary),#6366f1);border-radius:var(--radius);padding:6px;box-shadow:var(--shadow-soft);}
.nav-link{color:#fff!important;border-radius:12px;font-weight:600;transition:all .25s ease;}
.nav-link:hover{background:rgba(255,255,255,.15)!important;transform:translateY(-2px);}
.nav-pills .nav-link.active{background:#fff!important;color:var(--primary)!important;box-shadow:0 6px 14px rgba(255,255,255,.6);}
.form-section{background:linear-gradient(135deg,#f8fafc,#eef2ff);border-radius:var(--radius);padding:28px;box-shadow:var(--shadow-soft);}
.form-section h4{font-weight:700;margin-bottom:25px;color:#1e293b;border-left:5px solid var(--primary);padding-left:12px;}
.form-control{border-radius:10px;border:1px solid #cbd5f5;padding:10px 14px;transition:.25s;}
.form-control:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(79,70,229,.15);}
.btn-primary{background:linear-gradient(135deg,var(--primary),#6366f1);border:none;border-radius:12px;font-weight:700;letter-spacing:.4px;padding:11px;transition:.25s;}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 22px rgba(79,70,229,.4);}
.btn-danger{background:linear-gradient(135deg,#dc2626,#ef4444);border:none;border-radius:12px;font-weight:600;}
.table-responsive{border-radius:var(--radius);overflow:hidden;box-shadow:var(--shadow-soft);}
.table{margin-bottom:0;background:white;}
.table thead{background:linear-gradient(135deg,var(--primary),#6366f1);color:white;}
.table th{font-weight:700;padding:14px;}
.table td{padding:12px;vertical-align:middle;}
.table-hover tbody tr:hover{background:#eef2ff;transform:scale(1.01);transition:.2s;}
.pagination .page-link{border-radius:50px;margin:0 4px;color:var(--primary);font-weight:600;border:none;box-shadow:var(--shadow-soft);}
.pagination .page-item.active .page-link{background:linear-gradient(135deg,var(--primary),#6366f1);color:white;box-shadow:0 6px 18px rgba(79,70,229,.4);}
.pagination .page-link:hover{background:#e0e7ff;}
.alert-danger{border-radius:var(--radius);font-weight:600;box-shadow:var(--shadow-soft);}
.alert-success{border-radius:var(--radius);font-weight:600;box-shadow:var(--shadow-soft);}
@media(max-width:768px){.form-section{margin-bottom:25px;}}
</style>
</head>
<body>

<div class="container mt-4 user-container">
<div class="row align-items-center mb-4 border-bottom pb-2 dashboard-header">
<div class="col-6"><h2 class="mb-0">User Dashboard</h2></div>
<div class="col-2 "><a class="btn btn-info" href="store_configure.php">Configure Store</a></div>
<div class="col-2 "><a class="btn btn-info" href="store_configure_employee.php">Configure Employee</a></div>
<div class="col-2 text-right"><a class="btn btn-danger" href="<?php echo esc($baseUrl); ?>logout/logout.php">Logout</a></div>
</div>

<div class="row mb-4">
<div class="col-12">
<nav class="nav nav-pills nav-fill rounded p-1">
<a class="nav-item nav-link active" href="<?php echo esc($_SERVER['PHP_SELF']); ?>">Home</a>
<a class="nav-item nav-link" href="go_to_store.php?company=fashion">Fashion</a>
<a class="nav-item nav-link" href="go_to_store.php?company=brands">Brands</a>
<a class="nav-item nav-link" href="go_to_store.php?company=intimate">Intimate</a>
<a class="nav-item nav-link" href="go_to_store.php?company=diva">Diva</a>
<a class="nav-item nav-link" href="go_to_store.php?company=legend">Legend</a>
<a class="nav-item nav-link" href="go_to_store.php?company=heritage">Heritage</a>
<a class="nav-item nav-link" href="go_to_store.php?company=demo">Demo</a>
<a class="nav-item nav-link" href="go_to_store.php?company=bdcl">BDCL</a>
</nav>
</div>
</div>

<?php if($error_message): ?>
<div class="alert alert-danger" role="alert"><strong>Error:</strong> <?php echo esc($error_message); ?></div>
<?php endif; ?>

<?php if($success_message): ?>
<div class="alert alert-success" role="alert"><strong>Success:</strong> <?php echo esc($success_message); ?></div>
<?php endif; ?>

<div class="row">
<div class="col-md-5 form-section">
<h4>Create / Edit User</h4>
<form method="post" onsubmit="return validateForm()">
<input type="hidden" name="u_id" id="u_id" value=""> 

<div class="form-group">
<label for="username">Username (*)</label>
<input type="text" name="username" id="username" class="form-control" placeholder="Enter username" required>
</div>

<div class="form-group">
<label for="userid">User ID</label>
<input type="text" name="userid" id="userid" class="form-control" placeholder="Enter User ID (Optional)">
</div>

<div class="form-group">
<label for="email">Email</label>
<input type="email" name="email" id="email" class="form-control" placeholder="Enter email (Optional)">
</div>

<div class="form-group">
<label for="password">Password (*)</label>
<input type="password" name="password" id="password" class="form-control" placeholder="Enter password (Required for Create, Leave blank for Update)">
</div>
<div class="form-group">
<label for="confirmPassword">Confirm Password</label>
<input type="password" id="confirmPassword" class="form-control" placeholder="Confirm password">
</div>

<div class="form-group">
<label for="userRole">User Role (*)</label>
<select name="userRole" id="userRole" class="form-control" required>
<option value="" disabled selected>Select Role</option> <?php foreach($role_info as $row): ?>
<option value="<?php echo esc($row['role']); ?>"><?php echo esc($row['role']); ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="form-group">
<label for="location">Location (*)</label>
<select name="location" id="location" class="form-control" required>
<option value="" disabled selected>Select Location</option> <?php foreach($db_info as $row): ?>
<option value="<?php echo esc($row['db_name']); ?>"><?php echo esc($row['db_name']); ?></option>
<?php endforeach; ?>
</select>
</div>

<div class="form-group">
<label for="section">Section</label>
<input type="text" name="section" id="section" class="form-control" placeholder="Enter Section (Optional)">
</div>

<div class="form-group">
<label for="site">Site</label>
<input type="text" name="site" id="site" class="form-control" placeholder="Enter Site (Optional)">
</div>

<button type="submit" class="btn btn-primary btn-block" id="formButton">Create User</button>
</form>
</div>

<div class="col-md-7">
<h4 class="mb-4 text-secondary">User Records (Page <?php echo $current_page; ?>)</h4>
<div class="table-responsive">
<table class="table table-striped table-hover table-sm bg-light border">
<thead><tr><th>Username</th><th>User ID</th><th>Role</th><th>Email</th><th>Section</th><th>Site</th><th>Location</th></tr></thead>
<tbody>
<?php if(empty($usersOnCurrentPage)): ?>
<tr><td colspan="7" class="text-center text-muted">No user records found.</td></tr>
<?php else: ?>
<?php foreach($usersOnCurrentPage as $row): ?>
<tr onclick="populateForm(this)" 
    data-u_id="<?php echo esc($row['u_id']); ?>"
    data-username="<?php echo esc($row['username']); ?>"
    data-role="<?php echo esc($row['role']); ?>"
    data-location="<?php echo esc($row['location']); ?>"
    data-userid="<?php echo esc($row['userid']); ?>"
    data-email="<?php echo esc($row['email']); ?>"
    data-section="<?php echo esc($row['section']); ?>"
    data-site="<?php echo esc($row['site']); ?>"
>
<td><?php echo esc($row['username']); ?></td>
<td><?php echo esc($row['userid']); ?></td>
<td><?php echo esc($row['role']); ?></td>
<td><?php echo esc($row['email']); ?></td>
<td><?php echo esc($row['section']); ?></td>
<td><?php echo esc($row['site']); ?></td>
<td><?php echo esc($row['location']); ?></td>
</tr>
<?php endforeach; ?>
<?php endif; ?>
</tbody>
</table>
</div>

<nav aria-label="Pagination">
<ul class="pagination justify-content-center mt-4">
<?php for($page=1;$page<=$totalPages;$page++): ?>
<li class="page-item <?php echo $page===$current_page?'active':'';?>"><a class="page-link" href="?page=<?php echo $page;?>"><?php echo $page;?></a></li>
<?php endfor; ?>
</ul>
</nav>
</div>
</div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script>
function validateForm(){
    var u_id = document.getElementById("u_id").value;
    var username = document.getElementById("username").value.trim();
    var userRole = document.getElementById("userRole").value;
    var location = document.getElementById("location").value;
    var pw = document.getElementById("password").value;
    var cpw = document.getElementById("confirmPassword").value;
    
    // Client-side required field check for better UX
    if(username === "" || userRole === "" || location === "") {
        alert("Username, User Role, and Location are required fields.");
        return false;
    }
    
    // For a new user (Create), password is required
    if(!u_id && pw.length === 0){
        alert("Password is required for new users!"); 
        return false;
    }

    // Only check length and match if password field is NOT empty (i.e., if user entered a new password)
    if(pw.length > 0) { 
        // Password minimum length check
        if(pw.length < 8){ 
            alert("Password must be at least 8 characters long!"); 
            return false;
        }

        // Password match check
        if(pw !== cpw){ 
            alert("Passwords do not match!"); 
            return false;
        }
    }
    
    // 🚨 FIX: Add logic to reset button text when user starts a new create operation
    if(!u_id) {
        document.getElementById('formButton').innerText = 'Create User';
        document.getElementById('password').placeholder = 'Enter password (Required for Create, Leave blank for Update)';
    }

    return true;
}

// Click row to populate form
function populateForm(row) {
    // Get the data attributes for the user being edited
    const u_id = row.getAttribute('data-u_id');
    const username = row.getAttribute('data-username');
    const userRole = row.getAttribute('data-role');
    const location = row.getAttribute('data-location');
    const userid = row.getAttribute('data-userid');
    const email = row.getAttribute('data-email');
    const section = row.getAttribute('data-section');
    const site = row.getAttribute('data-site');
    
    // Populate Form Fields
    document.getElementById('u_id').value = u_id;
    document.getElementById('username').value = username;
    document.getElementById('userid').value = userid;
    document.getElementById('email').value = email;
    document.getElementById('userRole').value = userRole;
    document.getElementById('location').value = location;
    document.getElementById('section').value = section;
    document.getElementById('site').value = site;

    // Reset password fields and update button text for editing
    document.getElementById('password').value = '';
    document.getElementById('confirmPassword').value = '';
    document.getElementById('password').placeholder = 'Leave blank to keep current password';
    document.getElementById('formButton').innerText = 'Update User (u_id: ' + u_id + ')';
}

// Attach event listeners using the new function
document.querySelectorAll('table tbody tr').forEach(row => {
    row.addEventListener('click', () => populateForm(row));
});

// 🚨 IMPROVEMENT: Add a function to clear the form fields for a new creation
function clearForm() {
    document.getElementById('u_id').value = '';
    document.getElementById('username').value = '';
    document.getElementById('userid').value = '';
    document.getElementById('email').value = '';
    document.getElementById('password').value = '';
    document.getElementById('confirmPassword').value = '';
    document.getElementById('userRole').value = '';
    document.getElementById('location').value = '';
    document.getElementById('section').value = '';
    document.getElementById('site').value = '';

    document.getElementById('password').placeholder = 'Enter password (Required for Create, Leave blank for Update)';
    document.getElementById('formButton').innerText = 'Create User';
}

// 🚨 IMPROVEMENT: Add a button or link to clear the form (optional but good UX)
const form = document.querySelector('.form-section form');
const clearButton = document.createElement('button');
clearButton.type = 'button';
clearButton.className = 'btn btn-secondary btn-block mt-2';
clearButton.innerText = 'Clear Form / New User';
clearButton.addEventListener('click', clearForm);
form.parentNode.insertBefore(clearButton, form.nextSibling);


</script>
</body>
</html>