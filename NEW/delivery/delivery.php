<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

include('../connection.php');

if (!isset($_SESSION["username"])) {
    header("Location: ../../login.php");
    exit();
}

$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

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


$stmt->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Dashboard | Rice Website</title>
    <link rel="icon" href="../favicon.png" type="image/png">
    <link rel="stylesheet" href="../../styles/delivery.css">
</head>

<body>
    <header>
        <div><img src="../favicon.png" alt="Logo" class="logo"></div>
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
        <section class="dashboard">
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
                            <th> </th>
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
                                    <td><?php echo date('F d, Y', strtotime($order['order_date'])); ?></td>
                                    <td>₱ <?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge"><?php echo htmlspecialchars($order['order_status']); ?></span>
                                    </td>
                                    <td class="actions">
                                        <a class="btn-view" href="address_details.php?order_id=<?php echo htmlspecialchars($order['order_id']); ?>
                                            &address=<?php echo urlencode($order['address'] ?? 'N/A'); ?>
                                            &city=<?php echo urlencode($order['city'] ?? 'N/A'); ?>
                                            &zip_code=<?php echo urlencode($order['zip_code'] ?? 'N/A'); ?>
                                            &latitude=<?php echo urlencode($order['latitude'] ?? '0'); ?>
                                            &longitude=<?php echo urlencode($order['longitude'] ?? '0'); ?>">
                                            View Address
                                        </a>                                   
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
    
</body>

</html>
