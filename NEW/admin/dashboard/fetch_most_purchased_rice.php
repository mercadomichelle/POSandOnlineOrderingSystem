<?php
// fetch_most_purchased_rice.php

session_start();
include('../../connection.php');

// Get branch ID and timeframe filters
$branch_id = $_GET['branch_id'];
$current_month = date('m');
$current_year = date('Y');
$month = isset($_GET['month']) ? $_GET['month'] : $current_month;
$year = isset($_GET['year']) ? $_GET['year'] : $current_year;
$week = isset($_GET['week']) ? $_GET['week'] : '';
$timeframe_condition = '';

// Apply filters to timeframe
if ($year) $timeframe_condition .= "AND YEAR(o.order_date) = $year ";
if ($month) $timeframe_condition .= "AND MONTH(o.order_date) = $month ";
if ($week && $month && $year) {
    // Week calculation logic
    $total_days = cal_days_in_month(CAL_GREGORIAN, $month, $year);
    $first_day_of_month = strtotime("$year-$month-01");
    $first_weekday = date('w', $first_day_of_month);
    $weeks = [];
    $current_start_date = $first_day_of_month;
    $current_end_date = strtotime("+" . (6 - $first_weekday) . " days", $current_start_date);
    while ($current_start_date <= strtotime("$year-$month-$total_days")) {
        $weeks[] = [
            'start' => $current_start_date,
            'end' => min($current_end_date, strtotime("$year-$month-$total_days"))
        ];
        $current_start_date = strtotime("+1 day", $current_end_date);
        $current_end_date = strtotime("+6 days", $current_start_date);
    }
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

$data = ['riceVarieties' => [], 'quantities' => [], 'insights' => []];

if ($result->num_rows > 0) {
    $total_sales = 0; // To calculate the total sales for insights
    $riceVarieties = [];
    while ($row = $result->fetch_assoc()) {
        $data['riceVarieties'][] = $row['prod_name'];
        $data['quantities'][] = $row['total_quantity'];
        $total_sales += $row['total_quantity'];
        $riceVarieties[] = $row;
    }

    // Generate enhanced insights based on the fetched data
    foreach ($riceVarieties as $key => $variety) {
        $prod_name = $variety['prod_name'];
        $quantity = $variety['total_quantity'];
        $percentage = round(($quantity / $total_sales) * 100, 2);

        if ($key === 0) {
            $data['insights'][] = "Top seller: '$prod_name' with $quantity units, contributing $percentage% of total sales.";
        } else {
            $data['insights'][] = "'$prod_name' sold $quantity units, contributing $percentage% of total sales.";
        }
    }

    // Suggest a promotional action if sales are concentrated or evenly distributed
    if ($riceVarieties[0]['total_quantity'] > $total_sales * 0.5) {
        $data['insights'][] = "Consider highlighting '$riceVarieties[0]['prod_name']' in promotions as it dominates sales.";
    } else {
        $data['insights'][] = "Sales are more evenly distributed; focus on promoting variety to boost overall revenue.";
    }

    $data['insights'][] = "Total rice sales: $total_sales units.";
} else {
    $data['insights'][] = "No sales data for this period.";
}

echo json_encode($data);
$mysqli->close();
?>
