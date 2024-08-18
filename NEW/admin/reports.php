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

$stmt->close();
$mysqli->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rice Website</title>
    <link rel="stylesheet" href="../styles/reports.css">
</head>
<body>
<header>
        <div class="logo">RICE</div>
        <div class="account-info">
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
                <li><a href="../admin/admin.php"><img src="../images/dashboard-icon.png" alt="Dashboard">DASHBOARD</a></li>
                <li><a href="../admin/products.php"><img src="../images/products-icon.png" alt="Products">PRODUCTS</a></li>
                <li><a href="../admin/stocks.php"><img src="../images/stocks-icon.png" alt="Stocks">STOCKS</a></li>
                <li><a href="../admin/staff_list.php"><img src="../images/staffs-icon.png" alt="Staffs">STAFFS</a></li>
            </ul>
        </nav>
            <ul class="reports">
                <li><a href="../admin/reports.php" class="current"><img src="../images/reports-icon.png" alt="Reports">REPORTS</a></li>
            </ul>    
    </div>

<main>
    <div class="dashboard">
        <!-- Content for the dashboard will go here -->
        <div class="card">
            <h3>REPORTS</h3>
            <p>Select an option from the sidebar to get started.</p>
        </div>
    </div>
</main>

</body>
</html>