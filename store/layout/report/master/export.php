<?php
// Set the maximum execution time to 300 seconds
set_time_limit(300);



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class DbInfo {
    //make change as per hosting
    private $host = "BDAPPSS02V\SQLEXPRESS";
    private $user = "sa";
    private $pass = "sa@123";
    
    public function __construct() {
        // Empty constructor
    }
    public function getHost() {
        return $this->host;
    }

    public function getUser() {
        return $this->user;
    }
    
    public function getPass() {
        return $this->pass;
    }
}

class DbhReport extends DbInfo {
    private $db_name;

    public function __construct() {
        parent::__construct(); // Call the constructor of the parent class to initialize host, user, and pass.
        $this->db_name = $_SESSION['company'];
    }

    protected function connect() {
        $dsn = "sqlsrv:Server=" . $this->getHost() . ";Database=" . $this->db_name;
        try {
            $pdo = new PDO($dsn, $this->getUser(), $this->getPass());
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            return $pdo;
        } catch (PDOException $e) {
            // Handle database connection error here
            echo "Connection failed: " . $e->getMessage();
            return null;
        }
    }

    public function getData(string $sql) {
        
        $stmt = $this->connect()->query($sql);
        $results = $stmt->fetchAll();
        return $results;
    }
}
$section = (string) $_SESSION['section'];
//type cust into string $section

$start_date = $_GET['start_date'];
$end_date = $_GET['end_date'];

$report = new DbhReport();

///Connection close //////////////////////////
$sql = "SELECT d_id, d_name from department";
$departments = $report->getData($sql);

$sql = "SELECT category_item.c_id, category_item.c_name FROM category_item
WHERE section = '$section'";
$categories = $report->getData($sql);

function getinformation(int $i_id, string $section, string $start_date , string $end_date){
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


try {
    require '../vendor/autoload.php';

// Export the report to an Excel file
$spreadsheet = new PhpOffice\PhpSpreadsheet\Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
} catch (Exception $e) {
    echo 'An error occurred block 1: ' . $e->getMessage();
}
?>

<?php
$intotal_price=0;
                try{
                    $headers = array();
                $headers[] = "Item";
                foreach($departments as $department){
                    $headers[] = $department['d_name'];
                }
                $headers[] = "Total Qty";
                $headers[] = "Banance";
                $headers[] = "Avg Price";
                $headers[] = "Total Price";
               // print_r($headers);

                $rowNumber = 1;
                $columnNumber = 1;
                foreach ($headers as $header) {
                    $sheet->setCellValueByColumnAndRow($columnNumber, $rowNumber, $header);
                    $columnNumber++;
                }

                
            } catch (Exception $e) {
                echo 'An error occurred: ' . $e->getMessage();
            }
?>

        <?php 
        $rowNumber = 2;
        
            //print_r($categories);
        foreach($categories as $category){
            $columnNumber=1;
            
            $cellData = $category['c_name'];
            
            //add colour green fontsize 16
            $sheet->setCellValueByColumnAndRow($columnNumber, $rowNumber, $cellData); 
            // Apply font size and color to the specific cell
            $cellCoordinate = $sheet->getCellByColumnAndRow($columnNumber, $rowNumber)->getCoordinate();
            $sheet->getStyle($cellCoordinate)->getFont()->setSize(16);
            $sheet->getStyle($cellCoordinate)->getFont()->setBold(true);
            $sheet->getStyle($cellCoordinate)->getFont()->getColor()->setARGB('FF008000');
            

           // echo "<br> <tr class='bg-danger'> <td>
            // {$category['c_name']}
            //</td>
            // </tr> ";
             $rowNumber++;

             $items = getitem($category['c_id']);

             foreach($items as $item){
                $columnNumber=1;

                //echo "<br>";
                //echo "<td class='bg-success'>{$item['i_name']}</td>";
                $cellData = $item['i_name'];
                $sheet->setCellValueByColumnAndRow($columnNumber, $rowNumber, $cellData);
                
                
                
                $priceinfo= getinformation($item['i_id'], $_SESSION['section'], $start_date, $end_date);
                
                $totalquantity = 0;
                $avrageprice = 0;
                $totalprice = 0;

                $columnNumber = 2;//transfer

                foreach($departments as $department){
                    

                    $found = false;
                    
                    foreach($priceinfo as $price){

                        if($price['d_id'] == $department['d_id']){
                           // echo "<td class='bg-warning'>{$price['quantity']}</td>";
                           
                            $cellData = $price['quantity'];
                            $sheet->setCellValueByColumnAndRow($columnNumber, $rowNumber, $cellData);
                            $columnNumber++;


                            $found = true;
                            $totalquantity += $price['quantity'];  
                            $avrageprice = $price['avg_price'];
                            break; // Stop inner loop if condition is met
                        }
                    
                    }
                    
                    if (!$found) {
                       // echo "<td>0</td>";

                            $cellData = 0;
                            $sheet->setCellValueByColumnAndRow($columnNumber, $rowNumber, $cellData);
                            $columnNumber++;
                    }
                    
                }
               // echo "<td class='bg-info'>{$totalquantity}</td>";
                $cellData = $totalquantity;
                $sheet->setCellValueByColumnAndRow($columnNumber, $rowNumber, $cellData);
                $columnNumber++;
               // echo "<td class='bg-primary'>{$price['qty_balance']}</td>";
                $cellData = $price['qty_balance'];
                $sheet->setCellValueByColumnAndRow($columnNumber, $rowNumber, $cellData);
                $columnNumber++;
               // echo "<td class='bg-secondary'>{$avrageprice}</td>";
                $cellData = $avrageprice;
                $sheet->setCellValueByColumnAndRow($columnNumber, $rowNumber, $cellData);
                $columnNumber++;
                $totalprice = $totalquantity * $avrageprice;
               // echo "<td class='bg-primary'>{$totalprice}</td>";
                $cellData = $totalprice;
                $sheet->setCellValueByColumnAndRow($columnNumber, $rowNumber, $cellData);
                $columnNumber++;

                $intotal_price += $totalprice;
                
               // echo "</tr>";
                
                $rowNumber++; //take it below
            }
            

           
            
        }//catagery
        $columnNumber--;
        $columnNumber--;
        $cellData = 'SUM';
        $sheet->setCellValueByColumnAndRow($columnNumber, $rowNumber, $cellData);

        $columnNumber++;
        
        $cellData = $intotal_price;
        $sheet->setCellValueByColumnAndRow($columnNumber, $rowNumber, $cellData);
        //$columnNumber++;
         
         ?>
    
      
    <?php
    // Save the Excel file
    $writer = new PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $filename = 'Master.xlsx';
    $writer->save($filename);
   //echo 'Exported to Excel file successfully!';
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
    <span><a href="<?php echo $filename; ?>" class="btn btn-success"><i class="fas fa-file-export"></i> Export</a></span>
      
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
  </body>
</html>
