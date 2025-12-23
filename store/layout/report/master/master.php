<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../../database.php';
$section = (string) $_SESSION['section'];
//type cust into string $section


$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];

// echo $start_date;
// echo "<br>";
// echo $end_date;

$report = new DbhReport();

///Connection close //////////////////////////
$sql = "SELECT d_id, d_name from department";
$departments = $report->getData($sql);

$sql = "SELECT category_item.c_id, category_item.c_name FROM category_item
WHERE section = '$section'";
$categories = $report->getData($sql);

function getinformation(int $i_id, string $section, string $start_date , string $end_date ){
    $report = new DbhReport();
    $sql =  "SELECT 
    subquery.d_id,
    subquery.d_name,
    subquery.c_name,
    subquery.i_name,
	SUM(subquery.is_qty) AS quantity, 
    SUM(subquery.total_price) / SUM(subquery.is_qty) AS avg_price,
    subquery.qty_balance
FROM (
    SELECT 
        i.i_name,
        i.i_id,
        c.c_name,
        iss.is_qty,
        ist.total_price AS total_price,
        d.d_name,
        d.d_id,
        b.qty_balance
    FROM item_issue iss 
    INNER JOIN item i ON iss.i_id = i.i_id 
    INNER JOIN employee e ON iss.e_id = e.e_id
    INNER JOIN department d ON d.d_id = e.d_id
    INNER JOIN category_item c ON i.c_id = c.c_id
    INNER JOIN balance b ON i.i_id = b.i_id 
    INNER JOIN (
        SELECT SUM(ist_qty * ist_price) AS total_price, is_id 
        FROM item_issue_trac 
        GROUP BY is_id
    ) ist ON ist.is_id = iss.is_id
    WHERE i.i_id = {$i_id} and i.section = '$section' AND CAST(iss.is_datetime AS DATE) BETWEEN '$start_date' AND '$end_date'
) AS subquery 
INNER JOIN view_item_issue vis ON vis.i_id = subquery.i_id 
GROUP BY subquery.d_id, subquery.d_name, subquery.c_name, subquery.i_name, subquery.qty_balance, subquery.i_id";
 $data = $report->getData($sql);
    return $data;
}




function getitem($c_id){
    $report = new DbhReport();
    $sql = "SELECT  [i_id],[i_name],[c_id] FROM item WHERE c_id = {$c_id}";
     
    $items = $report->getData($sql);
    return $items;
}

?>

<!doctype html>
<html lang="en">
<head>
    <title>Title</title>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">
</head>
<body>
<a href="../indexmaster.php" class="btn btn-dark">Back</a>
<h1>Hop Lun  <span><a href="export.php?start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>">
<i class="fas fa-file-export"></i> Export</a></span></h1>
    
    <table class="table">
        <thead>
            <tr>
                <?php
                
                //print_r($headers);
                echo "<th>Item</th>";
                foreach($departments as $department){
                    echo "<th>{$department['d_name']}</th>";
                }
                echo "<th>Total Qty</th>";
                echo "<th>Banance</th>";
                echo "<th>Avg Price</th>";
                echo "<th>Total Price</th>";
                ?>
            </tr>
        </thead>
        <tbody>
    
        <?php 
        $colno = 0;
        $intotal_price=0;
        foreach($categories as $category){

            $rowdata[$colno][] = $category['c_name'];
             echo "<tr class='bg-danger'> <td>
             {$category['c_name']}
             </td>
             </tr>";
            
            $items = getitem($category['c_id']);
            //iteration for all item under a category
            foreach($items as $item){
                echo "<tr>";
                echo "<td class='bg-success'>{$item['i_name']}</td>";
                $priceinfo= getinformation($item['i_id'], $_SESSION['section'], $start_date, $end_date);
                //iteration for all prince under a category and item - department wise 

                $totalquantity = 0;
                $avrageprice = 0;
                $totalprice = 0;

                foreach($departments as $department){

                    $found = false;
                    
                    foreach($priceinfo as $price){

                        if($price['d_id'] == $department['d_id']){
                            echo "<td class='bg-warning'>{$price['quantity']}</td>";
                            $found = true;
                            $totalquantity += $price['quantity'];  
                            $avrageprice = $price['avg_price'];
                            break; // Stop inner loop if condition is met
                        }
                    
                    }
                    
                    if (!$found) {
                        echo "<td>0</td>";
                    }
                    
                }
                echo "<td class='bg-info'>{$totalquantity}</td>";
                echo "<td class='bg-primary'>{$price['qty_balance']}</td>";
                echo "<td class='bg-secondary'>{$avrageprice}</td>";
                $totalprice = $totalquantity * $avrageprice;
                echo "<td class='bg-primary'>{$totalprice}</td>";
                $intotal_price += $totalprice;
                echo "</tr>";
                
            }
            
        }
         
         ?>
            <tr>
                <td>In Total </td>
                <td>
                    <?php echo  $intotal_price?>
                </td>
            </tr>
      </tbody>
       
        
    </table>

    
    
    
    


    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDzwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
</body>
</html>
