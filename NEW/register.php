<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$data = new mysqli($host, $user, $password, $db);

if ($data->connect_error) {
    die("Connection failed: " . $data->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    $first_name = trim($_POST["fname"]);
    $last_name = trim($_POST["lname"]);
    $usertype = 'customer';

    $check_user = $data->prepare("SELECT * FROM login WHERE username=?");
    $check_user->bind_param("s", $username);
    $check_user->execute();
    $result = $check_user->get_result();

    if ($result->num_rows > 0) {
        $_SESSION["message"] = "Username already taken";
    } elseif ($password !== $confirm_password) {
        $_SESSION["message"] = "Passwords do not match. Please try again.";
    } else {
        // Insert user into database (without hashing the password)
        $stmt = $data->prepare("INSERT INTO login (username, password, usertype, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $password, $usertype, $first_name, $last_name);

        if ($stmt->execute()) {
            $_SESSION["message"] = "Registration successful!";
        } else {
            $_SESSION["message"] = "Error: \n Please try again." . $stmt->error;
        }

        $stmt->close();
    }

    $check_user->close();
}

$data->close();
header("Location: ../login.php");
exit();
?>
