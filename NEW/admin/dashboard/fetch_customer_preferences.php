<?php
// fetch_customer_preferences.php

session_start();
include('../../connection.php');

// Get the month, year, and branch_id parameters from the request
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('m'); // Default to current month
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');    // Default to current year
$branch_id = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : $_SESSION["branch_id"]; // Use session if not provided

// SQL query to fetch daily sales and rice variety data
$sql = "
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
$stmt->bind_param("iii", $month, $year, $branch_id); // Bind parameters
$stmt->execute();
$result = $stmt->get_result();

// Initialize arrays for storing data
$daily_totals = [];
$rice_variety_totals = [];
$overall_variety_totals = [];

// Process the result set
while ($row = $result->fetch_assoc()) {
    $day = $row['order_day'];
    $variety = $row['rice_variety'];
    $alternative = $row['alternative_variety'];
    $quantity = $row['total_quantity'];

    // Track total quantity for the day
    $daily_totals[$day] = ($daily_totals[$day] ?? 0) + $quantity;

    // Track quantity for each rice variety per day
    $rice_variety_totals[$day][$variety] = ($rice_variety_totals[$day][$variety] ?? 0) + $quantity;

    // Track overall totals for each rice variety
    $overall_variety_totals[$variety] = ($overall_variety_totals[$variety] ?? 0) + $quantity;

    // Store alternative varieties as suggestions
    if ($alternative && !isset($rice_variety_totals[$day][$alternative])) {
        $rice_variety_totals[$day]["alternative_suggestions"][] = $alternative;
    }
}

// Identify top five rice varieties based on total popularity
arsort($overall_variety_totals); // Sort in descending order
$top_varieties = array_keys(array_slice($overall_variety_totals, 0, 5, true)); // Get top 5 varieties

// Prepare the response
$response = [];

foreach ($rice_variety_totals as $day => $varieties) {
    $rice_quantities = [];
    $alternatives = [];
    $totalForDay = $daily_totals[$day];

    // Collect quantities for top rice varieties
    foreach ($top_varieties as $variety) {
        $quantity = $varieties[$variety] ?? 0;
        $rice_quantities[$variety] = $quantity;
    }

    // Collect alternative rice varieties that are not in the day's sales
    $alternatives = $varieties["alternative_suggestions"] ?? [];

    // Collect total quantities for rice varieties not in the top varieties
    $alternativeQuantity = 0;
    foreach ($varieties as $variety => $quantity) {
        if (!in_array($variety, $top_varieties) && $variety !== "alternative_suggestions") {
            $alternativeQuantity += $quantity;
        }
    }

    $rice_quantities["Alternative Rice"] = $alternativeQuantity;

    // Add data for this day
    $response[] = [
        'day' => $day,
        'quantities' => $rice_quantities,
        'alternatives' => $alternatives,
        'insights' => generateInsights($day, $totalForDay, $varieties, $alternativeQuantity) // Add insights for each day
    ];
}

// Generate insights for each day
function generateInsights($day, $totalForDay, $varieties, $alternativeQuantity) {
    if ($totalForDay > 100) {
        return "On {$day}, there was a spike in rice sales.";
    }
    foreach ($varieties as $variety => $quantity) {
        if ($quantity > 0 && $variety !== "alternative_suggestions") {
            return "{$variety} was popular on {$day} with {$quantity} units sold.";
        }
    }
    if ($alternativeQuantity > 0) {
        return "On {$day}, alternative rice varieties saw notable interest with a total of {$alternativeQuantity} units sold.";
    }
    return "No significant insights for {$day}.";
}


// Return the JSON response
header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT);

// Close the connection
$mysqli->close();
?>
