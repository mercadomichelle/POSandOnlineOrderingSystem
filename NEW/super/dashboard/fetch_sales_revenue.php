<?php
// fetch_sales_revenue.php 

session_start();
include('../../connection.php');

// Assuming branch_id is stored in the session upon login
$branch_id = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : null;
if (!$branch_id) {
    // If branch_id is not set in session, exit or redirect the user
    echo json_encode(['error' => 'Branch ID not found.']);
    exit();
}

// Get filters from the request
$month = isset($_GET['month']) ? $_GET['month'] : '';
$week = isset($_GET['week']) ? $_GET['week'] : '';
$year = isset($_GET['year']) ? $_GET['year'] : '';
$timeframe_condition = '';

// Apply year filter
if ($year) {
    $timeframe_condition .= "AND YEAR(order_date) = $year ";
}

// Apply month filter
if ($month) {
    $timeframe_condition .= "AND MONTH(order_date) = $month ";
}

// Apply week filter (based on calendar weeks)
if ($week && $month && $year) {
    $total_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $first_day_of_month = strtotime("$year-$month-01");
    $first_weekday = date('w', $first_day_of_month); // 0 (Sunday) to 6 (Saturday)

    $weeks = [];
    $current_start_date = $first_day_of_month;
    $current_end_date = strtotime("+" . (6 - $first_weekday) . " days", $current_start_date); // First week's end

    while ($current_start_date <= strtotime("$year-$month-$total_days")) {
        $weeks[] = [
            'start' => $current_start_date,
            'end' => min($current_end_date, strtotime("$year-$month-$total_days"))
        ];

        $current_start_date = strtotime("+1 day", $current_end_date);
        $current_end_date = strtotime("+6 days", $current_start_date);
    }

    // Get the selected week's date range
    if (isset($weeks[$week - 1])) {
        $start_date = $weeks[$week - 1]['start'];
        $end_date = $weeks[$week - 1]['end'];
        $timeframe_condition .= "AND order_date BETWEEN '" . date('Y-m-d', $start_date) . "' AND '" . date('Y-m-d', $end_date) . "' ";
    }
}

// SQL query to fetch sales data by order source and type, including branch filter
$sql = "SELECT ";
if ($month) {
    $sql .= "DATE_FORMAT(order_date, '%b %d') AS period, "; // Format like 'Month Day'
} elseif ($week && $month) {
    $sql .= "DATE(order_date) AS period, ";
} elseif ($year) {
    $sql .= "DATE_FORMAT(order_date, '%b %Y') AS period, "; // Format like 'Jan 2024'
} else {
    $sql .= "YEAR(order_date) AS period, ";
}
$sql .= "
        order_source,
        order_type,
        SUM(total_amount) AS total_sales
    FROM 
        orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    WHERE 
        1=1 
        $timeframe_condition
        AND oi.branch_id = $branch_id
    GROUP BY ";

if ($month) {
    $sql .= "DAY(order_date), order_source, order_type"; // Group by day, source, and type
} elseif ($week && $month) {
    $sql .= "DATE(order_date), order_source, order_type"; // Group by date, source, and type
} elseif ($year) {
    $sql .= "YEAR(order_date), MONTH(order_date), order_source, order_type"; // Group by year, month, source, and type
} else {
    $sql .= "YEAR(order_date), order_source, order_type"; // Group by year, source, and type
}

$sql .= " ORDER BY YEAR(order_date) ASC, MONTH(order_date) ASC, period ASC"; // Ensure chronological order


// Execute query
$result = $mysqli->query($sql);

$data = [
    'periods' => [],
    'in_store_retail' => [],
    'in_store_wholesale' => [],
    'online_wholesale' => []
];
$salesByDate = [];

// Process the results
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $period = $row['period'];
        $orderSource = $row['order_source'];
        $orderType = $row['order_type'];

        // Initialize sales for each period if not already done
        if (!isset($salesByDate[$period])) {
            $salesByDate[$period] = [
                'in_store_retail' => 0,
                'in_store_wholesale' => 0,
                'online_wholesale' => 0
            ];
        }

        // Sum the sales for the respective combination
        if ($orderSource === 'in-store' && $orderType === 'retail') {
            $salesByDate[$period]['in_store_retail'] += $row['total_sales'];
        } elseif ($orderSource === 'in-store' && $orderType === 'wholesale') {
            $salesByDate[$period]['in_store_wholesale'] += $row['total_sales'];
        } elseif ($orderSource === 'online' && $orderType === 'wholesale') {
            $salesByDate[$period]['online_wholesale'] += $row['total_sales'];
        }
    }
}

// Populate the data array
foreach ($salesByDate as $period => $sales) {
    $data['periods'][] = $period;
    $data['in_store_retail'][] = $sales['in_store_retail'];
    $data['in_store_wholesale'][] = $sales['in_store_wholesale'];
    $data['online_wholesale'][] = $sales['online_wholesale'];
}

// Return data as JSON
echo json_encode($data);
$mysqli->close();
