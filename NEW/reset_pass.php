<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

session_start();
$data = new mysqli($host, $user, $password, $db);

if ($data->connect_error) {
    die("Connection failed: " . $data->connect_error);
}

$errorMessage = "";
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = $_POST['token'];
    $newPassword = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm_password']);

    if ($newPassword !== $confirmPassword) {
        $errorMessage = "Passwords do not match.";
    } else {
        $sql = "SELECT * FROM login WHERE reset_token=? AND reset_expiration > ?";
        $stmt = $data->prepare($sql);
        $current_time = time();
        $stmt->bind_param("si", $token, $current_time);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE login SET password=?, reset_token=NULL, reset_expiration=NULL WHERE reset_token=?";
            $stmt = $data->prepare($sql);
            $stmt->bind_param("ss", $hashedPassword, $token);
            $stmt->execute();

            $successMessage = "Your password has been reset successfully.";
        } else {
            $errorMessage = "Invalid or expired token.";
        }

        $stmt->close();
    }
}

$data->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
</head>

<body>
    <h2>Reset Password</h2>
    <form action="reset_password.php" method="POST">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
        <label>New Password:</label>
        <input type="password" name="password" required>
        <label>Confirm New Password:</label>
        <input type="password" name="confirm_password" required>
        <button type="submit">Reset Password</button>
    </form>

    <?php if (!empty($errorMessage)) {
        echo "<p style='color: red;'>$errorMessage</p>";
    } ?>
    <?php if (!empty($successMessage)) {
        echo "<p style='color: green;'>$successMessage</p>";
    } ?>
</body>

</html>