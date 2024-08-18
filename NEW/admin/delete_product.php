<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

session_start();

if (!isset($_SESSION["username"])) {
    header("Location: ../login.php");
    exit();
}

$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $prod_id = $_POST["prod_id"];

    // Delete the product from the database
    $sql = "DELETE FROM products WHERE prod_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $prod_id);
    $stmt->execute();

    $stmt->close();
    $mysqli->close();

    // Redirect back to the products page
    header("Location: products.php");
    exit();
} else {
    // Redirect if accessed without POST method
    header("Location: products.php");
    exit();
}
?>
