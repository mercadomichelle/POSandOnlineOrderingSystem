<?php
// stock_notif.php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
include('../connection.php');

// Check if the branch ID is set in the AJAX request
if (isset($_GET['branchId'])) {
    $branchId = intval($_GET['branchId']);  // Get the branch ID from the request

    // Query to get stock data based on the branch ID
    $sql = "SELECT p.prod_id, p.prod_brand, p.prod_name, p.prod_image_path, 
                   COALESCE(SUM(s.stock_quantity), 0) AS stock_quantity 
            FROM products p 
            LEFT JOIN stocks s ON p.prod_id = s.prod_id
            WHERE s.branch_id = ? 
            GROUP BY p.prod_id, p.prod_brand, p.prod_name, p.prod_image_path
            ORDER BY stock_quantity ASC";

    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param("i", $branchId);  // Bind the dynamic branch ID
    $stmt->execute();
    $result = $stmt->get_result();

    $stocks = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['stock_quantity'] = max(0, $row['stock_quantity']);
            $row['is_low_stock'] = $row['stock_quantity'] > 0 && $row['stock_quantity'] < 10;
            $row['is_out_of_stock'] = $row['stock_quantity'] == 0;
            $stocks[] = $row;
        }
    }

    // Notifications for low stock and out of stock
    $lowStockNotifications = [];
    $outOfStockNotifications = [];

    foreach ($stocks as $stock) {
        if ($stock['is_low_stock']) {
            $lowStockNotifications[] = 'Low stock: ' . htmlspecialchars($stock['prod_name']);
        } elseif ($stock['is_out_of_stock']) {
            $outOfStockNotifications[] = 'Out of stock: ' . htmlspecialchars($stock['prod_name']);
        }
    }

    $notifications = array_merge($lowStockNotifications, $outOfStockNotifications);

    // Return the notifications as a JSON response
    echo json_encode($notifications);
} else {
    echo json_encode(["error" => "Branch ID not provided"]);
}

$mysqli->close();
?>
