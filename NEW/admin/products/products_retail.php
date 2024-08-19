<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

session_start();

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

$sql = "SELECT prod_id, prod_brand, prod_name, prod_price_retail AS prod_price, prod_image_path FROM products";
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
    <link rel="stylesheet" href="../../styles/products.css">
</head>
<body>
<header>
        <div class="logo">RICE</div>
        <div class="account-info">
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
                <li><a href="../admin.php"><img src="../../images/dashboard-icon.png" alt="Dashboard">DASHBOARD</a></li>
                <li><a href="../products/products.php" class="current"><img src="../../images/products-icon.png" alt="Products">PRODUCTS</a></li>
                <li><a href="../stocks/stocks.php"><img src="../../images/stocks-icon.png" alt="Stocks">STOCKS</a></li>
                <li><a href="../staffs/staff_list.php"><img src="../../images/staffs-icon.png" alt="Staffs">STAFFS</a></li>
            </ul>
        </nav>
            <ul class="reports">
                <li><a href="../reports/reports.php"><img src="../../images/reports-icon.png" alt="Reports">REPORTS</a></li>
            </ul>    
    </div>


    <main>
    <div class="products">
        <div class="product-controls">
            <button class="filter-button" id="wholesaleBtn" ><img src="../../images/wholesale-icon.png" alt="Wholesale">WHOLESALE</button>
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
                    <div class="product-card" data-price="<?php echo htmlspecialchars($product['prod_price']); ?>">
                        <img src="<?php echo $product['prod_image_path']; ?>" alt="<?php echo htmlspecialchars($product['prod_name']); ?>">
                        <h4><?php echo htmlspecialchars($product['prod_brand']); ?></h4>
                        <p><?php echo htmlspecialchars($product['prod_name']); ?></p>
                        <h3>₱ <?php echo number_format($product['prod_price'], 2); ?> / kilo</h3>
                       
                        <div class="product-actions">
                            <button class="edit-button" 
                                data-id="<?php echo htmlspecialchars($product['prod_id']); ?>"
                                data-brand="<?php echo htmlspecialchars($product['prod_brand']); ?>"
                                data-name="<?php echo htmlspecialchars($product['prod_name']); ?>"
                                data-price-retail="<?php echo htmlspecialchars($product['prod_price']); ?>"
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

            <div id="editProductModal" class="modal">
                <div class="modal-content">
                    <span class="close" onclick="closeEditModal()">&times;</span>
                    <h2>Edit Product</h2>
                    <form method="post" action="edit_products.php" enctype="multipart/form-data">
                        <input type="hidden" name="prod_id" value="<?php echo htmlspecialchars($product['prod_id']); ?>">
                        <input type="hidden" name="source_page" value="retail">
                        <label for="prod_brand">Product Brand:</label>
                        <input type="text" id="prod_brand" name="prod_brand" value="<?php echo htmlspecialchars($product['prod_brand']); ?>" required><br><br>
                        <label for="prod_name">Product Name:</label>
                        <input type="text" id="prod_name" name="prod_name" value="<?php echo htmlspecialchars($product['prod_name']); ?>" required><br><br>
                        <label for="prod_price_retail">Retail Price:</label>
                        <input type="number" id="prod_price_retail" name="prod_price_retail" value="<?php echo htmlspecialchars($product['prod_price']); ?>" required><br><br>
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

            <div id="deleteModal" class="message-modal" style="display: none;">
                <div class="message-modal-content">
                    <span class="message-close">&times;</span>
                    <div id="messageContent">
                        <div class="alert error">
                            <p>Are you sure you want to delete this product?</p>
                            <form id="deleteProductForm" method="post" action="delete_product.php">
                                <input type="hidden" name="prod_id" id="delete_prod_id">
                                <input type="hidden" name="source_page" value="retail"> 
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
        var editProductModal = document.getElementById("editProductModal");
        var deleteProductModal = document.getElementById("deleteModal");

        document.getElementById('editProductModal').onsubmit = function() {
            document.getElementById('loadingScreen').style.display = 'flex';
        };

        document.getElementById('deleteModal').onsubmit = function() {
            document.getElementById('loadingScreen').style.display = 'flex';
        };

        document.querySelectorAll('.edit-button').forEach(function(button) {
            button.addEventListener('click', function() {
                var prodId = this.getAttribute('data-id');
                var prodBrand = this.getAttribute('data-brand');
                var prodName = this.getAttribute('data-name');
                var prodPriceRetail = this.getAttribute('data-price-retail');
                var prodImage = this.getAttribute('data-image');

                editProductModal.querySelector('input[name="prod_id"]').value = prodId;
                editProductModal.querySelector('input[name="prod_brand"]').value = prodBrand;
                editProductModal.querySelector('input[name="prod_name"]').value = prodName;
                editProductModal.querySelector('input[name="prod_price_retail"]').value = prodPriceRetail;

                var imageLink = editProductModal.querySelector('#currentImageLink');
                if (prodImage) {
                    imageLink.href = prodImage;
                    imageLink.textContent = prodImage.split('/').pop(); // Extract filename
                    imageLink.style.display = 'block';
                } else {
                    imageLink.style.display = 'none';
                }

                editProductModal.style.display = 'block';
            });
        });

        document.querySelector("#editProductModal .close").onclick = function() {
            editProductModal.style.display = "none";
        };

        document.querySelectorAll('.delete-button').forEach(function(button) {
            button.addEventListener('click', function() {
                var prodId = this.getAttribute('data-id');

                deleteProductModal.querySelector('#delete_prod_id').value = prodId;

                deleteProductModal.style.display = 'block';
            });
        });

        document.querySelector('.cancel-delete-btn').onclick = function() {
            deleteProductModal.style.display = 'none';
        };

        document.querySelector('.message-close').onclick = function() {
            deleteProductModal.style.display = 'none';
        };

        document.getElementById('wholesaleBtn').onclick = function() {
            window.location.href = 'products.php';
        };

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
    });
</script>

</body>
</html>
