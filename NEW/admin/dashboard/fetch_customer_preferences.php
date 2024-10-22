<?php
// Database connection
$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";
$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get the month parameter from the request (default to January if not provided)
$month = isset($_GET['month']) ? (int)$_GET['month'] : 1;

// Query to fetch daily sales and rice variety data for the given month
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
    GROUP BY 
        order_day, rice_variety, alternative_variety
    ORDER BY 
        order_day;
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $month);
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
$top_varieties = array_keys(array_slice($overall_variety_totals, 0, 3, true)); // Get the top 3 popular varieties

// Calculate percentages for each rice variety per day
$response = []; // Initialize the final response array

foreach ($rice_variety_totals as $day => $varieties) {
    $rice_percentages = []; // Reset for each day
    $alternatives = []; // Reset for alternatives

    foreach ($varieties as $variety => $quantity) {
        // Include only top varieties
        if (in_array($variety, $top_varieties)) {
            $percent = ($quantity / $daily_totals[$day]) * 100;
            $rice_percentages[$variety] = $percent;

            // Recommend alternatives for the top varieties
            $alternative_varieties = []; // Initialize an array for alternatives
            if (!empty($alternative)) {
                $alternative_varieties[] = $alternative; // Collect alternatives
            }
            $alternatives[$variety] = $alternative_varieties; // Store alternatives for the variety
        }
    }

    // Only include days where we have data for the top varieties
    if (!empty($rice_percentages)) {
        $response[] = [
            'day' => $day, // Add the day
            'percentages' => $rice_percentages, // Add the rice variety percentages
            'alternatives' => $alternatives // Add alternative rice varieties
        ];
    }
}

// Return the data as JSON
header('Content-Type: application/json');
echo json_encode($response, JSON_PRETTY_PRINT); // Pretty print for readability

// Close the connection
$mysqli->close();
?>
