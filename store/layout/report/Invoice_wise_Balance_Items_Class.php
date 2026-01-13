<?php

class DateWiseReceive extends DbhReport
{
    private function getSection()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['section'] ?? '';
    }

    /**
     * Date wise receive report
     * Start Date REQUIRED
     * End Date OPTIONAL
     * Item Name OPTIONAL
     */
    public function generateReport($startDate, $endDate = null, $itemName = null)
    {
        //echo "Generating Date Wise Receive Report...\n";
        if (empty($startDate)) {
            return [];
        }
    
        $startDate = (new DateTime($startDate))->format('Y-m-d 00:00:00');
        $endDate = $endDate
            ? (new DateTime($endDate))->format('Y-m-d 23:59:59')
            : (new DateTime())->format('Y-m-d 23:59:59');
    
        $section = $this->getSection();
    
        $itemFilter = "";
        $params = [
            ':section'   => $section,
            ':startDate'=> $startDate,
            ':endDate'  => $endDate
        ];
    
        if (!empty($itemName)) {
            $itemFilter = " AND (i.i_name LIKE :itemName1 OR ip.p_po_no LIKE :itemName2) ";
            $params[':itemName1'] = "%$itemName%";
            $params[':itemName2'] = "%$itemName%";
        }
    
        $sql = "
            SELECT
                ip.p_po_no AS po_no,
                c.c_name AS category,
                i.i_name AS item,
                i.i_size AS size,
                i.i_unit AS unit,
                i.i_price AS system_price,
                ip.p_unit_price AS purchase_price,
                ip.p_req_qty AS request_qty,
                SUM(tpr.p_recive_qty) AS receive_qty,
                ip.p_unit_price * SUM(tpr.p_recive_qty) AS cost,
                ip.p_request_accept_datetime AS accept_date
            FROM item_purchase ip
            INNER JOIN item i ON ip.i_id = i.i_id
            INNER JOIN category_item c ON c.c_id = i.c_id
            LEFT JOIN tem_purchase_recive tpr 
                ON ip.p_id = tpr.p_id
            WHERE 
                i.section = :section
                AND ip.p_request_accept_datetime BETWEEN :startDate AND :endDate
                $itemFilter
            GROUP BY 
                ip.p_po_no,
                c.c_name,
                i.i_name,
                i.i_size,
                i.i_unit,
                i.i_price,
                ip.p_unit_price,
                ip.p_req_qty,
                ip.p_request_accept_datetime
            ORDER BY ip.p_request_accept_datetime
        ";
    
        $stmt = $this->connect()->prepare($sql);
    
        if (!$stmt->execute($params)) {
            print_r($stmt->errorInfo()); // 🔥 THIS shows the real issue
            return [];
        }
    
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        //print_r($result); // For debugging purposes 
        return $result;
    }
    
}
