<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

session_start();

if (!isset($_SESSION["username"])) {
    header("Location: ../../login.php");
    exit();
}

$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $staff_id = $_POST['staff_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $username = $_POST['username'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $page = $_POST['page'];

    if (!is_numeric($phone)) {
        $_SESSION['errorMessage'] = "Phone number can only contain numeric characters.";
        $_SESSION['formData'] = $_POST;
        header("Location: staff_list.php?page=$page&edit_id=$staff_id");
        exit();
    } elseif (strlen($phone) !== 11) {
        $_SESSION['errorMessage'] = "Phone number must be exactly 11 digits.";
        $_SESSION['formData'] = $_POST;
        header("Location: staff_list.php?page=$page&edit_id=$staff_id");
        exit();

    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['errorMessage'] = "Invalid email format.";
        $_SESSION['formData'] = $_POST;
        header("Location: staff_list.php?page=$page&edit_id=$staff_id");
        exit();
    }
    
    $current_sql = "SELECT login.first_name, login.last_name, login.username, staff.phone_number, staff.email_address 
                    FROM login 
                    JOIN staff ON login.id = staff.login_id 
                    WHERE staff.staff_id = ?";
    $current_stmt = $mysqli->prepare($current_sql);
    $current_stmt->bind_param("i", $staff_id);
    $current_stmt->execute();
    $current_result = $current_stmt->get_result();
    $current_data = $current_result->fetch_assoc();

    if ($current_data['first_name'] == $first_name &&
        $current_data['last_name'] == $last_name &&
        $current_data['username'] == $username &&
        $current_data['phone_number'] == $phone &&
        $current_data['email_address'] == $email) {
        $_SESSION["errorMessage"] = "No changes have been made.";
        $_SESSION['formData'] = $_POST;
        header("Location: staff_list.php?page=$page&edit_id=$staff_id");
        exit();
    }

    $check_sql = "SELECT * FROM login WHERE username = ? AND id != (SELECT login_id FROM staff WHERE staff_id = ?)";
    $check_stmt = $mysqli->prepare($check_sql);
    $check_stmt->bind_param("si", $username, $staff_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $_SESSION["errorMessage"] = "Username already exists.";
        $_SESSION['formData'] = $_POST;
        header("Location: staff_list.php?page=$page&edit_id=$staff_id");
        exit();
    } else {
        $update_sql = "UPDATE login 
                        JOIN staff ON login.id = staff.login_id 
                        SET login.first_name = ?, login.last_name = ?, login.username = ?, 
                            staff.name = CONCAT(?, ' ', ?), staff.phone_number = ?, staff.email_address = ?
                        WHERE staff.staff_id = ?";
        $update_stmt = $mysqli->prepare($update_sql);

        if ($update_stmt) {
            $update_stmt->bind_param("sssssssi", $first_name, $last_name, $username, $first_name, $last_name, $phone, $email, $staff_id);

            if ($update_stmt->execute()) {
                $_SESSION["successMessage"] = "Staff information updated successfully.";
            } else {
                $_SESSION["errorMessage"] = "Error updating staff information: " . $mysqli->error;
            }
            $update_stmt->close();
        } else {
            $_SESSION["errorMessage"] = "Error preparing update statement: " . $mysqli->error;
        }
    }

    $check_stmt->close();
}

$mysqli->close();

header("Location: staff_list.php?page=$page");
exit();
?>
