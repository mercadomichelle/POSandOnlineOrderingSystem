<?php
session_start();
include('../../connection.php');

// Ensure branch ID is selected and stored in the session
if (!isset($_SESSION['selected_branch'])) {
    $_SESSION['errorMessage'] = "Branch ID not found. Please select a branch.";
    header("Location: staff_list.php");
    exit();
}

// Get the selected branch ID from the session
$branch_id = $_SESSION['selected_branch'];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $usertype = trim($_POST['usertype']);

    // Save input data for reuse if there's an error
    $_SESSION['formData'] = [
        'first_name' => $first_name,
        'last_name' => $last_name,
        'username' => $username,
        'phone' => $phone,
        'email' => $email,
        'usertype' => $usertype,
    ];

    // Input Validation
    if (strlen($phone) !== 11 || !is_numeric($phone)) {
        $_SESSION['errorMessage'] = "Phone number must be 11 digits long and numeric.";
        header("Location: staff_list.php");
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['errorMessage'] = "Invalid email format.";
        header("Location: staff_list.php");
        exit();
    }

    if (empty($first_name) || empty($last_name) || empty($username) || empty($password) || empty($usertype)) {
        $_SESSION['errorMessage'] = "All fields are required.";
        header("Location: staff_list.php");
        exit();
    }

    // Check if username already exists
    $check_user = $mysqli->prepare("SELECT * FROM login WHERE username = ?");
    $check_user->bind_param("s", $username);
    $check_user->execute();
    $result = $check_user->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['errorMessage'] = "Username already taken.";
        header("Location: staff_list.php");
        exit();
    }

    // Begin transaction
    $mysqli->begin_transaction();

    try {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        // Insert into login table with the selected branch_id
        $stmt = $mysqli->prepare("INSERT INTO login (first_name, last_name, username, password, usertype, branch_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssi", $first_name, $last_name, $username, $hashed_password, $usertype, $branch_id);

        if ($stmt->execute()) {
            $login_id = $stmt->insert_id;
            $full_name = $first_name . ' ' . $last_name;

            // Insert into staff table
            $stmt2 = $mysqli->prepare("INSERT INTO staff (login_id, name, phone_number, email_address, usertype) VALUES (?, ?, ?, ?, ?)");
            $stmt2->bind_param("issss", $login_id, $full_name, $phone, $email, $usertype);

            if ($stmt2->execute()) {
                // Calculate total pages for pagination (assuming 10 items per page)
                $limit = 10;
                $result_count = $mysqli->query("SELECT COUNT(*) AS count FROM staff");
                $total_items = $result_count->fetch_assoc()['count'];
                $total_pages = ceil($total_items / $limit);

                $current_page = $total_pages;

                // Commit transaction
                $mysqli->commit();
                $_SESSION['successMessage'] = "New staff added successfully!";
                unset($_SESSION['formData']);
            } else {
                throw new Exception("Failed to add staff details.");
            }
        } else {
            throw new Exception("Failed to add login details.");
        }
    } catch (Exception $e) {
        $mysqli->rollback();
        $_SESSION['errorMessage'] = $e->getMessage();
    }

    $stmt->close();
    $stmt2->close();
    $check_user->close();
    $mysqli->close();

    header("Location: staff_list.php?page=" . $current_page);
    exit();
}

?>
