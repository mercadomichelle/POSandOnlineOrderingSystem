<?php
session_start();

$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

$mysqli = new mysqli($host, $user, $password, $db);
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

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
    $sql = "UPDATE orders SET order_status = 'Cancelled' WHERE order_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $order_id);

    if ($stmt->execute()) {
        // Set success message and redirect
        $_SESSION['success_message'] = "The order has been successfully cancelled.";
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
