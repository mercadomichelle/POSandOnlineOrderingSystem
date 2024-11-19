<?php
session_start();

$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

if (!isset($_SESSION["username"])) {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION["username"];
$order_id = $_GET['order_id'] ?? null;
$address = $_GET['address'] ?? 'Address not provided';
$city = $_GET['city'] ?? 'City not provided';
$zip_code = $_GET['zip_code'] ?? 'Zip code not provided';
$latitude = $_GET['latitude'] ?? '13.41'; // Default latitude
$longitude = $_GET['longitude'] ?? '122.56'; // Default longitude

$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Retrieve user data
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


// Check for order completion action
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['complete_order_id'])) {
    $complete_order_id = $_POST['complete_order_id'];

    // Update order status to "Delivered"
    $update_sql = "UPDATE orders SET order_status = 'Delivered', status_delivered_at = NOW() WHERE order_id = ?";
    $update_stmt = $mysqli->prepare($update_sql);
    $update_stmt->bind_param("i", $complete_order_id);

    if ($update_stmt->execute()) {
        $_SESSION["successMessage"] = "Order marked as delivered.";
    } else {
        $_SESSION["errorMessage"] = "Error updating order status: " . $mysqli->error;
    }

    $update_stmt->close();
    header("Location: delivery.php");
    exit();
}

// Retrieve ordered items for the specified order_id
$orderItemsSql = "SELECT order_items.prod_id, order_items.quantity, products.prod_name, products.prod_price_wholesale, products.prod_brand
    FROM order_items
    INNER JOIN products ON order_items.prod_id = products.prod_id
    WHERE order_items.order_id = ?";
$stmtItems = $mysqli->prepare($orderItemsSql);
$stmtItems->bind_param("i", $order_id);
$stmtItems->execute();
$orderItemsResult = $stmtItems->get_result();
$orderItems = $orderItemsResult->fetch_all(MYSQLI_ASSOC);

$stmtItems->close();

$orderSubTotal = 0;
foreach ($orderItems as $item) {
    $orderSubTotal += $item['prod_price_wholesale'] * $item['quantity'];
}

$stmt->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Address Details</title>
    <link rel="icon" href="../favicon.png" type="image/png">
    <link rel="stylesheet" href="../../styles/delivery.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
</head>

<body>
    <header>
        <div><img src="../favicon.png" alt="Logo" class="logo"></div>
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


    <main>
        <section class="dashboard">

            <div class="card">
                <button type="button" class="back-btn" onclick="window.location.href='delivery.php';">
                    <img src="../../images/back-icon.png" alt="Back" class="back-icon">Back</button>
                </button>

                <div class="details-container">
                    <div class="address-details">
                        <h2>Delivery Address Details</h2>
                        <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order_id); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($address); ?></p>
                        <div class="items-details">
                            <h3>Ordered Items</h3>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Product Name</th>
                                        <th>Brand</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orderItems as $item): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['prod_name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['prod_brand']); ?></td>
                                            <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                                            <td>₱<?php echo number_format($item['prod_price_wholesale'], 2); ?></td>
                                            <td>₱<?php echo number_format($item['prod_price_wholesale'] * $item['quantity'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="order-total">
                            <strong>Total Amount: </strong>₱<?php echo number_format($orderSubTotal, 2); ?>
                        </div>
                    </div>


                    <div id="map" class="map-container"></div>
                </div>
                <form method="POST" action="">
                    <input type="hidden" name="complete_order_id" value="<?php echo htmlspecialchars($order_id); ?>">
                    <button type="submit" class="btn-delivered">Mark as Delivered</button>
                </form>
            </div>

        </section>
    </main>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        // Initialize the map
        const map = L.map('map').setView([<?php echo htmlspecialchars($latitude); ?>, <?php echo htmlspecialchars($longitude); ?>], 15); // 15 is the zoom level

        // Add OpenStreetMap tile layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        // Add a marker at the customer's address
        L.marker([<?php echo htmlspecialchars($latitude); ?>, <?php echo htmlspecialchars($longitude); ?>]).addTo(map)
            .bindPopup('<?php echo htmlspecialchars($address . ", " . $city . ", " . $zip_code); ?>')
            .openPopup(); // Automatically open the popup
    </script>
</body>

</html>