<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

include '../database.php';

$query = $_POST['query'] ?? '';

$sql = "SELECT i_id, i_name, i_price
        FROM item
        WHERE i_active = 1
        AND (i_name LIKE ? OR i_manufactured_by LIKE ?)";

$stmt = $conn->prepare($sql);

$search = "%" . $query . "%";
$stmt->execute([$search, $search]);

$data = "";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    // Escape single quotes for JavaScript safety
    $i_name  = htmlspecialchars($row['i_name'], ENT_QUOTES);
    $i_price = htmlspecialchars($row['i_price'], ENT_QUOTES);

    $data .= "
        <a href='#' class='list-group-item list-group-item-action'
        onclick=\"addItem('{$row['i_id']}', '{$i_name}', '{$i_price}')\">
            {$row['i_name']} - {$row['i_price']}
        </a>";
}

echo $data !== "" 
    ? $data 
    : "<p class='text-danger p-2'>No item found</p>";
