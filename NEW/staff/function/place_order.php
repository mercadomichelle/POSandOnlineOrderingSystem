<?php
session_start();

$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Check if the user is logged in
if (!isset($_SESSION['login_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Get order details from the session
$login_id = $_SESSION['login_id'];
$prod_ids = $_SESSION['cart']['prod_id'] ?? [];
$quantities = $_SESSION['cart']['quantity'] ?? [];
$price_types = $_SESSION['cart']['price_type'] ?? []; 
$_SESSION['order_type'] = $order_type;


// Begin transaction
$mysqli->begin_transaction();

try {
    $totalAmount = 0;

// Determine the order type by checking all items in the cart
$order_type = 'retail'; // Default to retail
foreach ($price_types as $type) {
    if ($type === 'wholesale') {
        $order_type = 'wholesale';
        break; // If any product is wholesale, set order_type to wholesale
    }
}

// Calculate total amount for all products
for ($i = 0; $i < count($prod_ids); $i++) {
    $prod_id = $prod_ids[$i];
    $quantity = $quantities[$i];

    // Fetch the price based on the price_type for each product
    $sql = $price_types[$i] === 'wholesale' ? 
        "SELECT prod_price_wholesale AS prod_price FROM products WHERE prod_id = ?" : 
        "SELECT prod_price_retail AS prod_price FROM products WHERE prod_id = ?";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $prod_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $price = $row['prod_price'];

    // Calculate the total amount for this product
    $totalAmount += $price * $quantity;
}

// Insert the order with the determined order_type
$sql = "INSERT INTO orders (login_id, order_date, total_amount, order_source, order_type) VALUES (?, NOW(), ?, ?, ?)";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("idss", $login_id, $totalAmount, $order_source, $order_type);
$stmt->execute();
$order_id = $stmt->insert_id;
    
    // Calculate total amount
    for ($i = 0; $i < count($prod_ids); $i++) {
        $prod_id = $prod_ids[$i];
        $quantity = $quantities[$i];

        // Fetch the price for each product based on price_type
        $sql = $price_types[$i] === 'wholesale' ? 
            "SELECT prod_price_wholesale AS prod_price FROM products WHERE prod_id = ?" : 
            "SELECT prod_price_retail AS prod_price FROM products WHERE prod_id = ?";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $prod_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $price = $row['prod_price'];

        // Calculate the cost for the current item and add to total amount
        $totalAmount += $price * $quantity;
    }

    // Add a fixed delivery fee (assuming it's constant)
    $deliveryFee = 0;
    $totalAmount += $deliveryFee;

    // Set order source to 'in-store'
    $order_source = 'in-store';

    // Insert the order into the orders table with order_type
    $sql = "INSERT INTO orders (login_id, order_date, total_amount, order_source, order_type) VALUES (?, NOW(), ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("idss", $login_id, $totalAmount, $order_source, $order_type);
    $stmt->execute();
    
    // Get the generated order_id
    $order_id = $stmt->insert_id;

    // Process each cart item
    for ($i = 0; $i < count($prod_ids); $i++) {
        $prod_id = $prod_ids[$i];
        $quantity = $quantities[$i];

        // Reduce stock quantity
        $sql = "UPDATE stocks SET stock_quantity = stock_quantity - ? WHERE prod_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ii", $quantity, $prod_id);
        $stmt->execute();

        // Insert each product into the order_items table with the same order_id
        $sql = "INSERT INTO order_items (order_id, prod_id, quantity) VALUES (?, ?, ?)";
        $stmt->prepare($sql);
        $stmt->bind_param("iii", $order_id, $prod_id, $quantity);
        $stmt->execute();
    }

    // Clear the cart
    $sql = "DELETE FROM cart WHERE login_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $login_id);
    $stmt->execute();

    // Commit transaction
    $mysqli->commit();

    // Clear cart session data
    unset($_SESSION['cart']);

    // Redirect to a success page with a message
    $_SESSION['success_message'] = "Your order has been placed successfully!";
    header("Location: ../staff.php");
    exit();
} catch (Exception $e) {
    // Rollback transaction on error
    $mysqli->rollback();
    $_SESSION['error_message'] = "Failed to place the order: " . $e->getMessage();
    header("Location: ../staff.php");
    exit();
}

?>
