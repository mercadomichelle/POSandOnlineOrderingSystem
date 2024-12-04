<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('connection.php');

// if (!isset($_SESSION['branch_id'])) {
//     echo "Error: Branch ID not set. Please log in again.";
//     exit();
// }

$branch_id = $_SESSION['branch_id'];

$sql = "SELECT p.prod_id, p.prod_brand, p.prod_name, p.prod_image_path, 
               COALESCE(SUM(s.stock_quantity), 0) AS stock_quantity 
        FROM products p 
        LEFT JOIN stocks s ON p.prod_id = s.prod_id
        WHERE s.branch_id = ?
        GROUP BY p.prod_id, p.prod_brand, p.prod_name, p.prod_image_path
        ORDER BY stock_quantity ASC";

$stmt = $mysqli->prepare($sql);
$stmt->bind_param("i", $branch_id); 
$stmt->execute();
$result = $stmt->get_result();

$stocks = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $row['stock_quantity'] = max(0, $row['stock_quantity']);
        $row['is_low_stock'] = $row['stock_quantity'] > 0 && $row['stock_quantity'] < 10;
        $row['is_out_of_stock'] = $row['stock_quantity'] == 0;
        $stocks[] = $row;
    }
} else {
    echo "No stocks found.";
}

$lowStockNotifications = [];
$outOfStockNotifications = [];

foreach ($stocks as $stock) {
    if ($stock['is_low_stock']) {
        $lowStockNotifications[] = 'Low stock: ' . htmlspecialchars($stock['prod_name']);
    } elseif ($stock['is_out_of_stock']) {
        $outOfStockNotifications[] = 'Out of stock: ' . htmlspecialchars($stock['prod_name']);
    }
}

$notifications = array_merge($lowStockNotifications, $outOfStockNotifications);

?>