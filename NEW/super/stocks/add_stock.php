<?php
session_start();
include('../../connection.php');

// Check if the branch_id is set in the session
if (!isset($_SESSION['selected_branch']) || empty($_SESSION['selected_branch'])) {
    $_SESSION['errorMessage'] = "Branch ID is not set!";
    header("Location: stocks.php");
    exit();
}

$branch_id = $_SESSION['selected_branch'];

// Check if data was sent through POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $prod_id = $_POST['prod_id'];
    $quantity = $_POST['stock_quantity'];

    // Check if the branch exists in the branches table
    $sql = "SELECT * FROM branches WHERE branch_id = ?";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $branch_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if (!$result) {
        die('Error executing query: ' . $mysqli->error);
    }

    if ($result->num_rows == 0) {
        $_SESSION['errorMessage'] = "Branch ID does not exist! Branch ID: " . $branch_id;
        header("Location: stocks.php");
        exit();
    }

    // Check if the product already exists for the selected branch
    $sql_check = "SELECT * FROM stocks WHERE prod_id = ? AND branch_id = ?";
    $stmt_check = $mysqli->prepare($sql_check);
    $stmt_check->bind_param("ii", $prod_id, $branch_id);
    $stmt_check->execute();
    $check_result = $stmt_check->get_result();

    if ($check_result->num_rows > 0) {
        // Product already exists, update the stock quantity
        $sql_update = "UPDATE stocks SET stock_quantity = stock_quantity + ? WHERE prod_id = ? AND branch_id = ?";
        $stmt_update = $mysqli->prepare($sql_update);
        $stmt_update->bind_param("iii", $quantity, $prod_id, $branch_id);
        $stmt_update->execute();

        if ($stmt_update->affected_rows > 0) {
            $_SESSION['successMessage'] = "Stock quantity updated successfully!";
        } else {
            $_SESSION['errorMessage'] = "Error: Unable to update stock quantity.";
        }
        $stmt_update->close();
    } else {
        // Product doesn't exist, insert new stock record
        $sql_insert = "INSERT INTO stocks (prod_id, branch_id, stock_quantity) VALUES (?, ?, ?)";
        $stmt_insert = $mysqli->prepare($sql_insert);
        $stmt_insert->bind_param("iii", $prod_id, $branch_id, $quantity);
        $stmt_insert->execute();

        if ($stmt_insert->affected_rows > 0) {
            $_SESSION['successMessage'] = "Stock added successfully!";
        } else {
            $_SESSION['errorMessage'] = "Error: Unable to add stock.";
        }
        $stmt_insert->close();
    }

    $stmt_check->close();
    $mysqli->close();

    // Redirect to stocks page without passing branch_id in the URL
    header("Location: stocks.php");
    exit();
}
?>
