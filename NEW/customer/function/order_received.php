<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

session_start();

$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$order_id = $_POST['order_id'];

// Ensure that the order_id is an integer
$order_id = intval($order_id);

// Update the order status to "Received" if it has been shipped
$sql = "UPDATE orders SET status_delivered_at = NOW() WHERE order_id = ? AND status_shipped_at IS NOT NULL";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $order_id);

if ($stmt->execute()) {
    // Set success message
    $_SESSION['success_message'] = "Order marked as received.";
} else {
    // Set error message
    $_SESSION['error_message'] = "Error marking order as received: " . $mysqli->error;
}

$stmt->close();
$mysqli->close();

// Redirect to the order details page with messages
header("Location: order_details.php?order_id=" . $order_id);
exit();
?>
