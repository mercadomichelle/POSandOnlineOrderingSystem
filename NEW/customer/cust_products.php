<?php
session_start();

$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

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

// Fetch products
$sql = "SELECT products.prod_id, products.prod_brand, products.prod_name, products.prod_price_wholesale AS prod_price, 
        products.prod_image_path, stocks.stock_quantity 
        FROM products 
        JOIN stocks ON products.prod_id = stocks.prod_id";
$result = $mysqli->query($sql);

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
    $stmt->close();

    // Update stock quantity
    $sql = "UPDATE stocks SET stock_quantity = stock_quantity - ? WHERE prod_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $quantity, $prod_id);
    $stmt->execute();
    $stmt->close();

    // Redirect to avoid resubmission issues
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
    $stmt->close();

    // Remove item from cart
    $sql = "DELETE FROM cart WHERE prod_id = ? AND login_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $prod_id, $login_id);
    $stmt->execute();
    $stmt->close();

    // Update stock quantity
    $sql = "UPDATE stocks SET stock_quantity = stock_quantity + ? WHERE prod_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ii", $quantity, $prod_id);
    $stmt->execute();
    $stmt->close();

    // Redirect to avoid resubmission issues
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

$total = $subTotal + 150; // Fixed delivery fee

$cartIsEmpty = empty($cart);



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
                                    <input type="number" class="qty-input" value="1" min="1" max="<?php echo $display_stock; ?>" name="quantity" data-max="<?php echo $display_stock; ?>" <?php echo $display_stock == 0 ? 'disabled' : ''; ?>>
                                    <button class="qty-btn" type="button" onclick="updateQuantity(this, 1)" <?php echo $display_stock == 0 ? 'disabled' : ''; ?>>+</button>
                                    <button class="add-to-cart-btn" type="submit" <?php echo $display_stock == 0 ? 'disabled' : ''; ?>>Add</button>
                                </form>
                            <?php else: ?>
                                <!-- Redirect non-logged-in users to login -->
                                <form class="product-actions" method="POST" action="function/add_to_cart.php">
                                    <input type="hidden" name="prod_id" value="<?php echo htmlspecialchars($product['prod_id']); ?>">
                                    <button class="qty-btn" type="button" onclick="updateQuantity(this, -1)" <?php echo $display_stock == 0 ? 'disabled' : ''; ?>>-</button>
                                    <input type="number" class="qty-input" value="1" min="1" max="<?php echo $display_stock; ?>" name="quantity" data-max="<?php echo $display_stock; ?>" <?php echo $display_stock == 0 ? 'disabled' : ''; ?>>
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
                            <div class="total-row fee">
                                <span class="subtotal-label">Sub Total:</span>
                                <span class="subtotal-amount">₱<?php echo number_format($subTotal, 2); ?></span>
                            </div>
                            <div class="total-row total">
                                <span class="total-label">TOTAL:</span>
                                <span class="total-amount">₱<?php echo number_format($total, 2); ?></span>
                            </div>
                            <div class="minimum-order">Minimum order quantity to checkout: 10 sacks</div>
                            <button class="checkout-btn" onclick="document.getElementById('checkoutForm').submit()">Proceed to checkout</button>
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

        document.addEventListener('DOMContentLoaded', function() {
            var cartSummary = document.querySelector('.cart-summary');
            var toggleButton = document.querySelector('.toggle-cart');

            // Check the screen width and minimize the cart on load if in mobile mode
            if (window.innerWidth <= 768) {
                cartSummary.classList.add('minimized');
                toggleButton.innerHTML = '⏶'; // Set icon to "Expand" for mobile
            }

            // Toggle cart function to handle minimizing and expanding
            function toggleCart() {
                // Toggle the minimized class on click
                cartSummary.classList.toggle('minimized');

                // Update the button icon based on the current state
                if (cartSummary.classList.contains('minimized')) {
                    toggleButton.innerHTML = '⏶'; // Show "Expand" icon
                } else {
                    toggleButton.innerHTML = '⏷'; // Show "Collapse" icon
                }
            }

            // Attach the toggle function to the button click event
            toggleButton.addEventListener('click', toggleCart);

            // Ensure cart expands on larger screens if resized
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    cartSummary.classList.remove('minimized');
                    toggleButton.innerHTML = '⏷'; // Set to "Collapse" icon on desktop
                } else if (!cartSummary.classList.contains('minimized')) {
                    cartSummary.classList.add('minimized');
                    toggleButton.innerHTML = '⏶'; // Set to "Expand" icon on mobile
                }
            });
        });


        function redirectToHomepage(event) {
            event.preventDefault();
            window.location.href = '../login.php';
        }

        document.addEventListener('DOMContentLoaded', function() {
            var loadingScreen = document.getElementById("loadingScreen");

            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    loadingScreen.style.display = 'flex';
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const checkoutButton = document.querySelector('.checkout-btn');
            const loadingScreen = document.getElementById('loadingScreen');

            if (checkoutButton) {
                checkoutButton.addEventListener('click', function(event) {
                    loadingScreen.style.display = 'flex';

                    document.getElementById('checkoutForm').submit();
                });
            }
        });

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

        function updateQuantity(button, change) {
            const input = button.parentNode.querySelector('.qty-input');
            if (!input) {
                console.error('Input element not found');
                return;
            }

            let currentQuantity = parseInt(input.value);
            const maxQuantity = parseInt(input.getAttribute('data-max'));

            // Calculate new quantity
            currentQuantity += change;

            if (currentQuantity < 1) {
                currentQuantity = 1;
            } else if (currentQuantity > maxQuantity) {
                currentQuantity = maxQuantity;
            }

            input.value = currentQuantity;

            const cartItem = button.closest('.cart-item');
            const prodId = cartItem.getAttribute('data-prod-id');
            const priceType = cartItem.getAttribute('data-price-type'); // If price_type is needed

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
                            console.log('Quantity updated successfully');
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

            // Send the data to the server
            const postData = `prod_id=${encodeURIComponent(prodId)}&quantity=${encodeURIComponent(currentQuantity)}`;
            xhr.send(postData);
        }


        function checkout() {
            const cartItemsContainer = document.getElementById('cart-items');
            let totalQuantity = 0;

            cartItemsContainer.querySelectorAll('.cart-item').forEach(cartItem => {
                const quantity = parseInt(cartItem.querySelector('.qty-input').value);
                totalQuantity += quantity;
            });

            // Check if total quantity is less than 10
            if (totalQuantity < 10) {
                // Show custom modal instead of alert
                showCheckoutAlertModal();
                return; // Stop the function here if the quantity is less than 10
            }

            // Redirect to the checkout page
            window.location.href = "staff_checkout.php";
        }


        function showCheckoutAlertModal() {
            const modal = document.getElementById('checkoutAlertModal');
            modal.style.display = 'flex';
        }

        function closeCheckoutAlertModal() {
            const modal = document.getElementById('checkoutAlertModal');
            modal.style.display = 'none';
        }

        // Close the modal when the user clicks on the close button
        document.querySelector('.checkout-close').addEventListener('click', closeCheckoutAlertModal);


        // DELETE MODAL
        document.addEventListener('DOMContentLoaded', () => {
            const deleteModal = document.getElementById('deleteModal');
            const closeBtn = document.querySelector('.message-close');
            const cancelBtn = document.querySelector('.cancel-delete-btn');
            const deleteForm = document.getElementById('deleteItemForm');
            const deleteProdIdInput = document.getElementById('delete_prod_id');

            // When the user clicks on the remove button, open the modal
            document.querySelectorAll('.remove-item').forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    const prodId = this.closest('form').querySelector('input[name="prod_id"]').value;
                    deleteProdIdInput.value = prodId;
                    deleteModal.style.display = 'flex';
                });
            });

            // When the user clicks on <span> (x), close the modal
            closeBtn.addEventListener('click', function() {
                deleteModal.style.display = 'none';
            });

            // When the user clicks on the cancel button, close the modal
            cancelBtn.addEventListener('click', function() {
                deleteModal.style.display = 'none';
            });
        });

        function confirmDeletion() {
            return confirm("Are you sure you want to remove this item from your cart?");
        }

        function showDeleteModal(prodId) {
            const deleteModal = document.getElementById('deleteModal');
            const deleteProdIdInput = document.getElementById('delete_prod_id');

            deleteProdIdInput.value = prodId; // Set the hidden input value
            deleteModal.style.display = 'block'; // Show the modal
        }

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

        function closeCheckoutAlertModal() {
            const modal = document.getElementById('checkoutAlertModal');
            modal.style.display = 'none';
        }

        document.querySelector('.checkout-close').addEventListener('click', closeCheckoutAlertModal);
    </script>
</body>

</html>