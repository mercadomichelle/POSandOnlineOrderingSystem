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

// Fetch user orders with aggregated product details and order status
$sql = "SELECT orders.order_id, orders.order_date, 
               SUM(order_items.quantity) AS total_quantity, 
               orders.total_amount, orders.order_status,
               GROUP_CONCAT(products.prod_name SEPARATOR ', ') AS product_names
        FROM orders
        INNER JOIN order_items ON orders.order_id = order_items.order_id
        INNER JOIN products ON order_items.prod_id = products.prod_id
        WHERE orders.login_id = ? AND orders.order_status != 'Cancelled'
        GROUP BY orders.order_id, orders.order_date, orders.total_amount, orders.order_status";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $login_id);  // Bind $login_id as an integer
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    $orders = $result->fetch_all(MYSQLI_ASSOC);

    // Loop through each order to fetch order details (order items)
    foreach ($orders as &$order) {
        $sqlOrderDetails = "SELECT order_items.quantity, products.prod_name, products.prod_image_path, products.prod_brand, products.prod_price_wholesale 
                            FROM order_items 
                            INNER JOIN products ON order_items.prod_id = products.prod_id 
                            WHERE order_items.order_id = ?";
        $stmtOrderDetails = $mysqli->prepare($sqlOrderDetails);
        $stmtOrderDetails->bind_param("i", $order['order_id']);
        $stmtOrderDetails->execute();
        $resultOrderDetails = $stmtOrderDetails->get_result();

        // Fetch the order items and assign them to $orderDetails
        $orderDetails = $resultOrderDetails->fetch_all(MYSQLI_ASSOC);

        // Attach the order details to the current order in the orders array
        $order['details'] = $orderDetails;

        $stmtOrderDetails->close();
    }
} else {
    $orders = [];
}

$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$errorMessage = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$stmt->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rice Website | My Order</title>
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
                <img src="../../images/order-details-icon.png" alt="Order" class="order-icon">MY ORDERS
            </h4>

            <div class="modal" id="messageModal" style="display:none;">
                <div class="modal-content">
                    <span class="close" id="closeModal">&times;</span>
                    <p id="modalMessage"><?php echo htmlspecialchars($successMessage . $errorMessage); ?></p>
                    <button id="okButton">OK</button>
                </div>
            </div>

            <div class="cart">
                <div class="summary">
                    <?php if (count($orders) > 0): ?>
                        <div class="orders-list">
                            <?php foreach ($orders as $order): ?>
                                <a href="function/order_details.php?order_id=<?php echo htmlspecialchars($order['order_id']); ?>" class="order-card-link">
                                    <div class="order-card">
                                        <div class="order-header">
                                            <div class="order-id">
                                                <img src="../images/order-icon.png" alt="Order ID" class="icon">
                                                <span>Order ID: <?php echo htmlspecialchars($order['order_id']); ?></span>
                                            </div>
                                            <div class="order-date">
                                                <span><strong>Placed on: </strong><?php echo htmlspecialchars(date('F j, Y', strtotime($order['order_date']))); ?></span>
                                            </div>
                                        </div>
                                        <div class="order-body">
                                            <table>
                                                <tbody>
                                                    <?php if (!empty($order['details'])): ?>
                                                        <?php $firstItem = $order['details'][0]; ?>
                                                        <tr class="order-item">
                                                            <td class="product-image">
                                                                <?php if (!empty($firstItem['prod_image_path'])): ?>
                                                                    <img src="<?php echo htmlspecialchars($firstItem['prod_image_path']); ?>" alt="<?php echo htmlspecialchars($firstItem['prod_name']); ?>">
                                                                <?php else: ?>
                                                                    <img src="../../images/default-image.png" alt="Default Image">
                                                                <?php endif; ?>
                                                            </td>

                                                            <td class="product-details">
                                                                <div class="prod-name"><?php echo htmlspecialchars($firstItem['prod_name']); ?></div>
                                                                <div class="prod-brand"><?php echo htmlspecialchars($firstItem['prod_brand']); ?></div>
                                                            </td>


                                                            <td class="product-price">
                                                                <div class="total-price">₱ <?php echo number_format($firstItem['quantity'] * $firstItem['prod_price_wholesale'], 2); ?></div>
                                                                <div class="prod-quantity">Qty: <?php echo htmlspecialchars($firstItem['quantity']); ?></div>
                                                            </td>
                                                        </tr>


                <?php 
                $remainingItems = count($order['details']) - 1;
                if ($remainingItems > 0): ?>
                    <tr>
    <td colspan="3">
        <div class="more-items">+ <?php echo $remainingItems; ?> more item<?php echo ($remainingItems > 1) ? 's' : ''; ?></div>
    </td>
</tr>

                    <?php endif; ?>

                                                    <?php endif; ?>
                                                </tbody>


                                            </table>

                                            <div class="order-detail">
                                                <div class="total">
                                                    <span><strong>Total Amount:</strong> ₱<?php echo htmlspecialchars(number_format($order['total_amount'], 2)); ?></span>
                                                </div>
                                            </div>
                                            <div class="order-status-wrapper">
                                                <div class="order-status">
                                                    <span><strong>Order Status: </strong><?php echo htmlspecialchars($order['order_status']); ?></span>
                                                </div>
                                                <a href="function/order_details.php?order_id=<?php echo htmlspecialchars($order['order_id']); ?>" class="details-link">
                                                    <span>Order Details</span>
                                                    <span class="arrow">&#x2192;</span>
                                                </a>
                                            </div>

                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No orders found.</p>
                    <?php endif; ?>
                </div>
            </div>

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

    function showModal(message) {
        const modal = document.getElementById('messageModal');
        const modalMessage = document.getElementById('modalMessage');
        modalMessage.textContent = message;
        modal.style.display = 'flex';
    }

    function hideModal() {
        const modal = document.getElementById('messageModal');
        modal.style.display = 'none';
    }

    window.addEventListener('resize', updateNavLinks);
    window.addEventListener('DOMContentLoaded', updateNavLinks);

    document.getElementById('closeModal').addEventListener('click', hideModal);
    document.getElementById('okButton').addEventListener('click', hideModal);
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            hideModal();
        }
    });

    <?php if (!empty($successMessage) || !empty($errorMessage)): ?>
        showModal("<?php echo htmlspecialchars($successMessage . $errorMessage); ?>");
    <?php endif; ?>
</script>

</html>