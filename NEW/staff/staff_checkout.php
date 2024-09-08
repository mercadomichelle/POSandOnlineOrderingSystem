<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if 'prod_id' and 'quantity' are set in the POST data
    if (isset($_POST['prod_id']) && isset($_POST['quantity'])) {
        $prod_ids = $_POST['prod_id'];
        $quantities = $_POST['quantity'];

        // Check if the count of product IDs matches the count of quantities
        if (count($prod_ids) !== count($quantities)) {
            $_SESSION['error_message'] = "Mismatch between product IDs and quantities.";
            header("Location: staff.php");
            exit();
        }

        // Calculate total quantity
        $totalQuantity = array_sum($quantities);

        // Store the order details in the session
        $_SESSION['cart'] = [
            'prod_id' => $prod_ids,
            'quantity' => $quantities
        ];

        // Redirect to the confirmation page
        header("Location: function/confirm_order.php");
        exit();
    } else {
        // Handle the case where the POST data is missing
        $_SESSION['error_message'] = "Product IDs and quantities are required.";
        header("Location: staff.php");
        exit();
    }
}
?>
