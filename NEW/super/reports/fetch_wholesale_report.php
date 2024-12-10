<?php
session_start();
include('../../connection.php');

// Fetch input parameters
$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : null;
$selectedWeek = isset($_GET['week']) ? intval($_GET['week']) : null;
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : null;
$timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : 'yearly'; // Default to yearly if no timeframe is provided

// Generate date range for the selected timeframe
function generateDateRange($startDate, $endDate, $interval = 'P1D', $format = 'Y-m-d') {
    $period = new DatePeriod(new DateTime($startDate), new DateInterval($interval), (new DateTime($endDate))->modify('+1 day'));
    $dates = [];
    foreach ($period as $date) {
        $dates[] = $date->format($format);
    }
    return $dates;
}

// Combine conditions dynamically
$whereConditions = [];
if ($selectedYear) {
    $whereConditions[] = "YEAR(o.order_date) = $selectedYear";
}
if ($selectedMonth) {
    $whereConditions[] = "MONTH(o.order_date) = $selectedMonth";
}
if ($selectedWeek && $selectedMonth && $selectedYear) {
    $totalDays = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
    $firstDayOfMonth = strtotime("$selectedYear-$selectedMonth-01");
    $firstWeekday = date('w', $firstDayOfMonth);

    $weeks = [];
    $currentStartDate = $firstDayOfMonth;
    $currentEndDate = strtotime("+" . (6 - $firstWeekday) . " days", $currentStartDate);

    while ($currentStartDate <= strtotime("$selectedYear-$selectedMonth-$totalDays")) {
        $weeks[] = [
            'start' => $currentStartDate,
            'end' => min($currentEndDate, strtotime("$selectedYear-$selectedMonth-$totalDays"))
        ];

        $currentStartDate = strtotime("+1 day", $currentEndDate);
        $currentEndDate = strtotime("+6 days", $currentStartDate);
    }

    if (isset($weeks[$selectedWeek - 1])) {
        $startOfWeek = $weeks[$selectedWeek - 1]['start'];
        $endOfWeek = $weeks[$selectedWeek - 1]['end'];
        $whereConditions[] = "o.order_date BETWEEN '" . date('Y-m-d', $startOfWeek) . "' AND '" . date('Y-m-d', $endOfWeek) . "'";
    }
}

// Add branch condition only if a specific branch is selected
if ($branch_id) {
    $whereConditions[] = "oi.branch_id = ?";
}

// Combine conditions
$conditions = $whereConditions ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// Define SQL query and date range logic based on timeframe
if ($timeframe === 'yearly' && !$selectedYear) {
    $sql = "SELECT YEAR(o.order_date) AS period, SUM(o.total_amount) AS total_sales 
            FROM orders o 
            JOIN order_items oi ON o.order_id = oi.order_id 
            $conditions AND o.order_type = 'wholesale' 
            GROUP BY period ORDER BY period ASC";
    $format = 'Y';
} elseif ($timeframe === 'yearly' && $selectedYear && !$selectedMonth) {
    $sql = "SELECT DATE_FORMAT(o.order_date, '%Y-%m') AS period, SUM(o.total_amount) AS total_sales 
            FROM orders o 
            JOIN order_items oi ON o.order_id = oi.order_id 
            $conditions AND o.order_type = 'wholesale' 
            GROUP BY period ORDER BY period ASC";
    $format = 'M Y';
} elseif ($selectedYear && $selectedMonth) {
    $sql = "SELECT DATE_FORMAT(o.order_date, '%Y-%m-%d') AS period, SUM(o.total_amount) AS total_sales 
            FROM orders o 
            JOIN order_items oi ON o.order_id = oi.order_id 
            $conditions AND o.order_type = 'wholesale' 
            GROUP BY period ORDER BY period ASC";
    $format = 'M d';
} else {
    $sql = "SELECT DATE_FORMAT(o.order_date, '%Y-%m-%d') AS period, SUM(o.total_amount) AS total_sales 
            FROM orders o 
            JOIN order_items oi ON o.order_id = oi.order_id 
            $conditions AND o.order_type = 'wholesale' 
            GROUP BY period ORDER BY period ASC";
    $format = 'Y-m-d';
}

// Prepare and execute the query
$stmt = $mysqli->prepare($sql);

// Bind branch_id if selected
if ($branch_id) {
    $stmt->bind_param("i", $branch_id);
}
$stmt->execute();
$result = $stmt->get_result();

// Initialize response
$data = ['periods' => [], 'total_sales' => []];

while ($row = $result->fetch_assoc()) {
    $formattedPeriod = ($selectedYear && !$selectedMonth)
        ? date('M Y', strtotime($row['period']))
        : (($selectedYear && $selectedMonth)
            ? date('M d', strtotime($row['period']))
            : $row['period']);
    
    $data['periods'][] = $formattedPeriod;
    $data['total_sales'][] = $row['total_sales'];
}

echo json_encode($data);
$mysqli->close();
?>
