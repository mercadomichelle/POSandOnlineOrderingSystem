<?php
// fetch_most_purchased_rice.php

session_start();
include('../../connection.php');

// Get the branch ID (this may be passed from session or directly from URL)
$branch_id = $_GET['branch_id'];

// Get the current month and year
$current_month = date('m'); // Current month in numeric format (01 to 12)
$current_year = date('Y'); // Current year in 4 digits (e.g., 2024)

// Get the month and year parameters from the URL or use default values
$month = isset($_GET['month']) ? $_GET['month'] : $current_month;
$year = isset($_GET['year']) ? $_GET['year'] : $current_year;

$week = isset($_GET['week']) ? $_GET['week'] : '';
$timeframe_condition = '';

// Apply year filter
if ($year) {
    $timeframe_condition .= "AND YEAR(o.order_date) = $year ";
}

// Apply month filter
if ($month) {
    $timeframe_condition .= "AND MONTH(o.order_date) = $month ";
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
        $timeframe_condition .= "AND o.order_date BETWEEN '" . date('Y-m-d', $start_date) . "' AND '" . date('Y-m-d', $end_date) . "' ";
    }
}

$sql = "
    SELECT p.prod_name, SUM(oi.quantity) AS total_quantity
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    JOIN products p ON oi.prod_id = p.prod_id
    WHERE oi.branch_id = ?
    AND o.order_source IN ('in-store', 'online') 
    $timeframe_condition
    GROUP BY p.prod_name
    ORDER BY total_quantity DESC
    LIMIT 5
";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $branch_id);
$stmt->execute();
$result = $stmt->get_result();

$data = ['riceVarieties' => [], 'quantities' => []];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data['riceVarieties'][] = $row['prod_name'];
        $data['quantities'][] = $row['total_quantity'];
    }
}

echo json_encode($data);
$mysqli->close();
?>
