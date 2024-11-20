<?php
session_start();

include('../../connection.php');

if (!isset($_SESSION["username"])) {
    header("Location: ../../login.php");
    exit();
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



$stmt->close();
$mysqli->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rice Website | Reports</title>
    <link rel="icon" href="../../favicon.png" type="image/png">
    <link rel="stylesheet" href="../../styles/reports.css">
    <link href="https://fonts.googleapis.com/css2?family=Sulphur+Point:wght@300;400;700&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
</head>

<body>
    <header>
        <div><img src="../../favicon.png" alt="Logo" class="logo"></div>
        <div class="account-info">

            <div class="dropdown notifications-dropdown">
                <img src="../../images/notif-icon.png" alt="Notifications" class="notification-icon">
                <div class="dropdown-content" id="notificationDropdown">
                    <p class="notif">Notifications</p>
                    <?php if (empty($notifications)): ?>
                        <a href="#">No new notifications</a>
                    <?php else: ?>
                        <?php foreach ($notifications as $notification): ?>
                            <a href="../stocks/stocks.php"><?php echo $notification; ?></a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <span class="user-name"><?php echo htmlspecialchars($_SESSION["first_name"] . " " . $_SESSION["last_name"]); ?></span>
            <div class="dropdown">
                <img src="../../images/account-icon.png" alt="Account">
                <div class="dropdown-content">
                    <a href="../../logout.php">Logout</a>
                </div>
            </div>
        </div>
    </header>

    <div class="sidebar">
        <nav>
            <ul>
                <li><a href="../admin.php"><img src="../../images/dashboard-icon.png" alt="Dashboard">DASHBOARD</a></li>
                <li><a href="../products/products.php"><img src="../../images/products-icon.png" alt="Products">PRODUCTS</a></li>
                <li><a href="../stocks/stocks.php"><img src="../../images/stocks-icon.png" alt="Stocks">STOCKS</a></li>
                <li><a href="../staffs/staff_list.php"><img src="../../images/staffs-icon.png" alt="Staffs">STAFFS</a></li>
            </ul>
        </nav>
        <ul class="reports">
            <li><a class="current"><img src="../../images/reports-icon.png" alt="Reports">REPORTS</a></li>
        </ul>
    </div>

    <main>
        <div class="dashboard">
            <div class="upper">
                <div class="card1">
                    <h3>Wholesales Report</h3>
                    <div class="sorting-container">
                        <select id="timeframe">
                            <option value="monthly">Monthly</option>
                            <option value="weekly">Weekly</option>
                        </select>
                    </div>
                    <canvas id="wholesaleChart"></canvas>
                </div>
                <div class="card2">
                    <h3>Retail Sales Report</h3>
                    <canvas id="retailSalesChart"></canvas>
                </div>
                <div class="card3">
                    <h3>Online Sales Report</h3>
                    <canvas id="onlineSalesChart"></canvas>
                </div>
            </div>
            <div class="upper1">
                <div class="card4">
                    <h3>Detailed Wholesales Report</h3>
                    <div class="btn1">
                        <a href="data_report.php?type=wholesale&source=in-store&timeframe=daily" target="_blank"><button class="daily">Daily</button></a>
                        <a href="data_report.php?type=wholesale&source=in-store&timeframe=weekly" target="_blank"><button class="monthly">Weekly</button></a>
                    </div>
                    <div class="btn2">
                        <a href="data_report.php?type=wholesale&source=in-store&timeframe=monthly" target="_blank"><button class="daily">Monthly</button></a>
                        <a href="data_report.php?type=wholesale&source=in-store&timeframe=yearly" target="_blank"><button class="monthly">Yearly</button></a>
                    </div>
                </div>

                <div class="card5">
                    <h3>Detailed Retail Sales Report</h3>
                    <div class="btn1">
                        <a href="data_report.php?type=retail&source=in-store&timeframe=daily" target="_blank"><button class="daily">Daily</button></a>
                        <a href="data_report.php?type=retail&source=in-store&timeframe=weekly" target="_blank"><button class="monthly">Weekly</button></a>
                    </div>
                    <div class="btn2">
                        <a href="data_report.php?type=retail&source=in-store&timeframe=monthly" target="_blank"><button class="daily">Monthly</button></a>
                        <a href="data_report.php?type=retail&source=in-store&timeframe=yearly" target="_blank"><button class="monthly">Yearly</button></a>
                    </div>
                </div>

                <div class="card6">
                    <h3>Detailed Online Sales Report</h3>
                    <div class="btn1">
                        <a href="data_report.php?type=wholesale&source=online&timeframe=daily" target="_blank"><button class="daily">Daily</button></a>
                        <a href="data_report.php?type=wholesale&source=online&timeframe=weekly" target="_blank"><button class="monthly">Weekly</button></a>
                    </div>
                    <div class="btn2">
                        <a href="data_report.php?type=wholesale&source=online&timeframe=monthly" target="_blank"><button class="daily">Monthly</button></a>
                        <a href="data_report.php?type=wholesale&source=online&timeframe=yearly" target="_blank"><button class="monthly">Yearly</button></a>
                    </div>
                </div>


                <div class="card7">
                    <h3>Sales Distribution by Channel</h3>
                    <canvas id="salesDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </main>


    <script src="../../js/notif.js"></script>

    <script>
        function fetchSalesDistribution() {
            $.ajax({
                url: 'fetch_sales_distribution.php',
                type: 'GET',
                success: function(data) {
                    console.log("Data received:", data);

                    var result = JSON.parse(data);
                    var labels = result.map(function(item) {
                        return item.order_source + ' - ' + item.order_type; // Combine source and type for labels
                    });
                    var orderCounts = result.map(function(item) {
                        return item.order_count;
                    });

                    // Update the purchase preferences chart
                    salesDistributionChart.data.labels = labels;
                    salesDistributionChart.data.datasets[0].data = orderCounts;
                    salesDistributionChart.update();
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching sales distribution:", error);
                }
            });
        }

        $(document).ready(function() {
            fetchSalesDistribution();

            // Fetch the wholesale, retail, and online reports on page load
            fetchReports();

            // Change data fetch based on dropdown selection
            $('#timeframe').change(function() {
                fetchReports(); // Fetch data based on the selected timeframe
            });
        });

        function fetchReports() {
            var timeframe = $('#timeframe').val();

            // Fetch wholesale reports
            $.ajax({
                url: 'fetch_wholesale_report.php',
                type: 'GET',
                data: {
                    timeframe: timeframe
                },
                success: function(data) {
                    var result = JSON.parse(data);
                    wholesaleChart.data.labels = result.periods;
                    wholesaleChart.data.datasets[0].data = result.total_sales;

                    wholesaleChart.options.scales.x.title.text;
                    wholesaleChart.update();
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching wholesale reports:", error);
                }
            });

            // Fetch retail reports
            $.ajax({
                url: 'fetch_retail_report.php',
                type: 'GET',
                data: {
                    timeframe: timeframe
                },
                success: function(data) {
                    var result = JSON.parse(data);
                    retailSalesChart.data.labels = result.periods;
                    retailSalesChart.data.datasets[0].data = result.total_sales;

                    retailSalesChart.options.scales.x.title.text = timeframe === 'monthly' ? 'Month' : 'Week';
                    retailSalesChart.update();
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching retail reports:", error);
                }
            });

            // Fetch online reports
            $.ajax({
                url: 'fetch_online_report.php',
                type: 'GET',
                data: {
                    timeframe: timeframe
                },
                success: function(data) {
                    var result = JSON.parse(data);
                    onlineSalesChart.data.labels = result.periods;
                    onlineSalesChart.data.datasets[0].data = result.total_sales;

                    onlineSalesChart.options.scales.x.title.text = timeframe === 'monthly' ? 'Month' : 'Week';
                    onlineSalesChart.update();
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching online reports:", error);
                }
            });
        }

        // Create the purchase preferences chart (Pie Chart)
        var ctx4 = document.getElementById('salesDistributionChart').getContext('2d');
        var salesDistributionChart = new Chart(ctx4, {
            type: 'pie',
            data: {
                labels: [],
                datasets: [{
                    label: 'Number of Orders',
                    data: [],
                    backgroundColor: ['#FABE7A', '#FF6B6B', '#80CED7', '#7D74FF', '#FDE47F'],
                    borderColor: ['#F4A261', '#FF4D4D', '#66B2FF', '#6A4CFF', '#FCD034'],
                    borderWidth: 1
                }]
            },
            options: {
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'right',
                        labels: {
                            font: {
                                size: 10
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
                        top: 10,
                        bottom: 5
                    }
                }
            }
        });

        // Line chart for wholesale sales (Line Chart)
        var ctx2 = document.getElementById('wholesaleChart').getContext('2d');
        var wholesaleChart = new Chart(ctx2, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: '',
                    data: [],
                    borderColor: '#80CED7',
                    backgroundColor: createGradient(ctx2),
                    fill: true,
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
                            text: 'Total Amt(PHP)'
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

        // Line chart for retail sales (Line Chart)
        var ctx3 = document.getElementById('retailSalesChart').getContext('2d');
        var retailSalesChart = new Chart(ctx3, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: '',
                    data: [],
                    borderColor: '#80CED7',
                    backgroundColor: createGradient(ctx3),
                    fill: true,
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
                            text: 'Total Amt (PHP)'
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

        // Line chart for online sales (Line Chart)
        var ctx4 = document.getElementById('onlineSalesChart').getContext('2d');
        var onlineSalesChart = new Chart(ctx4, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: '',
                    data: [],
                    borderColor: '#80CED7',
                    backgroundColor: createGradient(ctx4),
                    fill: true,
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
                            text: 'Total Amt (PHP)'
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

        function createGradient(ctx) {
            var gradient = ctx.createLinearGradient(0, 0, 0, 400);
            gradient.addColorStop(0, 'rgba(128, 206, 215, 0.5)');
            gradient.addColorStop(1, 'rgba(128, 206, 215, 0)');
            return gradient;
        }
    </script>


</body>

</html>