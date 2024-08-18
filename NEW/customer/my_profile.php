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

$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$sql = "SELECT first_name, last_name, username FROM login WHERE username = ?";
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

$sql = "SELECT * FROM profile WHERE username = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$profileResult = $stmt->get_result();

if ($profileResult->num_rows === 1) {
    $profileData = $profileResult->fetch_assoc();
} else {
    $profileData = [
        'email' => '',
        'phone' => '',
        'address' => '',
        'barangay' => '',
        'city' => '',
        'province' => '',
        'zip_code' => ''
    ];
}
$stmt->close();
$mysqli->close();

$successMessage = isset($_SESSION['successMessage']) ? $_SESSION['successMessage'] : '';
$errorMessage = isset($_SESSION['errorMessage']) ? $_SESSION['errorMessage'] : '';
unset($_SESSION['successMessage'], $_SESSION['errorMessage']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rice Website</title>
    <link rel="stylesheet" href="../styles/my_profile.css">
</head>
<body>
<header>
    <div class="logo">RICE</div>
    <div class="nav-wrapper">
        <nav>
            <a href="../customer/customer.php">HOME</a>
            <a href="../customer/cust_products.php">PRODUCTS</a>
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

    <div id="loadingScreen" class="loading-screen" style="display: none;">
        <div class="spinner"></div>
        <p>Saving your changes...</p>
    </div>

<main>
    <div class="profile-container">
        <h1>MY PROFILE</h1>
        <div class="divider"></div> 

        <form id="profileForm" action="update_profile.php" method="POST">
            <div class="profile-section">
                <div class="form-group-wrapper">
                    <div class="form-group">
                        <label class="form-label">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="non-editable" value="<?php echo htmlspecialchars($userData['first_name']); ?>" readonly>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="non-editable" value="<?php echo htmlspecialchars($userData['last_name']); ?>" readonly>
                    </div>
                </div>
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="non-editable" value="<?php echo htmlspecialchars($userData['username']); ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($profileData['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="text" id="phone" name="phone" maxlength="11" value="<?php echo htmlspecialchars($profileData['phone']); ?>" required>
                </div>
            </div>
            <div class="divider1"></div>
            <div class="delivery-section">
                <h2>Delivery Address</h2>
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($profileData['address']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="barangay">Barangay</label>
                    <input type="text" id="barangay" name="barangay" value="<?php echo htmlspecialchars($profileData['barangay']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="city">City</label>
                    <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($profileData['city']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="province">Province</label>
                    <input type="text" id="province" name="province" value="<?php echo htmlspecialchars($profileData['province']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="zip_code">Zip Code</label>
                    <input type="text" id="zip_code" name="zip_code" maxlength="4" value="<?php echo htmlspecialchars($profileData['zip_code']); ?>" required>
                </div>
            </div>
            <div class="button-container">
                <button type="submit" class="save-button">SAVE</button>
            </div>
        </form>
        
        <div class="modal" id="messageModal" style="display:none;">
            <div class="modal-content">
                <span class="close" id="closeModal">&times;</span>
                <p id="modalMessage"><?php echo $successMessage . $errorMessage; ?></p>
                <button id="okButton">OK</button>
            </div>
        </div>

    </div>
</main>


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

    function showModal(message) {
        const modal = document.getElementById('messageModal');
        const modalMessage = document.getElementById('modalMessage');
        modalMessage.textContent = message;
        modal.style.display = 'flex';
        document.body.classList.add('modal-open');
    }

    function hideModal() {
        const modal = document.getElementById('messageModal');
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
    }

    document.getElementById('closeModal').addEventListener('click', hideModal);
    document.getElementById('okButton').addEventListener('click', hideModal);
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            hideModal();
        }
    });

    <?php if (!empty($successMessage) || !empty($errorMessage)): ?>
        showModal("<?php echo $successMessage . $errorMessage; ?>");
    <?php endif; ?>

    document.getElementById('profileForm').addEventListener('submit', function(event) {
        event.preventDefault();
        
        const initialData = {
            email: "<?php echo htmlspecialchars($profileData['email']); ?>",
            phone: "<?php echo htmlspecialchars($profileData['phone']); ?>",
            address: "<?php echo htmlspecialchars($profileData['address']); ?>",
            barangay: "<?php echo htmlspecialchars($profileData['barangay']); ?>",
            city: "<?php echo htmlspecialchars($profileData['city']); ?>",
            province: "<?php echo htmlspecialchars($profileData['province']); ?>",
            zip_code: "<?php echo htmlspecialchars($profileData['zip_code']); ?>"
        };

        const formData = {
            email: document.getElementById('email').value,
            phone: document.getElementById('phone').value,
            address: document.getElementById('address').value,
            barangay: document.getElementById('barangay').value,
            city: document.getElementById('city').value,
            province: document.getElementById('province').value,
            zip_code: document.getElementById('zip_code').value
        };

        const formChanged = Object.keys(initialData).some(key => initialData[key] !== formData[key]);

        if (!formChanged) {
            showModal("No changes has been made.");
            return;
        }

        const phoneRegex = /^[0-9]+$/;
        const zipCodeRegex = /^[0-9]+$/;

        if (!phoneRegex.test(formData.phone)) {
            showModal("Phone number can only contain numeric characters.");
            return;
        }

        if (!zipCodeRegex.test(formData.zip_code)) {
            showModal("Zip code can only contain numeric characters.");
            return;
        }

        if (formData.phone.length !== 11) {
            showModal("Phone number must be exactly 11 digits.");
            return;
        }

        if (formData.zip_code.length !== 4) {
            showModal("Zip code must be exactly 4 digits.");
            return;
        }

        const loadingScreen = document.getElementById('loadingScreen');
        loadingScreen.style.display = 'flex'; 
        document.body.classList.add('loading-open');

        this.submit();
    });
</script>

</body>
</html>
