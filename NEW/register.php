<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

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

    // Check if username already exists
    $check_user = $data->prepare("SELECT * FROM login WHERE username=?");
    $check_user->bind_param("s", $username);
    $check_user->execute();
    $result = $check_user->get_result();

    if ($result->num_rows > 0) {
        $_SESSION["message"] = "Username already taken";
    } else {
        // Insert user into database (with hashed password)
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $data->prepare("INSERT INTO login (username, password, usertype, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $hashed_password, $usertype, $first_name, $last_name);

        if ($stmt->execute()) {
            $_SESSION["message"] = "Registration successful!";
        } else {
            $_SESSION["message"] = "Error: Please try again. " . $stmt->error;
        }

        $stmt->close();
    }

    $check_user->close();
}

$data->close();
header("Location: ../login.php");
exit();
?>
