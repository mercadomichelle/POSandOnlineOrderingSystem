<?php
session_start();

include('../../connection.php');

$order_id = $_POST['order_id'];
$order_id = intval($order_id);

// Update the order status to "Received" if it has been shipped
$sql = "UPDATE orders SET status_delivered_at = NOW() WHERE order_id = ? AND status_shipped_at IS NOT NULL";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $order_id);

if ($stmt->execute()) {
    $_SESSION['success_message'] = "Order marked as received.";
} else {
    $_SESSION['error_message'] = "Error marking order as received: " . $mysqli->error;
}

$stmt->close();
$mysqli->close();

header("Location: order_details.php?order_id=" . $order_id);
exit();
?>
