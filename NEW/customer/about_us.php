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
        <title>About Us - Rice Website</title>
        <link rel="stylesheet" href="../styles/about_us.css">
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">
    </head>

<body>
    <header>
        <div class="logo">RICE</div>
        <div class="nav-wrapper">
            <nav>
                <a href="../customer/customer.php">HOME</a>
                <a href="../customer/cust_products.php" >PRODUCTS</a>
                <a href="../customer/my_cart.php" id="cart-link">MY CART</a>
                <a href="../customer/about_us.php" class="current" id="about-link">ABOUT US</a>
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

    <main>
        <section class="about-us">
            <h1>About Us</h1>
            <p>Welcome to Escalona-Delen Rice Dealer Website, your go-to destination for premium rice varieties. We’re committed to delivering the highest quality rice, with a focus on excellence, customer satisfaction, and sustainability.</p>
            <p>Founded in 2006, Escalona-Delen has evolved significantly from its inception. Our dedication to providing exceptional rice drove us to research extensively and transform our passion into a successful online store. Today, we proudly serve customers and support the eco-friendly, fair trade sector of the rice industry.</p>
            <p>We hope you love our products as much as we love offering them to you. Feel free to reach out with any questions or feedback.</p>
            <p>Warm regards, <br> The Escalona-Delen Team</p>
        </section>
    </main>

    <footer>
        <div class="contact-info">
            <!-- Email Icon with Custom Modal -->
            <div class="contact-item">
                <img src="../images/message-icon.png" alt="Message Icon" onclick="openEmailModal()">
                <div class="contact-text">
                    <p>Email Us</p>
                    <p class="contact">escalona-delen@email.com</p>
                </div>
            </div>
            <div class="divider"></div>
            <!-- Phone Icon with Custom Modal -->
            <div class="contact-item">
                <img src="../images/contact-icon.png" alt="Contact Icon" onclick="openPhoneModal()">
                <div class="contact-text">
                    <p>Contact Us</p>
                    <p class="contact">(63) 912-345-6789</p>
                </div>
            </div>
            <div class="divider"></div>
            <!-- Map Icon -->
            <div class="contact-item">
                <img src="../images/visit-icon.png" alt="Visit Icon" onclick="openMapModal()">
                <div class="contact-text">
                    <p>Visit Us</p>
                    <p class="contact">Pastor Road, Cuta, Batangas City<br>Main Branch</p>
                </div>
            </div>
        </div>   
    </footer>

        <div id="emailModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeEmailModal()">&times;</span>
                <h2>Send an Email</h2>
                <p>Do you want to send an email to escalona-delen@email.com?</p>
                <button onclick="sendEmail()">Yes</button>
                <button onclick="closeEmailModal()">No</button>
            </div>
        </div>

        <div id="phoneModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closePhoneModal()">&times;</span>
                <h2>Call Us</h2>
                <p>Do you want to call (63) 912-345-6789?</p>
                <button onclick="callPhone()">Yes</button>
                <button onclick="closePhoneModal()">No</button>
            </div>
        </div>

        <div id="mapModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeMapModal()">&times;</span>
                <h2>Open Google Maps</h2>
                <p>Do you want to open Google Maps for Pastor Road, Cuta, Batangas City?</p>
                <button onclick="openMaps()">Yes</button>
                <button onclick="closeMapModal()">No</button>
            </div>
        </div>
</body>

<script>
     function openEmailModal() {
        document.getElementById("emailModal").style.display = "block";
    }
    function closeEmailModal() {
        document.getElementById("emailModal").style.display = "none";
    }
    function sendEmail() {
        window.location.href = "mailto:escalona-delen@email.com";
    }

    function openPhoneModal() {
        document.getElementById("phoneModal").style.display = "block";
    }
    function closePhoneModal() {
        document.getElementById("phoneModal").style.display = "none";
    }
    function callPhone() {
        window.location.href = "tel:(63) 912-345-6789";
    }

    function openMapModal() {
        document.getElementById("mapModal").style.display = "block";
    }
    function closeMapModal() {
        document.getElementById("mapModal").style.display = "none";
    }
    function openMaps() {
        window.open("https://www.google.com/maps/search/?api=1&query=Pastor+Road,+Cuta,+Batangas+City", "_blank");
    }

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

</script>


</html>
