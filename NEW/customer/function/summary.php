<?php
session_start();

include('../../connection.php');

if (!isset($_SESSION['login_id'])) {
    header("Location: ../../index.php");
    exit();
}

$login_id = $_SESSION['login_id'];

// Fetch order details from the orders table for the logged-in user
$sql = "SELECT * FROM orders WHERE login_id = ? ORDER BY order_date DESC LIMIT 1";
$stmt1 = $mysqli->prepare($sql);
$stmt1->bind_param("i", $login_id);
$stmt1->execute();
$orderResult = $stmt1->get_result();

if ($orderResult->num_rows > 0) {
    // Fetch the latest order details
    $order = $orderResult->fetch_assoc();
    $order_id = $order['order_id'];
    $order_date = $order['order_date'];
    $totalAmount = $order['total_amount'];
    $order_source = $order['order_source'];
    $order_type = $order['order_type'];
} else {
    $order_id = $order_date = $totalAmount = $order_source = $order_type = null;
}

$stmt1->close();

// Fetch items for this order from the order_items table
$sql = "SELECT products.prod_id, products.prod_name, order_items.quantity, products.prod_price_wholesale AS prod_price
        FROM order_items 
        JOIN products ON order_items.prod_id = products.prod_id 
        WHERE order_items.order_id = ?";
$stmt2 = $mysqli->prepare($sql);
$stmt2->bind_param("i", $order_id);
$stmt2->execute();
$orderItemsResult = $stmt2->get_result();

$cart = [];
$subTotal = 0;

while ($row = $orderItemsResult->fetch_assoc()) {
    $totalPrice = $row['prod_price'] * $row['quantity'];
    $subTotal += $totalPrice;
    $cart[] = [
        'prod_id' => $row['prod_id'],
        'name' => $row['prod_name'],
        'quantity' => $row['quantity'],
        'price' => $row['prod_price']
    ];
}

$deliveryFee = 100; // Assuming fixed delivery fee
$total = $subTotal + $deliveryFee;

$stmt2->close();

// Close the connection
$mysqli->close();

$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$errorMessage = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rice Website | Order Summary</title>
    <link rel="icon" href="../../favicon.png" type="image/png">
    <link rel="stylesheet" href="../../styles/summary.css">
</head>

<body>
    <header>
        <div><img src="../../favicon.png" alt="Logo" class="logo"></div>
        <div class="nav-wrapper">
            <nav>
                <a href="../../customer/customer.php">HOME</a>
                <a href="../../customer/cust_products.php">PRODUCTS</a>
                <a href="../../customer/my_orders.php" id="orders-link">MY ORDERS</a>
                <a href="../../customer/about_us.php" id="about-link">ABOUT US</a>
            </nav>
        </div>
        <div class="account-info">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION["first_name"] . " " . $_SESSION["last_name"]); ?></span>
            <div class="dropdown">
                <img src="../../images/account-icon.png" alt="Account" class="account-icon">
                <div class="dropdown-content">
                    <a href="../../customer/my_profile.php">My Profile</a>
                    <a href="../../logout.php">Logout</a>
                </div>
            </div>
        </div>
    </header>


    <main>
        <div class="summary">
            <div class="checked"> <img src="../../images/checked-icon.png" alt="Checked" class="checked-icon">
            </div>
            <h2>ORDER CONFIRMED!</h2>

            <div class="cart-summary">
                <?php if ($errorMessage): ?>
                    <div class="message error">
                        <p><?php echo $errorMessage; ?></p>
                    </div>
                <?php endif; ?>

                <div class="cart">
                    <div class="summary">

                        <h4>Order Summary</h4>

                        <table>
                            <thead>
                                <tr>
                                    <th>Qty.</th>
                                    <th>Name</th>
                                    <th>Price per sack</th>
                                    <th>Price</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['quantity']); ?> x</td>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td>₱ <?php echo number_format($item['price'], 2); ?></td>
                                        <td>₱ <?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div class="total">
                            <span>TOTAL</span>
                            <span>₱ <?php echo number_format($total, 2); ?></span>
                        </div>
                    </div>

                    <!-- Removed Delivery Details Section -->

                </div>

                <!-- Changed "Place an Order" button to redirect to My Orders page -->
                <form action="../my_orders.php" class="button">
                    <button type="submit" class="confirm-btn">Go to My Orders</button>
                </form>

                <div id="loadingScreen" class="loading-screen" style="display: none;">
                    <div class="spinner"></div>
                    <p>Loading...</p>
                </div>

    </main>

    <script>
        const confirmButton = document.querySelector('.confirm-btn');

        confirmButton.disabled = false; // Button is always enabled now

        // Dropdown selection logic for address
        document.querySelector('form').addEventListener('submit', function() {
            document.getElementById('loadingScreen').style.display = 'flex';
        });

        // Hide dropdown if clicked outside
        document.addEventListener('click', function(event) {
            const dropdownOptions = document.getElementById('dropdown-options');
            if (dropdownOptions && dropdownOptions.style.display === 'block') {
                dropdownOptions.style.display = 'none';
            }
        });

        // Function to update nav links on resize
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

        // Show loading screen on form submit
        document.querySelector('form').addEventListener('submit', function() {
            document.getElementById('loadingScreen').style.display = 'flex';
        });
    </script>

</body>

</html>