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
$user_type = 'staff';
$source = $_POST['source'];  // Get source page

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
        if ($source === 'wholesale') {
            $sql = "SELECT prod_price_wholesale FROM products WHERE prod_id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $prod_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $prod = $result->fetch_assoc();
            $price = $prod['prod_price_wholesale'];
        } else {
            $sql = "SELECT prod_price_retail FROM products WHERE prod_id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $prod_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $prod = $result->fetch_assoc();
            $price = $prod['prod_price_retail'];
        }
        $stmt->close();

        $sql = "INSERT INTO cart (login_id, prod_id, quantity, price, user_type) VALUES (?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("iiids", $login_id, $prod_id, $quantity, $price, $user_type);
    }
    $stmt->execute();

    if ($source === 'wholesale') {
        header("Location: ../staff.php");
    } else {
        header("Location: ../staff_retail.php");
    }
} else {
    // Handle user not found
    header("Location: ../../login.php");
}

$stmt->close();
$mysqli->close();
?>
