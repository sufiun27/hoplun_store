<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../database.php';

// Retrieve form values
$supplierName = $_POST['supplier_name'] ?? '';
$address      = $_POST['address'] ?? '';
$phone        = $_POST['phone'] ?? '';
$email        = $_POST['email'] ?? '';

$username = $_SESSION['username'];
$section = $_SESSION['section'];
$defaultDateTime = $_SESSION['Asia_Dhaka_time'];

// --------------------------------------------------
// Supplier Class
// --------------------------------------------------
class Supplier extends Database
{
    public function addSupplier($supplierName, $address, $phone, $email, $defaultDateTime, $username, $section)
    {
        try {
            $sql = "INSERT INTO supplier 
                    (s_name, s_address, s_phone, s_email, s_add_datetime, s_add_by, section) 
                    VALUES 
                    (:s_name, :s_address, :s_phone, :s_email, :s_add_datetime, :s_add_by, :section)";

            $stmt = $this->getConnection()->prepare($sql);

            $stmt->execute([
                ":s_name"          => $supplierName,
                ":s_address"       => $address,
                ":s_phone"         => $phone,
                ":s_email"         => $email,
                ":s_add_datetime"  => $defaultDateTime,
                ":s_add_by"        => $username,
                ":section"         => $section
            ]);

            // Success message
            $message = "Record inserted successfully";
            header("Location: supplier_add.php?value_dep=" . urlencode($message));
            exit;

        } catch (PDOException $e) {

            // Duplicate or SQL error
            $message = "Duplicate record or error!";
            header("Location: supplier_add.php?value_dep=" . urlencode($message));
            exit;
        }
    }
}

// --------------------------------------------------
// Execute Add Supplier
// --------------------------------------------------
$addSupplier = new Supplier();
$addSupplier->addSupplier($supplierName, $address, $phone, $email, $defaultDateTime, $username, $section);

?>
