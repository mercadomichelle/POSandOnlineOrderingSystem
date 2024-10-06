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

    // Assuming payment is successful, insert into orders table
    $login_id = $_SESSION['login_id'];
    $order_status = 'Paid'; 
    $order_source = 'in-store'; 

    // Prepare and execute the insert statement
    $sql = "INSERT INTO orders (login_id, total_amount, order_status, order_source) VALUES (?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);

    if ($stmt === false) {
        die("Prepare failed: " . $mysqli->error);
    }

    $stmt->bind_param("idss", $login_id, $totalAmount, $order_status, $order_source);

    if (!$stmt->execute()) {
        die("Execute failed: " . $stmt->error);
    }

    // Clear the cart after payment
    unset($_SESSION['cart']);
    $_SESSION['payment_received'] = $enteredAmount;

    // Redirect to receipt page
    header("Location: receipt.php");
    exit();
} else {
    $_SESSION['error_message'] = "Invalid request.";
    header("Location: confirm_order.php");
    exit();
}
