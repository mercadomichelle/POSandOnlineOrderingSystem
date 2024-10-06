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

$stmt->close();

// Fetching Pending Online Orders
$sql = "SELECT o.order_id, o.login_id, o.total_amount, o.order_date, o.order_status, u.first_name, u.last_name
        FROM orders o
        JOIN login u ON o.login_id = u.id
        WHERE o.order_source = 'online'
        ORDER BY o.order_status ASC, o.order_date DESC";

$ordersResult = $mysqli->query($sql);

// Determine the smallest order_id in the current set of orders
$sql = "SELECT MIN(order_id) AS min_id FROM orders";
$result = $mysqli->query($sql);
$row = $result ? $result->fetch_assoc() : null;
$min_id = $row && $row['min_id'] ? $row['min_id'] : 0;

// Calculate the offset so that the smallest order_id starts at 1000
$offset = 1000 - $min_id;

// STOCKS NOTIFICATIONS
$sql = "SELECT p.prod_id, p.prod_brand, p.prod_name, p.prod_image_path, s.stock_quantity 
        FROM products p 
        LEFT JOIN stocks s ON p.prod_id = s.prod_id
        ORDER BY s.stock_quantity ASC";

$stocksResult = $mysqli->query($sql);

$stocks = [];
if ($stocksResult->num_rows > 0) {
    while ($row = $stocksResult->fetch_assoc()) {
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

if (isset($_POST['order_status'], $_POST['order_id'])) {
    $order_status = $_POST['order_status'];
    $order_id = $_POST['order_id'];
    $current_time = date('Y-m-d H:i:s');

    // Map order statuses to their corresponding timestamp column
    $status_timestamp_map = [
        'Pending'            => null, // No timestamp update needed
        'Being Packed'       => 'status_packed_at',
        'For Delivery'       => 'status_shipped_at',
        'Delivery Complete'  => 'status_delivered_at',
    ];

    // Ensure the order status provided is valid
    if (array_key_exists($order_status, $status_timestamp_map)) {
        $timestamp_column = $status_timestamp_map[$order_status];

        if ($timestamp_column) {
            // Build the SQL query dynamically with timestamp
            $sql = "UPDATE orders SET order_status = ?, $timestamp_column = ? WHERE order_id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("ssi", $order_status, $current_time, $order_id);
        } else {
            // No timestamp to update for 'Pending' status
            $sql = "UPDATE orders SET order_status = ? WHERE order_id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("si", $order_status, $order_id);
        }

        if ($stmt->execute()) {
            echo json_encode(['status' => true, 'order_id' => $order_id, 'order_status' => $order_status]);
        } else {
            echo json_encode(['status' => false, 'error' => $stmt->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['status' => false, 'error' => 'Invalid order status']);
    }
}

// Initialize arrays to group orders by status
$groupedOrders = [
    'Pending' => [],
    'Being Packed' => [],
    'For Delivery' => [],
    'Delivery Complete' => [],
    'Cancelled' => []
];

// Group the orders by their status
while ($row = $ordersResult->fetch_assoc()) {
    $status = isset($row['order_status']) ? $row['order_status'] : 'Pending';
    $groupedOrders[$status][] = $row;  // Push each order into the corresponding status array
}

// Calculate order_id offset
$offset = 1000 - $min_id;


$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rice Website | Online Orders</title>
    <link rel="stylesheet" href="../../styles/online_order.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <header>
        <div class="logo">RICE</div>
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
                <li><a href="../stocks/staff_stocks.php"><img src="../../images/stocks-icon.png" alt="Stocks">STOCKS</a></li>
                <li><a class="current"><img src="../../images/online-icon.png" alt="Online">ONLINE ORDER</a></li>
            </ul>
        </nav>
    </div>


    <main>
        <div class="body">
            <div class="card">
                <div class="sort-container">
                    <h2>Online Order Status</h2>
                    <div class="sort">
                        <label for="sortOrderStatus">Sort by Status:</label>
                        <select id="sortOrderStatus">
                            <option value="all">All</option>
                            <option value="Pending">Pending</option>
                            <option value="Being Packed">Being Packed</option>
                            <option value="For Delivery">For Delivery</option>
                            <option value="Delivery Complete">Delivery Complete</option>
                        </select>
                    </div>
                </div>

                <div id="orderList">
                    <?php foreach ($groupedOrders as $status => $orders): ?>
                        <div class="order-section" id="<?php echo strtolower(str_replace(' ', '_', $status)); ?>">
                            <h3><?php echo htmlspecialchars($status); ?> Orders</h3>

                            <?php if (count($orders) > 0): ?>
                                <div class="order-header">
                                    <div>Order ID</div>
                                    <div>Customer Name</div>
                                    <div>Total Amount</div>
                                    <div>Order Date</div>
                                    <div>Status</div>
                                </div>

                                <?php foreach ($orders as $row): ?>
                                    <div class="order-item-container">
                                        <div class="order-item" data-order-id="<?php echo $row['order_id']; ?>">
                                            <div><?php echo $row['order_id'] + $offset; ?></div>
                                            <div><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></div>
                                            <div><?php echo number_format($row['total_amount'], 2); ?></div>
                                            <div><?php echo date("F j, Y", strtotime($row['order_date'])); ?></div>
                                            <div>
                                                <form class="update-status-form">
                                                    <input type="hidden" name="order_id" value="<?php echo $row['order_id']; ?>">
                                                    <select name="order_status" <?php echo ($row['order_status'] === 'Cancelled') ? 'disabled' : ''; ?>>
                                                        <option value="Pending" <?php echo ($row['order_status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                                        <option value="Being Packed" <?php echo ($row['order_status'] === 'Being Packed') ? 'selected' : ''; ?>>Being Packed</option>
                                                        <option value="For Delivery" <?php echo ($row['order_status'] === 'For Delivery') ? 'selected' : ''; ?>>For Delivery</option>
                                                        <option value="Delivery Complete" <?php echo ($row['order_status'] === 'Delivery Complete') ? 'selected' : ''; ?>>Delivery Complete</option>
                                                        <?php if ($row['order_status'] === 'Cancelled'): ?>
                                                            <option value="Cancelled" selected disabled>Cancelled</option>
                                                        <?php endif; ?>
                                                    </select>
                                                </form>
                                            </div>
                                            <div>
                                                <button class="details-toggle">Details &#x25BC;</button>
                                            </div>
                                            <div class="order-details" style="display: none; width: 98%;"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>No orders found in this category.</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>

            </div>
        </div>
    </main>

    <script>
        $(document).ready(function() {
            $('.update-status-form select[name="order_status"]').on('change', function() {
                const form = $(this).closest('form');
                const formData = form.serialize();

                $.post('online_order.php', formData, function(response) {
                    console.log('Server Response:', response);
                    const data = JSON.parse(response);

                    if (data.status) {
                        // Optionally update the UI or show a success message
                        alert('Order status updated successfully.');
                    } else {
                        alert('Failed to update order status: ' + data.error);
                    }
                }).fail(function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                });
            });


            // Toggle display of order items on "Details" button click
            $('.details-toggle').on('click', function() {
                const orderItemContainer = $(this).closest('.order-item-container');
                const orderId = orderItemContainer.find('.order-item').data('order-id');
                const detailsContainer = orderItemContainer.find('.order-details');

                // Check if details are already loaded
                if (detailsContainer.children().length === 0) {
                    // Load order items via AJAX
                    $.post('fetch_order_items.php', {
                        order_id: orderId
                    }, function(data) {
                        detailsContainer.html(data);
                        detailsContainer.slideToggle();
                    });
                } else {
                    detailsContainer.slideToggle();
                }
            });

            $('#sortOrderStatus').on('change', function() {
                const selectedStatus = $(this).val();

                // Hide all sections
                $('.order-section').hide();

                // Show only the relevant section(s) based on the selected status
                if (selectedStatus === 'all') {
                    // Show all sections
                    $('.order-section').show();
                } else {
                    // Show only the section corresponding to the selected status
                    $('#' + selectedStatus.toLowerCase().replace(' ', '_')).show();
                }
            });

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
    </script>
</body>

</html>