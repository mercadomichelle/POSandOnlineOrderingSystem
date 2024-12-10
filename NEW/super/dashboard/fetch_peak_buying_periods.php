<?php
// fetch_peak_buying_periods.php

session_start();
include('../../connection.php');

// Get branch_id from the request or session
$branch_id = isset($_GET['branch_id']) ? $_GET['branch_id'] : $_SESSION['branch_id']; // Get from GET if available, else from session

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

// SQL query to fetch peak buying periods, including branch filter
$sql = "SELECT ";
if ($month) {
    $sql .= "DATE_FORMAT(order_date, '%b %d') AS period, "; // Format like 'Month Day'
} elseif ($week && $month) {
    $sql .= "DATE(order_date) AS period, ";
} elseif ($year) {
    $sql .= "DATE_FORMAT(order_date, '%Y-%m') AS period, "; // Format like '2024-01'
} else {
    // If no month, week, or year is selected, show distinct years
    $sql .= "YEAR(order_date) AS period, ";
}

$sql .= "
        SUM(total_amount) AS total_sales
    FROM 
        orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    WHERE 
        (o.order_source = 'in-store' OR o.order_source = 'online')
        AND oi.branch_id = ? 
        $timeframe_condition
    GROUP BY ";

// Adjust grouping logic for different filters
if ($month) {
    $sql .= "DAY(order_date)"; // Group by day within the selected month
} elseif ($week && $month) {
    $sql .= "DATE(order_date)";
} elseif ($year) {
    $sql .= "YEAR(order_date), MONTH(order_date)";
} else {
    $sql .= "YEAR(order_date)"; // Default grouping by year
}

$sql .= " ORDER BY period ASC";

// Prepare and execute query with branch_id parameter
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $branch_id); // Bind branch_id to the query
$stmt->execute();
$result = $stmt->get_result();

$data = ['periods' => [], 'total_sales' => []];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $formattedPeriod = '';
        if ($week && $month) {
            // Show the full date (e.g., 'Weekday, Month Day')
            $formattedPeriod = date('D, M d', strtotime($row['period']));
        } elseif ($month) {
            // For monthly view, just day number (e.g., 'Month Day')
            $formattedPeriod = date('M d', strtotime($row['period'])); // Show 'Month Day' (e.g., 'Nov 1')
        } elseif ($year) {
            // For yearly view, show month abbreviation (e.g., 'Month')
            $formattedPeriod = date('M Y', strtotime($row['period']));
        } else {
            // For default (yearly), show the year only
            $formattedPeriod = $row['period'];
        }
        $data['periods'][] = $formattedPeriod;
        $data['total_sales'][] = $row['total_sales'];
    }
}

// Return data as JSON
echo json_encode($data);
$stmt->close();
$mysqli->close();
