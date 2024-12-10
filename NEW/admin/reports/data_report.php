<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

include('../../connection.php');

// Get parameters from the request (type and source are already passed from the URL)
$type = isset($_GET['type']) ? $_GET['type'] : '';
$source = isset($_GET['source']) ? $_GET['source'] : '';
$month = isset($_GET['month']) ? $_GET['month'] : '';
$year = isset($_GET['year']) ? $_GET['year'] : '';
$week = isset($_GET['week']) ? $_GET['week'] : '';
$branch_id = isset($_GET['branch_id']) ? $_GET['branch_id'] : '';

// Default empty date range
$startDate = '';
$endDate = '';

// Check if month and year are selected, otherwise default to current month
if (!empty($month) && !empty($year)) {
    // If both month and year are selected, filter by that specific month and year
    $startDate = "$year-$month-01";
    $endDate = date("Y-m-t", strtotime($startDate)); // Get the last day of the month
} elseif (!empty($month)) {
    // If only month is selected, ignore year and fetch data for that month across all years
    $startDate = date("Y", strtotime("now")) . "-$month-01"; // Default to the current year
    $endDate = date("Y-m-t", strtotime($startDate)); // Get the last day of the month
} elseif (!empty($year)) {
    // Handle year filter only
    $startDate = "$year-01-01";
    $endDate = "$year-12-31";
} elseif (!empty($week) && !empty($year)) {
    // Handle week filter
    $startDate = date("Y-m-d", strtotime($year . "W" . $week)); // Start date of the week
    $endDate = date("Y-m-d", strtotime($year . "W" . $week . "7")); // End date of the week
} else {
    // If no filters are set, default to the current month
    $currentMonth = date('m'); // Get current month
    $currentYear = date('Y');  // Get current year
    $startDate = "$currentYear-$currentMonth-01";  // First day of the current month
    $endDate = date("Y-m-t", strtotime($startDate)); // Last day of the current month
}

// Prepare the SQL query with the filters
$sql = "
    SELECT o.order_id, o.order_date, o.total_amount, p.prod_name, oi.quantity
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.prod_id = p.prod_id
    WHERE 1=1
";

// Apply filters based on selected values
if (!empty($month) && empty($year)) {
    // If only month is selected, we extract records for that month, across all years
    $sql .= " AND MONTH(o.order_date) = '$month'"; // Filter by month only (across all years)
} elseif (!empty($startDate) && !empty($endDate)) {
    $sql .= " AND o.order_date BETWEEN '$startDate' AND '$endDate'"; // Date filter
}

if (!empty($type)) {
    $sql .= " AND o.order_type = '$type'"; // Filter by order type (wholesale or retail)
}
if (!empty($source)) {
    $sql .= " AND o.order_source = '$source'"; // Filter by order source (e.g., in-store, online)
}
if (!empty($branch_id)) {
    $sql .= " AND oi.branch_id = '$branch_id'"; // Filter by branch
}

// Execute the query
$sql .= " ORDER BY o.order_date DESC"; // Ensure records are ordered by the most recent first
$result = $mysqli->query($sql);

if (!$result) {
    die("Query failed: " . $mysqli->error);
}

// Determine the report title based on selected filters
$title = "Sales Report";

// Add type-based customization to the title if available
if (!empty($type)) {
    $title = ucfirst($type) . " Report";
}

// Add source-based customization to the title if available
if (!empty($source)) {
    $title = ucfirst($source) . " " . $title;
}

// Handle month and year selection in the title
if (!empty($month) && !empty($year)) {
    // Convert the numeric month to a full month name
    $monthName = date("F", mktime(0, 0, 0, $month, 10)); // Get the full month name
    $title .= " for $monthName $year"; // Add the selected month/year to the title
} elseif (!empty($month)) {
    // If only month is selected, use current year and show just the month
    $monthName = date("F", mktime(0, 0, 0, $month, 10)); // Get the full month name
    $title .= " for $monthName";
} elseif (!empty($year)) {
    // If only year is selected, just show the year
    $title .= " for $year";
} elseif (!empty($week)) {
    // If week is selected, include the week number and year
    $title .= " for Week $week, $year";
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Rice Website | <?= htmlspecialchars($title) ?></title>
    <link rel="icon" href="../../favicon.png" type="image/png">
    <link rel="stylesheet" href="../../styles/data_report.css">
</head>
<body>
    <h2><?= htmlspecialchars($title) ?></h2>

    <a href="download_report.php?type=<?= urlencode($type) ?>&source=<?= urlencode($source) ?>&month=<?= urlencode($month) ?>&year=<?= urlencode($year) ?>">Download Report</a>

    <form method="GET" action="">
        <div class="sorting-container">
            <!-- Hidden Fields to retain type, source, and branch_id -->
            <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">
            <input type="hidden" name="source" value="<?= htmlspecialchars($source) ?>">
            <input type="hidden" name="branch_id" value="<?= htmlspecialchars($branch_id) ?>">

            <!-- Month Filter -->
            <select name="month" id="monthSelector">
                <option value="">Select Month</option>
                <option value="1" <?= ($month == '1') ? 'selected' : '' ?>>January</option>
                <option value="2" <?= ($month == '2') ? 'selected' : '' ?>>February</option>
                <option value="3" <?= ($month == '3') ? 'selected' : '' ?>>March</option>
                <option value="4" <?= ($month == '4') ? 'selected' : '' ?>>April</option>
                <option value="5" <?= ($month == '5') ? 'selected' : '' ?>>May</option>
                <option value="6" <?= ($month == '6') ? 'selected' : '' ?>>June</option>
                <option value="7" <?= ($month == '7') ? 'selected' : '' ?>>July</option>
                <option value="8" <?= ($month == '8') ? 'selected' : '' ?>>August</option>
                <option value="9" <?= ($month == '9') ? 'selected' : '' ?>>September</option>
                <option value="10" <?= ($month == '10') ? 'selected' : '' ?>>October</option>
                <option value="11" <?= ($month == '11') ? 'selected' : '' ?>>November</option>
                <option value="12" <?= ($month == '12') ? 'selected' : '' ?>>December</option>
            </select>

            <!-- Year Filter -->
            <select name="year" id="yearSelector">
                <option value="">Select Year</option>
                <?php
                    $currentYear = date('Y');
                    $startYear = $currentYear - 1;
                    for ($yearOption = $startYear; $yearOption <= $currentYear; $yearOption++) {
                        $selected = ($year == $yearOption) ? 'selected' : '';
                        echo "<option value=\"$yearOption\" $selected>$yearOption</option>";
                    }
                ?>
            </select>

            <button type="submit">Filter</button>
        </div>
    </form>
    
    <table>
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Order Date</th>
                <th>Total Amount</th>
                <th>Product Name</th>
                <th>Quantity</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['order_id']) ?></td>
                        <td><?= htmlspecialchars($row['order_date']) ?></td>
                        <td><?= htmlspecialchars($row['total_amount']) ?></td>
                        <td><?= htmlspecialchars($row['prod_name']) ?></td>
                        <td><?= htmlspecialchars($row['quantity']) ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5">No records found for the selected filters.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
