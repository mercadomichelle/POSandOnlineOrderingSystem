<?php
session_start();

$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

$data = new mysqli($host, $user, $password, $db);

if ($data->connect_error) {
    die("Connection failed: " . $data->connect_error);
}

$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);

    // SQL query to find the user based on email
    $sql = "SELECT login.id FROM login 
            INNER JOIN staff ON login.id = staff.login_id 
            WHERE staff.email_address = ?";

    $stmt = $data->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $resetToken = bin2hex(random_bytes(32));
            $updateTokenSql = "UPDATE login SET reset_token=? WHERE id=?";
            $updateStmt = $data->prepare($updateTokenSql);
            $updateStmt->bind_param("si", $resetToken, $user['id']);
            $updateStmt->execute();

            $resetLink = "http://yourwebsite.com/reset_password.php?token=" . $resetToken;

            // Email settings
            $to = $email;
            $subject = "Password Reset Request";
            $message = "Click the following link to reset your password: <a href='" . $resetLink . "'>" . $resetLink . "</a>";
            $headers = "From: your-email@gmail.com\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html\r\n";

            if (mail($to, $subject, $message, $headers)) {
                echo "A password reset link has been sent to your email address.";
            } else {
                echo "Failed to send the reset email. Please try again later.";
            }
        } else {
            echo "No account found with that email address.";
        }

        $stmt->close();
    } else {
        $errorMessage = "Failed to prepare statement.";
    }
}

$data->close();
?>
