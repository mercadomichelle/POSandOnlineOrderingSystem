<?php
// Database connection
$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";
$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Assume timeframe is passed from AJAX
$timeframe = $_GET['timeframe'];

$timeframe_condition = '';

if ($timeframe === 'weekly') {
    $timeframe_condition = "AND YEARWEEK(o.order_date, 1) = YEARWEEK(CURDATE(), 1)";
} else if ($timeframe === 'monthly') {
    $timeframe_condition = "AND YEAR(o.order_date) = YEAR(CURDATE()) AND MONTH(o.order_date) = MONTH(CURDATE())";
}

$sql = "
    SELECT p.prod_name, SUM(oi.quantity) AS total_quantity
    FROM order_items oi
    JOIN orders o ON oi.order_id = o.order_id
    JOIN products p ON oi.prod_id = p.prod_id
    WHERE o.order_source = 'in-store'
    $timeframe_condition
    GROUP BY p.prod_name
    ORDER BY total_quantity DESC
    LIMIT 5
";


$result = $mysqli->query($sql);
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
