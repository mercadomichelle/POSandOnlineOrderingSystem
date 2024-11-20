<?php
session_start();

include('../../connection.php');

date_default_timezone_set('Asia/Manila');

if (!isset($_SESSION['login_id'])) {
    header("Location: ../../login.php");
    exit();
}

$login_id = $_SESSION['login_id'];
$order_id = $_SESSION['order_id'] ?? 0; // Fallback in case order_id is not set

if ($order_id == 0) {
    $_SESSION['error_message'] = "Order ID is not set. Please try again.";
    header("Location: confirm_order.php");
    exit();
}

$receiptID = '110' . $order_id; 
$_SESSION['receipt_id'] = $receiptID;  

// Fetch cart items from the database based on the logged-in user's cart
$sql = "SELECT products.prod_id, products.prod_name, cart.quantity, 
               CASE 
                   WHEN cart.price_type = 'wholesale' THEN products.prod_price_wholesale 
                   ELSE products.prod_price_retail 
               END AS prod_price 
        FROM cart 
        JOIN products ON cart.prod_id = products.prod_id 
        WHERE cart.login_id = ?";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $login_id);
$stmt->execute();
$result = $stmt->get_result();

$cart = [];
$subTotal = 0;

// Iterate over cart items to calculate the total
while ($row = $result->fetch_assoc()) {
    $totalPrice = $row['prod_price'] * $row['quantity'];
    $subTotal += $totalPrice;
    $cart[] = [
        'prod_id' => $row['prod_id'],
        'name' => $row['prod_name'],
        'quantity' => $row['quantity'],
        'price' => $row['prod_price'],
        'total' => $totalPrice
    ];
}

$total = $subTotal;
$_SESSION['total_amount'] = $total;

// Fetch the status_processed_at timestamp for the current order
$sql = "SELECT status_processed_at FROM orders WHERE order_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

$statusProcessedAt = null;

if ($row = $result->fetch_assoc()) {
    $statusProcessedAt = $row['status_processed_at'];
} else {
    // If no timestamp is found, handle the error
    $_SESSION['error_message'] = "Order details not found.";
    header("Location: confirm_order.php");
    exit();
}

// STOCKS NOTIFICATIONS
$sql = "SELECT p.prod_id, p.prod_brand, p.prod_name, p.prod_image_path, s.stock_quantity 
        FROM products p 
        LEFT JOIN stocks s ON p.prod_id = s.prod_id
        ORDER BY s.stock_quantity ASC";

$result = $mysqli->query($sql);

$stocks = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['stock_quantity'] = max(0, $row['stock_quantity']);
        $row['is_low_stock'] = $row['stock_quantity'] > 0 && $row['stock_quantity'] < 10;
        $row['is_out_of_stock'] = $row['stock_quantity'] == 0;
        $stocks[] = $row;
    }
} else {
    echo "No stocks found.";
}

$lowStockNotifications = [];
$outOfStockNotifications = [];

foreach ($stocks as $stock) {
    if ($stock['is_low_stock']) {
        $lowStockNotifications[] = 'Low stock: ' . htmlspecialchars($stock['prod_name']);
    } elseif ($stock['is_out_of_stock']) {
        $outOfStockNotifications[] = 'Out of stock: ' . htmlspecialchars($stock['prod_name']);
    }
}

$notifications = array_merge($lowStockNotifications, $outOfStockNotifications);

$stmt->close();
$mysqli->close();
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rice Website | Order Receipt</title>
    <link rel="icon" href="../../favicon.png" type="image/png">
    <link rel="stylesheet" href="../../styles/receipt.css">
</head>

<body>
    <header>
        <div><img src="../../favicon.png" alt="Logo" class="logo"></div>
        <div class="account-info">
            <div class="dropdown notifications-dropdown">
                <img src="../../images/notif-icon.png" alt="Notifications" class="notification-icon">
                <div class="dropdown-content" id="notificationDropdown">
                    <p class="notif">Notifications</p>
                    <?php if (empty($notifications)): ?>
                        <a href="#">No new notifications</a>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <a href="../stocks/staff_stocks.php"><?php echo $notification; ?></a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
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

        <div class="receipt-container" id="receiptContent">
            <img src="../../images/print-icon.png" alt="Print" class="print-btn" id="printReceiptBtn" width="25" height="25">
            <img src="../../images/close-icon.png" alt="Close" class="close-btn" id="closeReceiptBtn" width="25" height="25">

            <div class="header">
                <h1>Escalona-Delen Rice Dealer</h1>
                <p>(63)912-3456-789</p>
                <p>escalona-delen@gmail.com</p>
                <p>M.h Del Pilar St. Brgy 19,</p>
                <p>Batangas City, Philippines</p>
            </div>

            <div class="receipt-id">
                <strong>Receipt ID:</strong> <?php echo $receiptID; ?>
            </div>

            <table class="receipt-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Qty.</th>
                        <th>Price</th>
                        <th>Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['name']); ?></td>
                            <td><?= $item['quantity']; ?></td>
                            <td>₱ <?= number_format($item['price'], 2); ?></td>
                            <td>₱ <?= number_format($item['total'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <tr class="total-row">
                        <td colspan="3">TOTAL :</td>
                        <td>₱ <?= number_format($total, 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="3">CASH :</td>
                        <td>₱ <?= number_format($_SESSION['payment_received'] ?? 0, 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="3">CHANGE :</td>
                        <td>₱ <?= number_format(($_SESSION['payment_received'] ?? 0) - $total, 2); ?></td>
                    </tr>
                </tbody>
            </table>

            <div class="receipt-footer">
                <p class="footer">
                    Transaction No. <?php echo $receiptID; ?> - 
                    <?= htmlspecialchars(date('m/d/Y - h:i:s A', strtotime($statusProcessedAt))); ?>
                </p>
                <p class="footer-note">THIS IS YOUR OFFICIAL RECEIPT</p>
                <p class="footer-note1">Thank You, Come Again!</p>
            </div>
        </div>
    </main>


    <script>
        // Print functionality
        document.getElementById('printReceiptBtn').addEventListener('click', function() {
            window.print();
        });

        // Handle receipt close button click
        document.getElementById('closeReceiptBtn').addEventListener('click', function() {
            // Make a request to the clear_cart.php script to clear the cart
            fetch('clear_cart.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        // After successfully clearing the cart, redirect to staff.php
                        window.location.href = '../staff.php';
                    } else {
                        console.error('Failed to clear the cart.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
        });


        // NOTIFICATIONS
        document.addEventListener('DOMContentLoaded', function() {
            const notifIcon = document.querySelector('.notification-icon');
            const notifDropdown = document.getElementById('notificationDropdown');

            notifIcon.addEventListener('click', function(event) {
                event.stopPropagation(); // Prevent the click event from bubbling up
                notifDropdown.classList.toggle('show');
            });

            // Close the dropdown if the user clicks outside of it
            window.addEventListener('click', function(event) {
                if (!notifIcon.contains(event.target) && !notifDropdown.contains(event.target)) {
                    notifDropdown.classList.remove('show');
                }
            });
        });
    </script>

</body>

</html>