<?php
session_start();

// Include the centralized database class
// include('../database.php');
require_once '../database.php';

// Retrieve POST values safely
$supplierName = $_POST['supplier_name'] ?? '';
$address      = $_POST['address'] ?? '';
$phone        = $_POST['phone'] ?? '';
$email        = $_POST['email'] ?? '';
$username     = $_SESSION['username'] ?? '';
$defaultDateTime = $_SESSION['Asia_Dhaka_time'] ?? date('Y-m-d H:i:s');
$s_id         = isset($_POST['s_id']) ? (int)$_POST['s_id'] : 0;

class UpdateSupplier extends Database
{
    public function updateSupplier(
        string $supplierName,
        string $address,
        string $phone,
        string $email,
        string $defaultDateTime,
        string $username,
        int $s_id
    ): void {
        if ($s_id <= 0) {
            $message = "Invalid supplier ID";
        } else {
            try {
                $pdo = $this->getConnection();

                $sql = "UPDATE supplier
                        SET s_name = :s_name,
                            s_address = :s_address,
                            s_phone = :s_phone,
                            s_email = :s_email,
                            s_update_date_time = :update_dt,
                            s_update_by = :update_by
                        WHERE s_id = :s_id";

                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':s_name', $supplierName, PDO::PARAM_STR);
                $stmt->bindParam(':s_address', $address, PDO::PARAM_STR);
                $stmt->bindParam(':s_phone', $phone, PDO::PARAM_STR);
                $stmt->bindParam(':s_email', $email, PDO::PARAM_STR);
                $stmt->bindParam(':update_dt', $defaultDateTime, PDO::PARAM_STR);
                $stmt->bindParam(':update_by', $username, PDO::PARAM_STR);
                $stmt->bindParam(':s_id', $s_id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    $message = "Record updated successfully";
                } else {
                    $message = "Duplicate record or update failed";
                }

            } catch (PDOException $ex) {
                $message = "Database error occurred";
            }
        }

        header("Location: supplier_search.php?success=" . urlencode($message));
        exit();
    }
}

$supplier = new UpdateSupplier();
$supplier->updateSupplier($supplierName, $address, $phone, $email, $defaultDateTime, $username, $s_id);
