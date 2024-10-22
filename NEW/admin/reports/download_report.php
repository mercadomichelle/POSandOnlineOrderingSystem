<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";
$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$type = isset($_GET['type']) ? $_GET['type'] : '';
$timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : '';
$source = isset($_GET['source']) ? $_GET['source'] : '';

if (empty($type) || empty($source) || empty($timeframe)) {
    die("Error: Missing parameters.");
}

// Same logic to get the startDate and endDate based on timeframe
switch($timeframe) {
    case 'daily':
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d');
        break;
    case 'weekly':
        $startDate = date('Y-m-d', strtotime('-6 days'));
        $endDate = date('Y-m-d');
        break;
    case 'monthly':
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-t');
        break;
    case 'yearly':
        $startDate = date('Y-01-01');
        $endDate = date('Y-12-31');
        break;
}

// Debugging output
echo "Start Date: $startDate, End Date: $endDate\n";


header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="sales_report_' . $timeframe . '.csv"');

// Open file pointer
$output = fopen('php://output', 'w');

// Add column headings to the CSV
fputcsv($output, ['Order ID', 'Order Date', 'Total Amount', 'Product Name', 'Quantity']);

// Fetch the report data from the database
$sql = "
    SELECT o.order_id, o.order_date, o.total_amount, p.prod_name, oi.quantity
    FROM orders o
    JOIN order_items oi ON o.order_id = oi.order_id
    JOIN products p ON oi.prod_id = p.prod_id
    WHERE o.order_type = '$type' 
    AND o.order_source = '$source'
    AND o.order_date BETWEEN '$startDate' AND '$endDate'
    ORDER BY o.order_id, o.order_date
";

$result = $mysqli->query($sql);
if (!$result) {
    die("Query failed: " . $mysqli->error);
}

// Add rows to CSV
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['order_id'], 
        $row['order_date'], 
        '₱' . number_format($row['total_amount'], 2, '.', ''), // Explicitly set decimal and thousands separators
        $row['prod_name'], 
        $row['quantity']
    ]);
}


fclose($output);

?>
