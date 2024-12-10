<?php

ini_set('display_errors', 1); 
error_reporting(E_ALL);

session_start();

include('../../connection.php');

date_default_timezone_set('Asia/Manila'); 

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

    $host = "localhost";
    $user = "root";
    $password = "";
    $db = "system_db";

    $mysqli = new mysqli($host, $user, $password, $db);

    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }

    // Set MySQL timezone to +08:00 for Manila
    if (!$mysqli->query("SET time_zone = '+08:00'")) {
        error_log('MySQL Timezone Set Error: ' . $mysqli->error); // Log error if setting timezone fails
    }

    // Begin transaction
    $mysqli->begin_transaction();

    try {
        // Insert the order into the orders table
        $login_id = $_SESSION['login_id'];
        $order_status = 'Paid';
        $order_source = 'in-store';

        // Get the order type from session
        $order_type = $_SESSION['order_type'] ?? null;

        if (!$order_type) {
            $_SESSION['error_message'] = "Please try again.";
            header("Location: ../staff.php");
            exit();
        }

        $sql = "INSERT INTO orders (login_id, total_amount, order_status, order_source, order_type, order_date, status_processed_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $mysqli->prepare($sql);
        if (!$stmt) {
            error_log('SQL Prepare Error: ' . $mysqli->error); // Log any prepare error
            die("SQL error");
        }
        $stmt->bind_param("idsss", $login_id, $totalAmount, $order_status, $order_source, $order_type);
        $stmt->execute();

        // Get the generated order_id
        $order_id = $stmt->insert_id;
        $_SESSION['order_id'] = $order_id;

        // Insert into order_items table
        $prod_ids = $_SESSION['cart']['prod_id'] ?? [];
        $quantities = $_SESSION['cart']['quantity'] ?? [];

        for ($i = 0; $i < count($prod_ids); $i++) {
            $prod_id = $prod_ids[$i];
            $quantity = $quantities[$i];
            $branch_id = 1; 

            $sql = "INSERT INTO order_items (order_id, prod_id, quantity, branch_id) VALUES (?, ?, ?, ?)";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("iiii", $order_id, $prod_id, $quantity, $branch_id);
            $stmt->execute();

            // Reduce stock quantity
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
