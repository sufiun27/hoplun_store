<?php

class DateWiseReceive extends DbhReport
{
    private function getSection() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['section'] ?? '';
    }

    public function DateWiseReceiveReport($startDate, $endDate)
    {
        $startDateTime = new DateTime($startDate);
        $endDateTime = new DateTime($endDate);
        
        $formattedStart = $startDateTime->format('Y-m-d H:i:s');
        $formattedEnd = $endDateTime->format('Y-m-d H:i:s');
        $section = $this->getSection();

        $sql = "SELECT
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
                FROM item_purchase AS ip
                INNER JOIN item AS i ON ip.i_id = i.i_id
                INNER JOIN category_item AS c ON c.c_id = i.c_id
                RIGHT JOIN tem_purchase_recive AS tpr ON ip.p_id = tpr.p_id
                WHERE i.section = ? AND ip.p_request_accept_datetime BETWEEN ? AND ?
                GROUP BY tpr.p_id, ip.p_po_no, c.c_name, i.i_name, i.i_size, i.i_unit, i.i_price, ip.p_unit_price, ip.p_req_qty, ip.p_request_accept_datetime
                ORDER BY ip.p_request_accept_datetime";

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$section, $formattedStart, $formattedEnd]);
        return $stmt->fetchAll();
    }

    public function DateWiseNameReceiveReport($itemName)
    {
        $section = $this->getSection();
        $searchTerm = "%$itemName%";

        $sql = "SELECT
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
                FROM item_purchase AS ip
                INNER JOIN item AS i ON ip.i_id = i.i_id
                INNER JOIN category_item AS c ON c.c_id = i.c_id
                RIGHT JOIN tem_purchase_recive AS tpr ON ip.p_id = tpr.p_id
                WHERE i.section = ? AND ip.p_po_no LIKE ?
                GROUP BY tpr.p_id, ip.p_po_no, c.c_name, i.i_name, i.i_size, i.i_unit, i.i_price, ip.p_unit_price, ip.p_req_qty, ip.p_request_accept_datetime
                ORDER BY ip.p_request_accept_datetime";

        $stmt = $this->connect()->prepare($sql);
        $stmt->execute([$section, $searchTerm]);
        return $stmt->fetchAll();
    }
}