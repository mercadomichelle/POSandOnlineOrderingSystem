<?php
session_start();

include('../../connection.php');

$login_id = $_SESSION['login_id'];

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

// Fetch user's profile delivery address
$sql = "SELECT address, city FROM profile WHERE username = ?";
$stmt2 = $mysqli->prepare($sql); // Use a different variable for this statement
$stmt2->bind_param("s", $_SESSION['username']);
$stmt2->execute();
$addressResult = $stmt2->get_result();

if ($addressResult->num_rows === 1) {
    $addressData = $addressResult->fetch_assoc();
    $formattedAddress = $addressData['address'];
} else {
    $formattedAddress = "No address found";
}

$stmt2->close(); // Close the second statement

// Fetch branch details based on the selected branch ID
$branchName = "No branch selected";
if ($_SESSION['selected_branch']) {
    $selectedBranch = $_SESSION['selected_branch'];
    $sql = "SELECT branch_name FROM branches WHERE branch_id = ?";
    $stmtBranch = $mysqli->prepare($sql);
    $stmtBranch->bind_param("i", $selectedBranch);
    $stmtBranch->execute();
    $resultBranch = $stmtBranch->get_result();

    if ($resultBranch->num_rows === 1) {
        $branchData = $resultBranch->fetch_assoc();
        $branchName = $branchData['branch_name'];
    }
    $stmtBranch->close();
}


// Set delivery fee to a fixed value
$deliveryFee = 100; // Default fee

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
    <link rel="icon" href="../../favicon.png" type="image/png">
    <link rel="stylesheet" href="../../styles/confirm_order.css">
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
        <div class="cart-summary">
            <button type="button" class="cancel-btn" onclick="window.location.href='../cust_products.php';">
                <img src="../../images/back-icon.png" alt="Back" class="back-icon">Back</button>

            <?php if ($formattedAddress === "No address found"): ?>
                <div class="no-address-warning">
                    <button onclick="window.location.href='../my_profile.php'" class="update-profile-btn">Go to Profile</button>
                </div>
            <?php endif; ?>

            <?php if ($errorMessage): ?>
                <div class="message error">
                    <p><?php echo $errorMessage; ?></p>
                </div>
            <?php endif; ?>

            <div class="cart">
                <div class="summary">

                    <h4>
                        <img src="../../images/checkout-icon.png" alt="Cart" class="cart-icon">CHECKOUT ORDER
                    </h4>

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
                        <div class="selected-branch">
                            <h6>Selected Branch: <strong><?php echo htmlspecialchars($branchName); ?></strong></h6>
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
        // Initially check if the address is set to enable or disable the button
        const formattedAddress = "<?php echo $formattedAddress; ?>";
        const confirmButton = document.querySelector('.confirm-btn');

        // Disable if no address, enable otherwise
        if (formattedAddress === "No address found") {
            confirmButton.disabled = true;
        } else {
            confirmButton.disabled = false;
        }

        // Dropdown selection logic for address
        document.getElementById('selected-address').addEventListener('click', function(event) {
            event.stopPropagation();
            const dropdownOptions = document.getElementById('dropdown-options');
            dropdownOptions.style.display = dropdownOptions.style.display === 'block' ? 'none' : 'block';
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