<?php

session_start();

$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

if (!isset($_SESSION["username"])) {
    header("Location: ../../login.php");
    exit();
}

$username = $_SESSION["username"];
$email = $_POST["email"];
$phone = $_POST["phone"];
$address = $_POST["address"];
$zip_code = $_POST["zip_code"];
$latitude = $_POST["latitude"] ?? null;
$longitude = $_POST["longitude"] ?? null;


// Validation for phone and zip code
if (!is_numeric($phone) || !is_numeric($zip_code)) {
    $_SESSION['errorMessage'] = "Phone number and zip code must be numeric.";
    header("Location: ../my_profile.php");
    exit();
}

if (strlen($phone) > 11 || strlen($zip_code) > 4) {
    $_SESSION['errorMessage'] = "Phone number or zip code length exceeded.";
    header("Location: ../my_profile.php");
    exit();
}

// Ensure latitude and longitude are numeric
if (!is_numeric($latitude) || !is_numeric($longitude)) {
    $_SESSION['errorMessage'] = "Invalid coordinates.";
    header("Location: ../my_profile.php");
    exit();
}

// After you have latitude and longitude values
if ($latitude && $longitude) {
    // LocationIQ API URL
    $apiKey = 'pk.874b7e8302271991d4120988fae87225'; 
    $apiUrl = "https://us1.locationiq.com/v1/reverse.php?key={$apiKey}&lat={$latitude}&lon={$longitude}&format=json";

    // Make the API request
    $response = file_get_contents($apiUrl);
    
    if ($response !== false) {
        // Decode the JSON response
        $locationData = json_decode($response, true);
        
        // Extract city name
        if (isset($locationData['address']['city'])) {
            $city = $locationData['address']['city'];
        } elseif (isset($locationData['address']['town'])) {
            // Fallback in case 'city' is not set
            $city = $locationData['address']['town'];
        } elseif (isset($locationData['address']['village'])) {
            // Fallback for villages
            $city = $locationData['address']['village'];
        } else {
            $city = 'Unknown'; // Default value if city is not found
        }
    } else {
        // Handle API request error
        $_SESSION['errorMessage'] = "Error retrieving location data.";
        header("Location: ../my_profile.php");
        exit();
    }
} else {
    $city = 'No location provided'; // Handle case with no coordinates
}

// Now you can use the $city variable as needed
// Example: Store city in the session or update in the database
$_SESSION['city'] = $city;

// Create database connection
$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Check if profile exists
$sql = "SELECT * FROM profile WHERE username = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    // Update existing profile
    $sql = "UPDATE profile SET email=?, phone=?, address=?, zip_code=?, latitude=?, longitude=?, city=? WHERE username=?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ssssssss", $email, $phone, $address, $zip_code, $latitude, $longitude, $city, $username);
} else {
    // Insert new profile
    $sql = "INSERT INTO profile (username, email, phone, address, zip_code, city) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ssssss", $username, $email, $phone, $address, $zip_code, $city);
}




// Execute the query and handle success/error
if ($stmt->execute()) {
    $_SESSION['successMessage'] = "Profile updated successfully!";
    $_SESSION['city'] = $city; 
} else {
    $_SESSION['errorMessage'] = "Error updating profile: " . $stmt->error;
}

$stmt->close();
$mysqli->close();

// Redirect back to the profile page
header("Location: ../my_profile.php");
exit();

?>
