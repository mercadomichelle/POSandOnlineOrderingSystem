<?php
session_start();
include('../../connection.php');

// Fetch input parameters
$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : null;
$selectedWeek = isset($_GET['week']) ? intval($_GET['week']) : null;
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : null;

// SQL filters for month, week, and year
$whereConditions = [];
if ($selectedYear) {
    $whereConditions[] = "YEAR(o.order_date) = $selectedYear";
}
if ($selectedMonth) {
    $whereConditions[] = "MONTH(o.order_date) = $selectedMonth";
}
if ($selectedWeek && $selectedMonth && $selectedYear) {
    // Weekly filtering logic
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

// Combine conditions
$conditions = $whereConditions ? 'AND ' . implode(' AND ', $whereConditions) : '';

// Query to get purchase preferences with branch filtering and additional time-based filters
$query = "
    SELECT 
        o.order_source, 
        o.order_type, 
        COUNT(*) AS order_count, 
        SUM(o.total_amount) AS total_sales
    FROM 
        orders o
    JOIN 
        order_items oi ON o.order_id = oi.order_id
    WHERE 
        oi.branch_id = ?  -- Filter by branch_id in order_items
        $conditions  -- Apply the dynamic time filters
    GROUP BY 
        o.order_source, o.order_type
";

// Prepare and execute the query
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $branch_id); // Bind branch_id parameter
$stmt->execute();
$result = $stmt->get_result();

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
