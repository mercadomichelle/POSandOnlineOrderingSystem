<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

$host = "localhost";
$user = "root";
$password = "";
$mysqliSystem = new mysqli($host, $user, $password, "system_db");
if ($mysqliSystem->connect_error) {
    die("Connection failed: " . $mysqliSystem->connect_error);
}

$branchStocks = [];
$riceVarieties = [];
$branches = ["Calero", "Bauan", "San Pascual"];

function fetchStockData($mysqli, &$branchStocks, &$riceVarieties, &$branches) {
    $sql = "
        SELECT 
            b.branch_name,
            spb.prod_name AS rice_type,
            IFNULL(spb.prod_stocks, 0) AS stock_quantity
        FROM 
            branches b
        LEFT JOIN 
            stocks_per_branches spb ON b.branch_id = spb.branch_id
        ORDER BY 
            spb.prod_name, b.branch_name
    ";

    $result = $mysqli->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $branch = $row['branch_name'];
            $riceType = $row['rice_type'];
            $stockQuantity = $row['stock_quantity'];

            if (!isset($branchStocks[$riceType])) {
                $branchStocks[$riceType] = array_fill_keys($branches, 0);
            }
            $branchStocks[$riceType][$branch] = (int)$stockQuantity;

            if (!in_array($riceType, $riceVarieties)) {
                $riceVarieties[] = $riceType;
            }
        }
    }
}

fetchStockData($mysqliSystem, $branchStocks, $riceVarieties, $branches);

$maxStocks = [];
foreach ($branchStocks as $riceType => $stocks) {
    $maxStocks[$riceType] = max($stocks);
}

if (empty($riceVarieties) || empty($branchStocks)) {
    error_log("riceVarieties or branchStocks are empty.");
}

$output = [
    'riceVarieties' => $riceVarieties,
    'branchStocks' => $branchStocks,
    'maxStocks' => $maxStocks
];

header('Content-Type: application/json');
echo json_encode($output);
$mysqliSystem->close();
?>
