<?php
include('connection.php');

// Check if 'username' parameter is passed via GET
if (isset($_GET['username'])) {
    $username = trim($_GET['username']);

    // Prepare the query to check if the username exists
    $check_user = $mysqli->prepare("SELECT * FROM login WHERE username=?");
    $check_user->bind_param("s", $username);
    $check_user->execute();
    $result = $check_user->get_result();

    // Return a JSON response with the result
    if ($result->num_rows > 0) {
        echo json_encode(['exists' => true]);  // Username is already taken
    } else {
        echo json_encode(['exists' => false]);  // Username is available
    }

    $check_user->close();
}

$mysqli->close();
?>
