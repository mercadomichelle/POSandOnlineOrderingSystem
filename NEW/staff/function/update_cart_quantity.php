<?php
session_start();

include('../../connection.php');

// Validate and sanitize input
$prod_id = isset($_POST['prod_id']) ? (int)$_POST['prod_id'] : 0;
$quantity = isset($_POST['quantity']) ? max(1, (int)$_POST['quantity']) : 1;
$login_id = isset($_SESSION['login_id']) ? (int)$_SESSION['login_id'] : 0;

if ($prod_id <= 0 || $quantity <= 0 || $login_id <= 0) {
    echo json_encode(['error' => 'Invalid input']);
    exit();
}

// Update the cart with the new quantity
$sql = "UPDATE cart SET quantity = ? WHERE prod_id = ? AND login_id = ?";
$stmt = $mysqli->prepare($sql);
if (!$stmt) {
    echo json_encode(['error' => 'Database error: ' . $mysqli->error]);
    exit();
}
$stmt->bind_param("iii", $quantity, $prod_id, $login_id);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => 'Cart quantity updated']);

// Close the connection
$mysqli->close();
?>
