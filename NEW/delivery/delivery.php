<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

session_start();

if (!isset($_SESSION["username"])) {
    header("Location: ../../login.php");
    exit();
}

$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Retrieve user data
$username = $_SESSION["username"];
$sql = "SELECT first_name, last_name FROM login WHERE username = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $userData = $result->fetch_assoc();
    $_SESSION["first_name"] = $userData['first_name'];
    $_SESSION["last_name"] = $userData['last_name'];
} else {
    $_SESSION["first_name"] = "Guest";
    $_SESSION["last_name"] = "";
}

// Fetch orders for delivery
$order_sql = "SELECT o.order_id, o.order_date, o.total_amount, o.order_status, 
                     CONCAT(l.first_name, ' ', l.last_name) AS customer_name,
                     p.address, p.city, p.zip_code, p.latitude, p.longitude
              FROM orders o 
              JOIN login l ON o.login_id = l.id 
              JOIN profile p ON l.username = p.username
              WHERE o.order_status = 'For Delivery'";

$order_result = $mysqli->query($order_sql);

$orders = [];
if ($order_result->num_rows > 0) {
    while ($row = $order_result->fetch_assoc()) {
        $orders[] = $row;
    }
} else {
    $orders = []; // No orders found
}

// Check for order completion action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['complete_order_id'])) {
    $complete_order_id = $_POST['complete_order_id'];

    // Update order status to "Delivered"
    $update_sql = "UPDATE orders SET order_status = 'Delivered', status_delivered_at = NOW() WHERE order_id = ?";
    $update_stmt = $mysqli->prepare($update_sql);
    $update_stmt->bind_param("i", $complete_order_id);

    if ($update_stmt->execute()) {
        $_SESSION["successMessage"] = "Order marked as delivered.";
    } else {
        $_SESSION["errorMessage"] = "Error updating order status: " . $mysqli->error;
    }

    $update_stmt->close();
    header("Location: delivery.php");
    exit();
}

$stmt->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rice Website | Delivery</title>
    <link rel="stylesheet" href="../../styles/delivery.css">
</head>

<body>
    <header>
        <div class="logo">RICE</div>
        <div class="account-info">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION["first_name"] . " " . $_SESSION["last_name"]); ?></span>
            <div class="dropdown">
                <img src="../../images/account-icon.png" alt="Account">
                <div class="dropdown-content">
                    <a href="../../logout.php">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <main>
        <div class="card">
            <h3>Delivery Orders</h3>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer Name</th>
                        <th>Order Date</th>
                        <th>Total Amount</th>
                        <th>Order Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                        <tr>
                            <td colspan="6">No orders available for delivery.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($order['order_date']); ?></td>
                                <td><?php echo htmlspecialchars($order['total_amount']); ?></td>
                                <td><?php echo htmlspecialchars($order['order_status']); ?></td>
                                <td>
                                    <form method="POST" action="">
                                        <input type="hidden" name="complete_order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                                        <button type="submit">Mark as Delivered</button>
                                    </form>
                                    <a href="address_details.php?order_id=<?php echo htmlspecialchars($order['order_id']); ?>&address=<?php echo urlencode($order['address']); ?>&city=<?php echo urlencode($order['city']); ?>&zip_code=<?php echo urlencode($order['zip_code']); ?>&latitude=<?php echo urlencode($order['latitude']); ?>&longitude=<?php echo urlencode($order['longitude']); ?>">View Address</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <script>

    </script>
</body>

</html>