<?php
session_start();
include('../../connection.php');

// Ensure the user is logged in
if (!isset($_SESSION["username"])) {
    header("Location: ../../login.php");
    exit();
}

$username = $_SESSION["username"];

// Handle branch selection form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['branch_id'])) {
    $selectedBranch = intval($_POST['branch_id']);
    $_SESSION['selected_branch'] = $selectedBranch;
    header("Location: " . $_SERVER['PHP_SELF']); // Redirect to prevent form resubmission
    exit();
}

// Use selected branch from session
$selectedBranch = isset($_SESSION['selected_branch']) ? $_SESSION['selected_branch'] : null;

// Fetch user data for display
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

$sql = "SELECT p.prod_id, p.prod_brand, p.prod_name, p.prod_image_path, 
               COALESCE(SUM(s.stock_quantity), 0) AS stock_quantity,
               GROUP_CONCAT(CONCAT('Branch ', s.branch_id, ': ', s.stock_quantity) SEPARATOR '; ') AS branch_details
        FROM products p 
        LEFT JOIN stocks s ON p.prod_id = s.prod_id";

if ($selectedBranch) {
    $sql .= " WHERE s.branch_id = ?";
}

$sql .= " GROUP BY p.prod_id, p.prod_brand, p.prod_name, p.prod_image_path
          ORDER BY stock_quantity ASC";

$stmt = $mysqli->prepare($sql);

if ($selectedBranch) {
    $stmt->bind_param("i", $selectedBranch);
}

$stmt->execute();
$stocks_result = $stmt->get_result();

// Fetch all stocks into an array
$stocks = [];
while ($stock = $stocks_result->fetch_assoc()) {
    $stocks[] = $stock;
}

// STOCKS NOTIFICATIONS
$sql = "SELECT p.prod_id, p.prod_brand, p.prod_name, p.prod_image_path, 
               COALESCE(SUM(s.stock_quantity), 0) AS stock_quantity 
        FROM products p 
        LEFT JOIN stocks s ON p.prod_id = s.prod_id";

if ($selectedBranch) {
    $sql .= " WHERE s.branch_id = ?";
}

$sql .= " GROUP BY p.prod_id, p.prod_brand, p.prod_name, p.prod_image_path
          ORDER BY stock_quantity ASC";

// Debugging: Print out the final query to check if it's correct
// echo $sql; // Uncomment to debug

$stmt = $mysqli->prepare($sql);

// Bind branch_id if selected
if ($selectedBranch) {
    $stmt->bind_param("i", $selectedBranch);
}

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
        <div><img src="../../favicon.png" alt="Logo" class="logo"></div>

        <form method="POST" id="branchForm">
    <select class="branch-selector" id="branchSelector" name="branch_id" onchange="this.form.submit()">
        <option value="">Select Branch</option>
        <option value="1" <?php echo $selectedBranch == 1 ? 'selected' : ''; ?>>Calero</option>
        <option value="2" <?php echo $selectedBranch == 2 ? 'selected' : ''; ?>>Bauan</option>
        <option value="3" <?php echo $selectedBranch == 3 ? 'selected' : ''; ?>>San Pascual</option>
    </select>
</form>


        <div class="account-info">
            <div class="dropdown notifications-dropdown">
                <img src="../../images/notif-icon.png" alt="Notifications" class="notification-icon">
                <div class="dropdown-content" id="notificationDropdown">
                    <p class="notif">Notifications</p>
                    <?php if (empty($notifications)): ?>
                        <a href="#">No new notifications</a>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <a href="stocks.php"><?php echo $notification; ?></a>
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
                <li><a href="../super_admin.php"><img src="../../images/dashboard-icon.png" alt="Dashboard">DASHBOARD</a></li>
                <li><a href="../products/products.php"><img src="../../images/products-icon.png" alt="Products">PRODUCTS</a></li>
                <li><a class="current"><img src="../../images/stocks-icon.png" alt="Stocks">STOCKS</a></li>
                <li><a href="../staffs/staff_list.php"><img src="../../images/staffs-icon.png" alt="Staffs">STAFFS</a></li>
            </ul>
        </nav>
        <ul class="reports">
            <li><a href="../reports/reports.php"><img src="../../images/reports-icon.png" alt="Reports">REPORTS</a></li>
        </ul>
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
                                    data-id="<?php echo htmlspecialchars($stock['prod_id']); ?>"
                                    <?php echo !isset($_SESSION['selected_branch']) || $_SESSION['selected_branch'] == null ? 'disabled' : ''; ?>>
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
                        <form method="post" action="add_stock.php" id="addStockForm">
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
                                    echo '<div class="alert-success">' . htmlspecialchars($successMessage) . '</div>';
                                }
                                if ($errorMessage) {
                                    echo '<div class="alert-error">' . htmlspecialchars($errorMessage) . '</div>';
                                }
                                ?>
                                <button class="message-button" id="okButton">OK</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="../../js/notif.js"></script>
    <script src="../../js/search.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
    </script>
</body>

</html>