<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

include('../../connection.php');

// Get parameters from the request with default empty values
$type = isset($_GET['type']) ? $_GET['type'] : '';
$timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : '';
$source = isset($_GET['source']) ? $_GET['source'] : '';
$branch_id = isset($_GET['branch_id']) ? $_GET['branch_id'] : '';

// Initialize date filters
$startDate = '';
$endDate = '';

// Check if month and year are selected together
if (!empty($_GET['month']) && !empty($_GET['year'])) {
    $month = $_GET['month'];
    $year = $_GET['year'];
    $startDate = date('Y-m-01', strtotime("$year-$month-01")); // First day of the selected month
    $endDate = date('Y-m-t', strtotime("$year-$month-01"));   // Last day of the selected month
} elseif (!empty($_GET['year'])) {
    // If only year is selected, use the entire year range
    $startDate = $_GET['year'] . '-01-01';
    $endDate = $_GET['year'] . '-12-31';
} elseif (!empty($timeframe)) {
    // Handle timeframe like daily, weekly, etc.
    switch ($timeframe) {
        case 'daily':
            $startDate = date('Y-m-d');
            $endDate = date('Y-m-d');
            break;
        case 'weekly':
            $startDate = date('Y-m-d', strtotime('monday last week'));
            $endDate = date('Y-m-d', strtotime('sunday last week'));
            break;
        case 'monthly':
            $startDate = date('Y-m-01');
            $endDate = date('Y-m-t');
            break;
        case 'yearly':
            $startDate = date('Y-01-01');
            $endDate = date('Y-12-31');
            break;
        default:
            die("Error: Invalid timeframe.");
    }
} else {
    die("Error: Missing timeframe, month, or year.");
}

// Check for missing parameters that are essential (e.g., type and source)
if (empty($type) || empty($source)) {
    die("Error: Missing parameters 'type' or 'source'.");
}

// Set headers for CSV download
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="sales_report_' . $timeframe . '.csv"');

// Open file pointer
$output = fopen('php://output', 'w');

// Add BOM for UTF-8 encoding
fwrite($output, "\xEF\xBB\xBF");

// Add column headings to the CSV
fputcsv($output, ['Order ID', 'Order Date', 'Total Amount', 'Product Name', 'Quantity']);

// Prepare the SQL query
$sql = "
    SELECT o.order_id, o.order_date, o.total_amount, p.prod_name, oi.quantity
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.prod_id = p.prod_id
    WHERE o.order_date BETWEEN '$startDate' AND '$endDate'
";

// Apply additional filters if set
if (!empty($type)) {
    $sql .= " AND o.order_type = '$type'"; // Filter by order type (wholesale or retail)
}
if (!empty($source)) {
    $sql .= " AND o.order_source = '$source'"; // Filter by order source (e.g., in-store, online)
}
if (!empty($branch_id)) {
    $sql .= " AND oi.branch_id = '$branch_id'"; // Filter by branch
}

// Sort by order_date in descending order (most recent first)
$sql .= " ORDER BY o.order_date DESC";

// Execute the query
$result = $mysqli->query($sql);
if (!$result) {
    die("Query failed: " . $mysqli->error);
}

// Add rows to CSV
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['order_id'],
            $row['order_date'],
            '₱' . number_format($row['total_amount'], 2, '.', ''),
            $row['prod_name'],
            $row['quantity']
        ]);
    }
} else {
    // No data found
    fputcsv($output, ['No data found for the selected criteria.']);
}

// Close the file pointer
fclose($output);
exit;
?>
