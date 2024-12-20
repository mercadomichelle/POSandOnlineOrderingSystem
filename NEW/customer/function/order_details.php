<?php
session_start();

include('../../connection.php');

if (!isset($_SESSION["username"])) {
    header("Location: ../../index.php");
    exit();
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

$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Fetch the order details with items
$sql = "SELECT orders.order_id, orders.order_date, orders.total_amount, order_items.prod_id, order_items.quantity, 
        order_items.branch_id, products.prod_name, products.prod_price_wholesale, products.prod_brand, products.prod_image_path
        FROM orders
        INNER JOIN order_items ON orders.order_id = order_items.order_id
        INNER JOIN products ON order_items.prod_id = products.prod_id
        WHERE orders.order_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

$orderSubTotal = 0;
$branchNames = [];  // Array to hold branch names for each product (if different products are from different branches)
if ($result->num_rows > 0) {
    $orderDetails = $result->fetch_all(MYSQLI_ASSOC);

    foreach ($orderDetails as $item) {
        // Calculate order subtotal
        $orderSubTotal += $item['prod_price_wholesale'] * $item['quantity'];

        // Fetch the branch name associated with the branch_id from the order_items
        $branch_id = $item['branch_id'];
        $sqlBranch = "SELECT branch_name FROM branches WHERE branch_id = ?";
        $stmtBranch = $mysqli->prepare($sqlBranch);
        $stmtBranch->bind_param("i", $branch_id);
        $stmtBranch->execute();
        $resultBranch = $stmtBranch->get_result();
        
        if ($resultBranch->num_rows === 1) {
            $branchData = $resultBranch->fetch_assoc();
            $branchNames[$item['prod_id']] = $branchData['branch_name'];  // Store branch name keyed by product id
        }
    }
} else {
    $orderDetails = [];
    $orderSubTotal = 0; // Default subtotal if no items found
}


// Fetch the status timestamps
$sql = "SELECT order_id, order_date, total_amount, status_processed_at, status_packed_at, status_shipped_at, status_delivered_at, order_status
        FROM orders WHERE order_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$orderTimestamps = $stmt->get_result()->fetch_assoc();


// Fetch cart items
$sql = "SELECT products.prod_id, products.prod_name, cart.quantity, products.prod_price_wholesale AS prod_price 
        FROM cart 
        JOIN products ON cart.prod_id = products.prod_id 
        WHERE cart.login_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $login_id);
$stmt->execute();
$result = $stmt->get_result();

$cartSubTotal = 0;
$cart = [];
while ($row = $result->fetch_assoc()) {
    $totalPrice = $row['prod_price'] * $row['quantity'];
    $cartSubTotal += $totalPrice;
    $cart[] = [
        'prod_id' => $row['prod_id'],
        'name' => $row['prod_name'],
        'quantity' => $row['quantity'],
        'price' => $row['prod_price']
    ];
}

$deliveryFee = 100.00;

// Calculate total using both subtotals
$total = $orderSubTotal + $cartSubTotal + $deliveryFee;


$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$errorMessage = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

$isOrderReceivedButtonDisabled = !empty($orderTimestamps['status_shipped_at']) && empty($orderTimestamps['status_delivered_at']) ? '' : 'disabled';


$stmt->close();
$mysqli->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rice Website | Order Details</title>
    <link rel="icon" href="../../favicon.png" type="image/png">
    <link rel="stylesheet" href="../../styles/order_details.css">
</head>

<body>
    <header>
        <div><img src="../../favicon.png" alt="Logo" class="logo"></div>
        <div class="nav-wrapper">
            <nav>
                <a href="../../customer/customer.php">HOME</a>
                <a href="../../customer/cust_products.php">PRODUCTS</a>
                <a href="../../customer/my_orders.php" id="orders-link" class="current">MY ORDERS</a>
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
        <div class="cart-summary">
            <button type="button" class="cancel-btn" onclick="window.location.href='../my_orders.php';">
                <img src="../../images/back-icon.png" alt="Back" class="back-icon">Back</button>

            <div class="summary-header">
                <h4><img src="../../images/order-details-icon.png" alt="Cart" class="cart-icon">ORDER DETAILS</h4>
                <p><strong> Branch: </strong><?php echo isset($branchNames[$item['prod_id']]) ? htmlspecialchars($branchNames[$item['prod_id']]) : 'Unknown'; ?></p>  <!-- Display branch -->
                <p class="order-date">
                <strong> Order Date: </strong>
                    <?php if (!empty($orderDetails)): ?>
                        <?php echo htmlspecialchars(date('F j, Y', strtotime($orderDetails[0]['order_date']))); ?>
                    <?php else: ?>
                        <em>Date not available</em>
                    <?php endif; ?>
                </p>
            </div>

            <div class="cart">
                <div class="summary">
                    <table>
                        <tbody>
                            <?php foreach ($orderDetails as $item): ?>
                                <tr class="order-item">
                                    <td class="product-image">
                                        <?php if (!empty($item['prod_image_path'])): ?>
                                            <img src="<?php echo htmlspecialchars($item['prod_image_path']); ?>" alt="<?php echo htmlspecialchars($item['prod_name']); ?>">
                                        <?php else: ?>
                                            <img src="../../images/default-image.png" alt="Default Image">
                                        <?php endif; ?>
                                    </td>

                                    <td class="product-details">
                                        <div class="prod-name"><?php echo htmlspecialchars($item['prod_name']); ?></div>
                                        <div class="prod-brand"><?php echo htmlspecialchars($item['prod_brand']); ?></div>
                                    </td>

                                    <td class="product-price">
                                        <div class="total-price">₱ <?php echo number_format($item['quantity'] * $item['prod_price_wholesale'], 2); ?></div>
                                        <div class="prod-quantity">Qty: <?php echo htmlspecialchars($item['quantity']); ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="delivery-fee">
                        <span><strong>Delivery Fee</strong></span>
                        <span>₱ <?php echo number_format($deliveryFee, 2); ?></span>
                    </div>

                    <div class="total">
                        <span>TOTAL</span>
                        <span>₱ <?php echo number_format($total, 2); ?></span>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="order-status-container">
                    <h5><img src="../../images/order-status-icon.png" alt="Status" class="status-icon" style="width: 35px; height: 35px; margin: 5px;">ORDER STATUS</h5>

                    <div class="order-timeline">
                    <div class="order-timeline-item">
    <div class="order-timeline-date <?php echo empty($orderTimestamps['status_packed_at']) ? 'pending' : 'not-pending'; ?>">
        <?php echo !empty($orderTimestamps['status_packed_at']) ? htmlspecialchars(date('M j, Y g:i A', strtotime($orderTimestamps['status_packed_at']))) : 'Pending'; ?>
    </div>
    <div class="order-timeline-circle <?php echo empty($orderTimestamps['status_packed_at']) ? 'pending' : 'not-pending'; ?>"></div>
    <div class="order-timeline-icon">
        <img src="../../images/processed-icon.png" alt="Processed" class="<?php echo empty($orderTimestamps['status_packed_at']) ? 'pending' : 'not-pending'; ?>">
    </div>
    <div class="order-timeline-status <?php echo empty($orderTimestamps['status_packed_at']) ? 'pending' : 'not-pending'; ?>">Order has been processed</div>
</div>

<div class="order-timeline-item">
    <div class="order-timeline-date <?php echo empty($orderTimestamps['status_packed_at']) ? 'pending' : 'not-pending'; ?>">
        <?php echo !empty($orderTimestamps['status_packed_at']) ? htmlspecialchars(date('M j, Y g:i A', strtotime($orderTimestamps['status_packed_at']))) : 'Pending'; ?>
    </div>
    <div class="order-timeline-circle <?php echo empty($orderTimestamps['status_packed_at']) ? 'pending' : 'not-pending'; ?>"></div>
    <div class="order-timeline-icon">
        <img src="../../images/packed-icon.png" alt="Packed" class="<?php echo empty($orderTimestamps['status_packed_at']) ? 'pending' : 'not-pending'; ?>">
    </div>
    <div class="order-timeline-status <?php echo empty($orderTimestamps['status_packed_at']) ? 'pending' : 'not-pending'; ?>">Order has been packed</div>
</div>

<div class="order-timeline-item">
    <div class="order-timeline-date <?php echo empty($orderTimestamps['status_shipped_at']) ? 'pending' : 'not-pending'; ?>">
        <?php echo !empty($orderTimestamps['status_shipped_at']) ? htmlspecialchars(date('M j, Y g:i A', strtotime($orderTimestamps['status_shipped_at']))) : 'Pending'; ?>
    </div>
    <div class="order-timeline-circle <?php echo empty($orderTimestamps['status_shipped_at']) ? 'pending' : 'not-pending'; ?>"></div>
    <div class="order-timeline-icon">
        <img src="../../images/shipped-icon.png" alt="Shipped" class="<?php echo empty($orderTimestamps['status_shipped_at']) ? 'pending' : 'not-pending'; ?>">
    </div>
    <div class="order-timeline-status <?php echo empty($orderTimestamps['status_shipped_at']) ? 'pending' : 'not-pending'; ?>">Order has been shipped</div>
</div>

<div class="order-timeline-item">
    <div class="order-timeline-date <?php echo empty($orderTimestamps['status_delivered_at']) ? 'pending' : 'not-pending'; ?>">
        <?php echo !empty($orderTimestamps['status_delivered_at']) ? htmlspecialchars(date('M j, Y g:i A', strtotime($orderTimestamps['status_delivered_at']))) : 'Pending'; ?>
    </div>
    <div class="order-timeline-circle <?php echo empty($orderTimestamps['status_delivered_at']) ? 'pending' : 'not-pending'; ?>"></div>
    <div class="order-timeline-icon">
        <img src="../../images/delivered-icon.png" alt="Delivered" class="<?php echo empty($orderTimestamps['status_delivered_at']) ? 'pending' : 'not-pending'; ?>">
    </div>
    <div class="order-timeline-status <?php echo empty($orderTimestamps['status_delivered_at']) ? 'pending' : 'not-pending'; ?>">Order delivered</div>
</div>



                    </div>
                </div>

            </div>
            <!-- Show the 'Cancel Order' button if the order has not been packed yet -->
            <?php if (empty($orderTimestamps['status_packed_at'])): ?>
                <form action="cancel_order.php" method="post" class="button">
                    <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>">
                    <button type="submit" class="confirm-btn">Cancel Order</button>
                </form>
            <?php endif; ?>

            <!-- Update the "Order Received" button section -->
            <?php if (!empty($orderTimestamps['status_packed_at']) && empty($orderTimestamps['status_delivered_at'])): ?>
                <form id="orderReceivedForm" action="order_received.php" method="post" class="button">
                    <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order_id); ?>">
                    <button type="submit" class="confirm-btn" id="orderReceivedBtn" <?php echo $isOrderReceivedButtonDisabled; ?>>Order Received</button>
                </form>
            <?php endif; ?>


            <?php if (!empty($orderTimestamps['status_delivered_at'])): ?>
                <form id="orderReceivedForm" action="#" method="post" class="button">
                    <input type="hidden" name="order_id" value="5">
                    <button type="submit" id="orderReceivedBtn" class="confirm-btn">Order Received</button>
                </form>
            <?php endif; ?>
        </div>

        </div>

        <div class="modal" id="messageModal" style="display:none;">
            <div class="modal-content">
                <span class="close" id="closeModal">&times;</span>
                <p id="modalMessage"><?php echo htmlspecialchars($successMessage . $errorMessage); ?></p>
                <button class="ok-btn" id="okButton">OK</button>
            </div>
        </div>

        <div id="loadingScreen" class="loading-screen" style="display: none;">
            <div class="spinner"></div>
            <p>Loading...</p>
        </div>

    </main>

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
            console.log("Showing modal with message:", message); // Debugging output

            const modal = document.getElementById('messageModal');
            const modalMessage = document.getElementById('modalMessage');
            modalMessage.textContent = message;
            modal.style.display = 'flex';
        }

        function hideModal() {
            const modal = document.getElementById('messageModal');
            modal.style.display = 'none';
        }

        document.addEventListener('DOMContentLoaded', function() {
            const orderReceivedForm = document.getElementById('orderReceivedForm');
            const orderReceivedBtn = document.getElementById('orderReceivedBtn');

            console.log('Form:', orderReceivedForm); // Debugging output
            console.log('Button:', orderReceivedBtn); // Debugging output

            if (orderReceivedForm && orderReceivedBtn) {
                orderReceivedForm.addEventListener('submit', function(event) {
                    orderReceivedBtn.disabled = true;
                    orderReceivedBtn.textContent = "Processing...";
                });
            } else {
                console.error('Form or button not found');
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const orderReceivedBtn = document.getElementById('orderReceivedBtn');
            const statusShippedAt = <?php echo json_encode(!empty($orderTimestamps['status_shipped_at'])); ?>;
            const statusDeliveredAt = <?php echo json_encode(!empty($orderTimestamps['status_delivered_at'])); ?>;

            // Disable the button if status_shipped_at is empty or status_delivered_at is not empty
            if (!statusShippedAt || statusDeliveredAt) {
                if (orderReceivedBtn) {
                    orderReceivedBtn.disabled = true;
                    orderReceivedBtn.textContent = "Order Received"; // Optional: Update text if needed
                }
            }
        });

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

        document.querySelector('form').addEventListener('submit', function() {
            document.getElementById('loadingScreen').style.display = 'flex';
        });
    </script>
</body>

</html>