<?php
//C:\xampp\htdocs\hoplun_store\super_admin\store_configure_employee.php
declare(strict_types=1);

/**
 * Store Configuration – Excel Import (FINAL)
 */

require_once __DIR__ . '/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * DB Connection (using existing Dbh class)
 */
try {
    $db   = new Dbh();
    $conn = $db->connect();
} catch (PDOException $e) {
    error_log('DB Init Error: ' . $e->getMessage());
    die('<h3 style="color:red">Database connection failed.</h3>');
}

/**
 * Load dropdown data
 */
$sections  = [];
$companies = [];

try {
    // ✅ Correct table & column names
    $sections  = $conn->query("SELECT name FROM store_sections ORDER BY name")->fetchAll();
    $companies = $conn->query("SELECT db_name FROM dbinfo ORDER BY db_name")->fetchAll();
} catch (PDOException $e) {
    error_log('Dropdown Load Error: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Store Configuration - Excel Import</title>

    <!-- XLSX -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"
            crossorigin="anonymous"></script>

    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f4f6f8;
            padding: 30px;
        }
        .container {
            background: #fff;
            max-width: 1100px;
            margin: auto;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,.08);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 13px;
        }
        th, td {
            border: 1px solid #e0e0e0;
            padding: 8px;
        }
        th {
            background: #0d6efd;
            color: #fff;
        }
        select, button {
            padding: 8px 12px;
            border-radius: 4px;
        }
        .btn-primary {
            background: #0d6efd;
            color: #fff;
            border: none;
        }
        .btn-success {
            background: #198754;
            color: #fff;
            border: none;
            margin-top: 15px;
            display: none;
        }
        .form-row {
            display: flex;
            gap: 15px;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Employee Configuration – Excel Import
        <button class="btn btn-sm btn-secondary" style="float:right;" >
            <a href="index.php">Back to Dashboard</a>
        </button>
    </h2>

    <p><strong>Step 1:</strong> Upload your Excel file</p>
    <input type="file" id="excel_file" accept=".xlsx,.xls">

    <div id="preview_area">
        <h4 id="preview_title" style="display:none;">Preview (First 10 Rows)</h4>
        <table id="preview_table"></table>

        <form method="POST" action="store_configure_employee_store.php" id="import_form">
            <input type="hidden" name="excel_data" id="excel_data_input">

            <div class="form-row">
                
                

                <!-- COMPANY / DB DROPDOWN -->
                <select name="company" required>
                    <option value="">Select Company</option>
                    <?php foreach ($companies as $row): ?>
                        <option value="<?= htmlspecialchars($row['db_name']) ?>">
                            <?= htmlspecialchars($row['db_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn-success" id="submit_btn">
                Confirm & Import All Data
            </button>
        </form>
    </div>

    <br>
    <button class="btn-primary" onclick="downloadTemplate()">Download Excel Template</button>
</div>

<script>
document.getElementById('excel_file').addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (!file) return;

    const reader = new FileReader();

    reader.onload = function (evt) {
        const data = new Uint8Array(evt.target.result);
        const workbook = XLSX.read(data, { type: 'array' });
        const sheet = workbook.Sheets[workbook.SheetNames[0]];
        const rows = XLSX.utils.sheet_to_json(sheet, { header: 1 });

        document.getElementById('excel_data_input').value = JSON.stringify(rows);

        let html = '';
        const limit = Math.min(rows.length, 11);

        for (let i = 0; i < limit; i++) {
            html += '<tr>';
            rows[i].forEach(cell => {
                html += i === 0
                    ? `<th>${cell ?? ''}</th>`
                    : `<td>${cell ?? ''}</td>`;
            });
            html += '</tr>';
        }

        document.getElementById('preview_table').innerHTML = html;
        document.getElementById('preview_title').style.display = 'block';
        document.getElementById('submit_btn').style.display = 'inline-block';
    };

    reader.readAsArrayBuffer(file);
});

// CREATE TABLE "department" (
// 	"d_id" INT NOT NULL,
// 	"d_name" VARCHAR(500) NOT NULL COLLATE 'SQL_Latin1_General_CP1_CI_AS',
// 	"d_full_name" VARCHAR(500) NOT NULL COLLATE 'SQL_Latin1_General_CP1_CI_AS',
// 	"d_add_date_time" DATETIME NOT NULL,
// 	"d_add_by" VARCHAR(100) NOT NULL COLLATE 'SQL_Latin1_General_CP1_CI_AS',
// 	"d_update_date_time" DATETIME NULL DEFAULT NULL,
// 	"d_update_by" VARCHAR(100) NULL DEFAULT NULL COLLATE 'SQL_Latin1_General_CP1_CI_AS',
// 	"d_active" BIT NOT NULL DEFAULT '(1)',
// 	"d_inactive_by" VARCHAR(100) NULL DEFAULT NULL COLLATE 'SQL_Latin1_General_CP1_CI_AS',
// 	"d_inactive_datetime" DATETIME NULL DEFAULT NULL,
// 	PRIMARY KEY ("d_id"),
// 	UNIQUE INDEX "UQ_department_d_name" ("d_name")
// )
// ;

// CREATE TABLE "employee" (
// 	"e_id" INT NOT NULL,
// 	"e_com_id" VARCHAR(50) NOT NULL COLLATE 'SQL_Latin1_General_CP1_CI_AS',
// 	"e_name" VARCHAR(500) NOT NULL COLLATE 'SQL_Latin1_General_CP1_CI_AS',
// 	"d_id" INT NOT NULL,
// 	"e_designation" VARCHAR(500) NOT NULL COLLATE 'SQL_Latin1_General_CP1_CI_AS',
// 	"e_add_date_time" DATETIME NOT NULL,
// 	"e_add_by" VARCHAR(100) NOT NULL COLLATE 'SQL_Latin1_General_CP1_CI_AS',
// 	"e_update_date_time" DATETIME NULL DEFAULT NULL,
// 	"e_update_by" VARCHAR(100) NULL DEFAULT NULL COLLATE 'SQL_Latin1_General_CP1_CI_AS',
// 	"e_active" BIT NOT NULL DEFAULT '(1)',
// 	"e_inactive_by" VARCHAR(100) NULL DEFAULT NULL COLLATE 'SQL_Latin1_General_CP1_CI_AS',
// 	"e_inactive_datetime" DATETIME NULL DEFAULT NULL,
// 	"user_type" VARCHAR(50) NULL DEFAULT NULL COLLATE 'SQL_Latin1_General_CP1_CI_AS',
// 	FOREIGN KEY INDEX "FK_employee_d_id" ("d_id"),
// 	PRIMARY KEY ("e_id"),
// 	UNIQUE INDEX "UQ_employee_e_com_id" ("e_com_id"),
// 	CONSTRAINT "FK_employee_d_id" FOREIGN KEY ("d_id") REFERENCES "department" ("d_id") ON UPDATE NO_ACTION ON DELETE NO_ACTION
// )
// ;

function downloadTemplate() {
    const headers = [[
        "Employee ID", "Employee Name", "Department", "Designation", "User Type"  
    ]];

    const wb = XLSX.utils.book_new();
    const ws = XLSX.utils.aoa_to_sheet(headers);
    XLSX.utils.book_append_sheet(wb, ws, "Template");
    XLSX.writeFile(wb, "Store_employee_Setup_Template.xlsx");
}
</script>

</body>
</html>


<!-- make store_configure_employee_store.php 
logic step one ,
find the department id from department table if not found insert the department and get the id
step two ,
insert employee data into employee table with the department id , find by department name in department table , if employee id already exists skip that record

use pod connection for database operations

// 2. Database Credentials (Host and Auth remain same, DB is dynamic)
$host = "10.3.13.87";
$user = "sa";
$pass = "sa@123";

ms-sql db connection using pdo -->