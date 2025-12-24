

<?php
if (session_status() === PHP_SESSION_NONE) {
    // Start the session
    session_start();
    // Perform any other session initialization or setup here
}

// ✅ Auth check
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true) {

    // ✅ Safer default redirect
    $redirectUrl = $_SESSION['base_url'] ?? 'https://www.google.com';

    // ✅ Force valid URL format
    if (!preg_match('#^https?://#', $redirectUrl)) {
        $redirectUrl = 'http://' . $redirectUrl;
    }

    header("Location: $redirectUrl");
    exit();
}

// ✅ Safety check for dynamic database name
if (!isset($_SESSION['company']) || empty($_SESSION['company'])) {
    die("❌ No database selected in session.");
}


// ✅ Set timezone safely
date_default_timezone_set('Asia/Dhaka');

// ✅ Current datetime
$defaultDateTime = date('Y-m-d H:i:s');


include_once '../database.php';

////DB connection////////////


//TODO: CSRF token authentication class (commented out for now) || one user login protection
// class AuthCsrf extends Dbh
// {
//     public function authenticate($token, $uid)
//     {
//         $pdo = $this->connect();
//         $query = "SELECT csrf FROM user_token WHERE u_id = :uid ";
//         $stmt = $pdo->prepare($query);
//         $stmt->bindParam(':uid', $uid);
//         $stmt->execute();
//         //echo $stmt->errorInfo();
//         $row = $stmt->fetch();
//         if($row['csrf'] == $token)
//         {
//             return true;
//         }
//         else
//         {
//             return false;
//         }



//     }
// }

// $token = $_SESSION['csrf_token'];
// $uid = $_SESSION['uid'];
// $auth = new AuthCsrf();
// if ($auth->authenticate($token, $uid)) {
//     $_SESSION['csrf_token']=true;
// } else {
//     header("Location: http:10.3.13.87/storehl/");
//     $_SESSION['']=false;
// }


// ?>





<!--################################################################################################################################################################-->





<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
        <meta name="description" content="inventory management system" />
        <meta name="author" content="Abu Sufiun || email: abusufiun27@gmail.com" />
        <title>Hop Lun</title>
        <link href="https://cdn.jsdelivr.net/npm/simple-datatables@7.1.2/dist/style.min.css" rel="stylesheet" />
        <link href="../css/styles.css" rel="stylesheet" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js" crossorigin="anonymous"></script>
        <!-- bootstrap CDN-->
        <!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

<!-- Optional theme -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

<!-- Latest compiled and minified JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@3.3.7/dist/js/bootstrap.min.js" integrity="sha384-Tc5IQib027qvyjSMfHjOMaLkfuWVxZxUPnCJA7l2mCWNIpG9mGCD8wGNIcPD7Txa" crossorigin="anonymous"></script>
    
<style>

</style>

</head> 
    <!-- <body class="sb-nav-fixed" style="background: rgba(245, 39, 183, 0.20);"> -->
    <body class="sb-nav-fixed" 
    style=" background-image: url('http://drive.google.com/uc?export=view&id=1AkCiOQwLURrVukCTWFdhQ6vJv-ihIZx_'); 
    background-color: rgb(255, 255, 255); 
    background-size: cover; 
    height: 100vh; 
        width: 100vw;
    background-position: center;">
        <nav class="sb-topnav navbar navbar-expand navbar-light bg-white">
    <!-- Navbar Brand-->
    <?php
    $default_url=$_SESSION['base_url'];
    $extra_url='store/layout/start/';
    ?>
    <div style="background-color: rgb(255, 255, 255); border-radius: 5px; text-align: center; padding: 5px 10px;">
        <a class="navbar-brand ps-3 text-dark" href="<?php echo "http://$default_url/$extra_url"; ?>">
            <b>Hop Lun</b>
        </a>
    </div>
    
    <!-- Sidebar Toggle-->
    <button class="btn btn-link btn-sm order-1 order-lg-0 me-4 me-lg-0 text-dark" id="sidebarToggle" href="#!">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Navbar User Info -->
    <form class="d-none d-md-inline-block form-inline ms-auto me-0 me-md-3 my-2 my-md-0">
        <div class="input-group">
            <h4 class="text-dark"> <!-- changed from text-white to text-dark -->
                <?php echo $_SESSION['username']; ?> ( <?php echo $_SESSION['company']; ?> )
            </h4>
        </div>
    </form>

    <!-- Navbar Dropdown -->
    <ul class="navbar-nav ms-auto ms-md-0 me-3 me-lg-4">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle text-dark" id="navbarDropdown" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-user fa-fw"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                <li><hr class="dropdown-divider" /></li>
                <li>
                    <a class="dropdown-item" href="http://<?php echo $_SESSION['base_url'];?>/logout/logout.php">
                        Logout
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</nav>

<div id="layoutSidenav">
    <div id="layoutSidenav_nav">
        <nav class="sb-sidenav accordion sb-sidenav-white " id="sidenavAccordion" style="background-color:rgb(221, 240, 245);">
            <div class="sb-sidenav-menu">
                <div class="nav">
                             <!-- Core Section -->
                    <div class="sb-sidenav-menu-heading text-dark">Core</div>
                    <a class="nav-link text-dark" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/start/">
                        <div class="sb-nav-link-icon"><i class="fas fa-tachometer-alt"></i></div>
                        Dashboard
                    </a>
                             <!-- Section + Mode Buttons -->
                    <div class="sb-sidenav-menu-heading">
                        <span class="btn btn-success btn-xs"><?php echo $_SESSION['section'] ?></span>
                        <span>Mode</span>
                        <?php if($_SESSION['role']=='super_admin' || $_SESSION['role']=='group_admin'){ ?>
                            <a class="btn btn-info btn-xs" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/start/change_role.php?section=GEN111">GEN</a>
                            <a class="btn btn-info btn-xs" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/start/change_role.php?section=ELE111">MEC</a>
                            <a class="btn btn-info btn-xs" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/start/change_role.php?section=ELE222">ELE</a>
                        <?php } ?>
                    </div>
                             
                            
                                    <?php
                                     if($_SESSION['role']=='super_admin'){
                                        //echo $_SESSION['username'];
                                        echo '
                                        <a class="btn btn-info" href="http://'.$_SESSION['base_url'].'/super_admin/index.php">Admin Panel</a>
                                        ';
                                    }
                                    ?>

                           
                           
                            <?php if($_SESSION['role']=='admin'||$_SESSION['role']=='super_admin' || $_SESSION['role']=='group_admin'){ ?>
                            <!--add customer///////////////////////////////////////////--->
                            <!--add customer///////////////////////////////////////////-->
<a class="nav-link collapsed text-dark text-decoration-none" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts" aria-expanded="false" aria-controls="collapseLayouts">
    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
    Registration
    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
</a>
<div class="collapse" id="collapseLayouts" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
    <nav id="add_customer" class="sb-sidenav-menu-nested nav">
        <a id="collapseLayouts_list" class="nav-link text-dark text-decoration-none" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/add_customer/adduser_list.php">List Emp</a>
        <a id="collapseLayouts_department" class="nav-link text-dark text-decoration-none" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/add_customer/adduser_Department_list.php">Departments</a>
    </nav>
</div>
                                <?php } ?>
                                
                            <!--add suppliers////////////////////////////////////////-->
<a class="nav-link collapsed text-dark text-decoration-none" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts0" aria-expanded="false" aria-controls="collapseLayouts0">
    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
    Supplier
    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
</a>
<div class="collapse" id="collapseLayouts0" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
    <nav id="add_customer" class="sb-sidenav-menu-nested nav">
        <a id="collapseLayouts0_search" class="nav-link text-dark text-decoration-none" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/supplier/supplier_search.php">List</a>
        <!-- <a id="collapseLayouts0_add" class="nav-link text-dark text-decoration-none" href="http://<?php //echo $_SESSION['base_url'];?>/store/layout/supplier/supplier_add.php">Add</a>
        <a id="collapseLayouts0_list" class="nav-link text-dark text-decoration-none" href="http://<?php //echo $_SESSION['base_url'];?>/store/layout/supplier/supplier_list.php">List</a> -->
    </nav>
</div>

<!--product section-->  
<a class="nav-link collapsed text-dark text-decoration-none" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts1" aria-expanded="false" aria-controls="collapseLayouts1">
    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
    Product
    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
</a>
<div class="collapse" id="collapseLayouts1" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
    <nav class="sb-sidenav-menu-nested nav">
        <!-- <a id="collapseLayouts1_search" class="nav-link text-dark text-decoration-none" href="http://<?php //echo $_SESSION['base_url'];?>/store/layout/product/Product_search.php">Search</a> -->
        <!-- <a id="collapseLayouts1_add" class="nav-link text-dark text-decoration-none" href="http://<?php //echo $_SESSION['base_url'];?>/store/layout/product/product_add.php">Add</a> -->
        <a id="collapseLayouts1_list" class="nav-link text-dark text-decoration-none" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/product/product_list.php">List</a>
        <a id="collapseLayouts1_stock_out_list" class="nav-link text-dark text-decoration-none" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/product/Product_stock_out_list.php">Stock Out List</a>
        <a id="collapseLayouts1_add_category" class="nav-link text-dark text-decoration-none" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/product/product_add_catagory_1.php"> Category</a>
        <!-- <a id="collapseLayouts1_category" class="nav-link text-dark text-decoration-none" href="http://<?php // echo $_SESSION['base_url'];?>/store/layout/product/Product_category.php">Category</a> -->
    </nav>
</div>


                           <!--admin can only access this portion -->
<?php
if ($_SESSION['role'] == 'admin'){
    echo '<!-----Admin can only access this portion --------------------------------------------------------------------------------->';
}
?>

<!-------------------------------------------------------------------------------->
<a class="nav-link collapsed text-dark text-decoration-none" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts2" aria-expanded="false" aria-controls="collapseLayouts2">
    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
    Purchase Product
    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
</a>
<div class="collapse" id="collapseLayouts2" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
    <nav class="sb-sidenav-menu-nested nav">
        <a id="collapseLayouts2_add" class="nav-link text-dark text-decoration-none" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/purchase_product/purchase_item_process.php">Purchase</a>
        <?php
        $defaultEndDate = date('Y-m-d');
        $defaultStartDate = date('Y-m-d', strtotime('-15 days'));
        ?>
        <a id="collapseLayouts2_list" class="nav-link text-dark text-decoration-none" href="http://<?php echo $_SESSION['base_url']; ?>/store/layout/purchase_product/purchase_list.php?page=1&section=<?php echo $_SESSION['section']; ?>&startDate=<?php echo $defaultStartDate; ?>&endDate=<?php echo $defaultEndDate; ?>">Purchase List</a>
        <a id="collapseLayouts2_return" class="nav-link text-dark text-decoration-none" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/purchase_product/return_list.php">Return List</a>
    </nav>
</div>
<!-------------------------------------------------------------------------------->
<a class="nav-link collapsed text-dark text-decoration-none" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts3" aria-expanded="false" aria-controls="collapseLayouts3">
    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
    Issue Item
    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
</a>
<div class="collapse" id="collapseLayouts3" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
    <nav class="sb-sidenav-menu-nested nav">
        <a id="collapseLayouts3_add" class="nav-link text-dark text-decoration-none" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/issue_item/issue_iteam.php">Issue</a>
        <a id="collapseLayouts3_list" class="nav-link text-dark text-decoration-none" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/issue_item/issue_list.php">Issue List</a>
    </nav>
</div>
<!-------------------------------------------------------------------------------->
<?php if ($_SESSION['role'] == 'super_admin_null'){?>
<a class="nav-link collapsed text-dark text-decoration-none" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts4" aria-expanded="false" aria-controls="collapseLayouts4">
    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
    Stock Transfer
    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
</a>
<div class="collapse" id="collapseLayouts4" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
    <nav class="sb-sidenav-menu-nested nav">
        <a id="collapseLayouts4_add" class="nav-link text-dark text-decoration-none" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/stock_transfer/issue.php">Issue</a>
        <a id="collapseLayouts4_list" class="nav-link text-dark text-decoration-none" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/stock_transfer/issue_list.php">Issue List</a>
        <a id="collapseLayouts4_search" class="nav-link text-dark text-decoration-none" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/stock_transfer/issue_search.php">Search</a>
    </nav>
</div>
<?php } ?>
<!-------------------------------------------------------------------------------->
<?php if ($_SESSION['role'] == 'admin' || $_SESSION['role'] == 'super_admin' || $_SESSION['role']=='group_admin'){?>
<a class="nav-link collapsed text-dark text-decoration-none" href="#" data-bs-toggle="collapse" data-bs-target="#collapseLayouts5" aria-expanded="false" aria-controls="collapseLayouts5">
    <div class="sb-nav-link-icon"><i class="fas fa-columns"></i></div>
    Item Return
    <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
</a>
<div class="collapse" id="collapseLayouts5" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordion">
    <nav class="sb-sidenav-menu-nested nav">
        <a id="collapseLayouts5_add" class="nav-link text-dark text-decoration-none" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/item_return/issue.php">Return Issue Items</a>
        <a id="collapseLayouts5_list" class="nav-link text-dark text-decoration-none" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/item_return/return_list.php">Return List</a>
    </nav>
</div>
<?php } ?>

                            <!-------------------------------------------------------------------------------->

                            
                            
                            <!--exit add customer portin-->
                            <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#collapsePages_report" aria-expanded="false" aria-controls="collapsePages_report">
                                <div class="sb-nav-link-icon"><i class="fas fa-book-open"></i></div>
                                Report
                                <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                            </a>
                            <div class="collapse" id="collapsePages_report" aria-labelledby="headingTwo" data-bs-parent="#sidenavAccordion">
                                <nav class="sb-sidenav-menu-nested nav accordion" id="sidenavAccordionPages">
                                    <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#pagesCollapseStoreReport" aria-expanded="false" aria-controls="pagesCollapseStoreReport">
                                        Store
                                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                                    </a>
                                    <div class="collapse" id="pagesCollapseStoreReport" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordionPages">
                                        <nav class="sb-sidenav-menu-nested nav">
                                            <a id="dateWiseRecive" class="nav-link" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/report/Date_Wise_Receive.php">Date Wise Receive</a>
                                            <a id="dateWiseIssue" class="nav-link " href="http://<?php echo $_SESSION['base_url'];?>/store/layout/report/Date_Wise_Issue.php">Date Wise Issue</a>
                                            <a id="Invoice_wise_Balance_Items" class="nav-link" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/report/Invoice_wise_Balance_Items.php">Invoice Wise Balance Items (Purchase)</a>
                                            <a id="Balance_Items" class="nav-link" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/report/Balance_Items.php">Balance Items</a>
                                        </nav>
                                    </div>
                                    <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#pagesCollapseError" aria-expanded="false" aria-controls="pagesCollapseError">
                                        Department
                                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                                    </a>
                                    <div class="collapse" id="pagesCollapseError" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordionPages">
                                        <nav class="sb-sidenav-menu-nested nav">
                                            <a id="Department_Wise_Issue" class="nav-link" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/report/Department_Wise_Issue.php">Department Wise (Item) Cost</a>
                                            <a id="Department_Wise_Issue_Category" class="nav-link" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/report/Department_Wise_Issue_Category.php">Department Wise (Category) Cost</a>

                                        </nav>
                                    </div>
                                    
                                    <a class="nav-link collapsed" href="#" data-bs-toggle="collapse" data-bs-target="#mastera" aria-expanded="false" aria-controls="pagesCollapseError">
                                        Master
                                        <div class="sb-sidenav-collapse-arrow"><i class="fas fa-angle-down"></i></div>
                                    </a>
                                    <div class="collapse" id="mastera" aria-labelledby="headingOne" data-bs-parent="#sidenavAccordionPages">
                                        <nav class="sb-sidenav-menu-nested nav">
                                            <a id="Master" class="nav-link" href="http://<?php echo $_SESSION['base_url'];?>/store/layout/report/indexmaster.php">Master</a>
                                            

                                        </nav>
                                    </div>

                                    

                                    
                                </nav>
                            </div>

<!--                            <div class="sb-sidenav-menu-heading">Addons</div>-->
<!--                            <a class="nav-link" href="http://--><?php //echo $_SESSION['base_url'];?><!--/store/layout/chart/chart.php">-->
<!--                                <div class="sb-nav-link-icon"><i class="fas fa-chart-area"></i></div>-->
<!--                                Charts-->
<!--                            </a>-->
                            
                        </div>
                    </div>
                    <div class="sb-sidenav-footer ">
                        <div class="small">Log in as: <?php echo $_SESSION['role']."(".$_SESSION['email'].")" ;?></div>
                        Inventory Management System
                    </div>
                </nav>
            </div>
<!--################################################################################################################################################################-->
<div id="layoutSidenav_content">
    <main>

    <?php
// Show ERROR message
if (isset($_GET['error'])) {
    $error_message = trim(stripslashes($_GET['error']), '"'); // clean message
    echo '<div id="errorAlert" style="
            background-color: #f8d7da; 
            color: #721c24; 
            padding: 15px 20px; 
            border: 1px solid #f5c6cb; 
            border-radius: 5px; 
            margin: 10px 0;
            font-family: Arial, sans-serif;
            position: relative;
            transition: opacity 0.5s ease;
        ">
        <strong>Error!</strong> ' . htmlspecialchars($error_message) . '
        <span style="
            position: absolute;
            top: 5px;
            right: 10px;
            cursor: pointer;
            font-weight: bold;
            font-size: 18px;
        " onclick="this.parentElement.style.display=\'none\';">&times;</span>
    </div>

    <script>
        // Auto-hide error after 5 seconds
        setTimeout(function() {
            var alert = document.getElementById("errorAlert");
            if(alert){
                alert.style.opacity = "0";
                setTimeout(function(){ alert.style.display = "none"; }, 500);
            }
        }, 5000);
    </script>';
}

// Show SUCCESS message
if (isset($_GET['success'])) {
    $success_message = trim(stripslashes($_GET['success']), '"'); // clean message
    echo '<div id="successAlert" style="
            background-color: #d4edda; 
            color: #155724; 
            padding: 15px 20px; 
            border: 1px solid #c3e6cb; 
            border-radius: 5px; 
            margin: 10px 0;
            font-family: Arial, sans-serif;
            position: relative;
            transition: opacity 0.5s ease;
        ">
        <strong>Success!</strong> ' . htmlspecialchars($success_message) . '
        <span style="
            position: absolute;
            top: 5px;
            right: 10px;
            cursor: pointer;
            font-weight: bold;
            font-size: 18px;
        " onclick="this.parentElement.style.display=\'none\';">&times;</span>
    </div>

    <script>
        // Auto-hide success after 5 seconds
        setTimeout(function() {
            var alert = document.getElementById("successAlert");
            if(alert){
                alert.style.opacity = "0";
                setTimeout(function(){ alert.style.display = "none"; }, 500);
            }
        }, 5000);
    </script>';
}
?>


