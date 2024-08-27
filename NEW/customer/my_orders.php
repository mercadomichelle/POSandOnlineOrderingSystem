<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

session_start();

if (!isset($_SESSION["username"])) {
    header("Location: ../login.php");
    exit();
}

$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$username = $_SESSION["username"];

// Fetch user details
$sql = "SELECT id, first_name, last_name FROM login WHERE username = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $userData = $result->fetch_assoc();
    $_SESSION["login_id"] = $userData['id'];
    $_SESSION["first_name"] = $userData['first_name'];
    $_SESSION["last_name"] = $userData['last_name'];
    $login_id = $userData['id']; // Assign login_id
} else {
    $_SESSION["first_name"] = "Guest";
    $_SESSION["last_name"] = "";
    $login_id = 0; // Set default or handle accordingly if login_id not found
}

// Fetch user orders with product details
$sql = "
    SELECT 
        orders.order_id, 
        order_items.prod_id, 
        order_items.quantity, 
        orders.order_date, 
        orders.total_amount
    FROM 
        orders
    INNER JOIN 
        order_items ON orders.order_id = order_items.order_id
    WHERE 
        orders.login_id = ?
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $login_id);  // Bind $login_id as an integer
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    $orders = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $orders = [];  // Initialize as an empty array if no orders found or error
}

$stmt->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rice Website</title>
    <link rel="stylesheet" href="../styles/my_orders.css">
</head>

<body>
    <header>
        <div class="logo">RICE</div>
        <div class="nav-wrapper">
            <nav>
                <a href="../customer/customer.php">HOME</a>
                <a href="../customer/cust_products.php">PRODUCTS</a>
                <a href="../customer/my_orders.php" id="orders-link" class="current">MY ORDERS</a>
                <a href="../customer/about_us.php" id="about-link">ABOUT US</a>
            </nav>
        </div>
        <div class="account-info">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION["first_name"] . " " . $_SESSION["last_name"]); ?></span>
            <div class="dropdown">
                <img src="../images/account-icon.png" alt="Account" class="account-icon">
                <div class="dropdown-content">
                    <a href="../customer/my_profile.php">My Profile</a>
                    <a href="../logout.php">Logout</a>
                </div>
            </div>
    </header>

    <main>
        <div class="cart-summary">
            <h4>
                <img src="../../images/order-details-icon.png" alt="Order" class="order-icon">ORDER DETAILS
            </h4>

            <!-- <?php if ($errorMessage): ?>
                <div class="message error">
                    <p><?php echo $errorMessage; ?></p>
                </div>
            <?php endif; ?> -->

            <div class="cart">
                <div class="summary">
                    <?php if (count($orders) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Product ID</th>
                                    <th>Quantity</th>
                                    <th>Order Date</th>
                                    <th>Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                                        <td><?php echo htmlspecialchars($order['prod_id']); ?></td>
                                        <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                                        <td><?php echo htmlspecialchars(date('F j, Y, g:i a', strtotime($order['order_date']))); ?></td>
                                        <td><?php echo htmlspecialchars('₱' . number_format($order['total_amount'], 2)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No orders found.</p>
                    <?php endif; ?>
                </div>
            </div>
    </main>

</body>

<script>
    function updateNavLinks() {
        const ordersLink = document.getElementById('orders-link');
        const aboutLink = document.getElementById('about-link');

        if (window.innerWidth <= 649) {
            ordersLink.textContent = 'ORDERS';
            aboutLink.textContent = 'ABOUT';
        } else {
            ordersLink.textContent = 'MY ORDERS';
            aboutLink.textContent = 'ABOUT US';
        }
    }

    window.addEventListener('resize', updateNavLinks);
    window.addEventListener('DOMContentLoaded', updateNavLinks);
</script>

</html>