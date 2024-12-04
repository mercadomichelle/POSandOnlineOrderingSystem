<?php
session_start();

include('../../connection.php');
include('../../notifications.php');

// Redirect to login if user is not logged in
if (!isset($_SESSION["username"])) {
    header("Location: ../../login.php");
    exit();
}
if (!isset($_SESSION['branch_id'])) {
    $_SESSION['branch_id'] = 0; // Replace 0 with an appropriate default value
}
if (!isset($_SESSION['month'])) {
    $_SESSION['month'] = date('n'); // Current month (1-12)
}
if (!isset($_SESSION['week'])) {
    $_SESSION['week'] = 1; // Default to the first week
}
if (!isset($_SESSION['year'])) {
    $_SESSION['year'] = date('Y'); // Current year
}
$username = $_SESSION["username"];
$branch_id = $_SESSION["branch_id"];

// Fetch user details
$sql = "SELECT first_name, last_name, branch_id FROM login WHERE username = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $userData = $result->fetch_assoc();
    $_SESSION["first_name"] = $userData['first_name'];
    $_SESSION["last_name"] = $userData['last_name'];
    $_SESSION["branch_id"] = $userData['branch_id']; // Save branch_id to session
} else {
    $_SESSION["first_name"] = "Guest";
    $_SESSION["last_name"] = "";
    $_SESSION["branch_id"] = null; // Default branch_id if not found
}

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
            <input type="hidden" id="branch_id" value="<?php echo $_SESSION['branch_id']; ?>" />

            <div class="upper">
                <div class="card1">
                    <h3>Wholesales Report</h3>
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
                        <a href="data_report.php?type=wholesale&source=in-store&branch_id=<?= $_SESSION['branch_id'] ?>" target="_blank">
                            <button class="weekly">View Report</button></a>
                    </div>
                </div>

                <div class="card5">
                    <h3>Detailed Retail Sales Report</h3>
                    <div class="btn1">
                        <a href="data_report.php?type=retail&source=in-store&branch_id=<?= $_SESSION['branch_id'] ?>" target="_blank">
                            <button class="weekly">View Report</button></a>
                    </div>
                </div>

                <div class="card6">
                    <h3>Detailed Online Sales Report</h3>
                    <div class="btn1">
                        <a href="data_report.php?type=wholesale&source=online&branch_id=<?= $_SESSION['branch_id'] ?>" target="_blank">
                            <button class="weekly">View Report</button></a>
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
    <script src="../../js/weeks.js"></script>
    <script>
        $(document).ready(function() {
            const currentMonth = new Date().getMonth() + 1; // Get current month (1-12)
            const currentYear = new Date().getFullYear(); // Get current year

            // Set the default selected month and year
            $('#monthSelector').val(currentMonth);
            $('#yearSelector').val(currentYear);

            fetchSalesDistribution();
            fetchReports();

            // Change data fetch based on dropdown selection
            $('#timeframe').change(function() {
                fetchReports(); // Fetch data based on the selected timeframe
                fetchSalesDistribution();
            });

            // Capture changes in month, week, and year selectors
            $('#monthSelector, #weekSelector, #yearSelector').change(function() {
                fetchReports(); // Fetch data when any filter changes
                fetchSalesDistribution();
            });
        });

        // Updated fetchReports function to handle full month or week display
        function fetchReports() {
            var timeframe = $('#timeframe').val(); // Get the selected timeframe
            var branch_id = $('#branch_id').val(); // Get branch_id from hidden field
            var month = $('#monthSelector').val(); // Get selected month
            var week = $('#weekSelector').val(); // Get selected week
            var year = $('#yearSelector').val(); // Get selected year

            var data = {
                timeframe: timeframe,
                branch_id: branch_id,
                month: month,
                week: week,
                year: year
            };

            // Send AJAX request to fetch wholesale report
            $.ajax({
                url: 'fetch_wholesale_report.php',
                type: 'GET',
                data: data,
                success: function(response) {
                    var result = JSON.parse(response);
                    console.log("Wholesale Report Data:", result);

                    // Update the chart with the full range of days in the selected month or week
                    wholesaleChart.data.labels = result.periods;
                    wholesaleChart.data.datasets[0].data = result.total_sales;
                    wholesaleChart.options.scales.x.title.text = (timeframe === 'monthly' ? 'Day' : (timeframe === 'weekly' ? 'Week' : 'Year'));
                    wholesaleChart.update();
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching reports:", error);
                }
            });

            // Fetch retail reports
            $.ajax({
                url: 'fetch_retail_report.php',
                type: 'GET',
                data: data,
                success: function(data) {
                    var result = JSON.parse(data);
                    retailSalesChart.data.labels = result.periods;
                    retailSalesChart.data.datasets[0].data = result.total_sales;
                    retailSalesChart.options.scales.x.title.text = (timeframe === 'monthly' ? 'Day' : (timeframe === 'weekly' ? 'Week' : 'Year'));
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
                data: data,
                success: function(data) {
                    var result = JSON.parse(data);
                    onlineSalesChart.data.labels = result.periods;
                    onlineSalesChart.data.datasets[0].data = result.total_sales;
                    onlineSalesChart.options.scales.x.title.text = (timeframe === 'monthly' ? 'Day' : (timeframe === 'weekly' ? 'Week' : 'Year'));
                    onlineSalesChart.update();
                },
                error: function(xhr, status, error) {
                    console.error("Error fetching online reports:", error);
                }
            });
        }

        function fetchSalesDistribution() {
            var branch_id = $('#branch_id').val(); // Get branch_id from hidden 
            var month = $('#monthSelector').val(); // Get selected month
            var week = $('#weekSelector').val(); // Get selected week
            var year = $('#yearSelector').val(); // Get selected year

            $.ajax({
                url: 'fetch_sales_distribution.php',
                type: 'GET',
                data: {
                    branch_id: branch_id,
                    month: month,
                    week: week,
                    year: year
                },
                success: function(data) {
                    console.log("Sales Distribution Data:", data);

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
        // Create the purchase preferences chart (Pie Chart)
        var ctx4 = document.getElementById('salesDistributionChart').getContext('2d');
        var salesDistributionChart = new Chart(ctx4, {
            type: 'pie',
            data: {
                labels: [], // Empty labels initially
                datasets: [{
                    label: 'Number of Orders',
                    data: [], // Empty data initially
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
                        position: 'bottom',
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
                    label: 'Wholesale Sales',
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
                            display: false,
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

        // Fetch data and update chart when timeframe is changed
        $(document).ready(function() {
            fetchReports(); // Initial load

            $('#timeframe').change(function() {
                fetchReports(); // Fetch data again when the timeframe changes
            });
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