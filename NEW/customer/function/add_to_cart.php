<?php
session_start();

include('../../connection.php');

if (!isset($_SESSION["username"])) {
    header("Location: ../../index.php");
    exit();
}

$username = $_SESSION["username"];
$prod_id = $_POST['prod_id'];
$quantity = $_POST['quantity'];
$user_type = 'customer';
$price_type = 'wholesale'; // Set the price type to wholesale

// Fetch user ID
$sql = "SELECT id FROM login WHERE username = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $userData = $result->fetch_assoc();
    $login_id = $userData['id'];

    // Check if product is already in the cart
    $sql = "SELECT * FROM cart WHERE login_id = ? AND prod_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $login_id, $prod_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // The product is already in the cart, so update the quantity
        $sql = "UPDATE cart SET quantity = quantity + ? WHERE login_id = ? AND prod_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("iii", $quantity, $login_id, $prod_id);
        $stmt->execute();
    } else {
        // The product is not in the cart, fetch the wholesale price
        $sql = "SELECT prod_price_wholesale AS price FROM products WHERE prod_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $prod_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if the product was found and price is valid
        if ($result->num_rows === 1) {
            $productData = $result->fetch_assoc();
            $price = $productData['price'];

            // Ensure price is valid (not null or zero)
            if ($price === null || $price <= 0) {
                echo "Invalid wholesale price for product ID: $prod_id";
                exit(); // Stop further execution
            }

            // Insert the new product into the cart with the price type as wholesale
            $sql = "INSERT INTO cart (login_id, prod_id, quantity, price, price_type, user_type) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("iiidsi", $login_id, $prod_id, $quantity, $price, $price_type, $user_type);
            $stmt->execute();
        } else {
            echo "Product not found for ID: $prod_id";
            exit(); // Stop further execution
        }
    }
    
    // Redirect back to products page
    header("Location: ../cust_products.php");
    exit();
} else {
    // Handle user not found
    header("Location: ../../index.php");
}

$stmt->close();
$mysqli->close();
