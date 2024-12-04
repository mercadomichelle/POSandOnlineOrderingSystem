<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

session_start();

include('../connection.php');

// Ensure the user is logged in
if (!isset($_SESSION["username"])) {
    header("Location: ../../login.php");
    exit();
}

$username = $_SESSION["username"];

$sql = "SELECT login.id AS login_id, login.first_name, login.last_name, login.branch_id, branches.branch_name 
        FROM login 
        JOIN branches ON login.branch_id = branches.branch_id
        WHERE login.username = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $userData = $result->fetch_assoc();
    $_SESSION["first_name"] = $userData['first_name'];
    $_SESSION["last_name"] = $userData['last_name'];
    $_SESSION["branch_id"] = $userData['branch_id'];  // Make sure branch_id is set
    $_SESSION["login_id"] = $userData['login_id'];
    $_SESSION["branch_name"] = $userData['branch_name'];
} else {
    $_SESSION["first_name"] = "Guest";
    $_SESSION["last_name"] = "";
    $_SESSION["branch_id"] = null;  // Make sure it's set to null if not found
    $_SESSION["login_id"] = "";
    $_SESSION["branch_name"] = "Unknown";
}


// STOCKS NOTIFICATIONS
$sql = "SELECT p.prod_id, p.prod_brand, p.prod_name, p.prod_image_path, 
               COALESCE(SUM(s.stock_quantity), 0) AS stock_quantity 
        FROM products p 
        LEFT JOIN stocks s ON p.prod_id = s.prod_id
        GROUP BY p.prod_id, p.prod_brand, p.prod_name, p.prod_image_path
        ORDER BY stock_quantity ASC";

$result = $mysqli->query($sql);

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


$currentMonth = date('m');
$currentYear = date('Y');
$currentWeek = date('W');

$stmt->close();
$mysqli->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rice Website | Dashboard</title>
    <link rel="icon" href="../favicon.png" type="image/png">
    <link rel="stylesheet" href="../styles/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Sulphur+Point:wght@300;400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>

<body>
    <header>
        <div><img src="../favicon.png" alt="Logo" class="logo"></div>
        <div class="account-info">

            <div class="dropdown notifications-dropdown">
                <img src="../images/notif-icon.png" alt="Notifications" class="notification-icon">
                <div class="dropdown-content" id="notificationDropdown">
                    <p class="notif">Notifications</p>
                    <?php if (empty($notifications)): ?>
                        <a href="#">No new notifications</a>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <a href="stocks/stocks.php"><?php echo $notification; ?></a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <span class="user-name"><?php echo htmlspecialchars($_SESSION["first_name"] . " " . $_SESSION["last_name"]); ?></span>
            <div class="dropdown">
                <img src="../images/account-icon.png" alt="Account">
                <div class="dropdown-content">
                    <a href="../logout.php">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="sidebar">
        <nav>
            <ul>
                <li><a href="" class="current"><img src="../images/dashboard-icon.png" alt="Dashboard">DASHBOARD</a></li>
                <li><a href="products/products.php"><img src="../images/products-icon.png" alt="Products">PRODUCTS</a></li>
                <li><a href="stocks/stocks.php"><img src="../images/stocks-icon.png" alt="Stocks">STOCKS</a></li>
                <li><a href="staffs/staff_list.php"><img src="../images/staffs-icon.png" alt="Staffs">STAFFS</a></li>
            </ul>
        </nav>
        <ul class="reports">
            <li><a href="reports/reports.php"><img src="../images/reports-icon.png" alt="Reports">REPORTS</a></li>
        </ul>
    </div>

    <main>
        <div class="dashboard">
            <div class="upper">
                <div class="card1">
                    <h3>Most Purchased Rice Varieties</h3>
                    <canvas id="mostPurchasedRiceChart" style="max-height: 200px;"></canvas>
                </div>
                <div class="card2">
                    <h3>Peak Buying Periods</h3>
                    <div class="sorting-container">
                        <select id="monthSelector">
                            <option value="">Select Month</option>
                            <option value="1">January</option>
                            <option value="2">February</option>
                            <option value="3">March</option>
                            <option value="4">April</option>
                            <option value="5">May</option>
                            <option value="6">June</option>
                            <option value="7">July</option>
                            <option value="8">August</option>
                            <option value="9">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>

                        <select id="weekSelector">
                            <option value="">Select Week</option>
                            <option value="1">Week 1</option>
                            <option value="2">Week 2</option>
                            <option value="3">Week 3</option>
                            <option value="4">Week 4</option>
                            <option value="5">Week 5</option>
                        </select>

                        <select id="yearSelector">
                            <option value="">Select Year</option>
                            <script>
                                const currentYear = new Date().getFullYear();
                                for (let year = currentYear - 4; year <= currentYear; year++) {
                                    document.write(`<option value="${year}">${year}</option>`);
                                }
                            </script>
                        </select>
                    </div>
                    <canvas id="peakBuyingChart" style="max-height: 200px;"></canvas>
                </div>
            </div>
            <div class="bottom">
                <div class="card3">
                    <h3>Customer Purchase Preferences</h3>
                    <canvas id="purchasePreferencesChart" style="height: 455px; max-height: 455px;"></canvas>
                </div>
                <div class="bottom1">
                    <div class="card4">
                        <h3>Sales Revenue</h3>
                        <canvas id="salesRevenueChart"></canvas>
                    </div>
                    <div class="card-container">
                        <div class="card5">
                            <h3>Recommendation for Stock Allocation Per Branches</h3>
                            <div class="card6">
                                <canvas id="stockAllocationChart" style="height: 180px; max-height: 180px;"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script src="../js/weeks.js"></script>
    <script>
        // FUNCTION TO FETCH DATA 
        $(document).ready(function() {
            var branch_id = <?php echo $_SESSION["branch_id"]; ?>; // Get branch_id from PHP session
            var currentMonth = new Date().getMonth() + 1;
            var currentYear = new Date().getFullYear();
            var currentWeek = getCurrentWeek();

            $('#monthSelector').val(currentMonth);
            $('#yearSelector').val(currentYear);
            $('#weekSelector').val(currentWeek);

            // Fetch data specific to the branch
            fetchData(currentMonth, currentWeek, currentYear, branch_id);
            fetchCustomerPreferences(currentMonth, currentWeek, currentYear, branch_id);

            // Change event listeners for the timeframe selectors
            $('#monthSelector, #weekSelector, #yearSelector').change(function() {
                var branch_id = <?php echo $_SESSION["branch_id"]; ?>; // Get branch_id from PHP session
                var selectedMonth = $('#monthSelector').val();
                var selectedYear = $('#yearSelector').val();
                var selectedWeek = $('#weekSelector').val();

                // Update chart with new data based on selected timeframe
                fetchData(selectedMonth, selectedWeek, selectedYear, branch_id);
                fetchCustomerPreferences(selectedMonth, selectedWeek, selectedYear, branch_id);
            });

            // Fetch initial data
            fetchBuying(); // Peak buying periods
            fetchSalesRevenue(); // Sales Revenue

            // Change event listeners for other selectors
            $('#monthSelector, #weekSelector, #yearSelector').change(function() {
                var branch_id = <?php echo $_SESSION["branch_id"]; ?>; // Get branch_id from PHP session
                var selectedMonth = $('#monthSelector').val();
                var selectedYear = $('#yearSelector').val();
                var selectedWeek = $('#weekSelector').val();

                fetchData(selectedMonth, selectedWeek, selectedYear, branch_id);
                fetchCustomerPreferences(selectedMonth, selectedWeek, selectedYear, branch_id);
                fetchBuying();
                fetchSalesRevenue();
            });
        });


        // CUSTOMER PREFERENCES
        function fetchCustomerPreferences(month, week, year, branch_id) {
            $.ajax({
                url: 'dashboard/fetch_customer_preferences.php?timestamp=' + new Date().getTime(),
                type: 'GET',
                data: {
                    month: month,
                    week: week, // Pass the selected week
                    year: year, // Pass the selected year
                    branch_id: branch_id // Pass the branch_id from the session
                },
                dataType: 'json',
                success: function(data) {
                    var labels = [];
                    var riceVarieties = {};
                    var alternativeNames = {};

                    const colorPalette = [
                        'rgba(255, 99, 132, 0.8)',
                        'rgba(54, 162, 235, 0.8)',
                        'rgba(75, 192, 192, 0.8)',
                        'rgba(153, 102, 255, 0.8)',
                        'rgba(32, 189, 103, 0.8)',
                        'rgba(255, 205, 86, 0.8)',
                    ];

                    // Process the fetched data
                    data.forEach(function(item) {
                        if (!labels.includes(item.day)) {
                            labels.push(item.day);
                        }

                        alternativeNames[item.day] = item.alternatives;

                        Object.keys(item.percentages).forEach(function(variety) {
                            if (!riceVarieties[variety]) {
                                let backgroundColor, borderColor;
                                const index = Object.keys(riceVarieties).length;

                                if (variety === "Alternative Rice") {
                                    backgroundColor = 'rgba(255, 159, 64, 0.8)';
                                    borderColor = 'rgba(255, 159, 64, 1)';
                                } else {
                                    backgroundColor = colorPalette[index % colorPalette.length];
                                    borderColor = colorPalette[index % colorPalette.length].replace('0.4', '1');
                                }

                                riceVarieties[variety] = {
                                    label: variety,
                                    data: [],
                                    backgroundColor: backgroundColor,
                                    borderColor: borderColor,
                                    borderWidth: 1,
                                    zIndex: 0
                                };
                            }

                            riceVarieties[variety].data.push(item.percentages[variety]);
                        });
                    });

                    var datasets = Object.values(riceVarieties);

                    datasets.forEach(function(dataset) {
                        dataset.totalValue = dataset.data.reduce((sum, value) => sum + value, 0);
                    });

                    datasets.sort((a, b) => a.totalValue - b.totalValue);

                    datasets.forEach(function(dataset, index) {
                        dataset.zIndex = index;
                    });

                    // Update the chart with the new data
                    purchasePreferencesChart.data.labels = labels;
                    purchasePreferencesChart.data.datasets = datasets;
                    purchasePreferencesChart.update();

                    purchasePreferencesChart.options.plugins.tooltip.callbacks.alternativeNames = alternativeNames;
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching customer preferences:", error);
                }
            });
        }


        var ctx4 = document.getElementById('purchasePreferencesChart').getContext('2d');
        var purchasePreferencesChart = new Chart(ctx4, {
            type: 'bar',
            data: {
                labels: [], // Updated by the AJAX response
                datasets: [] // Filled by the success callback
            },
            options: {
                maintainAspectRatio: false, // Prevents Chart.js from enforcing aspect ratio
                indexAxis: 'y', // Horizontal bar chart
                scales: {
                    x: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Percentage' // X-axis label
                        }
                    },
                    y: {
                        beginAtZero: true,
                        stacked: true // Stacked chart
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            font: {
                                size: 11
                            },
                            usePointStyle: true // Changes legend box to a circle
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(tooltipItem) {
                                // Get the day index (label) to match it with alternative names
                                var dayLabel = purchasePreferencesChart.data.labels[tooltipItem.dataIndex];
                                var altNames = purchasePreferencesChart.options.plugins.tooltip.callbacks.alternativeNames;

                                if (tooltipItem.dataset.label === "Alternative Rice") {
                                    var alternatives = altNames[dayLabel] || []; // Fallback if no alternatives
                                    var alternativeNamesList = alternatives.join(", ");
                                    return `Alternative (${alternativeNamesList}): ${tooltipItem.raw.toFixed(2)}%`;
                                }

                                return `${tooltipItem.dataset.label}: ${tooltipItem.raw.toFixed(2)}%`;
                            }
                        }
                    }

                }
            }
        });

        
        function fetchSalesRevenue() {
            var selectedMonth = $('#monthSelector').val();
            var selectedWeek = $('#weekSelector').val();
            var selectedYear = $('#yearSelector').val();
            var timeframe = $('#timeframe').val();
            var branch_id = <?php echo $_SESSION["branch_id"]; ?>; // Get branch_id from PHP session

            var requestData = {
                timeframe: timeframe,
                month: selectedMonth,
                week: selectedWeek,
                year: selectedYear,
                branch_id: branch_id // Include branch_id in the request
            };

            $.ajax({
                url: 'dashboard/fetch_sales_revenue.php',
                type: 'GET',
                data: requestData,
                success: function(data) {
                    var result = JSON.parse(data);

                    if (result.periods && result.in_store_retail && result.in_store_wholesale && result.online_wholesale) {
                        salesRevenueChart.data.labels = result.periods;
                        salesRevenueChart.data.datasets[0].data = result.in_store_retail;
                        salesRevenueChart.data.datasets[1].data = result.in_store_wholesale;
                        salesRevenueChart.data.datasets[2].data = result.online_wholesale;

                        if (selectedWeek) {
                            salesRevenueChart.options.scales.x.title.text = `Week ${selectedWeek}`;
                        } else if (selectedMonth) {
                            salesRevenueChart.options.scales.x.title.text = 'Days of the Month';
                        } else if (selectedYear) {
                            salesRevenueChart.options.scales.x.title.text = 'Monthly';
                        } else {
                            salesRevenueChart.options.scales.x.title.text = 'Yearly';
                        }

                        salesRevenueChart.update();
                    } else {
                        console.error("Data format error: Missing required fields.");
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching sales revenue:", error);
                }
            });
        }


        var ctx3 = document.getElementById('salesRevenueChart').getContext('2d');
        var gradientRetail = ctx3.createLinearGradient(0, 0, 0, 400);
        gradientRetail.addColorStop(0, 'rgba(255, 99, 132, 0.5)');
        gradientRetail.addColorStop(1, 'rgba(255, 99, 132, 0)');

        var gradientWholesale = ctx3.createLinearGradient(0, 0, 0, 400);
        gradientWholesale.addColorStop(0, 'rgba(54, 162, 235, 0.5)');
        gradientWholesale.addColorStop(1, 'rgba(54, 162, 235, 0)');

        var gradientOnlineWholesale = ctx3.createLinearGradient(0, 0, 0, 400);
        gradientOnlineWholesale.addColorStop(0, 'rgba(75, 192, 192, 0.5)');
        gradientOnlineWholesale.addColorStop(1, 'rgba(75, 192, 192, 0)');

        var salesRevenueChart = new Chart(ctx3, {
            type: 'line',
            data: {
                labels: [], // Initialized as empty, filled by AJAX
                datasets: [{
                        label: 'In-Store Retail',
                        data: [],
                        borderColor: '#FF6384',
                        backgroundColor: gradientRetail,
                        fill: false,
                        tension: 0.3
                    },
                    {
                        label: 'In-Store Wholesale',
                        data: [],
                        borderColor: '#36A2EB',
                        backgroundColor: gradientWholesale,
                        fill: false,
                        tension: 0.3
                    },
                    {
                        label: 'Online Wholesale',
                        data: [],
                        borderColor: '#4BC0C0',
                        backgroundColor: gradientOnlineWholesale,
                        fill: false,
                        tension: 0.3
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: '' // Dynamically updated by AJAX
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Total Amount (PHP)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        labels: {
                            font: {
                                size: 11
                            },
                            usePointStyle: false,
                            boxWidth: 30,
                            boxHeight: 1,
                            padding: 15
                        }
                    },
                    tooltip: {
                        bodyFont: {
                            size: 10
                        }
                    }
                },
                layout: {
                    padding: {
                        top: 20,
                        bottom: 5
                    }
                }
            }
        });



        // PEAK BUYING PERIODS
        function fetchBuying() {
            var selectedMonth = $('#monthSelector').val();
            var selectedWeek = $('#weekSelector').val();
            var selectedYear = $('#yearSelector').val();
            var branchId = <?php echo $_SESSION["branch_id"]; ?>; // Get branch_id from PHP session (No need for hidden input)

            $.ajax({
                url: 'dashboard/fetch_peak_buying_periods.php',
                type: 'GET',
                data: {
                    month: selectedMonth,
                    week: selectedWeek,
                    year: selectedYear,
                    branch_id: branchId // Send branch_id in the request
                },
                success: function(data) {
                    var result = JSON.parse(data);

                    peakBuyingChart.data.labels = result.periods;
                    peakBuyingChart.data.datasets[0].data = result.total_sales;

                    if (selectedWeek) {
                        peakBuyingChart.options.scales.x.title.text = `Week ${selectedWeek}`;
                    } else if (selectedMonth) {
                        peakBuyingChart.options.scales.x.title.text = 'Days of the Month';
                    } else if (selectedYear) {
                        peakBuyingChart.options.scales.x.title.text = 'Monthly';
                    } else {
                        peakBuyingChart.options.scales.x.title.text = 'Yearly';
                    }

                    peakBuyingChart.update();
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching peak buying periods:", error);
                }
            });
        }


        var ctx2 = document.getElementById('peakBuyingChart').getContext('2d');
        var gradient = ctx2.createLinearGradient(0, 0, 0, 400); // Adjust the height as needed
        gradient.addColorStop(0, 'rgba(128, 206, 215, 0.5)'); // Dark color at the top
        gradient.addColorStop(1, 'rgba(128, 206, 215, 0)'); // Lighter color at the bottom
        var peakBuyingChart = new Chart(ctx2, {
            type: 'line',
            data: {
                labels: [], // Initialized as empty, filled by AJAX
                datasets: [{
                    label: '',
                    data: [],
                    borderColor: '#80CED7',
                    backgroundColor: gradient, // Use the gradient here
                    fill: true, // Enable filling
                    tension: 0.3
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: '' // Default text (optional)
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Total Amount (PHP)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false,
                        labels: {
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        bodyFont: {
                            size: 10
                        }
                    }
                },
                layout: {
                    padding: {
                        top: 20,
                        bottom: 5
                    }
                }
            }
        });


        // FETCH MOST PURCHASED RICE VARIETIES DATA
        function fetchData(selectedMonth, selectedWeek, selectedYear, branch_id) {
            $.ajax({
                url: 'dashboard/fetch_most_purchased_rice.php',
                type: 'GET',
                data: {
                    month: selectedMonth,
                    week: selectedWeek,
                    year: selectedYear,
                    branch_id: branch_id
                },
                success: function(data) {
                    try {
                        // Parse JSON response
                        var result = JSON.parse(data);

                        // Update chart data
                        chart.data.labels = result.riceVarieties;
                        chart.data.datasets[0].data = result.quantities;
                        chart.update(); // Redraw the chart
                    } catch (error) {
                        console.error("Error parsing data:", error);
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching data:", error);
                }
            });
        }

        // MOST PURCHASED RICE VARIETIES CHART
        var ctx = document.getElementById('mostPurchasedRiceChart').getContext('2d');
        var chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [], // Dynamically filled
                datasets: [{
                    label: 'Rice Varieties', // Updated label
                    data: [], // Dynamically filled
                    backgroundColor: ['#FABE7A', '#FF6B6B', '#80CED7', '#7D74FF', '#FDE47F'],
                    borderColor: ['#F4A261', '#FF4D4D', '#66B2FF', '#6A4CFF', '#FCD034'],
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 0,
                            autoSkip: false,
                            font: {
                                size: 12
                            }
                        },
                        title: {
                            display: true,
                            text: 'Rice Varieties'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Quantity Sold'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        bodyFont: {
                            size: 10
                        }
                    }
                },
                layout: {
                    padding: {
                        top: 20,
                        bottom: 5
                    }
                }
            }
        });





        // Function to get the current week number
        function getCurrentWeek() {
            var currentDate = new Date();
            var startDate = new Date(currentDate.getFullYear(), 0, 1);
            var days = Math.floor((currentDate - startDate) / (24 * 60 * 60 * 1000));
            return Math.ceil((days + 1) / 7);
        }


        // STOCK ALLOCATION
$.ajax({
    url: 'dashboard/fetch_stock_allocation.php',
    type: 'GET',
    success: function(data) {
        // console.log("Fetched data:", data);

        const riceVarieties = data.riceVarieties || []; // Array of rice types
        const branchStocks = data.branchStocks || {}; // Object: { riceType: stockQuantity }
        const maxStocks = data.maxStocks || {}; // Object: { riceType: maxStock }

        // Ensure valid data exists
        if (branchStocks && riceVarieties.length > 0) {
            const datasets = [
                {
                    label: 'Stocks',
                    data: riceVarieties.map(rice => branchStocks[rice] || 0), // Single branch stock data
                    backgroundColor: 'rgba(32, 189, 103, 0.8)', // You can adjust this color
                    borderWidth: 1
                }
            ];

            // Max Stock Dataset (for comparison)
            const maxStockDataset = {
                label: 'Maximum Stock',
                data: riceVarieties.map(rice => maxStocks[rice] || 0),
                borderWidth: 2,
                type: 'line',
                fill: false,
                pointStyle: 'circle',
                pointRadius: 5,
                pointBackgroundColor: 'black',
                borderColor: 'gray' // Optional: set line color for max stock dataset
            };

            // Setup chart context
            const ctx = document.getElementById('stockAllocationChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: riceVarieties,
                    datasets: [...datasets, maxStockDataset]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    aspectRatio: 2,
                    plugins: {
                        title: {
                            display: false,
                        },
                        legend: {
                            display: true,
                            position: 'top',
                            labels: {
                                usePointStyle: true,
                                boxWidth: 10,
                                filter: function(item, chart) {
                                    // Filter out the "Maximum Stock" dataset from the legend
                                    return item.text !== 'Maximum Stock';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: false,
                            },
                            stacked: false, // Keep bars grouped
                        },
                        y: {
                            title: {
                                display: true,
                                text: 'Stock Quantity'
                            },
                            beginAtZero: true
                        }
                    }
                }
            });

        } else {
            console.error("Invalid branchStocks or riceVarieties is empty.");
        }
    },
    error: function(xhr, status, error) {
        console.error('Error fetching stock allocation data:', error);
    }
});

    </script>


</body>

</html>