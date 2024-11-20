<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('connection.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);
    $confirm_password = trim($_POST["confirm_password"]);
    $first_name = trim($_POST["fname"]);
    $last_name = trim($_POST["lname"]);
    $usertype = 'customer';

    // Password validation regex patterns
    $password_requirements = [
        'uppercase' => '/[A-Z]/',  // at least one uppercase letter
        'lowercase' => '/[a-z]/',  // at least one lowercase letter
        'special' => '/[!@#$%^&*(),.?":{}|<>]/',  // at least one special character
        'number' => '/[0-9]/',  // at least one number
        'eightmin' => '/.{8,}/',  // minimum 8 characters
    ];

    // Check if password and confirm password match
    if ($password !== $confirm_password) {
        $_SESSION["message"] = "Passwords do not match. Please try again.";
    } else {
        // Check password against requirements
        $password_valid = true;
        foreach ($password_requirements as $key => $regex) {
            if (!preg_match($regex, $password)) {
                $password_valid = false;
                $_SESSION["message"] = "Password must contain at least one " . ucfirst($key) . ".";
                break;
            }
        }

        // If password doesn't meet requirements, don't proceed
        if (!$password_valid) {
            header("Location: login.php");  // Redirect to registration page
            exit();
        }

        // Check if username already exists
        $check_user = $mysqli->prepare("SELECT * FROM login WHERE username=?");
        $check_user->bind_param("s", $username);
        $check_user->execute();
        $result = $check_user->get_result();

        if ($result->num_rows > 0) {
            $_SESSION["message"] = "Username already taken";
        } else {
            // Insert user into database (with hashed password)
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $mysqli->prepare("INSERT INTO login (username, password, usertype, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
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

    $mysqli->close();
    header("Location: ../login.php");  
    exit();
}
?>
