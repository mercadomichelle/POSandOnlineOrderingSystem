<?php
session_start();
include('../connection.php');

// Fetch the selected branch ID from the form or session (preserved across page refresh)
$selectedBranch = isset($_POST['branch_id']) ? $_POST['branch_id'] : (isset($_SESSION['selected_branch']) ? $_SESSION['selected_branch'] : null);

// Store the selected branch in the session to preserve it across page reloads
if ($selectedBranch) {
    $_SESSION['selected_branch'] = $selectedBranch;
} else {
    $_SESSION['selected_branch'] = null;
}

// Fetch user details (same as before)
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];

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
    } else {
        // If login is invalid, redirect to login page
        header("Location: ../index.php");
        exit();
    }
} else {
    // User is not logged in, handle as guest
    $_SESSION["first_name"] = "Guest";
    $_SESSION["last_name"] = "";
    $_SESSION["login_id"] = null; // Set login_id to null or don't use it
}

// Modify the SQL query to filter by branch if selected
$sql = "SELECT products.prod_id, products.prod_brand, products.prod_name, products.prod_price_wholesale AS prod_price, 
        products.prod_image_path, COALESCE(SUM(stocks.stock_quantity), 0) AS stock_quantity 
        FROM products 
        JOIN stocks ON products.prod_id = stocks.prod_id";

if ($selectedBranch) {
    // If a branch is selected, filter the products and stock by branch
    $sql .= " WHERE stocks.branch_id = ?";
}

$sql .= " GROUP BY 
            products.prod_id, products.prod_brand, products.prod_name, products.prod_price_wholesale, products.prod_image_path
         ORDER BY 
            prod_name ASC";

// Prepare the statement based on whether a branch is selected
if ($selectedBranch) {
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $selectedBranch);
} else {
    $stmt = $mysqli->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();

$products = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

usort($products, function ($a, $b) {
    $aStock = $a['stock_quantity'];
    $bStock = $b['stock_quantity'];

    // Treat any non-positive stock quantity the same
    if ($aStock <= 0 && $bStock <= 0) {
        return 0; // Both are out of stock, keep original order
    }
    if ($aStock <= 0) {
        return 1; // Out-of-stock items go to the end
    }
    if ($bStock <= 0) {
        return -1; // Out-of-stock items go to the end
    }
    return 0; // Both are in stock, keep original order
});

// Handle quantity update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    $prod_id = $_POST['prod_id'];
    $quantity = max(1, (int)$_POST['quantity']);
    $login_id = $_SESSION['login_id'];

    // Update the cart with the new quantity
    $sql = "UPDATE cart SET quantity = ? WHERE prod_id = ? AND login_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("iii", $quantity, $prod_id, $login_id);
    $stmt->execute();

    // Update stock quantity
    $sql = "UPDATE stocks SET stock_quantity = stock_quantity - ? WHERE prod_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $quantity, $prod_id);
    $stmt->execute();

    // Set success message and avoid further redirects
    $_SESSION['success_message'] = "Cart updated successfully!";
    header("Location: cust_products.php");
    exit();
}

// Handle item removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $prod_id = $_POST['prod_id'];
    $login_id = $_SESSION['login_id'];

    // Get the quantity to add back to stock
    $sql = "SELECT quantity FROM cart WHERE prod_id = ? AND login_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $prod_id, $login_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $quantity = $result->fetch_assoc()['quantity'];

    // Remove item from cart
    $sql = "DELETE FROM cart WHERE prod_id = ? AND login_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $prod_id, $login_id);
    $stmt->execute();

    // Update stock quantity
    $sql = "UPDATE stocks SET stock_quantity = stock_quantity + ? WHERE prod_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $quantity, $prod_id);
    $stmt->execute();

    // Redirect to the same page with error message
    header("Location: cust_products.php");
    exit();
}

// Fetch cart items
$login_id = $_SESSION['login_id'];

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

// Calculate the subtotal only if there are items in the cart
if ($result->num_rows > 0) {
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
}


$cartIsEmpty = empty($cart);

// Validate the cart items for the selected branch
if ($selectedBranch) {
    $sql = "SELECT cart.prod_id, cart.quantity, stocks.stock_quantity 
                FROM cart 
                JOIN stocks ON cart.prod_id = stocks.prod_id 
                WHERE cart.login_id = ? AND stocks.branch_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $login_id, $selectedBranch);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch valid products for the selected branch
    $validProducts = [];
    while ($row = $result->fetch_assoc()) {
        // Store product id, quantity in the cart, and stock quantity available in the selected branch
        $validProducts[$row['prod_id']] = [
            'cart_quantity' => $row['quantity'],
            'stock_quantity' => $row['stock_quantity']
        ];
    }

    // Check the cart items against valid products and their stock availability
    foreach ($cart as $index => $item) {
        if (!array_key_exists($item['prod_id'], $validProducts)) {
            // Product is not available in the selected branch, remove it from the cart
            $prod_id = $item['prod_id'];

            // Remove invalid item from cart
            $sql = "DELETE FROM cart WHERE prod_id = ? AND login_id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("ii", $prod_id, $login_id);
            $stmt->execute();

            // Remove the item from the $cart array
            unset($cart[$index]);

            // Set error message for the removed product (not available in the selected branch)
            $_SESSION['error_message'] = "Some of the product was removed because it's not available in the selected branch.";
        } elseif ($validProducts[$item['prod_id']]['stock_quantity'] <= 0) {
            // Product exists but the stock is zero, remove it from the cart
            $prod_id = $item['prod_id'];

            // Remove invalid item from cart
            $sql = "DELETE FROM cart WHERE prod_id = ? AND login_id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("ii", $prod_id, $login_id);
            $stmt->execute();

            // Remove the item from the $cart array
            unset($cart[$index]);

            // Set error message for the removed product (no stock in the selected branch)
            $_SESSION['error_message'] = "Some of the product was removed because it's out of stock in the selected branch.";
        }
    }
}



$successMessage = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
$errorMessage = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

// Close the statement at the end of the script
$stmt->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rice Website | Products</title>
    <link rel="icon" href="../favicon.png" type="image/png">
    <link rel="stylesheet" href="../styles/cust_products.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <header>
        <div><img src="../favicon.png" alt="Logo" class="logo"></div>
        <div class="nav-wrapper">
            <nav>
                <a href="customer.php">HOME</a>
                <a href="cust_products.php" class="current">PRODUCTS</a>
                <?php if (isset($_SESSION["username"])): ?>
                    <a href="my_orders.php" id="orders-link">MY ORDERS</a>
                    <a href="about_us.php" id="about-link">ABOUT US</a>
                <?php else: ?>
                    <a href="about_us.php" id="about-link">ABOUT US</a>
                <?php endif; ?>
            </nav>
        </div>
        <div class="account-info">
            <?php if (isset($_SESSION["username"])): ?>
                <!-- Show user name and logout option if logged in -->
                <span class="user-name"><?php echo htmlspecialchars($_SESSION["first_name"] . " " . $_SESSION["last_name"]); ?></span>
                <div class="dropdown">
                    <img src="../images/account-icon.png" alt="Account" class="account-icon">
                    <div class="dropdown-content">
                        <a href="my_profile.php">My Profile</a>
                        <a href="../logout.php">Logout</a>
                    </div>
                </div>
            <?php else: ?>
                <!-- Show login button if not logged in -->
                <div class="login-btn">
                    <span><a class="user-name" href="../login.php">Login</a></span>
                    <img src="../images/account-icon.png" alt="Account" class="account-icon">
                </div>
            <?php endif; ?>
        </div>

    </header>

    <main>
        <div class="products">
            <div class="product-controls">
                <button class="filter-button" id="wholesaleBtn">
                    <img src="../../images/wholesale-icon.png" alt="Wholesale">WHOLESALE
                </button>

                <form method="POST" id="branchForm">
                    <select class="branch-selector" id="branchSelector" name="branch_id" onchange="this.form.submit()">
                        <option value="">Select Branch</option>
                        <option value="1" <?php echo isset($selectedBranch) && $selectedBranch == 1 ? 'selected' : ''; ?>>Calero</option>
                        <option value="2" <?php echo isset($selectedBranch) && $selectedBranch == 2 ? 'selected' : ''; ?>>Bauan</option>
                        <option value="3" <?php echo isset($selectedBranch) && $selectedBranch == 3 ? 'selected' : ''; ?>>San Pascual</option>
                    </select>
                </form>

                <div class="search-container">
                    <div class="search-wrapper">
                        <input type="text" placeholder="Search..." id="searchInput">
                        <img src="../../images/search-icon.png" alt="Search" class="search-icon">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <?php
                        // Ensure stock quantity is not negative
                        $display_stock = max(0, $product['stock_quantity']);
                        ?>
                        <div class="product-card">
                            <?php if ($display_stock == 0): ?>
                                <div class="out-of-stock-overlay">OUT OF STOCK</div>
                            <?php endif; ?>
                            <img src="<?php echo htmlspecialchars($product['prod_image_path']); ?>" alt="<?php echo htmlspecialchars($product['prod_name']); ?>">
                            <h4><?php echo htmlspecialchars($product['prod_brand']); ?></h4>
                            <p><?php echo htmlspecialchars($product['prod_name']); ?></p>
                            <h3>₱ <?php echo number_format($product['prod_price'], 2); ?> / sack</h3>
                            <div class="stock-info">Current Stocks: <?php echo $display_stock; ?></div>
                            <?php if (isset($_SESSION["username"])): ?>
                                <form class="product-actions" method="POST" action="function/add_to_cart.php">
                                    <input type="hidden" name="prod_id" value="<?php echo htmlspecialchars($product['prod_id']); ?>">
                                    <button class="qty-btn" type="button" onclick="updateQuantity(this, -1)" <?php echo $display_stock == 0 ? 'disabled' : ''; ?>>-</button>
                                    <input type="number" class="qty-input" value="1" min="1" max="<?php echo $display_stock; ?>" name="quantity" data-max="<?php echo $display_stock; ?>" data-previous-value="1" <?php echo $display_stock == 0 ? 'disabled' : ''; ?>>
                                    <button class="qty-btn" type="button" onclick="updateQuantity(this, 1)" <?php echo $display_stock == 0 ? 'disabled' : ''; ?>>+</button>
                                    <button class="add-to-cart-btn" type="submit" <?php echo $display_stock == 0 ? 'disabled' : ''; ?>>Add</button>
                                </form>
                            <?php else: ?>
                                <!-- Redirect non-logged-in users to login -->
                                <form class="product-actions" method="POST" action="function/add_to_cart.php">
                                    <input type="hidden" name="prod_id" value="<?php echo htmlspecialchars($product['prod_id']); ?>">
                                    <button class="qty-btn" type="button" onclick="updateQuantity(this, -1)" <?php echo $display_stock == 0 ? 'disabled' : ''; ?>>-</button>
                                    <input type="number" class="qty-input" value="1" min="1" max="<?php echo $display_stock; ?>" name="quantity" data-max="<?php echo $display_stock; ?>" data-previous-value="1" <?php echo $display_stock == 0 ? 'disabled' : ''; ?>>
                                    <button class="qty-btn" type="button" onclick="updateQuantity(this, 1)" <?php echo $display_stock == 0 ? 'disabled' : ''; ?>>+</button>
                                    <button class="add-to-cart-btn" onclick="redirectToHomepage(event)">Add</button>
                                </form>
                            <?php endif; ?>

                        </div>
                    <?php endforeach; ?>

                </div>

                <div id="noProductFound" class="no-product-found" style="display: none;">
                    <p>No product found</p>
                </div>
            </div>


            <!-- Orders summary section -->
            <div class="cart-summary">
                <h4>
                    <img src="../../images/cart-icon.png" alt="Cart" class="cart-icon">MY CART
                    <button class="toggle-cart">⏷</button>
                </h4>

                <div id="cart-contents">
                    <?php if ($cartIsEmpty): ?>
                        <p class="no-items-message">No items in the cart</p>
                    <?php else: ?>
                        <div id="cart-items">
                            <?php foreach ($cart as $item): ?>
                                <div class="cart-item" data-prod-id="<?php echo htmlspecialchars($item['prod_id']); ?>">
                                    <span class="item-quantity">
                                        <?php echo htmlspecialchars($item['quantity']) . 'x'; ?>
                                    </span>
                                    <div class="cart-item-info">
                                        <span class="item-name">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </span>
                                        <span class="item-price-per-unit">
                                            ₱<?php echo number_format($item['price'], 2); ?> / sack
                                        </span>
                                    </div>
                                    <div class="cart-item-controls">
                                        <form method="POST" action="cust_products.php" class="qty-form">
                                            <input type="hidden" name="prod_id" value="<?php echo htmlspecialchars($item['prod_id']); ?>">
                                            <input type="hidden" name="update_cart" value="1">
                                            <button class="qty-btn" type="button" onclick="updateQuantity(this, -1)">-</button>
                                            <input type="number" class="qty-input" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="1" name="quantity">
                                            <button class="qty-btn" type="button" onclick="updateQuantity(this, 1)">+</button>
                                            <span class="item-total-price">₱<?php echo number_format($item['quantity'] * $item['price'], 2); ?></span>
                                        </form>
                                        <form method="POST" action="cust_products.php" class="remove-form">
                                            <input type="hidden" name="prod_id" value="<?php echo htmlspecialchars($item['prod_id']); ?>">
                                            <input type="hidden" name="remove_item" value="1">
                                            <button class="remove-item" type="button" onclick="showDeleteModal('<?php echo htmlspecialchars($item['prod_id']); ?>')">×</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <!-- Orders summary section -->
                            <div class="total-row total">
                                <span class="total-label">TOTAL:</span>
                                <span class="total-amount">₱<?php echo number_format($subTotal, 2); ?></span>
                            </div>
                            <div class="minimum-order" id="minimumOrderMessage">Minimum order quantity to checkout: 8 sacks</div>
                            <button class="checkout-btn" id="checkoutBtn" onclick="document.getElementById('checkoutForm').submit()" disabled>Proceed to checkout</button>
                        <?php endif; ?>
                        </div>
                </div>
            </div>

            <form id="checkoutForm" method="POST" action="checkout.php">
                <?php foreach ($cart as $item): ?>
                    <input type="hidden" name="prod_id[]" value="<?php echo htmlspecialchars($item['prod_id']); ?>">
                    <input type="hidden" name="quantity[]" value="<?php echo htmlspecialchars($item['quantity']); ?>">
                <?php endforeach; ?>
            </form>


            <div id="loadingScreen" class="loading-screen" style="display: none;">
                <div class="spinner"></div>
                <p>Loading...</p>
            </div>



            <!-- Delete Confirmation Modal -->
            <div id="deleteModal" class="message-modal" style="display: none;">
                <div class="message-modal-content">
                    <span class="message-close">&times;</span>
                    <div id="messageContent">
                        <div class="alert error">
                            <p>Are you sure you want to remove this item from your cart?</p>
                            <form id="deleteItemForm" method="POST" action="cust_products.php">
                                <input type="hidden" name="prod_id" id="delete_prod_id">
                                <input type="hidden" name="remove_item" value="1">
                                <button type="submit" class="confirm-delete-btn">Yes, Remove</button>
                                <button type="button" class="cancel-delete-btn">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($errorMessage)): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <!-- Message Modal -->
            <div class="message-modal" id="checkoutAlertModal" style="display: <?php echo $successMessage || $errorMessage ? 'flex' : 'none'; ?>;">
                <div class="message-modal-content">
                    <span class="checkout-close" id="closeModal">&times;</span>
                    <p id="checkoutAlertMessage"><?php echo htmlspecialchars($successMessage . $errorMessage); ?></p>
                    <button class="close-modal-btn" onclick="closeCheckoutAlertModal()">OK</button>
                </div>
            </div>

    </main>
    <script>
        // Function to update navigation links based on screen width
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

        // Cart toggle behavior on mobile screens
        document.addEventListener('DOMContentLoaded', function() {
            const cartSummary = document.querySelector('.cart-summary');
            const toggleButton = document.querySelector('.toggle-cart');

            // Initial state for mobile view
            if (window.innerWidth <= 768) {
                cartSummary.classList.add('minimized');
                toggleButton.innerHTML = '⏶'; // Expand icon for mobile
            }

            // Toggle cart functionality
            function toggleCart() {
                cartSummary.classList.toggle('minimized');
                toggleButton.innerHTML = cartSummary.classList.contains('minimized') ? '⏶' : '⏷';
            }

            toggleButton.addEventListener('click', toggleCart);

            // Ensure cart expands on larger screens and minimizes on smaller screens
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    cartSummary.classList.remove('minimized');
                    toggleButton.innerHTML = '⏷'; // Collapse icon for desktop
                } else if (!cartSummary.classList.contains('minimized')) {
                    cartSummary.classList.add('minimized');
                    toggleButton.innerHTML = '⏶'; // Expand icon for mobile
                }
            });
        });

        // Redirect non-logged-in users to the login page
        function redirectToHomepage(event) {
            event.preventDefault();
            window.location.href = '../login.php';
        }

        // Show loading screen during form submissions
        document.addEventListener('DOMContentLoaded', function() {
            const loadingScreen = document.getElementById("loadingScreen");

            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    loadingScreen.style.display = 'flex';
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const checkoutButton = document.querySelector('.checkout-btn');
            const cartItemsContainer = document.getElementById('cart-items'); // Assuming this contains your cart items
            const loadingScreen = document.getElementById('loadingScreen');
            const minimumOrderMessage = document.getElementById('minimumOrderMessage');
            let totalQuantity = 0; // Initialize total quantity

            // Function to update the checkout button state
            function updateCheckoutButton() {
                if (totalQuantity >= 8) {
                    checkoutButton.disabled = false; // Enable button if quantity is 8 or more
                    minimumOrderMessage.style.color = 'green'; // Green message
                } else {
                    checkoutButton.disabled = true; // Disable button if quantity is less than 8
                    minimumOrderMessage.style.color = '#E53935'; // Red message
                }
            }

            // Event listener for quantity changes in the cart
            cartItemsContainer.addEventListener('input', function(event) {
                if (event.target.classList.contains('qty-input')) {
                    updateCartTotalQuantity(event.target); // Update the total quantity when input changes
                }
            });

            // Function to update the total quantity
            function updateCartTotalQuantity(inputElement) {
                const quantity = parseInt(inputElement.value);
                const previousQuantity = parseInt(inputElement.dataset.previousValue || 0); // Store the previous value in a data attribute

                // Update the total quantity by adding the difference
                totalQuantity += (quantity - previousQuantity);
                inputElement.dataset.previousValue = quantity; // Update the previous value for next time

                updateCheckoutButton(); // Update the checkout button state
            }

            // Initialize the total quantity when the page loads
            document.querySelectorAll('.cart-item').forEach(cartItem => {
                const quantity = parseInt(cartItem.querySelector('.qty-input').value);
                totalQuantity += quantity; // Add the initial quantity to the global total
            });

            // Update the checkout button state on page load
            updateCheckoutButton();

            // Checkout button click event
            if (checkoutButton) {
                checkoutButton.addEventListener('click', function(event) {
                    loadingScreen.style.display = 'flex';
                    document.getElementById('checkoutForm').submit();
                });
            }
        });


        // Recalculate cart total on quantity change
        function recalculateTotal() {
            const cartItemsContainer = document.getElementById('cart-items');
            const subtotalElement = document.querySelector('.subtotal-amount');
            const totalElement = document.querySelector('.total-amount');
            const deliveryFee = 150.00;

            let subtotal = 0;
            cartItemsContainer.querySelectorAll('.cart-item').forEach(cartItem => {
                const quantity = parseInt(cartItem.querySelector('.qty-input').value);
                const pricePerUnit = parseFloat(cartItem.querySelector('.item-price-per-unit').textContent.replace('₱', '').replace(/,/g, ''));
                subtotal += quantity * pricePerUnit;
            });

            subtotalElement.textContent = `₱${subtotal.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`;
            totalElement.textContent = `₱${(subtotal + deliveryFee).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`;
        }

        // Update cart quantity via buttons
        function updateQuantity(button, change) {
            const input = button.parentNode.querySelector('.qty-input');
            if (!input) {
                console.error('Input element not found');
                return;
            }

            let currentQuantity = parseInt(input.value);
            const maxQuantity = parseInt(input.getAttribute('data-max') || 9999);

            currentQuantity += change;

            if (currentQuantity < 1) {
                currentQuantity = 1;
            } else if (currentQuantity > maxQuantity) {
                currentQuantity = maxQuantity;
            }

            input.value = currentQuantity;
            input.setAttribute('data-previous-value', currentQuantity);

            const cartItem = button.closest('.cart-item');
            const prodId = cartItem.getAttribute('data-prod-id');

            if (!prodId || isNaN(currentQuantity)) {
                console.error('Invalid product ID or quantity');
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open("POST", "function/update_cart_quantity.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

            xhr.onload = function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response.success) {
                            const itemQuantitySpan = cartItem.querySelector('.item-quantity');
                            if (itemQuantitySpan) {
                                itemQuantitySpan.textContent = `${currentQuantity}x`;
                            }

                            const itemPriceElement = cartItem.querySelector('.item-price-per-unit');
                            const itemTotalPriceElement = cartItem.querySelector('.item-total-price');
                            if (itemPriceElement && itemTotalPriceElement) {
                                const pricePerUnit = parseFloat(itemPriceElement.textContent.replace('₱', '').replace(/,/g, ''));
                                const totalPrice = currentQuantity * pricePerUnit;
                                itemTotalPriceElement.textContent = `₱${totalPrice.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`;
                            }

                            recalculateTotal();
                        } else {
                            console.error(response.error || 'Unknown error occurred');
                        }
                    } catch (e) {
                        console.error('Error parsing server response', xhr.responseText);
                    }
                } else {
                    console.error('Request failed. Status:', xhr.status);
                }
            };

            xhr.onerror = function() {
                console.error('Request failed');
            };

            const postData = `prod_id=${encodeURIComponent(prodId)}&quantity=${encodeURIComponent(currentQuantity)}`;
            xhr.send(postData);
        }

        // Track cart quantity and update message
        document.addEventListener('DOMContentLoaded', function() {
            const minimumOrderMessage = document.getElementById('minimumOrderMessage');
            let totalQuantity = 0;

            function updateMinimumOrderMessage() {
                if (totalQuantity >= 8) {
                    minimumOrderMessage.style.color = 'green';
                } else {
                    minimumOrderMessage.style.color = '#E53935';
                }
            }

            function updateCartTotalQuantity(inputElement) {
                const quantity = parseInt(inputElement.value);
                const previousQuantity = parseInt(inputElement.dataset.previousValue || 0);
                totalQuantity += (quantity - previousQuantity);
                inputElement.dataset.previousValue = quantity;
                updateMinimumOrderMessage();
            }

            document.getElementById('cart-items').addEventListener('input', function(event) {
                if (event.target.classList.contains('qty-input')) {
                    updateCartTotalQuantity(event.target);
                }
            });

            document.querySelectorAll('.cart-item').forEach(cartItem => {
                const quantity = parseInt(cartItem.querySelector('.qty-input').value);
                totalQuantity += quantity;
            });

            updateMinimumOrderMessage();
        });

        // Proceed to checkout based on quantity
        function checkout() {
            const cartItemsContainer = document.getElementById('cart-items');
            let totalQuantity = 0;

            cartItemsContainer.querySelectorAll('.cart-item').forEach(cartItem => {
                const quantity = parseInt(cartItem.querySelector('.qty-input').value);
                totalQuantity += quantity;
            });

            if (totalQuantity < 8) {
                showCheckoutAlertModal();
                return;
            }

            window.location.href = "staff_checkout.php";
        }

        // Modal for checkout alert
        function showCheckoutAlertModal() {
            const modal = document.getElementById('checkoutAlertModal');
            modal.style.display = 'flex';
        }

        function closeCheckoutAlertModal() {
            const modal = document.getElementById('checkoutAlertModal');
            modal.style.display = 'none';
        }

        document.querySelector('.checkout-close').addEventListener('click', closeCheckoutAlertModal);

        // Handle item removal modal
        document.addEventListener('DOMContentLoaded', () => {
            const deleteModal = document.getElementById('deleteModal');
            const closeBtn = document.querySelector('.message-close');
            const cancelBtn = document.querySelector('.cancel-delete-btn');
            const deleteProdIdInput = document.getElementById('delete_prod_id');

            document.querySelectorAll('.remove-item').forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    const prodId = this.closest('form').querySelector('input[name="prod_id"]').value;
                    deleteProdIdInput.value = prodId;
                    deleteModal.style.display = 'flex';
                });
            });

            closeBtn.addEventListener('click', () => deleteModal.style.display = 'none');
            cancelBtn.addEventListener('click', () => deleteModal.style.display = 'none');
        });

        // Show delete confirmation modal
        function showDeleteModal(prodId) {
            const deleteModal = document.getElementById('deleteModal');
            const deleteProdIdInput = document.getElementById('delete_prod_id');
            deleteProdIdInput.value = prodId;
            deleteModal.style.display = 'block';
        }

        document.getElementById("branchSelector").addEventListener("change", function() {
            var branchId = this.value;

            var formData = new FormData();
            formData.append("branch_id", branchId);

            fetch("your_php_file.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // Update the product display based on the response
                    var productGrid = document.querySelector(".product-grid");
                    productGrid.innerHTML = ''; // Clear existing products

                    if (data.products.length > 0) {
                        data.products.forEach(function(product) {
                            var productCard = document.createElement("div");
                            productCard.classList.add("product-card");

                            var stockQuantity = Math.max(0, product.stock_quantity);
                            var outOfStock = stockQuantity === 0 ? "out-of-stock-overlay" : "";

                            productCard.innerHTML = `
                    <div class="${outOfStock}">OUT OF STOCK</div>
                    <img src="${product.prod_image_path}" alt="${product.prod_name}">
                    <h4>${product.prod_brand}</h4>
                    <p>${product.prod_name}</p>
                    <h3>₱ ${product.prod_price}</h3>
                    <div class="stock-info">Current Stocks: ${stockQuantity}</div>
                `;

                            productGrid.appendChild(productCard);
                        });
                    } else {
                        document.getElementById("noProductFound").style.display = "block";
                    }
                })
                .catch(error => console.error('Error fetching products:', error));
        });


        // Product search functionality
        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('searchInput');
            const productCards = document.querySelectorAll('.product-card');
            const noProductFound = document.getElementById('noProductFound');

            searchInput.addEventListener('input', function() {
                const searchValue = searchInput.value.toLowerCase();
                let anyCardVisible = false;

                productCards.forEach(card => {
                    const brandElement = card.querySelector('h4');
                    const nameElement = card.querySelector('p');

                    const brand = brandElement ? brandElement.textContent.toLowerCase() : '';
                    const name = nameElement ? nameElement.textContent.toLowerCase() : '';

                    if (brand.includes(searchValue) || name.includes(searchValue)) {
                        card.style.display = '';
                        anyCardVisible = true;
                    } else {
                        card.style.display = 'none';
                    }
                });

                noProductFound.style.display = anyCardVisible ? 'none' : 'block';
            });
        });
    </script>

</body>

</html>