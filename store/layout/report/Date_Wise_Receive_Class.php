<?php
class DateWiseReceive extends DbhReport
{
    public function generateReport($startDate, $endDate = null, $itemName = null)
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    $section = $_SESSION['section'];

    // ---- Start Date (REQUIRED) ----
    if (empty($startDate)) {
        return []; // or throw exception
    }

    // Convert to yyyy-mm-dd
    $startDateTime = new DateTime($startDate);
    $startDate = $startDateTime->format('Y-m-d 00:00:00');

    // ---- End Date (OPTIONAL → today) ----
    if (!empty($endDate)) {
        $endDateTime = new DateTime($endDate);
    } else {
        $endDateTime = new DateTime(); // today
    }
    $endDate = $endDateTime->format('Y-m-d 23:59:59');

    // ---- Item Name (OPTIONAL) ----
    $itemFilter = "";
    if (!empty($itemName)) {
        $itemName = str_replace("'", "''", $itemName); // basic SQL safety
        $itemFilter = " AND i.i_name LIKE '%$itemName%' ";
    }

    $sql = "
        SELECT 
            ip.p_po_no AS po_no,
            s.s_name AS supplier,
            r.p_recive_datetime AS date,
            c.c_name AS category,
            i.i_name AS item,
            i.i_manufactured_by AS brand,
            i.i_size AS size,
            i.i_price AS item_price,
            ip.p_unit_price AS purchase_price,
            r.p_recive_qty AS quantity,
            i.i_unit AS units,
            (r.p_recive_qty * ip.p_unit_price) AS total_price,
            CASE 
                WHEN r.cash1_creadit0 = 1 THEN 'Cash'
                WHEN r.cash1_creadit0 = 0 THEN 'Credit'
                ELSE 'Unknown'
            END AS cash1_creadit0
        FROM item_purchase ip
        LEFT JOIN tem_purchase_recive r ON r.p_id = ip.p_id
        INNER JOIN supplier s ON s.s_id = ip.s_id
        INNER JOIN item i ON i.i_id = ip.i_id
        INNER JOIN category_item c ON c.c_id = i.c_id
        WHERE 
            i.section = '$section'
            $itemFilter
            AND r.p_recive_datetime BETWEEN '$startDate' AND '$endDate'
        ORDER BY c.c_name, i.i_name
    ";

    $stmt = $this->connect()->query($sql);

    if (!$stmt) {
        return [];
    }

    return $stmt->fetchAll();
}

}
