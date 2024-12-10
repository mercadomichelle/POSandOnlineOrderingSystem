<?php
session_start();
include('../../connection.php');

// Fetch input parameters
$branch_id = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;
$selectedMonth = isset($_GET['month']) ? intval($_GET['month']) : null;
$selectedWeek = isset($_GET['week']) ? intval($_GET['week']) : null;
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : null;
$timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : 'monthly'; // Default to monthly if no timeframe is provided

// SQL filters for month, week, and year
$whereConditions = [];
if ($selectedYear) {
    $whereConditions[] = "YEAR(o.order_date) = $selectedYear";
}
if ($selectedMonth) {
    $whereConditions[] = "MONTH(o.order_date) = $selectedMonth";
}

// If a week is selected, we need to filter by the date range for that week
if ($selectedWeek && $selectedMonth && $selectedYear) {
    // Get the first day of the month
    $firstDayOfMonth = strtotime("$selectedYear-$selectedMonth-01");
    $totalDays = cal_days_in_month(CAL_GREGORIAN, $selectedMonth, $selectedYear);
    
    // Calculate the week start and end dates
    $startOfWeek = strtotime("+".(($selectedWeek - 1) * 7)." days", $firstDayOfMonth);
    $endOfWeek = strtotime("+6 days", $startOfWeek);
    
    // Ensure the end of the week doesn't exceed the end of the month
    if ($endOfWeek > strtotime("$selectedYear-$selectedMonth-$totalDays")) {
        $endOfWeek = strtotime("$selectedYear-$selectedMonth-$totalDays");
    }

    $whereConditions[] = "o.order_date BETWEEN '" . date('Y-m-d', $startOfWeek) . "' AND '" . date('Y-m-d', $endOfWeek) . "'";
}

// Combine conditions
$conditions = $whereConditions ? 'AND ' . implode(' AND ', $whereConditions) : '';

// If no filters are selected, show distinct years with total sales for each year
if (!$selectedMonth && !$selectedWeek && !$selectedYear) {
    // Fetch sales data for all years from 2021 to present
    $sql = "
    SELECT 
        YEAR(o.order_date) AS period,
        SUM(o.total_amount) AS total_sales
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.order_source = 'online'
    GROUP BY YEAR(o.order_date)
    ORDER BY period ASC
    ";
} else {
    // Define the start and end date based on timeframe and month/year selection
    if ($selectedYear && !$selectedMonth) {
        // If only year is selected, show months of the selected year
        $startDate = "$selectedYear-01-01";
        $endDate = "$selectedYear-12-31";
        $interval = 'P1M'; // Monthly interval for showing all months in the year
        $format = 'Y-m'; // Format for month (e.g., 2024-01)
    } else {
        // Default behavior for monthly and weekly timeframe
        if ($selectedYear && $selectedMonth) {
            $startDate = "$selectedYear-$selectedMonth-01";
            $endDate = date('Y-m-t', strtotime($startDate)); // Last day of the selected month
            $interval = 'P1D'; // Daily interval for showing all days in the month
            $format = 'Y-m-d'; // Format for day (e.g., 2024-01-01)
        } else {
            $startDate = date('Y-m-01', strtotime('-11 months')); 
            $endDate = date('Y-m-t'); // End of the current month
            $interval = 'P1M'; // Monthly interval
            $format = 'Y-m'; // Format for month
        }
    }

    // Default behavior for monthly and weekly queries
    if ($selectedYear && $selectedMonth) {
        $startDate = "$selectedYear-$selectedMonth-01";
        $endDate = date('Y-m-t', strtotime($startDate)); // Last day of the selected month

        $sql = "
        SELECT 
            DATE_FORMAT(o.order_date, '%Y-%m-%d') AS period, 
            SUM(o.total_amount) AS total_sales
        FROM 
            orders o
        JOIN 
            order_items oi ON o.order_id = oi.order_id
        WHERE 
            o.order_date >= '$startDate'
            AND o.order_date <= '$endDate'
            AND o.order_source = 'online'
            $conditions  -- Apply the conditions here
        GROUP BY 
            period
        ORDER BY 
            period ASC
        ";
    } else {
        // Default behavior for monthly and weekly queries
        if ($timeframe === 'weekly') {
            $sql = "
            SELECT 
                DATE_FORMAT(o.order_date, '%Y-%m-%d') AS period, 
                SUM(o.total_amount) AS total_sales
            FROM 
                orders o
            JOIN 
                order_items oi ON o.order_id = oi.order_id
            WHERE 
                o.order_date >= CURDATE() - INTERVAL 6 DAY
                AND o.order_date <= '$endDate 23:59:59'      
                AND o.order_source = 'online'
                $conditions  -- Apply the conditions here
            GROUP BY 
                DATE(o.order_date)
            ORDER BY 
                DATE(o.order_date) ASC
            ";
        } else {
            $sql = "
            SELECT 
                DATE_FORMAT(o.order_date, '%Y-%m') AS period,  
                SUM(o.total_amount) AS total_sales
            FROM 
                orders o
            JOIN 
                order_items oi ON o.order_id = oi.order_id
            WHERE 
                o.order_date >= '$startDate'
                AND o.order_date <= '$endDate'
                AND o.order_type = 'wholesale' 
                AND o.order_source = 'online'
                $conditions  -- Apply the conditions here
            GROUP BY 
                period
            ORDER BY 
                period ASC
            ";
        }
    }
}

// Prepare and execute the SQL query
$stmt = $mysqli->prepare($sql);

// Bind parameters based on branch selection
if ($branch_id) {
    $stmt->bind_param("i", $branch_id); // Bind branch_id parameter
} else {
    // If no branch_id is selected, execute the query without binding the branch_id
    $stmt->execute();
}

$result = $stmt->get_result();

// Initialize an array to hold the data
$data = ['periods' => [], 'total_sales' => []];
$salesByDate = [];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $salesByDate[$row['period']] = $row['total_sales']; // Store the sales by date
    }
}

// If no filter is selected, ensure all years from 2021 to present are included
if (!$selectedMonth && !$selectedWeek && !$selectedYear) {
    $currentYear = date('Y');
    $years = range(2021, $currentYear); // All years from 2021 to the current year

    foreach ($years as $year) {
        if (isset($salesByDate[$year])) {
            $data['periods'][] = $year;
            $data['total_sales'][] = $salesByDate[$year];
        } else {
            $data['periods'][] = $year;
            $data['total_sales'][] = 0; // No sales for this year
        }
    }
} else {
    // Populate the data array with months and their corresponding sales data
    foreach ($salesByDate as $date => $sales) {
        // When only the year is selected, format the period as 'M Y' (e.g., Jan 2024, Feb 2024)
        if ($selectedYear && !$selectedMonth) {
            $data['periods'][] = date('M Y', strtotime($date)); // Format as 'Jan 2024', 'Feb 2024'
        } else {
            // For month and week selection, format as 'M d' (e.g., Jan 01, Feb 01)
            $data['periods'][] = date('M d', strtotime($date)); // Format as 'Jan 01', 'Feb 01'
        }
        $data['total_sales'][] = $sales; // Sales data for this period
    }
}

echo json_encode($data);
$mysqli->close();
?>
