<?php

class DateWiseReceive extends DbhReport
{


    public function DateWiseNameReceiveReport(
        $start_date,
        $end_date = null,
        $item_name = null,
        $department = null
    ) {
        //echo "Hello from DateWiseNameReceiveReport";
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

        //echo "Section: $section, Item Name: $item_name, Department: $department, Start Date: $start_date, End Date: $end_date";
    
        // ---- SQL SERVER QUERY ----
        $sql = "
        SELECT 
            sq.d_name,
            sq.c_name,
            sq.i_name,
            sq.i_unit,
            sq.i_size,
            SUM(sq.is_qty) AS quantity,
            sq.i_price AS system_price,
            CASE 
                WHEN SUM(sq.is_qty) = 0 THEN 0
                ELSE SUM(sq.total_price) / SUM(sq.is_qty)
            END AS avg_price,
            SUM(sq.total_price) AS total_price,
            ROUND(
                (SUM(sq.total_price) * 100.0) / NULLIF(vis.total_item_issue_price, 0),
                2
            ) AS percentage
        FROM (
            SELECT 
                iss.is_id,
                i.i_id,
                i.i_name,
                i.i_unit,
                i.i_size,
                i.i_price,
                iss.is_qty,
                ist.total_price,
                iss.is_datetime,
                d.d_id,
                d.d_name,
                c.c_name
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
        INNER JOIN view_item_issue vis ON vis.i_id = sq.i_id
        GROUP BY 
            sq.d_id,
            sq.i_id,
            sq.d_name,
            sq.c_name,
            sq.i_name,
            sq.i_unit,
            sq.i_size,
            sq.i_price,
            vis.total_item_issue_price
        ";
    
        // ---- PREPARE & BIND (SQL SERVER SAFE) ----
        $stmt = $this->connect()->prepare($sql);
        $stmt->bindValue(':section', $section);
        $stmt->bindValue(':item_name', '%' . $item_name . '%');
        $stmt->bindValue(':department', '%' . $department . '%');
        $stmt->bindValue(':start_date', $start_date);
        $stmt->bindValue(':end_date', $end_date);
    
        if (!$stmt->execute()) {
            return [];
        }

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        //print_r($data);
        return $data;
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
