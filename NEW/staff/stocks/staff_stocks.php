<?php
session_start();
include('../../connection.php');
include('../../notifications.php');

// Ensure the user is logged in
if (!isset($_SESSION["username"])) {
    header("Location: ../../login.php");
    exit();
}

$username = $_SESSION["username"];
$branch_id = $_SESSION['branch_id'];

// Fetch user data and branch_id
$sql = "SELECT first_name, last_name, branch_id FROM login WHERE username = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $userData = $result->fetch_assoc();
    $_SESSION["first_name"] = $userData['first_name'];
    $_SESSION["last_name"] = $userData['last_name'];
    $_SESSION["branch_id"] = $userData['branch_id'];  // Store branch_id in session
} else {
    $_SESSION["first_name"] = "Guest";
    $_SESSION["last_name"] = "";
    $_SESSION["branch_id"] = null;
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $prod_id = $_POST['prod_id'];
    $quantity = $_POST['stock_quantity'];

    // Insert new stock record directly for the user's branch
    $sql = "INSERT INTO stocks (prod_id, branch_id, stock_quantity) VALUES (?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("iii", $prod_id, $branch_id, $quantity);  // Use session's branch_id here
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['successMessage'] = "Stock added successfully!";
    } else {
        $_SESSION['errorMessage'] = "Error: Unable to add stock.";
    }

    $stmt->close();
    $mysqli->close();

    // Redirect to stocks page without passing branch_id in the URL
    header("Location: staff_stocks.php");
    exit();
}


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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rice Website | Product Stocks</title>
    <link rel="icon" href="../../favicon.png" type="image/png">
    <link rel="stylesheet" href="../../styles/stocks.css">
</head>

<body>
    <header>
        <div>
            <img src="../../favicon.png" alt="Logo" class="logo">
            <span class="branch-name"><?php echo htmlspecialchars(string: $_SESSION["branch_name"] . " Branch"); ?></span>
        </div>

        <div class="account-info">
            <div class="dropdown notifications-dropdown">
                <img src="../../images/notif-icon.png" alt="Notifications" class="notification-icon">
                <div class="dropdown-content" id="notificationDropdown">
                    <p class="notif">Notifications</p>
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

    <div class="sidebar">
        <nav>
            <ul>
                <li><a href="../staff.php"><img src="../../images/create-icon.png" alt="Create">CREATE NEW ORDER</a></li>
                <li><a href="../products/staff_products.php"><img src="../../images/products-icon.png" alt="Products">PRODUCTS</a></li>
                <li><a class="current"><img src="../../images/stocks-icon.png" alt="Stocks">STOCKS</a></li>
                <li><a href="../online_orders/online_order.php"><img src="../../images/online-icon.png" alt="Online">ONLINE ORDER</a></li>
            </ul>
        </nav>
    </div>

    <main>
        <div class="stocks">
            <div class="stock-controls">
                <div class="search-container">
                    <div class="search-wrapper">
                        <input type="text" placeholder="Search..." id="searchInput">
                        <img src="../../images/search-icon.png" alt="Search" class="search-icon">
                    </div>
                </div>
            </div>

            
            <div class="card">
                <div class="stock-grid">
                    <?php foreach ($stocks as $stock): ?>
                        <div class="stock-card" data-stock="<?php echo htmlspecialchars($stock['stock_quantity']); ?>">
                            <img src="<?php echo $stock['prod_image_path']; ?>" alt="<?php echo htmlspecialchars($stock['prod_name']); ?>">
                            <h4><?php echo htmlspecialchars($stock['prod_brand']); ?></h4>
                            <p><?php echo htmlspecialchars($stock['prod_name']); ?></p>
                            <?php if ($stock['stock_quantity'] == 0): ?>
                                <div class="stock-notification out-of-stock">OUT OF STOCK</div>
                            <?php elseif ($stock['stock_quantity'] < 10): ?>
                                <div class="stock-notification low-stock">LOW STOCK</div>
                            <?php else: ?>
                                <div class="stock-notification in-stock">IN STOCK</div>
                            <?php endif; ?>

                            <h3>Stock: <?php echo htmlspecialchars($stock['stock_quantity']); ?></h3>

                            <div class="stock-actions">
                                <button class="add-stock-button"
                                    data-id="<?php echo htmlspecialchars($stock['prod_id']); ?>">
                                    Add Stock
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div id="noStockFound" class="no-stock-found" style="display: none;">
                    <p>No stock found</p>
                </div>

                <div id="addStockModal" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeAddStockModal()">&times;</span>
                        <h2>Add Stock</h2>
                        <form method="post" action="staff_add_stock.php" id="addStockForm">
                            <input type="hidden" name="prod_id" id="add_stock_prod_id">
                            <label for="stock_quantity">Stock Quantity:</label>
                            <input type="number" id="stock_quantity" name="stock_quantity" min="1" required><br><br>
                            <div class="form-group">
                                <button type="submit" class="add-btn">Add Stock</button>
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
                                    echo '<div class="alert-success">' . htmlspecialchars($successMessage) . '</div><br>';
                                } elseif ($errorMessage) {
                                    echo '<div class="alert-error">' . htmlspecialchars($errorMessage) . '</div><br>';
                                }
                                ?>
                                <button class="message-button" id="okButton">OK</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div id="loadingScreen" class="loading-screen" style="display: none;">
                <div class="spinner"></div>
                <p>Loading...</p>
            </div>
        </div>
    </main>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Search Functionality
            const searchStockInput = document.getElementById('searchInput');
            const stockCards = document.querySelectorAll('.stock-card');
            const noStockFound = document.getElementById('noStockFound');

            searchStockInput.addEventListener('input', function() {
                const searchValue = searchStockInput.value.toLowerCase();
                let anyCardVisible = false;

                stockCards.forEach(card => {
                    const brandElement = card.querySelector('h4');
                    const nameElement = card.querySelector('p');
                    const stockQuantity = card.getAttribute('data-stock');

                    const brand = brandElement ? brandElement.textContent.toLowerCase() : '';
                    const name = nameElement ? nameElement.textContent.toLowerCase() : '';
                    const stockText = stockQuantity ? stockQuantity.toLowerCase() : '';

                    if (brand.includes(searchValue) || name.includes(searchValue) || stockText.includes(searchValue)) {
                        card.style.display = '';
                        anyCardVisible = true;
                    } else {
                        card.style.display = 'none';
                    }
                });

                noStockFound.style.display = anyCardVisible ? 'none' : 'block';
            });

            // Add Stock Button Click Handler
            document.querySelectorAll('.add-stock-button').forEach(button => {
                button.addEventListener('click', function() {
                    const productId = this.getAttribute('data-id');
                    openAddStockModal(productId);
                });
            });

            document.getElementById('addStockForm').onsubmit = function() {
                document.getElementById('loadingScreen').style.display = 'flex';
            };

            const okButton = document.getElementById('okButton');
            if (okButton) {
                okButton.addEventListener('click', function() {
                    document.getElementById('messageModal').style.display = 'none';
                });
            }

            const closeButton = document.querySelector('.message-close');
            if (closeButton) {
                closeButton.addEventListener('click', function() {
                    document.getElementById('messageModal').style.display = 'none';
                });
            }
        });

        // Function to open Add Stock Modal
        function openAddStockModal(productId) {
            document.getElementById('add_stock_prod_id').value = productId;
            document.getElementById('addStockModal').style.display = 'block';
        }

        // Function to close the Add Stock Modal
        function closeAddStockModal() {
            document.getElementById('addStockModal').style.display = 'none';
        }

        // Function to close the Message Modal
        function closeMessageModal() {
            document.getElementById('messageModal').style.display = 'none';
        }

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