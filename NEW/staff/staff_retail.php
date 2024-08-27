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
} else {
    $_SESSION["first_name"] = "Guest";
    $_SESSION["last_name"] = "";
}

// Fetch products
$sql = "SELECT products.prod_id, products.prod_brand, products.prod_name, products.prod_price_retail AS prod_price, 
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




$stmt->close();
$mysqli->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rice Website</title>
    <link rel="stylesheet" href="../styles/staff.css">
</head>

<body>
    <header>
        <div class="logo">RICE</div>
        <div class="account-info">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION["first_name"] . " " . $_SESSION["last_name"]); ?></span>
            <div class="dropdown">
                <img src="../images/account-icon.png" alt="Account">
                <div class="dropdown-content">
                    <a href="../logout.php">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="sidebar">
        <nav>
            <ul>
                <li><a class="current"><img src="../images/create-icon.png" alt="Create">CREATE NEW ORDER</a></li>
                <li><a href="products/staff_products.php"><img src="../images/products-icon.png" alt="Products">PRODUCTS</a></li>
                <li><a href="stocks/staff_stocks.php"><img src="../images/stocks-icon.png" alt="Stocks">STOCKS</a></li>
                <li><a href="online_orders/online_order.php"><img src="../images/online-icon.png" alt="Online">ONLINE ORDER</a></li>
            </ul>
        </nav>
    </div>


    <main>
        <div class="products">
        <div class="product-controls">
                <button class="filter-button" id="wholesaleBtn"><img src="../../images/wholesale-icon.png" alt="Wholesale">WHOLESALE</button>
                <button class="filter-button-current" id="retailBtn"><img src="../../images/retail-icon.png" alt="Retail">RETAIL</button>
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
                        <div class="product-card">
                            <img src="<?php echo htmlspecialchars($product['prod_image_path']); ?>" alt="<?php echo htmlspecialchars($product['prod_name']); ?>">
                            <h4><?php echo htmlspecialchars($product['prod_brand']); ?></h4>
                            <p><?php echo htmlspecialchars($product['prod_name']); ?></p>
                            <h3>₱ <?php echo number_format($product['prod_price'], 2); ?> / sack</h3>
                            <div class="stock-info">Current Stocks: <?php echo htmlspecialchars($product['stock_quantity']); ?></div>

                            <form class="product-actions" method="POST" action="function/add_to_cart.php">
                                <input type="hidden" name="prod_id" value="<?php echo htmlspecialchars($product['prod_id']); ?>">
                                <button class="qty-btn" type="button" onclick="updateQuantity(this, -1)">-</button>
                                <input type="number" class="qty-input" value="1" min="1" name="quantity">
                                <button class="qty-btn" type="button" onclick="updateQuantity(this, 1)">+</button>
                                <button class="add-to-cart-btn" type="submit">Add</button>
                            </form>

                            <div class="minimum-order">Minimum order quantity: 10 sacks</div>
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
                </h4>
                <div id="cart-items">
                    <?php foreach ($cart as $item): ?>
                        <div class="cart-item">
                            <div class="cart-item-info">
                                <span class="item-quantity-name">
                                    <?php echo htmlspecialchars($item['quantity']) . ' x ' . htmlspecialchars($item['name']); ?>
                                </span>
                                <span class="item-price-per-unit">
                                    ₱<?php echo number_format($item['price'], 2); ?> / sack
                                </span>
                            </div>
                            <div class="cart-item-controls">
                                <form method="POST" action="cust_products.php">
                                    <input type="hidden" name="prod_id" value="<?php echo htmlspecialchars($item['prod_id']); ?>">
                                    <input type="hidden" name="update_cart" value="1">
                                    <button class="qty-btn" type="button" onclick="updateQuantity(this, -1)">-</button>
                                    <input type="number" class="qty-input" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="1" name="quantity">
                                    <button class="qty-btn" type="button" onclick="updateQuantity(this, 1)">+</button>
                                </form>
                                <span class="item-total-price">₱<?php echo number_format($item['quantity'] * $item['price'], 2); ?></span>
                                <form method="POST" action="cust_products.php" style="display:inline;">
                                    <input type="hidden" name="prod_id" value="<?php echo htmlspecialchars($item['prod_id']); ?>">
                                    <input type="hidden" name="remove_item" value="1">
                                    <button class="remove-item" type="button" onclick="showDeleteModal('<?php echo htmlspecialchars($item['prod_id']); ?>')">×</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="total-row">
                    <span class="subtotal-label">Subtotal:</span>
                    <span class="subtotal-amount">₱<?php echo number_format($subTotal, 2); ?></span>
                </div>
                <div class="total-row fee">
                    <span class="delivery-fee-label">Delivery fee:</span>
                    <span class="delivery-fee-amount">₱150.00</span>
                </div>
                <div class="total-row total">
                    <span class="total-label">TOTAL:</span>
                    <span class="total-amount">₱<?php echo number_format($total, 2); ?></span>
                </div>
                <button class="checkout-btn" onclick="checkout()">Proceed to checkout</button>
            </div>
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

        <!-- Checkout Alert Modal -->
        <div id="checkoutAlertModal" class="message-modal" style="display: none;">
            <div class="message-modal-content">
                <span class="checkout-close">&times;</span>
                <div id="checkoutAlertMessage" class="alert error">
                    <p>You must have at least 10 items in your cart to proceed to checkout.</p>
                    <button class="close-modal-btn" onclick="closeCheckoutAlertModal()">OK</button>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert success"><?php echo $_SESSION['success_message']; ?></div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert error"><?php echo $_SESSION['error_message']; ?></div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <script>
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
                let currentQuantity = parseInt(input.value);
                currentQuantity += change;

                if (currentQuantity < 1) {
                    currentQuantity = 1;
                }

                input.value = currentQuantity;

                // Update the total price displayed for the item
                const cartItem = button.closest('.cart-item');
                const pricePerUnit = parseFloat(cartItem.querySelector('.item-price-per-unit').textContent.replace('₱', '').replace(/,/g, ''));
                const totalPriceElement = cartItem.querySelector('.item-total-price');
                const totalPrice = currentQuantity * pricePerUnit;
                totalPriceElement.textContent = `₱${totalPrice.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`;

                // Update the quantity next to the product name in the cart summary
                const quantityNameElement = cartItem.querySelector('.item-quantity-name');
                const productName = quantityNameElement.textContent.split(' x ')[1]; // Get the product name
                quantityNameElement.textContent = `${currentQuantity} x ${productName}`; // Update the text with the new quantity

                // Recalculate the subtotal and total
                recalculateTotal();

                // Send AJAX request to update the quantity in the database
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "function/update_cart_quantity.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.send(`prod_id=${cartItem.querySelector('input[name="prod_id"]').value}&quantity=${currentQuantity}`);
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

                // Proceed with checkout if total quantity is 10 or more
                const formData = new FormData();

                cartItemsContainer.querySelectorAll('.cart-item').forEach(cartItem => {
                    const prodId = cartItem.querySelector('input[name="prod_id"]').value;
                    const quantity = cartItem.querySelector('.qty-input').value;
                    formData.append(`quantities[${prodId}]`, quantity);
                });

                // Send AJAX request to checkout.php
                const xhr = new XMLHttpRequest();
                xhr.open("POST", "checkout.php", true);
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        console.log('Checkout successful:', xhr.responseText);
                        // Redirect to a success page or order summary page
                        window.location.href = "cust_products.php"; // Replace with the correct URL
                    } else {
                        console.error('Checkout failed:', xhr.responseText);
                        // Optionally display an error message
                    }
                };
                xhr.send(formData);
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
            
            // Handle the wholesale button click
            document.getElementById('wholesaleBtn').onclick = function() {
                window.location.href = 'staff.php';
            };
        </script>
</body>

</html>