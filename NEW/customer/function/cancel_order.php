<?php
session_start();

include('../../connection.php');

$order_id = $_POST['order_id'];

// Check if the order has been packed
$sql = "SELECT status_packed_at FROM orders WHERE order_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (empty($order['status_packed_at'])) {
    // If not packed, allow cancellation by updating order status
    // Step 1: Fetch order items to update stock
    $sql = "SELECT prod_id, quantity FROM order_items WHERE order_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order_items_result = $stmt->get_result();
    
    // Step 2: Update stock for each product
    while ($item = $order_items_result->fetch_assoc()) {
        $prod_id = $item['prod_id'];
        $quantity = $item['quantity'];

        // Increase stock quantity for each product in the order
        $sql = "UPDATE stocks SET stock_quantity = stock_quantity + ? WHERE prod_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("ii", $quantity, $prod_id);
        $stmt->execute();
    }

    // Step 3: Now cancel the order
    $sql = "UPDATE orders SET order_status = 'Cancelled' WHERE order_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $order_id);

    if ($stmt->execute()) {
        // Set success message and redirect
        $_SESSION['success_message'] = "The order has been successfully cancelled and stock has been updated.";
        $stmt->close();
        $mysqli->close();
        header("Location: ../my_orders.php");
        exit();
    } else {
        // Handle error
        $_SESSION['error_message'] = "Error cancelling order: " . $mysqli->error;
        $stmt->close();
        $mysqli->close();
        header("Location: ../my_orders.php");
        exit();
    }
} else {
    // If already packed, show an error message
    $_SESSION['error_message'] = "Order cannot be cancelled because it is already packed.";
    $stmt->close();
    $mysqli->close();
    header("Location: ../my_orders.php");
    exit();
}
?>
