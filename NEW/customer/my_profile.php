<?php
session_start();

include('../connection.php');

if (!isset($_SESSION["username"])) {
    header("Location: ../index.php");
    exit();
}

$username = $_SESSION["username"];

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
    $latitude = $profileData['latitude'];
    $longitude = $profileData['longitude'];
} else {
    $profileData = [
        'email' => '',
        'phone' => '',
        'address' => '',
        'zip_code' => '',
        'latitude' => '',
        'longitude' => ''
    ];
    $latitude = null;
    $longitude = null;
}

$city = $_SESSION['city'] ?? 'City not available'; // Get city from session with default


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
    <title>Rice Website | My Profile</title>
    <link rel="icon" href="../favicon.png" type="image/png">
    <link rel="stylesheet" href="../styles/my_profile.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <header>
        <div><img src="../favicon.png" alt="Logo" class="logo"></div>
        <div class="nav-wrapper">
            <nav>
                <a href="../customer/customer.php">HOME</a>
                <a href="../customer/cust_products.php">PRODUCTS</a>
                <a href="../customer/my_orders.php" id="orders-link">MY ORDERS</a>
                <a href="../customer/about_us.php" id="about-link">ABOUT US</a>
            </nav>
        </div>
        <div class="account-info">
            <span class="user-name"><?php echo htmlspecialchars($_SESSION["first_name"] . " " . $_SESSION["last_name"]); ?></span>
            <div class="dropdown">
                <img src="../images/account-icon.png" alt="Account" class="account-icon">
                <div class="dropdown-content">
                    <a href="../customer/my_profile.php">My Profile</a>
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

            <form id="profileForm" action="function/update_profile.php" method="POST">

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
                        <!-- <p>City: <?php echo htmlspecialchars($city); ?></p> -->
                        <input type="text" id="phone" name="phone" maxlength="11" value="<?php echo htmlspecialchars($profileData['phone']); ?>" required>
                    </div>
                </div>

                <div class="divider1"></div>


                <div class="delivery-section">
                    <h2>Delivery Address</h2>
                    <div class="form-group">

                        <label for="location">Address</label>
                        <input type="text" id="location" name="address" placeholder="Type your address" value="<?php echo htmlspecialchars($profileData['address']); ?>" required>
                        <input type="hidden" id="city" name="city" value="">
                        <input type="hidden" id="latitude" name="latitude" value="<?php echo htmlspecialchars($profileData['latitude']); ?>">
                        <input type="hidden" id="longitude" name="longitude" value="<?php echo htmlspecialchars($profileData['longitude']); ?>">
                        <div id="suggestions" class="suggestions-container"></div>
                    </div>
                    <div id="map" style="height: 200px; width: 100%; z-index:auto;"></div>
                    <div class="form-group">
                        <label for="zip_code">Zip Code</label>
                        <input type="text" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($profileData['zip_code']); ?>" required>
                    </div>
                </div>

                <div class="button-container">
                    <button type="submit" class="save-button">SAVE</button>
                    <!-- <input type="submit" value="Save Changes" class="submit-button"> -->
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
            const ordersLink = document.getElementById('orders-link');
            const aboutLink = document.getElementById('about-link');

            if (window.innerWidth <= 649) {
                ordersLink.textContent = 'ORDERS';
                aboutLink.textContent = 'ABOUT';
            } else {
                ordersLink.textContent = 'MY ORDERS';
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
                zip_code: "<?php echo htmlspecialchars($profileData['zip_code']); ?>"
            };

            const formData = {
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                address: document.getElementById('location').value,
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

        $(document).ready(function() {
            const locationIqApiKey = 'pk.874b7e8302271991d4120988fae87225';
            const latitude = "<?php echo $latitude ? $latitude : '13.41'; ?>"; // Default latitude
            const longitude = "<?php echo $longitude ? $longitude : '122.56'; ?>"; // Default longitude

            // Initialize the map
            const map = L.map('map').setView([latitude, longitude], 13);

            // Add OpenStreetMap tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            // Marker for selected location
            let marker;

            // If latitude and longitude are available, add a marker
            if (latitude && longitude) {
                marker = L.marker([latitude, longitude]).addTo(map);
            }

            // Add click event to the map
            map.on('click', function(e) {
                const lat = e.latlng.lat;
                const lng = e.latlng.lng;

                // Remove the existing marker if there is one
                if (marker) {
                    map.removeLayer(marker);
                }

                // Add a new marker
                marker = L.marker([lat, lng]).addTo(map);

                // Use LocationIQ API to get address from coordinates
                fetch(`https://us1.locationiq.com/v1/reverse.php?key=${locationIqApiKey}&lat=${lat}&lon=${lng}&format=json`)
                    .then(response => response.json())
                    .then(data => {
                        const address = data.display_name;
                        const city = data.address.city || data.address.town || data.address.village; // Try to extract the city

                        $('#location').val(address); // Fill the input with the full address
                        $('#latitude').val(lat); // Update the latitude field
                        $('#longitude').val(lng); // Update the longitude field

                        // Set the city for delivery fee lookup
                        $('#city').val(city); // Assuming you have a hidden input field for the city
                    })
                    .catch(error => console.error('Error fetching address:', error));

            });

            // Debounce function to limit API requests
            function debounce(func, wait) {
                let timeout;
                return function(...args) {
                    clearTimeout(timeout);
                    timeout = setTimeout(() => func.apply(this, args), wait);
                };
            }

            // Location input autocomplete
            $('#location').on('input', debounce(function() {
                const query = $(this).val();
                if (query.length > 2) { // Fetch suggestions if input is longer than 2 characters
                    fetch(`https://us1.locationiq.com/v1/autocomplete.php?key=${locationIqApiKey}&q=${encodeURIComponent(query + ' Philippines')}&limit=10`)
                        .then(response => response.json())
                        .then(data => {
                            $('#suggestions').empty().show();
                            data.forEach(item => {
                                $('#suggestions').append(`<div class="suggestion-item">${item.display_name}</div>`);
                            });
                        })
                        .catch(error => {
                            console.error('Error fetching suggestions:', error);
                        });
                } else {
                    $('#suggestions').empty().hide(); // Hide suggestions if input is too short
                }
            }, 300)); // Debounce time set to 300ms

            // Handle click on suggestion
            $('#suggestions').on('click', '.suggestion-item', function() {
                const selectedAddress = $(this).text();
                $('#location').val(selectedAddress); // Set the input value to the clicked suggestion

                // Geocode the selected address to get latitude and longitude
                fetch(`https://us1.locationiq.com/v1/search.php?key=${locationIqApiKey}&q=${encodeURIComponent(selectedAddress)}&format=json`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.length > 0) {
                            const lat = data[0].lat;
                            const lon = data[0].lon;

                            // Remove the existing marker if there is one
                            if (marker) {
                                map.removeLayer(marker);
                            }

                            // Add a new marker
                            marker = L.marker([lat, lon]).addTo(map);
                            map.setView([lat, lon], 13); // Zoom to the marker

                            // Update the latitude and longitude in hidden fields
                            $('#latitude').val(lat);
                            $('#longitude').val(lon);
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching address:', error);
                    });

                $('#suggestions').empty().hide(); // Hide suggestions after selection
            });

            // Hide suggestions when clicking outside
            $(document).click(function(event) {
                if (!$(event.target).closest('#location').length) {
                    $('#suggestions').empty().hide();
                }
            });
        });
    </script>

</body>

</html>