<?php
session_start();
include('../../connection.php');

// Ensure branch ID is available in the session
if (!isset($_SESSION['selected_branch'])) {
    $_SESSION['errorMessage'] = "Branch ID not found. Please select a branch.";
    header("Location: staff_list.php");
    exit();
}

$branch_id = $_SESSION['selected_branch'];  // Use the selected branch ID from the session

if (isset($_POST['staff_id'], $_POST['page'])) {
    $staff_id = $_POST['staff_id'];
    $page = (int)$_POST['page'];  // Ensure page number is an integer

    // Begin transaction for consistency
    $mysqli->begin_transaction();

    try {
        // Fetch the login_id and branch_id for the staff from the login table
        $sql = "SELECT login.id AS login_id, login.branch_id FROM staff 
                JOIN login ON staff.login_id = login.id 
                WHERE staff.staff_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $staff_id);
        $stmt->execute();
        $stmt->bind_result($login_id, $staff_branch_id);
        $stmt->fetch();
        $stmt->close();

        if ($login_id) {
            // Ensure the staff belongs to the same branch as the logged-in user
            if ($staff_branch_id == $branch_id) {
                // Delete from staff table
                $sql = "DELETE FROM staff WHERE staff_id = ?";
                $stmt = $mysqli->prepare($sql);
                $stmt->bind_param("i", $staff_id);
                if ($stmt->execute()) {
                    // Delete from login table
                    $sql = "DELETE FROM login WHERE id = ?";
                    $stmt = $mysqli->prepare($sql);
                    $stmt->bind_param("i", $login_id);

                    if ($stmt->execute()) {
                        // Commit transaction
                        $mysqli->commit();
                        $_SESSION['successMessage'] = "Staff deleted successfully.";
                    } else {
                        throw new Exception("Failed to delete from login: " . $stmt->error);
                    }
                    $stmt->close();
                } else {
                    throw new Exception("Failed to delete staff: " . $stmt->error);
                }
            } else {
                $_SESSION['errorMessage'] = "You cannot delete a staff member from another branch.";
            }
        } else {
            $_SESSION['errorMessage'] = "No login_id found for staff_id: " . $staff_id;
        }
    } catch (Exception $e) {
        // Rollback transaction if any error occurs
        $mysqli->rollback();
        $_SESSION['errorMessage'] = $e->getMessage();
    }

    // Close the connection after the operation
    $mysqli->close();
} else {
    $_SESSION['errorMessage'] = "Invalid request. Staff ID or page is missing.";
}

// Redirect back to staff list page with proper error/success message
header("Location: staff_list.php?page=$page");
exit;
?>
