<?php
session_start();
include('../../connection.php');

// Get the logged-in user's branch ID from the session
$branch_id = $_SESSION['branch_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $prod_id = $_POST['prod_id'];
    $quantity = $_POST['stock_quantity'];

    // Check if the branch exists in the branches table
    $sql = "SELECT * FROM branches WHERE branch_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $branch_id);  // Use session's branch_id here
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Branch does not exist
        $_SESSION['errorMessage'] = "Branch ID does not exist!";
        header("Location: stocks.php");
        exit();
    }

    // Insert new stock record directly without checking if it exists
    $sql = "INSERT INTO stocks (prod_id, branch_id, stock_quantity) VALUES (?, ?, ?)";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("iii", $prod_id, $branch_id, $quantity);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['successMessage'] = "Stock added successfully!";
    } else {
        $_SESSION['errorMessage'] = "Error: Unable to add stock.";
    }

    $stmt->close();
    $mysqli->close();

    // Redirect to stocks page without passing branch_id in the URL
    header("Location: stocks.php");
    exit();
}
?>
