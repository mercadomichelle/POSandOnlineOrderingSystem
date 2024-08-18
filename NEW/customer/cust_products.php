<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

session_start();

// Check if user is logged in
if (!isset($_SESSION["username"])) {
    header("Location: ../login.php");
    exit();
}

// Create a new database connection
$mysqli = new mysqli($host, $user, $password, $db);

// Check connection
if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Fetch user details
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
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Rice Website</title>
        <link rel="stylesheet" href="../styles/cust_products.css">
    </head>
    
<body>
    <header>
        <div class="logo">RICE</div>
        <div class="nav-wrapper">
            <nav>
                <a href="../customer/customer.php">HOME</a>
                <a href="../customer/cust_products.php" class="current" >PRODUCTS</a>
                <a href="../customer/my_cart.php" id="cart-link">MY CART</a>
                <a href="../customer/about_us.php" id="about-link">ABOUT US</a>
            </nav>
        </div>
        <div class="account-info">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION["first_name"] . " " . $_SESSION["last_name"]); ?></span>
            <div class="dropdown">
                <img src="../images/account-icon.png" alt="Account" class="account-icon">
                <div class="dropdown-content">
                    <a href="../customer/my_profile.php">My Profile</a>
                    <a href="../customer/my_orders.php">My Orders</a>
                    <a href="../logout.php">Logout</a>
                </div>
            </div>
        </div>
    </header>
</body>

<script>
    function updateNavLinks() {
        const ordersLink = document.getElementById('cart-link');
        const aboutLink = document.getElementById('about-link');

        if (window.innerWidth <= 649) {
            ordersLink.textContent = 'CART';
            aboutLink.textContent = 'ABOUT';
        } else {
            ordersLink.textContent = 'MY CART';
            aboutLink.textContent = 'ABOUT US';
        }
    }

    window.addEventListener('resize', updateNavLinks);
    window.addEventListener('DOMContentLoaded', updateNavLinks);

    window.addEventListener('resize', updateNavLinks);
    window.addEventListener('DOMContentLoaded', updateNavLinks);

</script>

</html>
