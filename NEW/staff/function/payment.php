<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['login_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Ensure total amount is set
if (!isset($_SESSION['total_amount']) || empty($_SESSION['total_amount'])) {
    die("Total amount is not set.");
}

// Get the submitted payment amount
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $enteredAmount = $_POST['amount'] ?? 0;
    $totalAmount = $_SESSION['total_amount'];

    // Validate payment
    if ($enteredAmount < $totalAmount) {
        $_SESSION['error_message'] = "Insufficient amount. Please enter the correct total.";
        header("Location: confirm_order.php");
        exit();
    }

    // Store the entered payment amount in the session
    $_SESSION['payment_received'] = $enteredAmount;

    // Database connection details
    $host = "localhost";
    $user = "root";
    $password = "";
    $db = "system_db";

    $mysqli = new mysqli($host, $user, $password, $db);

    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    // Begin transaction
    $mysqli->begin_transaction();

    try {
        // Assuming payment is successful, insert into orders table
        $login_id = $_SESSION['login_id'];
        $order_status = 'Paid'; 
        $order_source = 'in-store';
// Determine the order type based on the cart contents
$order_type = 'retail'; // Default to retail
foreach ($_SESSION['cart']['price_type'] as $price_type) {
    if ($price_type === 'wholesale') {
        $order_type = 'wholesale';
        break; // If any product is wholesale, set order_type to wholesale
    }
}

// Insert the order with the determined order_type
$sql = "INSERT INTO orders (login_id, total_amount, order_status, order_source, order_type, order_date) VALUES (?, ?, ?, ?, ?, NOW())";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("idsss", $login_id, $totalAmount, $order_status, $order_source, $order_type);
$stmt->execute();
$order_id = $stmt->insert_id;

        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $mysqli->error);
        }

        $stmt->bind_param("idsss", $login_id, $totalAmount, $order_status, $order_source, $order_type);

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        // Get the generated order_id
        $order_id = $stmt->insert_id;

        // Insert into order_items table for each product in the cart
        $prod_ids = $_SESSION['cart']['prod_id'] ?? [];
        $quantities = $_SESSION['cart']['quantity'] ?? [];

        for ($i = 0; $i < count($prod_ids); $i++) {
            $prod_id = $prod_ids[$i];
            $quantity = $quantities[$i];

            // Insert each product into the order_items table with the generated order_id
            $sql = "INSERT INTO order_items (order_id, prod_id, quantity) VALUES (?, ?, ?)";
            $stmt = $mysqli->prepare($sql);

            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $mysqli->error);
            }

            $stmt->bind_param("iii", $order_id, $prod_id, $quantity);

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            // Reduce stock quantity for the product
            $sql = "UPDATE stocks SET stock_quantity = stock_quantity - ? WHERE prod_id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("ii", $quantity, $prod_id);
            $stmt->execute();
        }

        // Clear the cart after payment
        unset($_SESSION['cart']);
        $_SESSION['payment_received'] = $enteredAmount;

        // Commit transaction
        $mysqli->commit();

        // Redirect to receipt page
        header("Location: receipt.php");
        exit();
    } catch (Exception $e) {
        // Rollback transaction on error
        $mysqli->rollback();
        $_SESSION['error_message'] = "Failed to process the order: " . $e->getMessage();
        header("Location: confirm_order.php");
        exit();
    }
} else {
    $_SESSION['error_message'] = "Invalid request.";
    header("Location: confirm_order.php");
    exit();
}

?>
