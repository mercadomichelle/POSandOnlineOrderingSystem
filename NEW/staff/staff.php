<?php
session_start();

include('../connection.php');

if (!isset($_SESSION["username"])) {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION["username"];

$sql = "SELECT login.id AS login_id, login.first_name, login.last_name, login.branch_id, branches.branch_name 
        FROM login 
        JOIN branches ON login.branch_id = branches.branch_id
        WHERE login.username = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $userData = $result->fetch_assoc();
    $_SESSION["first_name"] = $userData['first_name'];
    $_SESSION["last_name"] = $userData['last_name'];
    $_SESSION["branch_id"] = $userData['branch_id'];  // Make sure branch_id is set
    $_SESSION["login_id"] = $userData['login_id'];
    $_SESSION["branch_name"] = $userData['branch_name'];
} else {
    $_SESSION["first_name"] = "Guest";
    $_SESSION["last_name"] = "";
    $_SESSION["branch_id"] = null;  // Make sure it's set to null if not found
    $_SESSION["login_id"] = "";
    $_SESSION["branch_name"] = "Unknown";
}


// STOCKS NOTIFICATIONS

$branch_id = $_SESSION['branch_id'];

$sql = "SELECT p.prod_id, p.prod_brand, p.prod_name, p.prod_image_path, 
               COALESCE(SUM(s.stock_quantity), 0) AS stock_quantity 
        FROM products p 
        LEFT JOIN stocks s ON p.prod_id = s.prod_id
        WHERE s.branch_id = ?
        GROUP BY p.prod_id, p.prod_brand, p.prod_name, p.prod_image_path
        ORDER BY stock_quantity ASC";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $branch_id); 
$stmt->execute();
$result = $stmt->get_result();

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



// Fetch products
$sql = "SELECT products.prod_id, products.prod_brand, products.prod_name, 
               products.prod_price_wholesale, products.prod_price_retail, 
               products.prod_image_path, COALESCE(SUM(stocks.stock_quantity), 0) AS stock_quantity
        FROM products 
        JOIN stocks ON products.prod_id = stocks.prod_id
        GROUP BY 
            products.prod_id, products.prod_brand, products.prod_name, products.prod_price_wholesale, 
            products.prod_price_retail, products.prod_image_path
        ORDER BY 
            prod_name ASC";
$result = $mysqli->query($sql);

$_SESSION['prod_price'] = 'retail';

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
    $price_type = $_POST['price_type'];

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
    header("Location: staff.php");
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
    header("Location: staff.php");
    exit();
}

// Fetch cart items
$login_id = $_SESSION['login_id'];

$price_type = 'retail';
if (isset($_POST['price_type']) && in_array($_POST['price_type'], ['retail', 'wholesale'])) {
    $price_type = $_POST['price_type'];
}

$sql = "SELECT products.prod_id, products.prod_name, cart.quantity, cart.price_type,
        CASE 
            WHEN cart.price_type = 'retail' THEN products.prod_price_retail
                ELSE products.prod_price_wholesale
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

while ($row = $result->fetch_assoc()) {
    $totalPrice = $row['prod_price'] * $row['quantity'];
    $subTotal += $totalPrice;
    $cart[] = [
        'prod_id' => $row['prod_id'],
        'name' => $row['prod_name'],
        'quantity' => $row['quantity'],
        'price' => $row['prod_price'],
        'price_type' => $row['price_type']
    ];
}

$total = $subTotal;
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
    <title>Rice Website | In-Store Order</title>
    <link rel="icon" href="../favicon.png" type="image/png">
    <link rel="stylesheet" href="../styles/staff.css">
</head>

<body>
    <header>
        <div>
            <img src="../favicon.png" alt="Logo" class="logo">
            <span class="branch-name"><?php echo htmlspecialchars(string: $_SESSION["branch_name"] . " Branch"); ?></span>
        </div>

        <div class="account-info">
            <div class="dropdown notifications-dropdown">
                <img src="../images/notif-icon.png" alt="Notifications" class="notification-icon">
                <div class="dropdown-content" id="notificationDropdown">
                    <p class="notif">Notifications</p>
                    <?php if (empty($notifications)): ?>
                        <a href="#">No new notifications</a>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <a href="stocks/staff_stocks.php"><?php echo $notification; ?></a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
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
                <button class="filter-button-current" id="wholesaleBtn">
                    <img src="../../images/wholesale-icon.png" alt="Wholesale">WHOLESALE
                </button>
                <button class="filter-button" id="retailBtn">
                    <img src="../../images/retail-icon.png" alt="Retail">RETAIL
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
                        $price_type = isset($_SESSION['price_type']) ? $_SESSION['price_type'] : 'retail';
                        $display_stock = isset($product['stock_quantity']) ? max(0, $product['stock_quantity']) : 0;
                        ?>
                        <div class="product-card">
                            <?php if ($display_stock == 0): ?>
                                <div class="out-of-stock-overlay">OUT OF STOCK</div>
                            <?php endif; ?>

                            <img src="<?php echo htmlspecialchars($product['prod_image_path']); ?>" alt="<?php echo htmlspecialchars($product['prod_name']); ?>">
                            <h4><?php echo htmlspecialchars($product['prod_brand']); ?></h4>
                            <p><?php echo htmlspecialchars($product['prod_name']); ?></p>
                            <input type="hidden" name="price_type" value="wholesale">
                            <h3>
                                ₱
                                <?php
                                if (isset($_POST['price_type']) && $_POST['price_type'] === 'retail') {
                                    $price = $product['prod_price_retail'];
                                } else {
                                    $price = $product['prod_price_wholesale'];
                                }
                                echo number_format($price, 2);
                                ?>
                                / sack
                            </h3>

                            <form class="product-actions" method="POST" action="function/add_to_cart.php">
                                <input type="hidden" name="source" value="wholesale">
                                <input type="hidden" name="prod_id" value="<?php echo htmlspecialchars($product['prod_id']); ?>">
                                <button class="qty-btn" type="button" onclick="updateQuantity(this, -1)" <?php echo $display_stock == 0 ? 'disabled' : ''; ?>>-</button>
                                <input type="number" class="qty-input" value="1" min="1" max="<?php echo $display_stock; ?>" name="quantity" data-max="<?php echo $display_stock; ?>" <?php echo $display_stock == 0 ? 'disabled' : ''; ?>>
                                <button class="qty-btn" type="button" onclick="updateQuantity(this, 1)" <?php echo $display_stock == 0 ? 'disabled' : ''; ?>>+</button>
                                <button class="add-to-cart-btn" type="submit" <?php echo $display_stock == 0 ? 'disabled' : ''; ?>>Add</button>
                            </form>
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
                    <img src="../../images/cart-icon.png" alt="Cart" class="cart-icon">CART
                    <button class="toggle-cart">⏷</button>
                </h4>

                <div id="cart-contents">
                    <?php if ($cartIsEmpty): ?>
                        <p class="no-items-message">No items in the cart</p>
                    <?php else: ?>
                        <div id="cart-items">
                            <?php foreach ($cart as $item): ?>
                                <div class="cart-item" data-prod-id="<?php echo htmlspecialchars($item['prod_id']); ?>"
                                    data-price="<?php echo htmlspecialchars($item['price']); ?>"
                                    data-price-type="<?php echo htmlspecialchars($item['price_type']); ?>">
                                    <span class="item-quantity">
                                        <?php echo htmlspecialchars($item['quantity']) . 'x'; ?>
                                    </span>
                                    <div class="cart-item-info">
                                        <span class="item-name">
                                            <?php echo htmlspecialchars($item['name']); ?>
                                        </span>
                                        <span class="item-price-per-unit">
                                            ₱<?php echo number_format($item['price'], 2); ?>
                                            <?php echo ($item['price_type'] === 'wholesale') ? '/ sack' : '/ kilo'; ?>
                                        </span>
                                    </div>
                                    <div class="cart-item-controls">
                                        <form method="POST" action="staff.php" class="qty-form">
                                            <input type="hidden" name="prod_id" value="<?php echo htmlspecialchars($item['prod_id']); ?>">
                                            <input type="hidden" name="price_type" value="<?php echo htmlspecialchars($item['price_type']); ?>"> <!-- Ensure price_type is passed -->
                                            <input type="hidden" name="update_cart" value="1">
                                            <button class="qty-btn" type="button" onclick="updateQuantity(this, -1)">-</button>
                                            <input type="number" class="qty-input" value="<?php echo htmlspecialchars($item['quantity']); ?>" min="1" name="quantity">
                                            <button class="qty-btn" type="button" onclick="updateQuantity(this, 1)">+</button>
                                            <span class="item-total-price">₱<?php echo number_format($item['quantity'] * $item['price'], 2); ?></span>
                                        </form>
                                        <form method="POST" action="staff.php" class="remove-form">
                                            <input type="hidden" name="prod_id" value="<?php echo htmlspecialchars($item['prod_id']); ?>">
                                            <input type="hidden" name="price" value="<?php echo htmlspecialchars($item['price']); ?>"> <!-- Add price hidden field -->
                                            <input type="hidden" name="remove_item" value="1">
                                            <button class="remove-item" type="button" onclick="showDeleteModal('<?php echo htmlspecialchars($item['prod_id']); ?>')">×</button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="total-row total">
                            <span class="total-label">TOTAL:</span>
                            <span class="total-amount">₱<?php echo number_format($total, 2); ?></span>
                        </div>
                        <button class="checkout-btn" onclick="document.getElementById('checkoutForm').submit()">Proceed to payment</button>
                    <?php endif; ?>
                </div>
            </div>

            <div id="loadingScreen" class="loading-screen" style="display: none;">
                <div class="spinner"></div>
                <p>Loading...</p>
            </div>


            <form id="checkoutForm" method="POST" action="staff_checkout.php">
                <?php foreach ($cart as $item): ?>
                    <input type="hidden" id="source" value="wholesale">
                    <input type="hidden" name="prod_id[]" value="<?php echo htmlspecialchars($item['prod_id']); ?>">
                    <input type="hidden" name="quantity[]" value="<?php echo htmlspecialchars($item['quantity']); ?>">
                <?php endforeach; ?>
            </form>

            <!-- Delete Confirmation Modal -->
            <div id="deleteModal" class="message-modal" style="display: none;">
                <div class="message-modal-content">
                    <span class="message-close">&times;</span>
                    <div id="messageContent">
                        <div class="alert error">
                            <p>Are you sure you want to remove this item from your cart?</p>
                            <form id="deleteItemForm" method="POST" action="staff.php">
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

            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var loadingScreen = document.getElementById("loadingScreen");

                    document.querySelectorAll('form').forEach(form => {
                        form.addEventListener('submit', function() {
                            loadingScreen.style.display = 'flex';
                        });
                    });
                });

                document.addEventListener('DOMContentLoaded', function() {
                    var cartSummary = document.querySelector('.cart-summary');
                    var toggleButton = document.querySelector('.toggle-cart');

                    // Minimize cart if in mobile mode
                    if (window.innerWidth <= 999) {
                        cartSummary.classList.add('minimized');
                        toggleButton.innerHTML = '⏶'; // Set icon to "Expand" for minimized cart
                    }

                    // Toggle cart function to handle minimizing and expanding
                    function toggleCart() {
                        cartSummary.classList.toggle('minimized');
                        toggleButton.innerHTML = cartSummary.classList.contains('minimized') ? '⏶' : '⏷';
                    }

                    // Attach the toggle function to the button
                    toggleButton.addEventListener('click', toggleCart);

                    // Ensure cart expands on larger screens if resized
                    window.addEventListener('resize', function() {
                        if (window.innerWidth > 999) {
                            cartSummary.classList.remove('minimized');
                            toggleButton.style.display = 'none'; // Hide toggle button on desktop
                        } else {
                            toggleButton.style.display = 'inline'; // Show toggle button on mobile
                        }
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
                    const totalElement = document.querySelector('.total-amount');

                    let total = 0;

                    // Calculate the total price of the cart
                    cartItemsContainer.querySelectorAll('.cart-item').forEach(cartItem => {
                        const quantity = parseInt(cartItem.querySelector('.qty-input').value);
                        const pricePerUnit = parseFloat(cartItem.querySelector('.item-price-per-unit').textContent.replace('₱', '').replace(/,/g, ''));
                        total += quantity * pricePerUnit;
                    });

                    // Update the total amount
                    if (totalElement) {
                        totalElement.textContent = `₱${total.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`;
                    }
                }

                function updateQuantity(button, change) {
                    const input = button.parentNode.querySelector('.qty-input');
                    if (!input) {
                        console.error('Input element not found');
                        return;
                    }

                    let currentQuantity = parseInt(input.value);
                    const maxQuantity = parseInt(input.getAttribute('data-max')) || Infinity;

                    currentQuantity += change;

                    if (currentQuantity < 1) {
                        currentQuantity = 1;
                    } else if (currentQuantity > maxQuantity) {
                        currentQuantity = maxQuantity;
                    }

                    input.value = currentQuantity;

                    const cartItem = button.closest('.cart-item');
                    if (!cartItem) {
                        console.error('Cart item element not found');
                        return;
                    }

                    const pricePerUnitElement = cartItem.querySelector('.item-price-per-unit');

                    const pricePerUnit = parseFloat(pricePerUnitElement.textContent.replace('₱', '').replace(/,/g, ''));
                    const totalPriceElement = cartItem.querySelector('.item-total-price');
                    const totalPrice = currentQuantity * pricePerUnit;

                    totalPriceElement.textContent = `₱${totalPrice.toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,')}`;

                    const quantityElement = cartItem.querySelector('.item-quantity');
                    quantityElement.textContent = `${currentQuantity}x`;

                    recalculateTotal();

                    const prodId = cartItem.getAttribute('data-prod-id');
                    const priceType = cartItem.getAttribute('data-price-type'); // Get price type

                    // Send the product ID, price type, and new quantity via AJAX
                    const xhr = new XMLHttpRequest();
                    xhr.open("POST", "function/update_cart_quantity.php", true);
                    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                    xhr.send(`prod_id=${prodId}&price_type=${priceType}&quantity=${currentQuantity}`);


                }


                function checkout() {
                    const cartItemsContainer = document.getElementById('cart-items');
                    let totalQuantity = 0;

                    cartItemsContainer.querySelectorAll('.cart-item').forEach(cartItem => {
                        const quantity = parseInt(cartItem.querySelector('.qty-input').value);
                        totalQuantity += quantity;
                    });

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

                // Handle the wholesale button click
                document.getElementById('retailBtn').onclick = function() {
                    window.location.href = 'staff_retail.php';
                };
            </script>
</body>

</html>