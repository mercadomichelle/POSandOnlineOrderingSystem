<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

session_start();

if (!isset($_SESSION["username"])) {
    header("Location: ../login.php");
    exit();
}

$mysqli = new mysqli($host, $user, $password, $db);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$username = $_SESSION["username"];
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
$sql = "SELECT p.prod_id, p.prod_brand, p.prod_name, p.prod_image_path, s.stock_quantity 
        FROM products p 
        LEFT JOIN stocks s ON p.prod_id = s.prod_id
        ORDER BY s.stock_quantity ASC";

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

$riceVarieties = [];
$quantities = [];

$stmt->close();
$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rice Website | Dashboard</title>
    <link rel="stylesheet" href="../styles/admin.css">
    <link href="https://fonts.googleapis.com/css2?family=Sulphur+Point:wght@300;400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>

<body>
    <header>
        <div class="logo">RICE</div>
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
                    <canvas id="mostPurchasedRiceChart"></canvas>
                </div>
                <div class="card2">
                    <h3>Peak Buying Periods</h3>
                    <div class="sorting-container">
                        <select id="timeframe">
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <canvas id="peakBuyingChart"></canvas>

                </div>
            </div>
            <div class="bottom">
                <div class="card3">
                    <h3>Customer Purchase Preferences</h3>
                    <div class="sorting-container">
                        <select id="timeframe1">
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
                    </div>
                    <canvas id="purchasePreferencesChart" width="400" height="200"></canvas>
                </div>
                <div class="bottom1">
                    <div class="card4">
                        <h3>Sales Revenue</h3>
                        <canvas id="salesRevenueChart"></canvas>
                    </div>
                    <div class="card5">
                        <h3>Stock Allocation Across Branches</h3>
                        <canvas id="stockAllocationChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <script>
        function getWeekName(dateString) {
            const date = new Date(dateString);
            const options = {
                weekday: 'short'
            };
            return date.toLocaleDateString('en-US', options); // 'en-US' for English abbreviations
        }

        $(document).ready(function() {
            // Fetch the initial data on page load
            fetchData();
            fetchSalesRevenue();

            // Get the current month (1-12)
            var currentMonth = new Date().getMonth() + 1; // JavaScript months are 0-11

            // Fetch customer preferences for the current month
            fetchCustomerPreferences(currentMonth);

            // Set the dropdown to the current month
            $('#timeframe1').val(currentMonth);

            // Continue updating the data every 5 seconds
            // setInterval(fetchData, 5000);
            // setInterval(fetchSalesRevenue, 5000);

            // Change data fetch based on dropdown selection
            $('#timeframe').change(function() {
                fetchData(); // Fetch data based on the selected timeframe
                fetchSalesRevenue();
            });

            // Listen for changes in the dropdown and fetch data based on the selected month
            $('#timeframe1').change(function() {
                var selectedMonth = $(this).val(); // Get the selected month value
                fetchCustomerPreferences(selectedMonth);
            });
        });

        function fetchData() {
            // Get the selected timeframe
            var timeframe = $('#timeframe').val();

            // Fetch most purchased rice varieties
            $.ajax({
                url: 'dashboard/fetch_most_purchased_rice.php',
                type: 'GET',
                data: {
                    timeframe: timeframe
                }, // Send the selected timeframe
                success: function(data) {
                    var result = JSON.parse(data);
                    // Update bar chart (most purchased rice varieties)
                    chart.data.labels = result.riceVarieties;
                    chart.data.datasets[0].data = result.quantities;
                    chart.update();
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching rice varieties:", error);
                }
            });


            // Fetch peak buying periods with the selected timeframe
            $.ajax({
                url: 'dashboard/fetch_peak_buying_periods.php',
                type: 'GET',
                data: {
                    timeframe: timeframe
                }, // Send the timeframe
                success: function(data) {
                    var result = JSON.parse(data);

                    // Update line chart (peak buying period - total sales)
                    peakBuyingChart.data.labels = result.periods;

                    // Set dataset values
                    peakBuyingChart.data.datasets[0].data = result.total_sales;

                    // Update Y-axis label based on timeframe
                    if (timeframe === 'weekly') {
                        peakBuyingChart.options.scales.x.title.text = 'Weekly'; // Set the label for weekly
                    } else {
                        peakBuyingChart.options.scales.x.title.text = 'Monthly'; // You can customize if needed for monthly
                    }

                    peakBuyingChart.update();
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching peak buying periods:", error);
                }
            });
        }



        // Bar chart for most purchased rice varieties
        var ctx = document.getElementById('mostPurchasedRiceChart').getContext('2d');
        var chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [], // Initialized as empty, filled by AJAX
                datasets: [{
                    label: '', // Set to empty string to remove label
                    data: [], // Initialized as empty, filled by AJAX
                    backgroundColor: [
                        '#FABE7A', '#FF6B6B', '#80CED7', '#7D74FF', '#FDE47F'
                    ],
                    borderColor: [
                        '#F4A261', '#FF4D4D', '#66B2FF', '#6A4CFF', '#FCD034'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    x: {
                        ticks: {
                            callback: function(value, index, ticks) {
                                return this.getLabelForValue(value);
                            },
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

        // Line chart for peak buying period (total sales)
        var ctx2 = document.getElementById('peakBuyingChart').getContext('2d');

        // Create a gradient for the fill
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
                            display: false
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

        function fetchSalesRevenue() {
            var timeframe = $('#timeframe').val(); // Get the selected timeframe

            $.ajax({
                url: 'dashboard/fetch_sales_revenue.php',
                type: 'GET',
                data: {
                    timeframe: timeframe
                }, // Send the timeframe
                success: function(data) {
                    var result = JSON.parse(data);

                    // Update line chart (sales revenue)
                    salesRevenueChart.data.labels = result.periods;

                    // Set dataset values
                    salesRevenueChart.data.datasets[0].data = result.retail_sales; // Retail sales
                    salesRevenueChart.data.datasets[1].data = result.wholesale_sales; // Wholesale sales

                    // Update Y-axis label based on timeframe
                    if (timeframe === 'weekly') {
                        salesRevenueChart.options.scales.x.title.text = 'Weekly'; // Set the label for weekly
                    } else {
                        salesRevenueChart.options.scales.x.title.text = 'Monthly'; // Set the label for monthly
                    }

                    salesRevenueChart.update();
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching sales revenue:", error);
                }
            });
        }


        // Line chart for sales revenue
        var ctx3 = document.getElementById('salesRevenueChart').getContext('2d');

        // Create gradients if needed
        var gradientRetail = ctx3.createLinearGradient(0, 0, 0, 400);
        gradientRetail.addColorStop(0, 'rgba(255, 99, 132, 0.5)');
        gradientRetail.addColorStop(1, 'rgba(255, 99, 132, 0)');

        var gradientWholesale = ctx3.createLinearGradient(0, 0, 0, 400);
        gradientWholesale.addColorStop(0, 'rgba(54, 162, 235, 0.5)');
        gradientWholesale.addColorStop(1, 'rgba(54, 162, 235, 0)');

        var salesRevenueChart = new Chart(ctx3, {
            type: 'line',
            data: {
                labels: [], // Initialized as empty, filled by AJAX
                datasets: [{
                        label: 'Retail Sales',
                        data: [],
                        borderColor: '#FF6384',
                        backgroundColor: gradientRetail,
                        fill: false,
                        tension: 0.3
                    },
                    {
                        label: 'Wholesale',
                        data: [],
                        borderColor: '#36A2EB',
                        backgroundColor: gradientWholesale,
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
                            display: false
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


        // CUSTOMER PURCHASE PREFERENCES
function fetchCustomerPreferences(month) {
    $.ajax({
        url: 'dashboard/fetch_customer_preferences.php',
        type: 'GET',
        data: {
            month: month
        },
        dataType: 'json',
        success: function(data) {
            console.log("Parsed JSON:", data);

            var labels = [];
            var riceVarieties = {};
            var alternativeData = []; // To hold alternative rice percentages

            data.forEach(function(item) {
                // Add the day to the labels array if it's not already there
                if (!labels.includes(item.day)) {
                    labels.push(item.day);
                }

                // Process regular rice varieties
                Object.keys(item.percentages).forEach(function(variety) {
                    if (!riceVarieties[variety]) {
                        riceVarieties[variety] = {
                            label: variety, // Show rice variety name here
                            data: [],
                            backgroundColor: 'rgba(75, 192, 192, 0.2)', // Regular rice color
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1
                        };
                    }
                    riceVarieties[variety].data.push(item.percentages[variety]);
                });

                // Process alternative rice varieties
                if (item.alternatives.length > 0) {
                    item.alternatives.forEach(function(alternative) {
                        let alternativePercentage = item.percentages[alternative] || 0;
                        alternativeData.push({
                            day: item.day,
                            variety: alternative,
                            percentage: alternativePercentage
                        });
                    });
                }
            });

            // Create datasets for regular rice varieties
            var datasets = Object.values(riceVarieties);

            // Prepare dataset for alternative rice with label "Alternative Rice"
            var alternativeDataset = {
                label: "Alternative Rice", // This will be displayed in the legend and for tooltips
                data: [],
                backgroundColor: 'rgba(255, 99, 132, 0.2)', // Alternative rice color
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            };

            // Fill the alternative dataset with values for each day
            labels.forEach(day => {
                const alternativeForDay = alternativeData.find(a => a.day === day);
                alternativeDataset.data.push(alternativeForDay ? alternativeForDay.percentage : 0);
            });

            // Push the alternative dataset to the end of the datasets array
            datasets.push(alternativeDataset);

            // Log the datasets before updating the chart
            console.log("Datasets:", datasets);

            if (labels.length > 0 && datasets.length > 0) {
                purchasePreferencesChart.data.labels = labels;
                purchasePreferencesChart.data.datasets = datasets;
                purchasePreferencesChart.update();
            } else {
                console.warn("No data to display on the chart.");
            }
        },
        error: function(xhr, status, error) {
            console.error("Error fetching customer preferences:", error);
        }
    });
}

// Function to format the date string
function formatDate(dateString) {
    const date = new Date(dateString); // Create a new Date object
    const options = {
        month: 'short',
        day: 'numeric'
    }; // Specify options for formatting
    return date.toLocaleString('en-US', options); // Format the date
}

var ctx4 = document.getElementById('purchasePreferencesChart').getContext('2d');
var purchasePreferencesChart = new Chart(ctx4, {
    type: 'bar',
    data: {
        labels: [], // This will be updated by the AJAX response
        datasets: [] // Filled by the success callback
    },
    options: {
        indexAxis: 'y', // This makes the bar chart horizontal
        scales: {
            x: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Percentage' // Label for x-axis
                }
            },
            y: {
                beginAtZero: true,
                stacked: true // Stacked bar chart
            }
        },
        plugins: {
            legend: {
                position: 'top' // Places the legend at the top of the chart
            },
            tooltip: {
                callbacks: {
                    label: function(tooltipItem) {
                        // Check if it's the alternative rice
                        if (tooltipItem.dataset.label === "Alternative Rice") {
                            return "Alternative Rice"; // Always show "Alternative Rice" for the label
                        }
                        // Display the name of the rice variety and its percentage for regular rice
                        return tooltipItem.dataset.label + ': ' + tooltipItem.raw.toFixed(2) + '%';
                    }
                }
            }
        }
    }
});

    </script>


</body>

</html>