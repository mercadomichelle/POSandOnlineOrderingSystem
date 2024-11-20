<?php
session_start();

include('../../connection.php');

date_default_timezone_set('Asia/Manila');

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

if (empty($prod_ids) || empty($quantities) || empty($price_types)) {
    $_SESSION['error_message'] = "Cart is empty.";
    header("Location: ../staff.php");
    exit();
}

// Get the order type from session
$order_type = $_SESSION['order_type'] ?? null;

if (!$order_type) {
    $_SESSION['error_message'] = "Order type not set.";
    header("Location: ../staff.php");
    exit();
}

// Begin transaction
$mysqli->begin_transaction();

try {
    $totalAmount = 0;
    
    // Calculate total amount for all products in the cart
    foreach ($prod_ids as $i => $prod_id) {
        $quantity = $quantities[$i];
        $sql = $price_types[$i] === 'wholesale' ?
            "SELECT prod_price_wholesale AS prod_price FROM products WHERE prod_id = ?" :
            "SELECT prod_price_retail AS prod_price FROM products WHERE prod_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $prod_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $price = $row['prod_price'];
        $totalAmount += $price * $quantity;
    }

    // Process cart items
    foreach ($prod_ids as $i => $prod_id) {
        $quantity = $quantities[$i];

        // Reduce stock quantity
        $sql = "UPDATE stocks SET stock_quantity = stock_quantity - ? WHERE prod_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ii", $quantity, $prod_id);
        $stmt->execute();
    }

    // Clear the cart
    $sql = "DELETE FROM cart WHERE login_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $login_id);
    $stmt->execute();

    // Commit transaction
    $mysqli->commit();

    // Store order type in the session (already set, no need to default)
    $_SESSION['order_type'] = $order_type;

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
