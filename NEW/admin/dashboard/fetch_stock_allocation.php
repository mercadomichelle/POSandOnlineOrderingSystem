<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Main database connection (system_db)
$host = "localhost";
$user = "root";
$password = "";

// Connection for system_db
$mysqliSystem = new mysqli($host, $user, $password, "system_db");
if ($mysqliSystem->connect_error) {
    die("Connection failed: " . $mysqliSystem->connect_error);
}

// Connection for calero_db
$mysqliCalero = new mysqli($host, $user, $password, "calero_db");
if ($mysqliCalero->connect_error) {
    die("Connection failed: " . $mysqliCalero->connect_error);
}

// Array to store stock data
$branchStocks = [];
$riceVarieties = [];

// Function to fetch stock data from a given database connection
function fetchStockData($mysqli, &$branchStocks, &$riceVarieties) {
    $sql = "
        SELECT 
            b.branch_name,
            p.prod_name AS rice_type,
            IFNULL(s.stock_quantity, 0) AS stock_quantity
        FROM 
            branches b
        LEFT JOIN 
            stocks s ON b.branch_id = s.branch_id
        LEFT JOIN 
            products p ON s.prod_id = p.prod_id
        ORDER BY 
            p.prod_name, b.branch_name
    ";

    $result = $mysqli->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $branch = $row['branch_name'];
            $riceType = $row['rice_type'];
            $stockQuantity = $row['stock_quantity'];

            // Store stock data for each rice type and branch
            if (!isset($branchStocks[$riceType])) {
                $branchStocks[$riceType] = [];
            }
            $branchStocks[$riceType][$branch] = (int)$stockQuantity;

            // Track unique rice varieties
            if (!in_array($riceType, $riceVarieties)) {
                $riceVarieties[] = $riceType;
            }
        }
    }
}

// Fetch stock data from both databases
fetchStockData($mysqliSystem, $branchStocks, $riceVarieties);
fetchStockData($mysqliCalero, $branchStocks, $riceVarieties);

// Calculate maximum stock for each rice type
$maxStocks = [];
foreach ($branchStocks as $riceType => $stocks) {
    $maxStocks[$riceType] = max($stocks);
}

if (empty($riceVarieties) || empty($branchStocks)) {
    error_log("riceVarieties or branchStocks are empty.");
}

// Prepare data for JSON output
$output = [
    'riceVarieties' => $riceVarieties,
    'branchStocks' => $branchStocks,
    'maxStocks' => $maxStocks
];

// Output data as JSON
header('Content-Type: application/json');
echo json_encode($output);

// Close connections
$mysqliSystem->close();
$mysqliCalero->close();
?>
