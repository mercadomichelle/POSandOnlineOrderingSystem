<?php
session_start();

if (!isset($_SESSION['login_id'])) {
    header("Location: ../login.php");
    exit();
}

// Database connection
$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$login_id = $_SESSION['login_id'];

// Clear the cart in the database for the current user
$sql = "DELETE FROM cart WHERE login_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $login_id);
$stmt->execute();
$stmt->close();

// Optionally, clear cart data from session
unset($_SESSION['cart']);

// Close the database connection
$mysqli->close();

// Return a response (for AJAX)
echo json_encode(['status' => 'success']);
?>
