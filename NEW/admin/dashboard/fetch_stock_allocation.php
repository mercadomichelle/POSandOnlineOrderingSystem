<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();

// Assuming the user's branch_id is stored in the session
$branchId = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : null;

if (!$branchId) {
    error_log("User's branch ID not set in the session.");
    exit('Branch ID is required');
}

include('../../connection.php');

$branchStocks = [];
$riceVarieties = [];

// Function to fetch stock data for the user's branch
function fetchStockData($mysqli, &$branchStocks, &$riceVarieties, $branchId)
{
    $sql = "
        SELECT 
            products.prod_name AS rice_type,
            branches.branch_name,
            stocks.branch_id,
            products.prod_id,
            GREATEST(SUM(stock_quantity), 0) AS total_stock_quantity 
        FROM 
            stocks
        LEFT JOIN products ON products.prod_id = stocks.prod_id
        LEFT JOIN branches ON stocks.branch_id = branches.branch_id
        WHERE
            stocks.branch_id = ?  -- Filter by the user's branch
        GROUP BY 
            stocks.branch_id, products.prod_id
        ORDER BY 
            products.prod_id
    ";

    // Prepare statement to avoid SQL injection
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('i', $branchId); // Bind the branch ID parameter
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $riceType = $row['rice_type'];
                $stockQuantity = $row['total_stock_quantity'];

                // Initialize array for rice type if not set
                if (!isset($branchStocks[$riceType])) {
                    $branchStocks[$riceType] = 0;
                }

                // Store stock quantity
                $branchStocks[$riceType] = max(0, (int)$stockQuantity); // Ensure stock is non-negative

                // Store rice variety if not already added
                if (!in_array($riceType, $riceVarieties)) {
                    $riceVarieties[] = $riceType;
                }
            }
        } else {
            error_log("Query returned no results or failed: " . $mysqli->error);
        }

        $stmt->close();
    } else {
        error_log("Failed to prepare SQL statement: " . $mysqli->error);
    }
}

fetchStockData($mysqli, $branchStocks, $riceVarieties, $branchId);

// If no rice varieties are found, log an error
if (empty($riceVarieties) || empty($branchStocks)) {
    error_log("riceVarieties or branchStocks are empty.");
}

$output = [
    'riceVarieties' => $riceVarieties,
    'branchStocks' => $branchStocks,
    'maxStocks' => $branchStocks // Since we are only showing data for one branch, max is just the current stock
];

header('Content-Type: application/json');
echo json_encode($output);

// Properly close the database connection
$mysqli->close();
