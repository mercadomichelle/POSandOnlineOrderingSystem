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

$username = $_SESSION["username"];
$prod_id = $_POST['prod_id'];
$quantity = $_POST['quantity'];

$sql = "SELECT id FROM login WHERE username = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $userData = $result->fetch_assoc();
    $login_id = $userData['id'];

    $sql = "SELECT * FROM cart WHERE login_id = ? AND prod_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $login_id, $prod_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $sql = "UPDATE cart SET quantity = quantity + ? WHERE login_id = ? AND prod_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("iii", $quantity, $login_id, $prod_id);
    } else {
        $sql = "INSERT INTO cart (login_id, prod_id, quantity) VALUES (?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("iii", $login_id, $prod_id, $quantity);
    }
    $stmt->execute();

    // Redirect back to products page
    header("Location: ../cust_products.php");
} else {
    // Handle user not found
    header("Location: ../../login.php");
}

$stmt->close();
$mysqli->close();
