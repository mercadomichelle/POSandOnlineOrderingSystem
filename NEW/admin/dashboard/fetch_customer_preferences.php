<?php
// fetch_customer_preferences.php

session_start();
include('../../connection.php');

// Get the month, week, year, and branch parameters from the request
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m'); // Default to current month
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y'); // Default to current year
$branch_id = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : $_SESSION["branch_id"]; // Get branch_id from the request or session

// SQL query to fetch daily sales and rice variety data for the given month, year, and branch
$sql =
    "
    SELECT 
        p1.prod_name AS rice_variety,
        p2.prod_name AS alternative_variety,
        DATE(o.order_date) AS order_day,
        SUM(oi.quantity) AS total_quantity
    FROM 
        orders o
    JOIN 
        order_items oi ON o.order_id = oi.order_id
    JOIN 
        products p1 ON oi.prod_id = p1.prod_id
    LEFT JOIN 
        alternative_varieties av ON p1.prod_id = av.product_id
    LEFT JOIN 
        products p2 ON av.alternative_product_id = p2.prod_id
    WHERE 
        MONTH(o.order_date) = ? 
        AND YEAR(o.order_date) = ? 
        AND oi.branch_id = ?
    GROUP BY 
        DATE(o.order_date), p1.prod_name, p2.prod_name
";

// Prepare and execute the query
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("iii", $month, $year, $branch_id); // Bind month, year, and branch_id parameters
$stmt->execute();
$result = $stmt->get_result();

// Initialize arrays for storing daily totals and rice varieties
$daily_totals = [];
$rice_variety_totals = [];
$overall_variety_totals = [];

// Fetch daily sales from the database
while ($row = $result->fetch_assoc()) {
    $day = $row['order_day'];
    $variety = $row['rice_variety'];
    $alternative = $row['alternative_variety'];
    $quantity = $row['total_quantity'];

    // Update total quantity for the day
    if (!isset($daily_totals[$day])) {
        $daily_totals[$day] = 0;
    }
    $daily_totals[$day] += $quantity;

    // Update quantity for the specific rice variety on that day
    if (!isset($rice_variety_totals[$day][$variety])) {
        $rice_variety_totals[$day][$variety] = 0;
    }
    $rice_variety_totals[$day][$variety] += $quantity;

    // Track overall totals for each variety
    if (!isset($overall_variety_totals[$variety])) {
        $overall_variety_totals[$variety] = 0;
    }
    $overall_variety_totals[$variety] += $quantity;
}

// Identify top three rice varieties based on overall popularity
arsort($overall_variety_totals); // Sort to find the most popular
$top_varieties = array_keys(array_slice($overall_variety_totals, 0, 5, true)); // Get the top 3 popular varieties

// Calculate percentages for each rice variety per day
$response = []; // Initialize the final response array  

foreach ($rice_variety_totals as $day => $varieties) {
    $rice_percentages = []; // Reset for each day  
    $alternatives = []; // Array to hold alternative varieties for this day  
    $totalForDay = $daily_totals[$day]; // Total quantity for the day  

    // First, calculate percentages for each top rice variety  
    foreach ($top_varieties as $variety) {
        $quantity = $varieties[$variety] ?? 0; // Use null coalescing operator  
        $percent = ($quantity / $totalForDay) * 100;
        $rice_percentages[$variety] = $percent;
    }

    $alternativeQuantity = 0;
    foreach ($varieties as $variety => $quantity) {
        if (!in_array($variety, $top_varieties)) {
            $alternativeQuantity += $quantity;
            $alternatives[] = $variety; // Add to alternatives array
        }
    }

    $alternativePercent = ($alternativeQuantity / $totalForDay) * 100;
    $rice_percentages["Alternative Rice"] = $alternativePercent;

    $response[] = [
        'day' => $day,
        'percentages' => $rice_percentages,
        'alternatives' => $alternatives
    ];
}

// Return the data as JSON
header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT); // Pretty print for readability

// Close the connection
$mysqli->close();
