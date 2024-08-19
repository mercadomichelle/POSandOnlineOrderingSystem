<?php
// $host = "localhost";
// $user = "root";
// $password = "";
// $db = "system_db";

// session_start();

// if (!isset($_SESSION["username"])) {
//     header("Location: ../login.php");
//     exit();
// }

// $mysqli = new mysqli($host, $user, $password, $db);

// if ($mysqli->connect_error) {
//     die("Connection failed: " . $mysqli->connect_error);
// }

// if (isset($_GET['id'])) {
//     $id = $_GET['id'];
//     $sql = "SELECT login.id, login.first_name, login.last_name, login.username, staff.staff_id, staff.phone_number, staff.email_address 
//             FROM login 
//             JOIN staff 
//             ON login.id = staff.login_id 
//             WHERE staff.staff_id = ?";
//     $stmt = $mysqli->prepare($sql);
//     $stmt->bind_param("i", $id);
//     $stmt->execute();
//     $result = $stmt->get_result();

//     if ($result->num_rows === 1) {
//         $staff = $result->fetch_assoc();
//         echo json_encode($staff);
//     }

//     $stmt->close();
// }

// $mysqli->close();
// exit();
?>
