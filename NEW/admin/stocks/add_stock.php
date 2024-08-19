<?php
session_start();

$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    $_SESSION['errorMessage'] = "Connection failed: " . $mysqli->connect_error;
    header("Location: stocks.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $prod_id = $_POST['prod_id'];
    $stock_quantity = $_POST['stock_quantity'];

    // Validate inputs
    if (empty($prod_id) || empty($stock_quantity) || !is_numeric($stock_quantity) || $stock_quantity < 0) {
        $_SESSION['errorMessage'] = "Invalid input.";
        header("Location: stocks.php");
        exit();
    }

    // Prepare the SQL statement
    $sql = "INSERT INTO stocks (prod_id, stock_quantity) 
            VALUES (?, ?) 
            ON DUPLICATE KEY UPDATE stock_quantity = stock_quantity + VALUES(stock_quantity)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $prod_id, $stock_quantity);

    if ($stmt->execute()) {
        $_SESSION['successMessage'] = "Stock added successfully!";
    } else {
        $_SESSION['errorMessage'] = "Error: " . $stmt->error;
    }

    $stmt->close();
}

$mysqli->close();

header("Location: stocks.php");
exit();
?>
