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
$email = $_POST["email"];
$phone = $_POST["phone"];
$address = $_POST["address"];
$barangay = $_POST["barangay"];
$city = $_POST["city"];
$province = $_POST["province"];
$zip_code = $_POST["zip_code"];

if (!is_numeric($phone) || !is_numeric($zip_code)) {
    $_SESSION['errorMessage'] = "Phone number and zip code must be numeric.";
    header("Location: my_profile.php");
    exit();
}

if (strlen($phone) > 11 || strlen($zip_code) > 4) {
    $_SESSION['errorMessage'] = "Phone number or zip code length exceeded.";
    header("Location: my_profile.php");
    exit();
}

$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$sql = "SELECT * FROM profile WHERE username = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $sql = "UPDATE profile SET email=?, phone=?, address=?, barangay=?, city=?, province=?, zip_code=? WHERE username=?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ssssssss", $email, $phone, $address, $barangay, $city, $province, $zip_code, $username);
} else {
    $sql = "INSERT INTO profile (username, email, phone, address, barangay, city, province, zip_code) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("ssssssss", $username, $email, $phone, $address, $barangay, $city, $province, $zip_code);
}

if ($stmt->execute()) {
    $_SESSION['successMessage'] = "Profile updated successfully!";
} else {
    $_SESSION['errorMessage'] = "Error updating profile: " . $stmt->error;
}

$stmt->close();
$mysqli->close();

header("Location: my_profile.php");
?>
