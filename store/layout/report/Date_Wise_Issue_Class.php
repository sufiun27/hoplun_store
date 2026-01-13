<?php
//Date_Wise_Issue_Class.php
class DateWiseReceive extends DbhReport
{
    public function generateReport($startDate, $endDate = null, $itemName = null)
{
    // ---- Start date REQUIRED ----
    if (empty($startDate)) {
        return [];
    }

    // ---- Date handling ----
    $startDate = (new DateTime($startDate))->format('Y-m-d 00:00:00');

    if (!empty($endDate)) {
        $endDate = (new DateTime($endDate))->format('Y-m-d 23:59:59');
    } else {
        $endDate = (new DateTime())->format('Y-m-d 23:59:59');
    }

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $section = $_SESSION['section'];

    // ---- Optional item filter ----
    $itemFilter = "";
    $params = [
        ':section'   => $section,
        ':startDate'=> $startDate,
        ':endDate'  => $endDate
    ];

    if (!empty($itemName)) {
        $itemFilter = " AND i.i_name LIKE :itemName ";
        $params[':itemName'] = "%$itemName%";
    }

    $sql = "
        SELECT 
            iss.is_po_no AS po_no, 
            d.d_name AS department, 
            iss.is_datetime AS issue_date, 
            c.c_name AS category, 
            i.i_name AS item, 
            i.i_manufactured_by AS brand, 
            i.i_size AS size, 
            ist.ist_price AS purchase_price, 
            iss.is_qty AS quantity, 
            i.i_unit AS units, 
            COALESCE(ist.total_price, 0) AS total_price
        FROM item_issue iss
        INNER JOIN employee e ON iss.e_id = e.e_id
        INNER JOIN department d ON e.d_id = d.d_id
        INNER JOIN item i ON iss.i_id = i.i_id
        INNER JOIN category_item c ON c.c_id = i.c_id
        INNER JOIN (
            SELECT 
                SUM(ist_qty * ist_price) AS total_price,
                is_id,
                AVG(ist_price) AS ist_price
            FROM item_issue_trac
            GROUP BY is_id
        ) ist ON ist.is_id = iss.is_id
        WHERE 
            i.section = :section
            AND iss.is_active = 1
            AND iss.is_datetime BETWEEN :startDate AND :endDate
            $itemFilter
        ORDER BY c.c_name, i.i_name
    ";

    $stmt = $this->connect()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


}
