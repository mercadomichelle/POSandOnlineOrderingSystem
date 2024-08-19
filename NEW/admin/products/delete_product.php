<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

session_start();

if (!isset($_SESSION["username"])) {
    header("Location: ../../login.php");
    exit();
}

$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $prod_id = intval($_POST["prod_id"]);
    $source_page = $_POST["source_page"]; // Get the source page from the form

    // Start transaction
    $mysqli->begin_transaction();

    try {
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

        // Close the connection
        $mysqli->close();

        // Redirect based on the source page
        if ($source_page === 'retail') {
            header("Location: products_retail.php");
        } elseif ($source_page === 'wholesale') {
            header("Location: products.php");
        } else {
            header("Location: products.php");
        }
        exit();
    } catch (Exception $e) {
        // Rollback the transaction if something goes wrong
        $mysqli->rollback();
        die("Error: " . $e->getMessage());
    }
} else {
    // Redirect if accessed without POST method
    header("Location: products.php");
    exit();
}
?>
