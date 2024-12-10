<?php
// fetch_stock_allocation.php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();
include('../../connection.php');

// Assuming the user's branch_id is stored in the session
$branchId = isset($_SESSION['branch_id']) ? $_SESSION['branch_id'] : null;

if (!$branchId) {
    error_log("User's branch ID not set in the session.");
    exit('Branch ID is required');
}

$branchStocks = [];
$riceVarieties = [];
$demandData = [];
$allocationRecommendations = [];
$replenishmentInsights = [];
$priorityInsights = [];

// Fetch stock data
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

    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('i', $branchId);  // Bind the branch_id to the SQL query
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $riceType = $row['rice_type'];
                $stockQuantity = $row['total_stock_quantity'];

                if (!isset($branchStocks[$riceType])) {
                    $branchStocks[$riceType] = 0;
                }

                $branchStocks[$riceType] = max(0, (int)$stockQuantity);

                if (!in_array($riceType, $riceVarieties)) {
                    $riceVarieties[] = $riceType;
                }
            }
        } else {
            error_log("No stock data found for branch ID: " . $branchId);
        }

        $stmt->close();
    } else {
        error_log("Failed to prepare SQL statement for stock data: " . $mysqli->error);
    }
}

// Fetch demand data (sales data for the past 30 days)
function fetchDemandData($mysqli, &$demandData, $branchId)
{
    $sql = "
        SELECT 
            oi.prod_id, 
            p.prod_name AS rice_type, 
            SUM(oi.quantity) AS total_sold
        FROM 
            order_items oi
        JOIN 
            orders o ON oi.order_id = o.order_id
        JOIN 
            products p ON oi.prod_id = p.prod_id
        WHERE 
            oi.branch_id = ? AND o.order_date >= CURDATE() - INTERVAL 30 DAY
        GROUP BY 
            oi.prod_id
        ORDER BY 
            total_sold DESC
    ";

    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('i', $branchId);  // Bind the branch_id to the SQL query
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $riceType = $row['rice_type'];
                $totalSold = (int)$row['total_sold'];

                // Store the total sold quantity by rice type
                $demandData[$riceType] = $totalSold;
            }
        } else {
            error_log("No demand data found for branch ID: " . $branchId);
        }

        $stmt->close();
    } else {
        error_log("Failed to prepare SQL statement for demand data: " . $mysqli->error);
    }
}

// Calculate stock recommendations based on demand and stock levels
function calculateStockRecommendations($branchStocks, $demandData, &$allocationRecommendations, &$replenishmentInsights)
{
    foreach ($demandData as $riceType => $totalSold) {
        $totalStock = isset($branchStocks[$riceType]) ? $branchStocks[$riceType] : 0;

        // If no stock available
        if ($totalStock === 0) {
            $replenishmentInsights[] = "You need to add more stock for '$riceType' as the current stock ($totalStock) is less than the demand ($totalSold).";
        }

        // If stock is less than demand
        if ($totalStock < $totalSold) {
            $replenishmentInsights[] = "You need to add more stock for '$riceType' as the current stock ($totalStock) is less than the demand ($totalSold).";
        }

        // Maximum stock allocation (based on projected demand for the next 7 days)
        $avgDailyDemand = $totalSold / 30;  // Average daily sales in the last 30 days
        $projectedDemand = $avgDailyDemand * 7;  // Projected demand for the next 7 days

        // Add a safety stock buffer (e.g., 10% more)
        $safetyStockBuffer = ceil($projectedDemand * 0.10);
        $totalProjectedStockNeed = $projectedDemand + $safetyStockBuffer;

        // Store the allocation recommendation
        $allocationRecommendations[$riceType] = max($totalStock, $totalProjectedStockNeed);

        // Insights for stock sufficiency or shortfall
        if ($totalStock >= $totalSold) {
            $replenishmentInsights[] = "For '$riceType', you have sufficient stock to meet demand for the next 30 days.";
        }
    }
}

// Calculate stock replenishment recommendation considering sales forecast
function calculateStockReplenishment($branchStocks, $demandData, &$allocationRecommendations, &$replenishmentInsights, $forecastDays = 7)
{
    foreach ($demandData as $riceType => $totalSold) {
        $totalStock = isset($branchStocks[$riceType]) ? $branchStocks[$riceType] : 0;

        // Calculate the average daily demand for the rice variety (over the last 30 days)
        $avgDailyDemand = $totalSold / 30;

        // Estimate the demand for the next few days (projected)
        $projectedDemand = $avgDailyDemand * $forecastDays;

        // Add a safety stock buffer (e.g., 10% more)
        $safetyStockBuffer = ceil($projectedDemand * 0.10);
        $totalProjectedStockNeed = $projectedDemand + $safetyStockBuffer;

        // Calculate how much stock needs to be added
        $stockToAdd = max(0, $totalProjectedStockNeed - $totalStock);

        // Store the allocation recommendation
        $allocationRecommendations[$riceType] = max($totalStock, $totalProjectedStockNeed);  // Ensure stock doesn't fall below current available stock

        // Add insights for projected demand
        if ($stockToAdd > 0) {
            $replenishmentInsights[] = "For '$riceType', based on the sales trend, you should consider adding " . number_format($stockToAdd) . " units for the next $forecastDays days.";
        } else {
            $replenishmentInsights[] = "For '$riceType', you have sufficient stock to meet demand for the next $forecastDays days.";
        }
    }
}

function calculatePriorityScore($branchStocks, $demandData)
{
    $priorityScores = [];

    foreach ($demandData as $riceType => $totalSold) {
        $totalStock = isset($branchStocks[$riceType]) ? $branchStocks[$riceType] : 0;

        // Calculate stock deficit
        $stockDeficit = max(0, $totalSold - $totalStock);

        // Priority score can be based on:
        // 1. Demand (higher demand gets a higher score)
        // 2. Stock deficit (larger deficit increases priority)
        // The score is a simple sum of both factors.
        $priorityScore = $totalSold + $stockDeficit * 2;  // Giving double weight to the stock deficit

        // Store the calculated priority score for each rice type
        $priorityScores[$riceType] = $priorityScore;
    }

    // Sort the rice types by priority score (highest first)
    arsort($priorityScores);  // Sort in descending order of priority score

    return $priorityScores;
}
function generateReplenishmentInsights($branchStocks, $demandData)
{
    $replenishmentInsights = [];

    foreach ($demandData as $riceType => $totalSold) {
        $totalStock = isset($branchStocks[$riceType]) ? $branchStocks[$riceType] : 0;

        // Store possible insights for the rice type
        $insights = [];

        if ($totalStock < $totalSold) {
            $insights[] = "You need to add more stock for '$riceType' as the current stock ($totalStock) is less than the demand ($totalSold).";
        }
        if ($totalStock >= $totalSold) {
            $insights[] = "For '$riceType', you have sufficient stock to meet demand for the next 30 days.";
        }
        if ($totalStock === 0) {
            $insights[] = "For '$riceType', order at least $totalSold units to meet current demand for the next 30 days.";
        }

        $avgDailyDemand = $totalSold / 30;
        $projectedDemand = $avgDailyDemand * 7;
        $safetyStockBuffer = ceil($projectedDemand * 0.10);
        $totalProjectedStockNeed = $projectedDemand + $safetyStockBuffer;

        $stockToAdd = max(0, $totalProjectedStockNeed - $totalStock);
        if ($stockToAdd > 0) {
            $insights[] = "For '$riceType', consider adding " . number_format($stockToAdd) . " units for the next 7 days.";
        }

        // Store insights as an array
        $replenishmentInsights[$riceType] = $insights ? $insights[array_rand($insights)] : "No specific insights available.";
    }

    return $replenishmentInsights;
}

// Example change in output
$output = [
    'riceVarieties' => array_keys($demandData),
    'branchStocks' => $branchStocks,
    'allocationRecommendations' => $allocationRecommendations,
    'insights' => $replenishmentInsights, // Associative array with rice varieties as keys
    'priorityInsights' => implode("<br>", $priorityInsights)
];



// Call the priority score function
$priorityScores = calculatePriorityScore($branchStocks, $demandData);

// Generate priority-based insights
foreach ($priorityScores as $riceType => $score) {
    // Classify the priority based on the score
    $priorityLevel = 'Low';
    if ($score > 1000) {
        $priorityLevel = 'High';
    } elseif ($score > 500) {
        $priorityLevel = 'Medium';
    }

    $priorityInsights[] = "Top priority: '$riceType' (Priority Score: $score, Priority Level: $priorityLevel)";
}

// Fetch stock data, demand data, and calculate recommendations
fetchStockData($mysqli, $branchStocks, $riceVarieties, $branchId);
fetchDemandData($mysqli, $demandData, $branchId);
calculateStockRecommendations($branchStocks, $demandData, $allocationRecommendations, $replenishmentInsights);
calculateStockReplenishment($branchStocks, $demandData, $allocationRecommendations, $replenishmentInsights);

$replenishmentInsights = generateReplenishmentInsights($branchStocks, $demandData);

// Format the replenishment insights
function processInsights($insights)
{
    return nl2br($insights);  // Convert newlines to HTML line breaks for better display in HTML
}

$output = [
    'riceVarieties' => array_keys($demandData),
    'branchStocks' => $branchStocks,
    'allocationRecommendations' => $allocationRecommendations,
    'insights' => $replenishmentInsights, // Associative array with rice varieties as keys
    'priorityInsights' => implode("<br>", $priorityInsights)
];


header('Content-Type: application/json');
echo json_encode($output);
