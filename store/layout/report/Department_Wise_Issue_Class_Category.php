<?php
//Department_Wise_Issue_Class_Category.php
class DateWiseReceive extends DbhReport
{
    public function ReceiveReport($department,$item_name)
    {
        
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
$section=$_SESSION['section'];


        
        $sql = "
       SELECT 
    subquery.d_name, 
    subquery.c_name, 
    
    subquery.i_name,
    SUM(subquery.is_qty) AS quantity, 
    SUM(subquery.total_price) / SUM(subquery.is_qty) AS avg_price, 
    subquery.i_price AS system_price,
    SUM(subquery.total_price) AS total_price,
    CAST((SUM(subquery.total_price) * 100 / vis.total_item_issue_price) AS DECIMAL(10, 2)) AS percentage



FROM (
    SELECT 
        iss.is_id, 
        i.i_name, 
        i.i_id, 
        c.c_name, 
        c.c_id, 
        i.i_size, 
        i.i_unit, 
        i.i_price,
        iss.is_qty, 
        ist.total_price AS total_price, 
        iss.is_datetime, 
        iss.is_item_issue_by, 
        e.e_com_id, 
        e.e_name, 
        d.d_name, 
        d.d_id
    FROM item_issue iss 
    INNER JOIN item i ON iss.i_id = i.i_id 
    INNER JOIN employee e ON iss.e_id = e.e_id
    INNER JOIN department d ON d.d_id = e.d_id
    INNER JOIN category_item c ON i.c_id = c.c_id
    INNER JOIN (
        SELECT SUM(ist_qty * ist_price) AS total_price, is_id 
        FROM item_issue_trac 
        GROUP BY is_id
    ) ist ON ist.is_id = iss.is_id
    WHERE i.section ='$section' AND c.c_name LIKE '%" . $item_name . "%' AND d.d_name LIKE '%" . $department . "%'
) AS subquery 
INNER JOIN (
    SELECT 
        item.c_id, 
        SUM(view_item_issue.total_item_issue) AS total_item_issue, 
        SUM(view_item_issue.total_item_issue_price) AS total_item_issue_price
    FROM view_item_issue 
    INNER JOIN item ON item.i_id = view_item_issue.i_id 
    GROUP BY item.c_id
) vis ON vis.c_id = subquery.c_id 
GROUP BY subquery.d_name, subquery.c_name, subquery.c_id,subquery.i_price,vis.total_item_issue_price,subquery.i_name;
      
";

        $stmt = $this->connect()->query($sql);

        if (!$stmt) {
            return [];
        } else {
            $result = $stmt->fetchAll();
            return $result;
        }
    }

    public function DateWiseNameReceiveReport(
        $start_date,
        $end_date = null,
        $item_name = null,
        $department = null
    ) {
        // ---- START DATE REQUIRED ----
        if (empty($start_date)) {
            return [];
        }
    
        // ---- DATE HANDLING ----
        $start_date = (new DateTime($start_date))->format('Y-m-d 00:00:00');
    
        if (!empty($end_date)) {
            $end_date = (new DateTime($end_date))->format('Y-m-d 23:59:59');
        } else {
            $end_date = (new DateTime())->format('Y-m-d 23:59:59');
        }
    
        // ---- SESSION ----
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $section = $_SESSION['section'] ?? '';
    
        // ---- DEFAULT FILTERS ----
        $item_name  = $item_name  ?? '';
        $department = $department ?? '';
    
        // ---- SQL SERVER QUERY ----
        $sql = "
        SELECT 
            sq.d_name,
            sq.c_name,
            sq.i_name,
            SUM(sq.is_qty) AS quantity,
    
            CASE 
                WHEN SUM(sq.is_qty) = 0 THEN 0
                ELSE SUM(sq.total_price) / SUM(sq.is_qty)
            END AS avg_price,
    
            sq.i_price AS system_price,
            SUM(sq.total_price) AS total_price,
    
            CAST(
                (SUM(sq.total_price) * 100.0) / NULLIF(vis.total_item_issue_price, 0)
                AS DECIMAL(10,2)
            ) AS percentage
    
        FROM (
            SELECT 
                iss.is_id,
                i.i_id,
                i.i_name,
                i.i_price,
                iss.is_qty,
                ist.total_price,
                d.d_name,
                d.d_id,
                c.c_name,
                c.c_id
            FROM item_issue iss
            INNER JOIN item i ON iss.i_id = i.i_id
            INNER JOIN employee e ON iss.e_id = e.e_id
            INNER JOIN department d ON e.d_id = d.d_id
            INNER JOIN category_item c ON i.c_id = c.c_id
            INNER JOIN (
                SELECT 
                    is_id,
                    SUM(ist_qty * ist_price) AS total_price
                FROM item_issue_trac
                GROUP BY is_id
            ) ist ON ist.is_id = iss.is_id
            WHERE 
                i.section = :section
                AND c.c_name LIKE :item_name
                AND d.d_name LIKE :department
                AND iss.is_datetime BETWEEN :start_date AND :end_date
        ) sq
        INNER JOIN (
            SELECT 
                item.c_id,
                SUM(v.total_item_issue_price) AS total_item_issue_price
            FROM view_item_issue v
            INNER JOIN item ON item.i_id = v.i_id
            GROUP BY item.c_id
        ) vis ON vis.c_id = sq.c_id
    
        GROUP BY 
            sq.d_name,
            sq.c_name,
            sq.c_id,
            sq.i_name,
            sq.i_price,
            vis.total_item_issue_price
        ";
    
        // ---- PREPARE & EXECUTE ----
        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([
            ':section'     => $section,
            ':item_name'   => '%' . $item_name . '%',
            ':department'  => '%' . $department . '%',
            ':start_date'  => $start_date,
            ':end_date'    => $end_date
        ]);
    
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    

    public function DepartmentsName()
    {
        $sql = "SELECT d_name, d_id From department";

        $stmt = $this->connect()->query($sql);

        if (!$stmt) {
            return [];
        } else {
            $result = $stmt->fetchAll();
            return $result;
        }
    }

    public function ItemsName()
    {
        session_start();
        $section=$_SESSION['section'];
        $sql = "SELECT c_name, c_id From category_item where section ='$section'";

        $stmt = $this->connect()->query($sql);

        if (!$stmt) {
            return [];
        } else {
            $result = $stmt->fetchAll();
            return $result;
        }
    }
}
