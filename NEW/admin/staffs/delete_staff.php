<?php
session_start();

$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

if (isset($_POST['staff_id'])) {
    $staff_id = $_POST['staff_id'];
    $page = $_POST['page'];

    $mysqli->begin_transaction();

    try {
        $sql = "SELECT login_id FROM staff WHERE staff_id = ?";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param("i", $staff_id);
        $stmt->execute();
        $stmt->bind_result($login_id);
        $stmt->fetch();
        $stmt->close();

        if ($login_id) {
            $sql = "DELETE FROM staff WHERE staff_id = ?";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param("i", $staff_id);
            if ($stmt->execute()) {
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
