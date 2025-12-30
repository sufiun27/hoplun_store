<?php
declare(strict_types=1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request method.');
}

/* ===============================
   1. Get POST Data
================================ */
$raw_data = $_POST['excel_data'] ?? '';
$db_name  = $_POST['company'] ?? '';

if ($raw_data === '' || $db_name === '') {
    die('Missing data or company selection.');
}

$rows = json_decode($raw_data, true);
if (!is_array($rows) || count($rows) < 2) {
    die('No valid data found in Excel.');
}

/* ===============================
   2. Database Connection
================================ */
$host = "10.3.13.87";
$user = "sa";
$pass = "sa@123";

try {
    $pdo = new PDO(
        "sqlsrv:Server=$host;Database=$db_name",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]
    );
} catch (PDOException $e) {
    die("Database Connection Error: " . $e->getMessage());
}

/* ===============================
   3. Prepare Statements
================================ */
$getDeptStmt = $pdo->prepare(
    "SELECT d_id FROM department WHERE d_name = ?"
);

$insDeptStmt = $pdo->prepare(
    "INSERT INTO department
     (d_name, d_full_name, d_add_date_time, d_add_by, d_active)
     OUTPUT INSERTED.d_id
     VALUES (?, ?, GETDATE(), ?, 1)"
);

$checkEmpStmt = $pdo->prepare(
    "SELECT 1 FROM employee WHERE e_com_id = ?"
);

$insEmpStmt = $pdo->prepare(
    "INSERT INTO employee
     (e_com_id, e_name, d_id, e_designation,
      e_add_date_time, e_add_by, e_active, user_type)
     VALUES (?, ?, ?, ?, GETDATE(), ?, 1, ?)"
);

/* ===============================
   4. Processing
================================ */
$current_user = "System_Admin";
$importCount = 0;
$skipCount   = 0;

/* Remove Excel Header Row */
array_shift($rows);

try {
    $pdo->beginTransaction();

    foreach ($rows as $row) {

        if (!isset($row[0])) {
            continue;
        }

        $e_com_id    = trim((string)$row[0]);
        $e_name      = trim((string)($row[1] ?? ''));
        $dept_name   = trim((string)($row[2] ?? 'General'));
        $designation = trim((string)($row[3] ?? ''));
        $user_type   = trim((string)($row[4] ?? 'Staff'));

        if ($e_com_id === '') {
            continue;
        }

        /* ===============================
           STEP 1: Department
        ================================ */
        $getDeptStmt->execute([$dept_name]);
        $dept = $getDeptStmt->fetch(PDO::FETCH_ASSOC);

        if ($dept) {
            $d_id = (int)$dept['d_id'];
        } else {
            try {
                $insDeptStmt->execute([
                    $dept_name,
                    $dept_name,
                    $current_user
                ]);
                $d_id = (int)$insDeptStmt->fetchColumn();
            } catch (PDOException $e) {
                /* In case another row inserted same department */
                $getDeptStmt->execute([$dept_name]);
                $d_id = (int)$getDeptStmt->fetchColumn();
            }
        }

        /* ===============================
           STEP 2: Employee
        ================================ */
        $checkEmpStmt->execute([$e_com_id]);
        if ($checkEmpStmt->fetch()) {
            $skipCount++;
            continue;
        }

        $insEmpStmt->execute([
            $e_com_id,
            $e_name,
            $d_id,
            $designation,
            $current_user,
            $user_type
        ]);

        $importCount++;
    }

    $pdo->commit();

    echo "<script>
        alert('Import Successful!\\nImported: {$importCount}\\nSkipped: {$skipCount}');
        window.location.href = 'store_configure_employee.php';
    </script>";

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log($e->getMessage());
    die("Critical Error: " . $e->getMessage());
}
