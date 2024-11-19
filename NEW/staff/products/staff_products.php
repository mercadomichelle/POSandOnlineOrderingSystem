<?php
session_start();

$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

if (!isset($_SESSION["username"])) {
    header("Location: ../../login.php");
    exit();
}

$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$username = $_SESSION["username"];

$sql = "SELECT first_name, last_name FROM login WHERE username = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $userData = $result->fetch_assoc();
    $_SESSION["first_name"] = $userData['first_name'];
    $_SESSION["last_name"] = $userData['last_name'];
} else {
    $_SESSION["first_name"] = "Guest";
    $_SESSION["last_name"] = "";
}

$sql = "SELECT prod_id, prod_brand, prod_name, prod_price_wholesale AS prod_price, prod_image_path FROM products";
$result = $mysqli->query($sql);

$products = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
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


$successMessage = isset($_SESSION['successMessage']) ? $_SESSION['successMessage'] : null;
$errorMessage = isset($_SESSION['errorMessage']) ? $_SESSION['errorMessage'] : null;

unset($_SESSION['successMessage']);
unset($_SESSION['errorMessage']);

$stmt->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Rice Website | Wholesale Products</title>
    <link rel="icon" href="../../favicon.png" type="image/png">
    <link rel="stylesheet" href="../../styles/products.css">
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
                        <a href="../stocks/staff_stocks.php">No new notifications</a>
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

    <div class="sidebar">
        <nav>
            <ul>
                <li><a href="../staff.php"><img src="../../images/create-icon.png" alt="Create">CREATE NEW ORDER</a></li>
                <li><a class="current"><img src="../../images/products-icon.png" alt="Products">PRODUCTS</a></li>
                <li><a href="../stocks/staff_stocks.php"><img src="../../images/stocks-icon.png" alt="Stocks">STOCKS</a></li>
                <li><a href="../online_orders/online_order.php"><img src="../../images/online-icon.png" alt="Online">ONLINE ORDER</a></li>
            </ul>
        </nav>
    </div>

    <main>
        <div class="products">
            <div class="product-controls">
                <button class="filter-button-current" id="wholesaleBtn"><img src="../../images/wholesale-icon.png" alt="Wholesale">WHOLESALE</button>
                <button class="filter-button" id="retailBtn"><img src="../../images/retail-icon.png" alt="Retail">RETAIL</button>
                <div class="search-container">
                    <div class="search-wrapper">
                        <input type="text" placeholder="Search..." id="searchInput">
                        <img src="../../images/search-icon.png" alt="Search" class="search-icon">
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="product-grid">
                    <div class="product-card add-new" id="addNewProductBtn">
                        <div class="add-icon">+</div>
                        <p>Add new product</p>
                    </div>
                    <?php foreach ($products as $product): ?>
                        <div class="product-card" data-price="<?php echo htmlspecialchars($product['prod_price']); ?>">
                            <img class="prod-img" src="<?php echo $product['prod_image_path']; ?>" alt="<?php echo htmlspecialchars($product['prod_name']); ?>">
                            <h4><?php echo htmlspecialchars($product['prod_brand']); ?></h4>
                            <p><?php echo htmlspecialchars($product['prod_name']); ?></p>
                            <h3>₱ <?php echo number_format($product['prod_price'], 2); ?> / sack</h3>

                            <div class="product-actions">
                                <button class="edit-button"
                                    data-id="<?php echo htmlspecialchars($product['prod_id']); ?>"
                                    data-brand="<?php echo htmlspecialchars($product['prod_brand']); ?>"
                                    data-name="<?php echo htmlspecialchars($product['prod_name']); ?>"
                                    data-price-wholesale="<?php echo htmlspecialchars($product['prod_price']); ?>"
                                    data-image="<?php echo htmlspecialchars($product['prod_image_path']); ?>">
                                    <img src="../../images/edit-icon.png" alt="Edit">
                                    Edit
                                </button>
                                <button class="delete-button"
                                    data-id="<?php echo htmlspecialchars($product['prod_id']); ?>">
                                    <img src="../../images/delete-icon.png" alt="Delete">
                                    Delete
                                </button>
                            </div>

                        </div>
                    <?php endforeach; ?>

                </div>

                <div id="noProductFound" class="no-product-found" style="display: none;">
                    <p>No product found</p>
                </div>
                
                <div id="addProductModal" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h2>Add New Product</h2>
                        <form id="addProductForm" method="post" action="staff_add_product.php" enctype="multipart/form-data">
                            <label for="prod_brand">Product Brand:</label>
                            <input type="text" id="prod_brand" name="prod_brand" required><br><br>
                            <label for="prod_name">Product Name:</label>
                            <input type="text" id="prod_name" name="prod_name" required><br><br>
                            <label for="prod_price_wholesale">Wholesale Price:</label>
                            <input type="number" id="prod_price_wholesale" name="prod_price_wholesale" required><br><br>
                            <label for="prod_price_retail">Retail Price:</label>
                            <input type="number" id="prod_price_retail" name="prod_price_retail" required><br><br>
                            <label for="prod_image">Product Image:</label>
                            <input type="file" id="prod_image" name="prod_image" accept="image/*"><br><br>
                            <div class="form-button-container">
                                <button type="submit" class="save-btn">Submit</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div id="editProductModal" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeEditModal()">&times;</span>
                        <h2>Edit Product</h2>
                        <form method="post" action="staff_edit_product.php" enctype="multipart/form-data">
                            <input type="hidden" name="prod_id" value="<?php echo htmlspecialchars($product['prod_id']); ?>">
                            <input type="hidden" name="source_page" value="wholesale">
                            <label for="prod_brand">Product Brand:</label>
                            <input type="text" id="prod_brand" name="prod_brand" value="<?php echo htmlspecialchars($product['prod_brand']); ?>" required><br><br>
                            <label for="prod_name">Product Name:</label>
                            <input type="text" id="prod_name" name="prod_name" value="<?php echo htmlspecialchars($product['prod_name']); ?>" required><br><br>
                            <label for="prod_price_wholesale">Wholesale Price:</label>
                            <input type="number" id="prod_price_wholesale" name="prod_price_wholesale" value="<?php echo htmlspecialchars($product['prod_price']); ?>" required><br><br>
                            <label for="prod_image">Product Image:</label>
                            <input type="file" id="prod_image" name="prod_image" accept="images/*"><br><br>
                            <label for="prod_image">Current Image:</label>
                            <a id="currentImageLink" href="#" target="_blank" style="display:none;"></a>
                            <div class="form-group">
                                <button type="submit" class="save-btn">Update</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Message Modal -->
                <?php if ($successMessage || $errorMessage): ?>
                    <div id="messageModal" class="message-modal" style="display: block;">
                        <div class="message-modal-content">
                            <span class="message-close">&times;</span>
                            <div id="messageContent">
                                <?php
                                if ($successMessage) {
                                    echo '<div class="alert-success">' . htmlspecialchars($successMessage) . '</div>';
                                } elseif ($errorMessage) {
                                    echo '<div class="alert-error">' . htmlspecialchars($errorMessage) . '</div>';
                                }
                                ?>
                                <button class="message-button" id="okButton">OK</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div id="deleteModal" class="message-modal" style="display: none;">
                    <div class="message-modal-content">
                        <span class="message-close">&times;</span>
                        <div id="messageContent">
                            <div class="alert error">
                                <p>Are you sure you want to delete this product?</p>
                                <form id="deleteProductForm" method="post" action="staff_delete_product.php">
                                    <input type="hidden" name="prod_id" id="delete_prod_id">
                                    <input type="hidden" name="source_page" value="wholesale">
                                    <button type="submit" class="confirm-delete-btn">Yes, Delete</button>
                                    <button type="button" class="cancel-delete-btn">Cancel</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="loadingScreen" class="loading-screen" style="display: none;">
                    <div class="spinner"></div>
                    <p>Loading...</p>
                </div>

            </div>
        </div>
    </main>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var addProductModal = document.getElementById("addProductModal");
            var editProductModal = document.getElementById("editProductModal");
            var deleteProductModal = document.getElementById("deleteModal");
            var messageModal = document.getElementById("messageModal");
            var loadingScreen = document.getElementById("loadingScreen");
            var closeButton = document.querySelector("#messageModal .message-close");
            var okButton = document.getElementById('okButton');

            // Handle the Add New Product button click
            document.getElementById("addNewProductBtn").onclick = function() {
                addProductModal.style.display = "block";
            };

            // Handle the close button in the Add New Product modal
            document.querySelector("#addProductModal .close").onclick = function() {
                addProductModal.style.display = "none";
            };

            // Handle form submissions
            document.getElementById('addProductForm').onsubmit = function() {
                loadingScreen.stydle.isplay = 'flex';
            };

            document.getElementById('editProductModal').onsubmit = function() {
                loadingScreen.style.display = 'flex';
            };

            document.getElementById('deleteModal').onsubmit = function() {
                loadingScreen.style.display = 'flex';
            };

            // Handle the Edit Product button clicks
            document.querySelectorAll('.edit-button').forEach(function(button) {
                button.addEventListener('click', function() {
                    var prodId = this.getAttribute('data-id');
                    var prodBrand = this.getAttribute('data-brand');
                    var prodName = this.getAttribute('data-name');
                    var prodPriceWholesale = this.getAttribute('data-price-wholesale');
                    var prodImage = this.getAttribute('data-image');

                    // Populate the edit modal fields with the clicked product data
                    editProductModal.querySelector('input[name="prod_id"]').value = prodId;
                    editProductModal.querySelector('input[name="prod_brand"]').value = prodBrand;
                    editProductModal.querySelector('input[name="prod_name"]').value = prodName;
                    editProductModal.querySelector('input[name="prod_price_wholesale"]').value = prodPriceWholesale;

                    var imageLink = editProductModal.querySelector('#currentImageLink');
                    if (prodImage) {
                        imageLink.href = prodImage;
                        imageLink.textContent = prodImage.split('/').pop(); // Extract filename
                        imageLink.style.display = 'block';
                    } else {
                        imageLink.style.display = 'none';
                    }

                    // Show the edit modal
                    editProductModal.style.display = 'block';
                });
            });

            // Handle the close button in the Edit Product modal
            document.querySelector("#editProductModal .close").onclick = function() {
                editProductModal.style.display = "none";
            };

            // Handle the Delete Product button clicks
            document.querySelectorAll('.delete-button').forEach(function(button) {
                button.addEventListener('click', function() {
                    var prodId = this.getAttribute('data-id');

                    // Set the product ID in the delete modal
                    deleteProductModal.querySelector('#delete_prod_id').value = prodId;

                    // Show the delete modal
                    deleteProductModal.style.display = 'block';
                });
            });

            // Handle the cancel button in the Delete Product modal
            document.querySelector('.cancel-delete-btn').onclick = function() {
                deleteProductModal.style.display = 'none';
            };

            document.querySelector("#deleteModal .message-close").onclick = function() {
                deleteProductModal.style.display = 'none';
            };

            // Handle message modal showing
            if (messageModal) {
                // Handle the OK button in the message modal
                okButton.onclick = function() {
                    messageModal.style.display = 'none';
                };

                // Handle the close button in the message modal
                closeButton.onclick = function() {
                    messageModal.style.display = 'none';
                };
            }

            // Handle search input
            const searchInput = document.getElementById('searchInput');
            const productCards = document.querySelectorAll('.product-card');
            const noProductFound = document.getElementById('noProductFound');

            searchInput.addEventListener('input', function() {
                const searchValue = searchInput.value.toLowerCase();
                let anyCardVisible = false;

                productCards.forEach(card => {
                    const brandElement = card.querySelector('h4');
                    const nameElement = card.querySelector('p');
                    const price = card.getAttribute('data-price');

                    const brand = brandElement ? brandElement.textContent.toLowerCase() : '';
                    const name = nameElement ? nameElement.textContent.toLowerCase() : '';
                    const priceText = price ? price.toLowerCase() : '';

                    if (brand.includes(searchValue) || name.includes(searchValue) || priceText.includes(searchValue)) {
                        card.style.display = '';
                        anyCardVisible = true;
                    } else {
                        card.style.display = 'none';
                    }
                });

                noProductFound.style.display = anyCardVisible ? 'none' : 'block';
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


            // Handle the wholesale button click
            document.getElementById('retailBtn').onclick = function() {
                window.location.href = 'staff_retail.php';
            };
        });
    </script>

</body>

</html>