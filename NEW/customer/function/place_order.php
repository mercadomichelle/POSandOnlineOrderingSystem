<?php
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

session_start();

include('../../connection.php');

if (!isset($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$login_id = $_SESSION['login_id'];
$prod_ids = $_SESSION['cart']['prod_id'] ?? [];
$quantities = $_SESSION['cart']['quantity'] ?? [];
$branch_id = $_SESSION['selected_branch'] ?? null;  // Get branch ID from session

if (!$branch_id) {
    $_SESSION['error_message'] = "Please select a branch before placing an order.";
    header("Location: ../cust_products.php");  // Redirect to product page if branch not selected
    exit();
}

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

        // Reduce stock quantity for the selected branch
        $sql = "UPDATE stocks SET stock_quantity = stock_quantity - ? WHERE prod_id = ? AND branch_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("iii", $quantity, $prod_id, $branch_id);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            throw new Exception("Insufficient stock or invalid branch for product ID $prod_id");
        }

        // Insert each product into the order_items table with the same order_id and branch_id
        $sql = "INSERT INTO order_items (order_id, prod_id, quantity, branch_id) VALUES (?, ?, ?, ?)";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("iiii", $order_id, $prod_id, $quantity, $branch_id);
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
    header("Location: summary.php");
    session_write_close();
    exit();
} catch (Exception $e) {
    // Rollback transaction on error
    $mysqli->rollback();
    $_SESSION['error_message'] = "Failed to place the order: " . $e->getMessage();
    header("Location: ../cust_products.php");
    exit();
}?>
