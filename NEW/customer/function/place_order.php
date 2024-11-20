<?php
session_start();

include('../../connection.php');

if (!isset($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$login_id = $_SESSION['login_id'];
$prod_ids = $_SESSION['cart']['prod_id'] ?? [];
$quantities = $_SESSION['cart']['quantity'] ?? [];

// Begin transaction
$mysqli->begin_transaction();

try {
    $totalAmount = 0;

    // Calculate total amount
    for ($i = 0; $i < count($prod_ids); $i++) {
        $prod_id = $prod_ids[$i];
        $quantity = $quantities[$i];

        // Fetch the price for each product
        $sql = "SELECT prod_price_wholesale AS prod_price FROM products WHERE prod_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $prod_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $price = $row['prod_price'];

        // Calculate the cost for the current item and add to total amount
        $totalAmount += $price * $quantity;
    }

    $deliveryFee = 100;
    $totalAmount += $deliveryFee;

    $order_source = 'online';

    // Insert the order into the orders table
    $sql = "INSERT INTO orders (login_id, order_date, total_amount, order_source) VALUES (?, NOW(), ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $order_source = 'online';
    $stmt->bind_param("ids", $login_id, $totalAmount, $order_source);
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
        $stmt = $mysqli->prepare($sql);
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
    header("Location: ../cust_products.php");
    exit();
} catch (Exception $e) {
    // Rollback transaction on error
    $mysqli->rollback();
    $_SESSION['error_message'] = "Failed to place the order: " . $e->getMessage();
    header("Location: ../cust_products.php");
    exit();
}
?>
