<?php
session_start();

// Include the centralized database class
// include('../database.php');
require_once '../database.php';

// Retrieve the supplier ID from GET
$s_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

class DeleteSupplier extends Database
{
    public function deleteSupplier(int $s_id): void
    {
        if ($s_id <= 0) {
            $value = "Invalid supplier ID";
        } else {
            try {
                $pdo = $this->getConnection();

                $sql = "DELETE FROM supplier WHERE s_id = :s_id";
                $stmt = $pdo->prepare($sql);
                $stmt->bindParam(':s_id', $s_id, PDO::PARAM_INT);

                if ($stmt->execute()) {
                    $value = "Record deleted successfully";
                } else {
                    $value = "Error deleting record";
                }
            } catch (PDOException $ex) {
                $value = "Can't delete, error occurred";
            }
        }

        header("Location: supplier_list.php?success=" . urlencode($value));
        exit();
    }
}

$supplier = new DeleteSupplier();
$supplier->deleteSupplier($s_id);
