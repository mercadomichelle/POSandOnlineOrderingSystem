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

// Get the timeframe from the request
$timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : 'weekly';

// Function to generate all dates between a start and end date
function generateDateRange($startDate, $endDate, $interval = 'P1D', $format = 'Y-m-d')
{
    $period = new DatePeriod(
        new DateTime($startDate),
        new DateInterval($interval),
        (new DateTime($endDate))->modify('+1 day')
    );

    $dates = [];
    foreach ($period as $date) {
        $dates[] = $date->format($format);
    }

    return $dates;
}

// Define the start and end date
if ($timeframe === 'monthly') {
    $startDate = date('Y-m-01', strtotime('-11 months'));
    $endDate = date('Y-m-t'); // End of the current month
    $interval = 'P1M'; // Monthly interval
    $format = 'Y-m'; // Format for month
} else {
    $startDate = date('Y-m-d', strtotime('-6 days')); // 6 days ago
    $endDate = date('Y-m-d'); // Current date
    $interval = 'P1D'; // Daily interval
    $format = 'Y-m-d'; // Format for day
}

// Generate all dates (weekly or monthly)
$allDates = generateDateRange($startDate, $endDate, $interval, $format);

// SQL query for fetching sales revenue based on timeframe
if ($timeframe === 'monthly') {
    $sql = "
        SELECT 
            DISTINCT DATE_FORMAT(order_date, '%Y-%m') AS period,
            order_type,
            SUM(total_amount) AS total_sales
        FROM 
            orders
        WHERE 
            order_date >= '$startDate'
            AND order_date <= '$endDate'
        GROUP BY 
            period, order_type
        ORDER BY 
            period ASC
    ";
} else {
    $sql = "
    SELECT 
        DATE(order_date) AS period,
        order_type,
        SUM(total_amount) AS total_sales
    FROM 
        orders
    WHERE 
        order_date >= CURDATE() - INTERVAL 6 DAY 
        AND order_date <= '$endDate 23:59:59' 
    GROUP BY 
        DATE(order_date), order_type
    ORDER BY 
        DATE(order_date) ASC
";
}


$result = $mysqli->query($sql);

// Initialize arrays to hold sales data for each type
$data = ['periods' => [], 'retail_sales' => [], 'wholesale_sales' => []];
$salesByDate = [];

// Process the results
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $period = $row['period'];
        $orderType = $row['order_type'];
        
        // Initialize sales for each period if not already done
        if (!isset($salesByDate[$period])) {
            $salesByDate[$period] = ['retail' => 0, 'wholesale' => 0];
        }

        // Sum the sales for the respective order type
        if ($orderType === 'retail') {
            $salesByDate[$period]['retail'] += $row['total_sales'];
        } elseif ($orderType === 'wholesale') {
            $salesByDate[$period]['wholesale'] += $row['total_sales'];
        }
    }
}

// Populate the data array
foreach ($allDates as $date) {
    if ($timeframe === 'monthly') {
        $data['periods'][] = date('M Y', strtotime($date)); // Format as 'Mon YYYY'
    } else {
        $data['periods'][] = date('D, M d', strtotime($date)); // Weekly format
    }
    
    // Set sales for each type or 0 if not present
    $data['retail_sales'][] = isset($salesByDate[$date]['retail']) ? $salesByDate[$date]['retail'] : 0; 
    $data['wholesale_sales'][] = isset($salesByDate[$date]['wholesale']) ? $salesByDate[$date]['wholesale'] : 0; 
}

echo json_encode($data);

$mysqli->close();
