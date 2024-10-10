<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['login_id'])) {
    header("Location: ../../homepage.php");
    exit();
}

// Fetch order details from the session
$prod_ids = $_SESSION['cart']['prod_id'] ?? [];
$quantities = $_SESSION['cart']['quantity'] ?? [];

// Validate product IDs and quantities
if (count($prod_ids) !== count($quantities)) {
    $_SESSION['error_message'] = "Mismatch between product IDs and quantities.";
    header("Location: ../cust_products.php");
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
$stmt1 = $mysqli->prepare($sql); // Use a new variable for this statement
$stmt1->bind_param("i", $login_id);
$stmt1->execute();
$result = $stmt1->get_result();

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

$stmt1->close(); // Close the first statement after it's used

// Fetch user's profile delivery address and city
$sql = "SELECT address, city FROM profile WHERE username = ?";
$stmt2 = $mysqli->prepare($sql); // Use a different variable for this statement
$stmt2->bind_param("s", $_SESSION['username']);
$stmt2->execute();
$addressResult = $stmt2->get_result();

if ($addressResult->num_rows === 1) {
    $addressData = $addressResult->fetch_assoc();
    $formattedAddress = $addressData['address'];
    $city = $addressData['city']; // Fetch city
    error_log("City retrieved: " . $city);
} else {
    $formattedAddress = "No address found";
    $city = null; // Handle case where city is not found
}

$stmt2->close(); // Close the second statement

// Fetch delivery fee based on user's city
$deliveryFee = 0; // Initialize default fee

if ($city) {
    // Corrected SQL query with a placeholder
    $sql = "SELECT city, fee FROM delivery_fees WHERE city = ?";
    $stmt3 = $mysqli->prepare($sql); // Use a new variable for this statement

    if ($stmt3 === false) {
        error_log("Error preparing statement: " . $mysqli->error);
        $_SESSION['errorMessage'] = "Database error.";
        exit(); // Exit if there is a serious database error
    }

    // Bind the city parameter to the query
    $stmt3->bind_param("s", $city);

    // Execute the statement
    if ($stmt3->execute()) {
        // Fetch the result
        $feeResult = $stmt3->get_result();

        if ($feeResult->num_rows === 1) {
            $feeData = $feeResult->fetch_assoc();
            $deliveryFee = $feeData['fee']; // Set the fee from the database
        } else {
            $deliveryFee = 1;  // Default delivery fee if no match found
            error_log("No delivery fee found for city: " . $city);
            $_SESSION['errorMessage'] = "Delivery fee not found for city: " . $city;
        }
    } else {
        error_log("SQL Error: " . $stmt3->error);
    }

    $stmt3->close(); // Close the third prepared statement
} else {
    $deliveryFee = 0; // Default if city is not set
}

// Calculate the total (subtotal + delivery fee)
$total = $subTotal + $deliveryFee;

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
    <title>Rice Website | Checkout Order</title>
    <link rel="stylesheet" href="../../styles/confirm_order.css">
</head>

<body>
    <header>
        <div class="logo">RICE</div>
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
        <div class="cart-summary">
            <button type="button" class="cancel-btn" onclick="window.location.href='../cust_products.php';">
                <img src="../../images/back-icon.png" alt="Back" class="back-icon">Back</button>

            <?php if ($formattedAddress === "No address found"): ?>
                <div class="no-address-warning">
                    <button onclick="window.location.href='../my_profile.php'" class="update-profile-btn">Go to Profile</button>
                </div>
            <?php endif; ?>

            <h4>
                <img src="../../images/checkout-icon.png" alt="Cart" class="cart-icon">CHECKOUT ORDER
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
                                    <td>₱ <?php echo number_format($item['price'], 2); ?></td>
                                    <td>₱ <?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>


                    <div class="summary-item">
                        <div class="sub-total">
                            <span>Sub Total (<?php echo count($cart); ?> Item/s)</span>
                            <span>₱ <?php echo number_format($subTotal, 2); ?></span>
                        </div>
                        <div class="delivery-fee">
                            <span>Delivery Fee</span>
                            <span>₱ <?php echo number_format($deliveryFee, 2); ?></span>
                        </div>

                    </div>
                    <div class="total">
                        <span>TOTAL</span>
                        <span>₱ <?php echo number_format($total, 2); ?></span>
                    </div>
                </div>

                <div class="divider"></div>

                <div class="delivery-details">
                    <h5><img src="../../images/delivery-icon.png" alt="Delivery" class="delivery-icon">DELIVERY DETAILS</h5>
                    <div class="delivery-address">
                        <h6>Select Delivery Details:</h6>
                        <div class="custom-dropdown">
                            <div class="dropdown-selected" id="selected-address">
                                <?php echo htmlspecialchars($formattedAddress); ?>
                            </div>
                            <div class="dropdown-options" id="dropdown-options">
                                <div class="dropdown-option" data-value="<?php echo htmlspecialchars($formattedAddress); ?>">
                                    <?php echo htmlspecialchars($formattedAddress); ?>
                                </div>
                            </div>
                        </div>
                    </div>


                    <div>
                        <input type="checkbox" name="request_invoice" id="request_invoice">
                        <label for="request_invoice">Request Invoice</label>
                    </div>

                </div>
            </div>

            <form action="place_order.php" method="post" class="button">
                <button type="submit" class="confirm-btn">Place an Order</button>
            </form>


            <div id="loadingScreen" class="loading-screen" style="display: none;">
                <div class="spinner"></div>
                <p>Loading...</p>
            </div>

    </main>

    <script>
        document.querySelector('.confirm-btn').disabled = true;

        document.getElementById('selected-address').addEventListener('click', function() {
            const dropdownOptions = document.getElementById('dropdown-options');
            dropdownOptions.style.display = dropdownOptions.style.display === 'block' ? 'none' : 'block';
        });

        document.querySelectorAll('.dropdown-option').forEach(option => {
            option.addEventListener('click', function() {
                const selectedAddress = this.getAttribute('data-value');
                document.getElementById('selected-address').textContent = selectedAddress;
                document.getElementById('dropdown-options').style.display = 'none';

                // AJAX request to get delivery fee based on selected address
                fetch(`get_delivery_fee.php?address=${encodeURIComponent(selectedAddress)}`)
                    .then(response => response.json())
                    .then(data => {
                        const deliveryFee = data.fee;
                        document.querySelector('.delivery-fee span:last-child').textContent = `₱ ${deliveryFee.toFixed(2)}`;

                        // Update total
                        const subTotal = parseFloat(document.querySelector('.sub-total span:last-child').textContent.replace('₱ ', ''));
                        const total = subTotal + parseFloat(deliveryFee);
                        document.querySelector('.total span:last-child').textContent = `₱ ${total.toFixed(2)}`;
                    })
                    .catch(error => console.error('Error fetching delivery fee:', error));
            });
        });


        // Hide dropdown if clicked outside
        document.addEventListener('click', function(event) {
            const dropdown = document.querySelector('.custom-dropdown');
            if (!dropdown.contains(event.target)) {
                document.getElementById('dropdown-options').style.display = 'none';
            }
        });

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

        document.querySelector('form').addEventListener('submit', function() {
            document.getElementById('loadingScreen').style.display = 'flex';
        });
    </script>
</body>

</html>