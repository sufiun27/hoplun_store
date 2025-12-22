<?php
if(session_status() == PHP_SESSION_NONE){
    session_start();
}
include '../database.php';

$input = $_POST['input'] ?? '';
$currentCategory = $_POST['current'] ?? '';

if($input !== '') {
    $search = "%$input%";
    $stmt = $conn->prepare("SELECT * FROM category_item WHERE c_name LIKE :search ORDER BY c_name ");
    $stmt->bindParam(':search', $search);
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($categories as $cat) {
        $checked = ($cat['c_id'] == $currentCategory) ? 'checked' : '';
        echo '<div class="search-item-hover p-2 border-bottom" style="cursor:pointer;">
                <input type="radio" name="c_id_radio" value="'. $cat['c_id'] .'" ' . $checked . '> ' 
                . htmlspecialchars($cat['c_name']) . '
              </div>';
    }
}
?>
