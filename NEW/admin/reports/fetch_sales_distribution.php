<?php
session_start();

include('../../connection.php');

// Query to get purchase preferences
$query = "SELECT order_source, order_type, COUNT(*) AS order_count, SUM(total_amount) AS total_sales
          FROM orders
          GROUP BY order_source, order_type";

// Execute the query
$result = $mysqli->query($query);

// Check if the query was successful
if (!$result) {
    // Return error message as JSON
    echo json_encode(["error" => $mysqli->error]);
    exit;
}

// Fetch the data
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Output the data as JSON
echo json_encode($data);

// Close the connection
$mysqli->close();
?>
