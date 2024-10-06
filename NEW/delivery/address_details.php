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

$stmt->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Address Details</title>
    <link rel="stylesheet" href="../../styles/delivery.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
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

    <main>
        <div class="card">
        <button type="button" class="back-btn" onclick="window.location.href='delivery.php';">
                <img src="../../images/back-icon.png" alt="Back" class="back-icon">Back</button>

            <div class="address-details">
                <h2>Delivery Address Details</h2>
                <p><strong>Order ID:</strong> <?php echo htmlspecialchars($order_id); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($address); ?></p>
                <p><strong>City:</strong> <?php echo htmlspecialchars($city); ?></p>
                <p><strong>Zip Code:</strong> <?php echo htmlspecialchars($zip_code); ?></p>
            </div>

            <div id="map"></div>
        </div>
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