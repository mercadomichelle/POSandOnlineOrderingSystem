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

$stmt->close();
$mysqli->close();
?>


<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Rice Website</title>
        <link rel="stylesheet" href="../styles/products.css">
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
                <li><a href="../admin/admin.php"><img src="../images/dashboard-icon.png" alt="Dashboard">DASHBOARD</a></li>
                <li><a href="../admin/products.php" class="current"><img src="../images/products-icon.png" alt="Products">PRODUCTS</a></li>
                <li><a href="../admin/stocks.php"><img src="../images/stocks-icon.png" alt="Stocks">STOCKS</a></li>
                <li><a href="../admin/staff_list.php"><img src="../images/staffs-icon.png" alt="Staffs">STAFFS</a></li>
            </ul>
        </nav>
            <ul class="reports">
                <li><a href="../admin/reports.php"><img src="../images/reports-icon.png" alt="Reports">REPORTS</a></li>
            </ul>    
    </div>
    
    <main>
    <div class="products">
        <div class="product-controls">
            <button class="filter-button-current" id="wholesaleBtn" ><img src="../images/wholesale-icon.png" alt="Wholesale">WHOLESALE</button>
            <button class="filter-button" id="retailBtn"><img src="../images/retail-icon.png" alt="Retail">RETAIL</button>
            <div class="search-container">
                <div class="search-wrapper">
                    <input type="text" placeholder="Search..." id="searchInput">
                    <img src="../images/search-icon.png" alt="Search" class="search-icon">
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
                    <div class="product-card">
                        <img src="<?php echo $product['prod_image_path']; ?>" alt="<?php echo htmlspecialchars($product['prod_name']); ?>">
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
                                <img src="../images/edit-icon.png" alt="Edit">
                                Edit
                            </button>

                            <button class="delete-button" 
                            data-id="<?php echo htmlspecialchars($product['prod_id']); ?>">
                            <img src="../images/delete-icon.png" alt="Delete">
                            Delete
                            </button>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>

            <div id="addProductModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Add New Product</h2>
                    <form id="addProductForm" method="post" action="add_products.php" enctype="multipart/form-data">
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
                    <form method="post" action="edit_products.php" enctype="multipart/form-data">
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
                        <?php if (!empty($product['prod_image_path'])): ?>
                            <img id="currentImage" src="<?php echo htmlspecialchars($product['prod_image_path']); ?>" alt="Current Image" style="max-width: 200px; margin-top: 10px;">
                        <?php endif; ?>
                        <div class="form-group">
                            <button type="submit" class="save-btn">Update</button>
                        </div>
                    </form>
                </div>
            </div>

            <div id="deleteModal" class="message-modal" style="display: none;">
                <div class="message-modal-content">
                    <span class="message-close">&times;</span>
                    <div id="messageContent">
                        <div class="alert error">
                            <p>Are you sure you want to delete this product?</p>
                            <form id="deleteProductForm" method="post" action="delete_product.php">
                                <input type="hidden" name="prod_id" id="delete_prod_id">
                                <button type="submit" class="confirm-delete-btn">Yes, Delete</button>
                                <button type="button" class="cancel-delete-btn">Cancel</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            
        </div>
    </div>
    </main>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var addProductModal = document.getElementById("addProductModal");
        var editProductModal = document.getElementById("editProductModal");
        var deleteProductModal = document.getElementById("deleteModal");

        // Handle the Add New Product button click
        document.getElementById("addNewProductBtn").onclick = function() {
            addProductModal.style.display = "block";
        };

        // Handle the close button in the Add New Product modal
        document.querySelector("#addProductModal .close").onclick = function() {
            addProductModal.style.display = "none";
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

        // Handle the close button in the Delete Product modal
        document.querySelector('.message-close').onclick = function() {
            deleteProductModal.style.display = 'none';
        };

        // Handle the wholesale button click
        document.getElementById('retailBtn').onclick = function() {
            window.location.href = 'products_retail.php';
        };

        // Handle the search input
        document.getElementById('searchInput').oninput = function() {
            // Implement search functionality here if needed
        };
    });

</script>

</body>
</html>