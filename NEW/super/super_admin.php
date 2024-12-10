<?php
session_start();
include('../connection.php');

// Ensure the user is logged in
if (!isset($_SESSION["username"])) {
    header("Location: ../login.php");
    exit();
}

$username = $_SESSION["username"];

// Handle branch selection form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['branch_id'])) {
    $selectedBranch = intval($_POST['branch_id']);
    $_SESSION['selected_branch'] = $selectedBranch;
    header("Location: " . $_SERVER['PHP_SELF']); // Redirect to prevent form resubmission
    exit();
}

// Use selected branch from session
$selectedBranch = isset($_SESSION['selected_branch']) ? $_SESSION['selected_branch'] : null;

// Fetch user data for display
$sql = "SELECT first_name, last_name FROM login WHERE username = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $userData = $result->fetch_assoc();
    $_SESSION["first_name"] = $userData['first_name'];
    $_SESSION["last_name"] = $userData['last_name'];
} else {
    $_SESSION["first_name"] = "Guest";
    $_SESSION["last_name"] = "";
}

// STOCKS NOTIFICATIONS
$sql = "SELECT p.prod_id, p.prod_brand, p.prod_name, p.prod_image_path, 
               COALESCE(SUM(s.stock_quantity), 0) AS stock_quantity 
        FROM products p 
        LEFT JOIN stocks s ON p.prod_id = s.prod_id";

if ($selectedBranch) {
    $sql .= " WHERE s.branch_id = ?";
}

$sql .= " GROUP BY p.prod_id, p.prod_brand, p.prod_name, p.prod_image_path
          ORDER BY stock_quantity ASC";

// Debugging: Print out the final query to check if it's correct
// echo $sql; // Uncomment to debug

$stmt = $mysqli->prepare($sql);

// Bind branch_id if selected
if ($selectedBranch) {
    $stmt->bind_param("i", $selectedBranch);
}

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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>

<body>
    <header>
        <div><img src="../favicon.png" alt="Logo" class="logo"></div>


        <form method="POST" id="branchForm">
            <select class="branch-selector" id="branchSelector" name="branch_id" onchange="this.form.submit()">
                <option value="">Select Branch</option>
                <option value="1" <?php echo $selectedBranch == 1 ? 'selected' : ''; ?>>Calero</option>
                <option value="2" <?php echo $selectedBranch == 2 ? 'selected' : ''; ?>>Bauan</option>
                <option value="3" <?php echo $selectedBranch == 3 ? 'selected' : ''; ?>>San Pascual</option>
            </select>
        </form>

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
                                for (let year = currentYear - 1; year <= currentYear; year++) {
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
                                <canvas id="stockAllocationChart" style="height: 200px; max-height: 200px;"></canvas>
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

                        // Update chart title based on selected period
                        if (selectedWeek) {
                            salesRevenueChart.options.scales.x.title.text = `Week ${selectedWeek}`;
                        } else if (selectedMonth) {
                            salesRevenueChart.options.scales.x.title.text = 'Days of the Month';
                        } else if (selectedYear) {
                            salesRevenueChart.options.scales.x.title.text = 'Monthly';
                        } else {
                            salesRevenueChart.options.scales.x.title.text = 'Yearly';
                        }

                        // Set tooltip callback with shorter insights and word wrapping
                        salesRevenueChart.options.plugins.tooltip.callbacks = {
                            label: function(tooltipItem) {
                                var sales = tooltipItem.raw;
                                var period = tooltipItem.label;
                                var insight = "";
                                var currentIndex = tooltipItem.dataIndex;
                                var previousSales = currentIndex > 0 ? salesRevenueChart.data.datasets[tooltipItem.datasetIndex].data[currentIndex - 1] : null;

                                // Shortened insights based on sales value
                                if (sales > 20000) {
                                    insight = "Sales on " + period + " are strong. Consider leveraging targeted promotions to maintain growth.";
                                } else if (sales < 5000) {
                                    insight = "Sales on " + period + " are low. Review marketing strategies or promotions to boost performance.";
                                } else {
                                    insight = "Sales for " + period + " are steady. Continue with current strategies to maintain momentum.";
                                }

                                // Comparison to the previous period (day, week, or month)
                                if (previousSales !== null) {
                                    const absoluteChange = sales - previousSales; // Absolute difference in sales

                                    // Case when sales are higher than the previous period
                                    if (absoluteChange > 0) {
                                        insight += ` Sales increased by ₱${absoluteChange.toLocaleString()} compared to last period.`;
                                    }
                                    // Case when sales are lower than the previous period
                                    else if (absoluteChange < 0) {
                                        insight += ` Sales decreased by ₱${Math.abs(absoluteChange).toLocaleString()} compared to last period.`;
                                    }
                                    // Case when sales are the same as the previous period
                                    else {
                                        insight += " Sales are consistent with the previous period.";
                                    }
                                }

                                // Wrap insight text if it's too long
                                const wrappedInsights = wordWrap(insight, 60); // Wrap at 60 characters

                                // Return the tooltip content with wrapped insights
                                return [
                                    period + ": ₱" + sales.toLocaleString(), // Sales display
                                    ...wrappedInsights // Spread the wrapped insight lines into the array
                                ];
                            }
                        };


                        // Update the chart with new data
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

        // Word wrap function to split text into chunks based on max width
        function wordWrap(str, maxWidth) {
            const regex = new RegExp(`(.{1,${maxWidth}})(\\s|$)`, 'g');
            return str.match(regex) || [str];
        }

        // Initialize the salesRevenueChart with gradients for each dataset
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
                                size: 12
                            },
                            usePointStyle: false,
                            boxWidth: 30,
                            boxHeight: 1,
                            padding: 15
                        }
                    },
                    tooltip: {
                        bodyFont: {
                            size: 12
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






        function fetchBuying() {
            var selectedMonth = $('#monthSelector').val();
            var selectedWeek = $('#weekSelector').val();
            var selectedYear = $('#yearSelector').val();
            var branchId = <?php echo $_SESSION["branch_id"]; ?>;

            $.ajax({
                url: 'dashboard/fetch_peak_buying_periods.php',
                type: 'GET',
                data: {
                    month: selectedMonth,
                    week: selectedWeek,
                    year: selectedYear,
                    branch_id: branchId
                },
                success: function(data) {
                    var result = JSON.parse(data);

                    peakBuyingChart.data.labels = result.periods;
                    peakBuyingChart.data.datasets[0].data = result.total_sales;

                    // Word wrap function to split text into chunks based on max width
                    function wordWrap(str, maxWidth) {
                        const regex = new RegExp(`(.{1,${maxWidth}})(\\s|$)`, 'g');
                        return str.match(regex) || [str];
                    }

                    // Define tooltip callbacks
                    var tooltipCallbacks = {
                        label: function(tooltipItem) {
                            var sales = tooltipItem.raw;
                            var period = tooltipItem.label;
                            var insight = "";
                            var currentIndex = tooltipItem.dataIndex;
                            var previousSales = currentIndex > 0 ? peakBuyingChart.data.datasets[0].data[currentIndex - 1] : null;

                            // Base insight depending on sales value
                            if (sales > 20000) {
                                insight = "The sales on " + period + " are exceptionally high. Consider leveraging this momentum with targeted promotions.";
                            } else if (sales < 5000) {
                                insight = "Sales on " + period + " are relatively low. It might be beneficial to review marketing efforts or store promotions.";
                            } else {
                                insight = "The sales for " + period + " are in line with average trends, indicating steady customer interest.";
                            }
                            // Add comparison to the previous period (day, week, or month)
                            if (previousSales !== null) {
                                const absoluteChange = sales - previousSales; // Absolute difference in sales

                                // Case when sales are higher than the previous period
                                if (absoluteChange > 0) {
                                    insight += ` Sales increased by ₱${absoluteChange.toLocaleString()} compared to last period, indicating growth.`;
                                }
                                // Case when sales are lower than the previous period
                                else if (absoluteChange < 0) {
                                    insight += ` Sales decreased by ₱${Math.abs(absoluteChange).toLocaleString()} compared to last period. Consider revising strategies.`;
                                }
                                // Case when sales are the same as the previous period
                                else {
                                    insight += " Sales are steady compared to last period, with no significant change.";
                                }
                            }


                            // Wrap insight text if it's too long
                            const wrappedInsights = wordWrap(insight, 60); // Wrap at 60 characters

                            // Return the tooltip content
                            return [
                                period + ": ₱" + sales.toLocaleString(), // Sales display
                                ...wrappedInsights // Spread the wrapped insight lines into the array
                            ];
                        }
                    };

                    // Adjust the X-axis label based on selected filters
                    if (selectedWeek) {
                        peakBuyingChart.options.scales.x.title.text = `Week ${selectedWeek}`;
                    } else if (selectedMonth) {
                        peakBuyingChart.options.scales.x.title.text = 'Days of the Month';
                    } else if (selectedYear) {
                        peakBuyingChart.options.scales.x.title.text = 'Monthly';
                    } else {
                        peakBuyingChart.options.scales.x.title.text = 'Yearly';
                    }

                    // Update tooltip
                    peakBuyingChart.options.plugins.tooltip.callbacks = tooltipCallbacks;

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
                            size: 12
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

                        // Attach insights to chart meta for tooltip usage
                        chart.data.datasets[0].insights = result.insights;

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
                    borderWidth: 1,
                    insights: [] // Store insights dynamically
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
                        callbacks: {
                            title: function(context) {
                                // Use the label as the title
                                return context[0].label;
                            },
                            label: function(context) {
                                // Retrieve relevant data
                                var quantity = context.raw;
                                var insights = context.dataset.insights || [];

                                // Correctly access the insight for the current bar
                                var insight = insights[context.dataIndex] || "No additional insights.";

                                // Utility function to wrap text
                                function wordWrap(str, maxWidth) {
                                    const regex = new RegExp(`(.{1,${maxWidth}})(\\s|$)`, 'g');
                                    return str.match(regex) || [str];
                                }

                                // Wrap insight text
                                const wrappedInsights = wordWrap(insight, 60);

                                // Combine data into tooltip
                                return [
                                    `Sold: ${quantity} units`,
                                    `Insight:`,
                                    ...wrappedInsights // Spread the wrapped insight lines into the array
                                ];
                            }
                        },
                        bodyFont: {
                            size: 12
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
                            labels.push(item.day); // Add day to labels
                        }

                        // Store alternative suggestions for each day
                        alternativeNames[item.day] = item.alternatives;

                        // Populate riceVarieties with quantities
                        Object.keys(item.quantities).forEach(function(variety) {
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
                                    order: 0 // Initial order value
                                };
                            }

                            riceVarieties[variety].data.push(item.quantities[variety]);
                        });
                    });

                    var datasets = Object.values(riceVarieties);

                    // Compute total values for sorting
                    datasets.forEach(function(dataset) {
                        dataset.totalValue = dataset.data.reduce((sum, value) => sum + value, 0);
                    });

                    // Sort datasets by totalValue in ascending order (smaller on top)
                    datasets.sort((a, b) => a.totalValue - b.totalValue);

                    // Reassign 'order' based on sorted values to control stacking
                    datasets.forEach(function(dataset, index) {
                        dataset.order = index; // Use order for stacking control
                    });

                    // Update the chart with the new data
                    purchasePreferencesChart.data.labels = labels;
                    purchasePreferencesChart.data.datasets = datasets;
                    purchasePreferencesChart.update();
                    purchasePreferencesChart.options.plugins.tooltip.callbacks = {
                        label: function(tooltipItem) {
                            var dataset = tooltipItem.dataset;
                            var index = tooltipItem.dataIndex;
                            var variety = dataset.label; // The rice variety
                            var quantity = dataset.data[index]; // Quantity for this data point
                            var day = purchasePreferencesChart.data.labels[index]; // Get the corresponding day

                            // Fetch the day's data
                            var dayData = data.find(item => item.day === day);
                            var insights = dayData ? dayData.insights : "No insights available.";
                            var alternativeRice = dayData && dayData.alternatives.length > 0 ? dayData.alternatives[0] : "No alternative";

                            var label = [];

                            if (variety === "Alternative Rice") {
                                // Show alternative rice variety and insight with line breaks
                                label.push(`Alternative Rice: ${alternativeRice}`);
                                label.push(`Insight:\n${insights}`);
                            } else {
                                // Show regular rice variety and insight with line breaks
                                label.push(`${variety}: ${quantity} units sold`);
                                label.push(`Insight:\n${insights}`);
                            }

                            return label;
                        }
                    };


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
                            text: 'Quantity' // Updated X-axis label
                        }
                    },
                    y: {
                        beginAtZero: true,
                        stacked: true // Stacked chart for cumulative quantities
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
                                    var alternatives = altNames[dayLabel] || ["No alternative available"]; // Fallback if no alternatives
                                    var alternativeNamesList = alternatives.join(", ");
                                    return `Alternative (${alternativeNamesList}): ${tooltipItem.raw} units`;
                                }

                                return `${tooltipItem.dataset.label}: ${tooltipItem.raw} units`;
                            }
                        }
                    }
                }
            }
        });








        $(document).ready(function() {
            // Initialize the chart
            const ctx = document.getElementById('stockAllocationChart').getContext('2d');
            const chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: [], // Empty initially
                    datasets: [{
                            label: 'Stocks',
                            data: [],
                            backgroundColor: 'rgba(128, 206, 215, 0.8)',
                            borderWidth: 1
                        },
                        {
                            label: 'Maximum Stock',
                            data: [],
                            borderWidth: 2,
                            type: 'line',
                            fill: false,
                            pointStyle: 'circle',
                            pointRadius: 5,
                            pointBackgroundColor: 'red',
                            borderColor: 'red',
                            hoverBackgroundColor: 'red'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(tooltipItem) {
                                    const riceType = chart.data.labels[tooltipItem.dataIndex];
                                    const stock = chart.data.datasets[0].data[tooltipItem.dataIndex];
                                    const recommendation = chart.data.datasets[1].data[tooltipItem.dataIndex];
                                    const insights = tooltipItem.chart.$insights || {}; // Insights object
                                    const insightText = insights[riceType] || "No insights available.";

                                    // Utility function to split text into chunks
                                    function wordWrap(str, maxWidth) {
                                        const regex = new RegExp(`(.{1,${maxWidth}})(\\s|$)`, 'g');
                                        return str.match(regex) || [str];
                                    }

                                    // Wrap insight text
                                    const wrappedInsights = wordWrap(insightText, 60);

                                    // Create the tooltip text
                                    return [
                                        `Rice Type: ${riceType}`,
                                        `Stock: ${stock.toFixed(0)}`,
                                        `Recommendation: `,
                                        ...wrappedInsights // Spread the wrapped insight lines into the array
                                    ];
                                }

                            }
                        }
                    }
                }
            });

            $.ajax({
                url: 'dashboard/fetch_stock_allocation.php',
                type: 'GET',
                success: function(data) {
                    console.log("Received data:", data);

                    const riceVarieties = data.riceVarieties || [];
                    const branchStocks = data.branchStocks || {};
                    const allocationRecommendations = data.allocationRecommendations || {};
                    const insights = data.insights || {};

                    // Attach insights to the chart instance
                    chart.$insights = insights;

                    // If the data is valid, update the chart
                    if (data.riceVarieties && data.insights) {
                        chart.$insights = data.insights; // Attach insights object
                        chart.data.labels = data.riceVarieties;
                        chart.data.datasets[0].data = data.riceVarieties.map(rice => data.branchStocks[rice] || 0);
                        chart.data.datasets[1].data = data.riceVarieties.map(rice => data.allocationRecommendations[rice] || 0);
                        chart.update();
                    } else {
                        console.error("API response invalid or missing required fields:", data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching stock allocation data:', error);
                }
            });
        });
    </script>


</body>

</html>