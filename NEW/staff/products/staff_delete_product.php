<?php
session_start();

include('../../connection.php');

if (!isset($_SESSION["username"])) {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $prod_id = intval($_POST["prod_id"]);
    $source_page = $_POST["source_page"]; // Get the source page from the form

    // Start transaction
    $mysqli->begin_transaction();

    try {
        // Delete related rows in the alternative_varieties table
        $sql = "DELETE FROM alternative_varieties WHERE product_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $prod_id);
        $stmt->execute();
        $stmt->close();

        // Delete related rows in the stocks table
        $sql = "DELETE FROM stocks WHERE prod_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $prod_id);
        $stmt->execute();
        $stmt->close();

        // Delete the product from the products table
        $sql = "DELETE FROM products WHERE prod_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $prod_id);
        $stmt->execute();
        $stmt->close();

        // Commit the transaction
        $mysqli->commit();

        $_SESSION["successMessage"] = "Product and related stocks have been deleted successfully.";

        // Close the connection
        $mysqli->close();
        
    } catch (Exception $e) {
        // Rollback the transaction if something goes wrong
        $mysqli->rollback();
        // Set error message
        $_SESSION["errorMessage"] = "Error deleting product: " . $e->getMessage();
    }

    // Redirect based on the source page
    if ($source_page === 'retail') {
        header("Location: staff_retail.php");
    } elseif ($source_page === 'wholesale') {
        header("Location: staff_products.php");
    } else {
        header("Location: staff_products.php");
    }
    exit();
} else {
    // Redirect if accessed without POST method
    header("Location: staff_products.php");
    exit();
}
?>