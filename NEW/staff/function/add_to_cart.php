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
$source = $_POST['source'];  // Get source page: wholesale or retail

// Fetch the login id based on the session username
$sql = "SELECT id FROM login WHERE username = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $userData = $result->fetch_assoc();
    $login_id = $userData['id'];

    // Check if the product is already in the cart
    $sql = "SELECT * FROM cart WHERE login_id = ? AND prod_id = ? AND price_type = ?";
    $price_type = $source; // 'wholesale' or 'retail' based on the source page
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("iis", $login_id, $prod_id, $price_type);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Update the quantity if the product is already in the cart for the given price type
        $sql = "UPDATE cart SET quantity = quantity + ? WHERE login_id = ? AND prod_id = ? AND price_type = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("iiis", $quantity, $login_id, $prod_id, $price_type);
    } else {
        // Get the price based on the source (wholesale or retail)
        if ($source === 'wholesale') {
            $sql = "SELECT prod_price_wholesale AS price FROM products WHERE prod_id = ?";
        } else {
            $sql = "SELECT prod_price_retail AS price FROM products WHERE prod_id = ?";
        }
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $prod_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $prod = $result->fetch_assoc();
        $price = $prod['price'];
        $stmt->close();

        // Insert new product into cart with the correct price and price_type
        $sql = "INSERT INTO cart (login_id, prod_id, quantity, price, user_type, price_type) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("iiidss", $login_id, $prod_id, $quantity, $price, $user_type, $price_type);        
    }
    $stmt->execute();

    // Redirect to appropriate page
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
