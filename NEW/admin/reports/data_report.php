<?php
session_start();

include('../../connection.php');

// Get type (wholesale, retail, online) and timeframe (daily, weekly, etc.)
$type = isset($_GET['type']) ? $_GET['type'] : '';
$timeframe = isset($_GET['timeframe']) ? $_GET['timeframe'] : '';
$source = isset($_GET['source']) ? $_GET['source'] : '';

// Generate date range based on timeframe
switch ($timeframe) {
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


?>

<html>

<head>
    <meta charset="UTF-8">
    <title><?= ucfirst($type) ?> Sales Report</title>
</head>


<body>
    <h2><?= ucfirst($source) ?> <?= ucfirst($type) ?> Sales Report (<?= ucfirst($timeframe) ?>)</h2>
    <a href="download_report.php?type=<?= $type ?>&source=<?= $source ?>&timeframe=<?= $timeframe ?>">Download Report</a>

    <table border="1">
        <tr>
            <th>Order ID</th>
            <th>Order Date</th>
            <th>Total Amount</th>
            <th>Product Name</th>
            <th>Quantity</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['order_id'] ?></td>
                <td><?= $row['order_date'] ?></td>
                <td>₱<?= number_format($row['total_amount'], 2) ?></td>
                <td><?= $row['prod_name'] ?></td>
                <td><?= $row['quantity'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>


</body>

</html>