<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['login_id'])) {
    header("Location: ../../login.php");
    exit();
}

// Fetch order details from the session
$prod_ids = $_SESSION['cart']['prod_id'] ?? [];
$quantities = $_SESSION['cart']['quantity'] ?? [];

// Validate product IDs and quantities
if (count($prod_ids) !== count($quantities)) {
    $_SESSION['error_message'] = "Mismatch between product IDs and quantities.";
    header("Location: ../staff.php");
    exit();
}

// Calculate total quantity
$totalQuantity = array_sum($quantities);

// Database connection details
$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$login_id = $_SESSION['login_id'];

// Fetch cart items
$sql = "SELECT products.prod_id, products.prod_name, cart.quantity, products.prod_price_wholesale AS prod_price 
        FROM cart 
        JOIN products ON cart.prod_id = products.prod_id 
        WHERE cart.login_id = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $login_id);
$stmt->execute();
$result = $stmt->get_result();

$cart = [];
$subTotal = 0;

while ($row = $result->fetch_assoc()) {
    $totalPrice = $row['prod_price'] * $row['quantity'];
    $subTotal += $totalPrice;
    $cart[] = [
        'prod_id' => $row['prod_id'],
        'name' => $row['prod_name'],
        'quantity' => $row['quantity'],
        'price' => $row['prod_price']
    ];
}

$total = $subTotal;



// STOCKS NOTIFICATIONS
$sql = "SELECT p.prod_id, p.prod_brand, p.prod_name, p.prod_image_path, s.stock_quantity 
        FROM products p 
        LEFT JOIN stocks s ON p.prod_id = s.prod_id
        ORDER BY s.stock_quantity ASC";

$result = $mysqli->query($sql);

$stocks = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
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

$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$errorMessage = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rice Website | Confirm Order</title>
    <link rel="stylesheet" href="../../styles/confirm_order.css">
</head>

<body>
    <header>
        <div class="logo">RICE</div>
        <div class="account-info">
            <div class="dropdown notifications-dropdown">
                <img src="../../images/notif-icon.png" alt="Notifications" class="notification-icon">
                <div class="dropdown-content" id="notificationDropdown">
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

        <div class="cart-summary">
            <button type="button" class="cancel-btn" onclick="window.location.href='../staff.php';">
                <img src="../../images/back-icon.png" alt="Back" class="back-icon">Back</button>

            <h4>
                <img src="../../images/checkout-icon.png" alt="Cart" class="cart-icon"> 
            </h4>

            <?php if ($errorMessage): ?>
                <div class="message error">
                    <p><?php echo $errorMessage; ?></p>
                </div>
            <?php endif; ?>

            <div class="cart">
                <div class="summary">
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
                                    <td>₱<?php echo number_format($item['price'], 2); ?></td>
                                    <td>₱<?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="summary-item">
                        <div class="delivery-fee">
                            <span>Sub Total (<?php echo count($cart); ?> Item/s)</span>
                            <span>₱<?php echo number_format($subTotal, 2); ?></span>
                        </div>
                    </div>
                    <div class="total">
                        <span>TOTAL</span>
                        <span>₱<?php echo number_format($total, 2); ?></span>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="payment-section">
                    <h5><img src="../../images/payment-icon.png" alt="Delivery" class="delivery-icon">PAYMENT</h5>
                    <div class="cal-pad">

                        <div class="total-line">
                            <span>Total Amount:</span>
                            <span>₱<?php echo number_format($total, 2); ?></span>
                        </div>

                        <div class="display-box">
                            <input type="text" id="entered-amount" placeholder="Enter amount here" maxlength="8" oninput="validateNumberInput(this)">
                            <button class="key clear-btn" onclick="clearInput()">X</button>
                        </div>

                        <div class="keypad">
                            <div class="row">
                                <button class="key" onclick="enterNumber('1')">1</button>
                                <button class="key" onclick="enterNumber('2')">2</button>
                                <button class="key" onclick="enterNumber('3')">3</button>
                            </div>
                            <div class="row">
                                <button class="key" onclick="enterNumber('4')">4</button>
                                <button class="key" onclick="enterNumber('5')">5</button>
                                <button class="key" onclick="enterNumber('6')">6</button>
                            </div>
                            <div class="row">
                                <button class="key" onclick="enterNumber('7')">7</button>
                                <button class="key" onclick="enterNumber('8')">8</button>
                                <button class="key" onclick="enterNumber('9')">9</button>
                            </div>
                            <div class="row">
                                <button class="key" onclick="enterNumber('.')">.</button>
                                <button class="key" onclick="enterNumber('0')">0</button>
                                <button class="key" onclick="enterNumber('00')">00</button>
                            </div>
                        </div>
                        <form action="payment.php" method="post" class="button">
                            <button type="submit" class="pay-btn">PAY</button>
                        </form>

                        <div class="receipt">
                            <input type="checkbox" name="request_receipt" id="request_receipt" checked>
                            <label for="request_receipt">Receipt</label>
                        </div>
                    </div>
                </div>

            </div>
        </div>

        <div id="loadingScreen" class="loading-screen" style="display: none;">
            <div class="spinner"></div>
            <p>Loading...</p>
        </div>
    </main>
</body>

</html>
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

    function validateNumberInput(input) {
        input.value = input.value.replace(/[^0-9.]/g, '');
    }

    function enterNumber(num) {
        const enteredAmount = document.getElementById('entered-amount');
        enteredAmount.value += num;
    }

    function clearInput() {
        document.getElementById('entered-amount').value = '';
    }

    function processPayment() {
        const enteredAmount = document.getElementById('entered-amount').value;
        if (enteredAmount) {
            alert('Payment of ₱' + enteredAmount + ' processed successfully!');
        } else {
            alert('Please enter an amount.');
        }
    }

    window.addEventListener('resize', updateNavLinks);
    window.addEventListener('DOMContentLoaded', updateNavLinks);

    document.querySelector('form').addEventListener('submit', function() {
        document.getElementById('loadingScreen').style.display = 'flex';
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