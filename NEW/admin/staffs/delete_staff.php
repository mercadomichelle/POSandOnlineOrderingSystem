<?php
session_start();
include('../../connection.php');

// Ensure branch ID is available in the session
$branch_id = $_SESSION['branch_id'];

if (isset($_POST['staff_id'])) {
    $staff_id = $_POST['staff_id'];
    $page = $_POST['page'];

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
        $mysqli->rollback();
        $_SESSION['errorMessage'] = $e->getMessage();
    }

    $mysqli->close();
} else {
    $_SESSION['errorMessage'] = "Invalid request.";
}

header("Location: staff_list.php?page=$page");
exit;
?>
