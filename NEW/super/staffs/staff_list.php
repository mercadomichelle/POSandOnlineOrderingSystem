<?php
session_start();

include('../../connection.php');

// Ensure the user is logged in
if (!isset($_SESSION["username"])) {
    header("Location: ../../login.php");
    exit();
}

$username = $_SESSION["username"];

// Fetch user details for session variables
$sql = "SELECT login.id AS login_id, login.first_name, login.last_name
        FROM login 
        WHERE login.username = ?";
$stmt = $mysqli->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $userData = $result->fetch_assoc();
    $_SESSION["first_name"] = $userData['first_name'];
    $_SESSION["last_name"] = $userData['last_name'];
    $_SESSION["login_id"] = $userData['login_id'];
} else {
    $_SESSION["first_name"] = "Guest";
    $_SESSION["last_name"] = "";
    $_SESSION["login_id"] = "";
}

// Handle branch selection form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['branch_id'])) {
    $selectedBranch = intval($_POST['branch_id']);
    $_SESSION['selected_branch'] = $selectedBranch;  // Store selected branch in session
    header("Location: " . $_SERVER['PHP_SELF']); // Redirect to prevent form resubmission
    exit();
}

// Retrieve selected branch from session
$selectedBranch = isset($_SESSION['selected_branch']) ? $_SESSION['selected_branch'] : null;


$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);

$limit = 10;
$offset = ($page - 1) * $limit;

$offset = max(0, $offset);

// Construct SQL query
$sql = "SELECT login.id, login.first_name, login.last_name, login.username, 
        staff.staff_id, staff.phone_number, staff.email_address, staff.usertype 
        FROM login 
        JOIN staff ON login.id = staff.login_id";

if ($selectedBranch) {
    $sql .= " WHERE login.branch_id = ?";
}

$sql .= " LIMIT ?, ?";

$stmt = $mysqli->prepare($sql);

// Bind parameters based on branch selection
if ($selectedBranch) {
    $stmt->bind_param("iii", $selectedBranch, $offset, $limit);
} else {
    $stmt->bind_param("ii", $offset, $limit);
}

$stmt->execute();
$result = $stmt->get_result();


if (!$result) {
    die("Query failed: " . $mysqli->error);
}

$staffData = $result->fetch_all(MYSQLI_ASSOC);

// Count total staff records for pagination
$sqlCount = "SELECT COUNT(*) as total 
             FROM login 
             JOIN staff 
             ON login.id = staff.login_id 
             WHERE login.branch_id = ?";
$stmtCount = $mysqli->prepare($sqlCount);
$stmtCount->bind_param("i", $selectedBranch);  // Use selectedBranch to filter by the branch ID
$stmtCount->execute();
$resultCount = $stmtCount->get_result();
$rowCount = $resultCount->fetch_assoc();
$totalRecords = $rowCount['total'];
$totalPages = ceil($totalRecords / $limit);

$maxPagesToShow = 10;
$startPage = max(1, $page - floor($maxPagesToShow / 2));
$endPage = min($totalPages, $startPage + $maxPagesToShow - 1);

if ($endPage - $startPage + 1 < $maxPagesToShow) {
    $startPage = max(1, $endPage - $maxPagesToShow + 1);
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


$successMessage = isset($_SESSION['successMessage']) ? $_SESSION['successMessage'] : null;
$errorMessage = isset($_SESSION['errorMessage']) ? $_SESSION['errorMessage'] : null;
$editStaffId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : null;
$formData = isset($_SESSION['formData']) ? $_SESSION['formData'] : [];

unset($_SESSION['successMessage']);
unset($_SESSION['errorMessage']);
unset($_SESSION['formData']);

$mysqli->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rice Website | Staff List</title>
    <link rel="icon" href="../../favicon.png" type="image/png">
    <link rel="stylesheet" href="../../styles/staff_list.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body>
    <header>
        <div><img src="../../favicon.png" alt="Logo" class="logo"></div>

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
                <li><a href="../super_admin.php"><img src="../../images/dashboard-icon.png" alt="Dashboard">DASHBOARD</a></li>
                <li><a href="../products/products.php"><img src="../../images/products-icon.png" alt="Products">PRODUCTS</a></li>
                <li><a href="../stocks/stocks.php"><img src="../../images/stocks-icon.png" alt="Stocks">STOCKS</a></li>
                <li><a class="current"><img src="../../images/staffs-icon.png" alt="Staffs">STAFFS</a></li>
            </ul>
        </nav>
        <ul class="reports">
            <li><a href="../reports/reports.php"><img src="../../images/reports-icon.png" alt="Reports">REPORTS</a></li>
        </ul>
    </div>

    <main>
        <div class="card">
            <h3>STAFFS</h3>
            <button id="addNewStaffBtn" <?php echo empty($_SESSION['selected_branch']) ? 'disabled' : ''; ?>>
                <img class="add" src="../../images/add-icon.png" alt="Add">ADD NEW STAFF
            </button>
            <div id="staffList">
                <div class="staff-header">
                    <div>ID</div>
                    <div>NAME</div>
                    <div>USERNAME</div>
                    <div>PHONE NUMBER</div>
                    <div>EMAIL ADDRESS</div>
                    <div>USER TYPE</div>
                    <div class="edit">EDIT</div>
                    <div class="delete">DELETE</div>
                </div>
                <?php if (!empty($staffData)): ?>
                    <?php $counter = $offset + 1; ?>
                    <?php foreach ($staffData as $row): ?>
                        <div class='staff-item'>
                            <div><?php echo $counter++; ?></div>
                            <div><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></div>
                            <div><?php echo htmlspecialchars($row['username']); ?></div>
                            <div><?php echo htmlspecialchars($row['phone_number']); ?></div>
                            <div><?php echo htmlspecialchars($row['email_address']); ?></div>
                            <div><?php echo htmlspecialchars($row['usertype']); ?></div>
                            <div class='edit'><img class='edit-btn' data-id='<?php echo htmlspecialchars($row['staff_id']); ?>' src='../../images/edit-icon.png' alt='Edit'></div>
                            <div class='delete'><img class='delete-btn' data-id='<?php echo htmlspecialchars($row['staff_id']); ?>' src='../../images/delete-icon.png' alt='Delete'></div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class='no-staff'>No staff found for your branch.</div>
                <?php endif; ?>
            </div>

            <div class="pagination-container">
                <div class="pagination-prev">
                    <?php if ($page > 1): ?>
                        <a href="staff_list.php?page=<?php echo $page - 1; ?>">Previous</a>
                    <?php else: ?>
                        <span class="disabled">Previous</span>
                    <?php endif; ?>
                </div>

                <div class="pagination">
                    <?php if ($startPage > 1): ?>
                        <a href="staff_list.php?page=1">1</a>
                        <?php if ($startPage > 2): ?>
                            <span>...</span>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="staff_list.php?page=<?php echo $i; ?>" <?php if ($i === $page) echo 'class="active"'; ?>>
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span>...</span>
                        <?php endif; ?>
                        <a href="staff_list.php?page=<?php echo $totalPages; ?>"><?php echo $totalPages; ?></a>
                    <?php endif; ?>
                </div>


                <div class="pagination-next">
                    <?php if ($page < $totalPages): ?>
                        <a href="staff_list.php?page=<?php echo $page + 1; ?>">Next</a>
                    <?php else: ?>
                        <span class="disabled">Next</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Add New Staff Modal -->
            <div id="myModal" class="modal">
                <div class="modal-content">
                    <span class="close">&times;</span>
                    <h2>Add New Staff</h2>
                    <form id="addStaffForm" method="post" action="add_staff.php">
                        <input type="hidden" name="current_page" value="<?php echo isset($_GET['page']) ? htmlspecialchars($_GET['page']) : 1; ?>">
                        <label for="first_name">First Name:</label>
                        <input type="text" id="first_name" name="first_name" required value="<?php echo isset($_SESSION['formData']['first_name']) ? htmlspecialchars($_SESSION['formData']['first_name']) : ''; ?>"><br><br>
                        <label for="last_name">Last Name:</label>
                        <input type="text" id="last_name" name="last_name" required value="<?php echo isset($_SESSION['formData']['last_name']) ? htmlspecialchars($_SESSION['formData']['last_name']) : ''; ?>"><br><br>
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" required value="<?php echo isset($_SESSION['formData']['username']) ? htmlspecialchars($_SESSION['formData']['username']) : ''; ?>"><br><br>
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required><br><br>
                        <label for="phone">Phone Number:</label>
                        <input type="text" id="phone" maxlength="11" name="phone" required value="<?php echo isset($_SESSION['formData']['phone']) ? htmlspecialchars($_SESSION['formData']['phone']) : ''; ?>"><br><br>
                        <label for="email">Email address:</label>
                        <input type="email" id="email" name="email" required value="<?php echo isset($_SESSION['formData']['email']) ? htmlspecialchars($_SESSION['formData']['email']) : ''; ?>"><br><br>
                        <label for="usertype">User Type:</label>
                        <select id="usertype" name="usertype" required>
                            <option value="staff">Staff</option>
                            <option value="delivery">Delivery</option>
                        </select>
                        <div class="form-button-container">
                            <button type="submit" class="save-btn">Submit</button>
                        </div>
                    </form>
                </div>
            </div>


            <!-- Edit Staff Modal -->
            <div id="editModal" class="modal" style="<?php echo $editStaffId ? 'display: block;' : 'display: none;'; ?>">
                <div class="modal-content1">
                    <span class="close">&times;</span>
                    <h2>Edit Staff</h2>
                    <form id="editStaffForm" method="post" action="edit_staff.php">
                        <input type="hidden" name="staff_id" id="edit_staff_id" value="<?php echo $editStaffId ? htmlspecialchars($editStaffId) : ''; ?>">
                        <input type="hidden" name="page" id="edit_page" value="<?php echo htmlspecialchars($page); ?>">
                        <label for="edit_first_name">First Name:</label>
                        <input type="text" name="first_name" id="edit_first_name" class="edit" required value="<?php echo isset($formData['first_name']) ? htmlspecialchars($formData['first_name']) : ''; ?>">
                        <label for="edit_last_name">Last Name:</label>
                        <input type="text" name="last_name" id="edit_last_name" class="edit" required value="<?php echo isset($formData['last_name']) ? htmlspecialchars($formData['last_name']) : ''; ?>">
                        <label for="edit_username">Username:</label>
                        <input type="text" name="username" id="edit_username" class="edit" required value="<?php echo isset($formData['username']) ? htmlspecialchars($formData['username']) : ''; ?>">
                        <label for="edit_phone">Phone Number:</label>
                        <input type="text" name="phone" id="edit_phone" maxlength="11" class="edit" required value="<?php echo isset($formData['phone']) ? htmlspecialchars($formData['phone']) : ''; ?>">
                        <label for="edit_email">Email Address:</label>
                        <input type="email" name="email" id="edit_email" class="edit" required value="<?php echo isset($formData['email']) ? htmlspecialchars($formData['email']) : ''; ?>">
                        <label for="edit_usertype">User Type:</label>
                        <select id="edit_usertype" name="usertype" required>
                            <option value="staff" <?php echo (isset($formData['usertype']) && $formData['usertype'] == 'staff') ? 'selected' : ''; ?>>Staff</option>
                            <option value="delivery" <?php echo (isset($formData['usertype']) && $formData['usertype'] == 'delivery') ? 'selected' : ''; ?>>Delivery</option>
                        </select>
                        <div class="form-button-container">
                            <button type="submit" class="save-btn">Save changes</button>
                    </form>
                </div>
            </div>
        </div>

        <div id="loadingScreen" class="loading-screen" style="display: none;">
            <div class="spinner"></div>
            <p>Loading...</p>
        </div>

        <!-- Message Modal -->
        <?php if ($successMessage || $errorMessage): ?>
            <div id="messageModal" class="message-modal" style="display: block;">
                <div class="message-modal-content">
                    <span class="message-close">&times;</span>
                    <div id="messageContent">
                        <?php
                        if ($successMessage) {
                            echo '<div class="alert-success">' . htmlspecialchars($successMessage) . '</div>';
                        } elseif ($errorMessage) {
                            echo '<div class="alert-error">' . htmlspecialchars($errorMessage) . '</div>';
                        }
                        ?>
                        <button class="message-button" id="okButton">OK</button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Delete Confirmation Modal -->
        <div id="deleteModal" class="message-modal" style="display: none;">
            <div class="message-modal-content">
                <span class="message-close">&times;</span>
                <div id="messageContent">
                    <div class="alert error">
                        <p class="delete">Are you sure you want to delete this staff?</p>
                        <form id="deleteStaffForm" method="post" action="delete_staff.php">
                            <input type="hidden" name="staff_id" id="delete_staff_id">
                            <input type="hidden" name="page" id="delete_page">
                            <button type="submit" class="confirm-delete-btn">Yes, Delete</button>
                            <button type="button" class="cancel-delete-btn">Cancel</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php
    if (isset($_SESSION['formData'])) {
        unset($_SESSION['formData']);
    }
    ?>

    <script>
        document.getElementById('addNewStaffBtn').onclick = function() {
            document.getElementById('myModal').style.display = 'block';
        };

        document.getElementById('addStaffForm').onsubmit = function() {
            document.getElementById('loadingScreen').style.display = 'flex';
        };

        document.getElementById('editStaffForm').onsubmit = function() {
            document.getElementById('loadingScreen').style.display = 'flex';
        };

        document.getElementById('deleteStaffForm').onsubmit = function() {
            document.getElementById('loadingScreen').style.display = 'flex';
        };

        document.querySelectorAll('.message-close').forEach(function(closeButton) {
            closeButton.onclick = function() {
                closeButton.closest('.message-modal').style.display = 'none';
            };
        });

        document.querySelectorAll('.close').forEach(function(closeButton) {
            closeButton.onclick = function() {
                closeButton.closest('.modal').style.display = 'none';
            };
        });

        document.addEventListener("DOMContentLoaded", function() {
            const editModal = document.getElementById('editModal');
            if (editModal && editModal.style.display === 'block') {
                editModal.style.display = 'block';
            }
        });

        document.querySelectorAll('.edit-btn').forEach(function(editButton) {
            editButton.onclick = function() {
                const staffId = editButton.getAttribute('data-id');
                const staffItem = editButton.closest('.staff-item');
                const firstName = staffItem.children[1].textContent.trim();
                const lastName = firstName.split(" ").slice(1).join(" ");
                const username = staffItem.children[2].textContent.trim();
                const phoneNumber = staffItem.children[3].textContent.trim();
                const emailAddress = staffItem.children[4].textContent.trim();
                const userType = staffItem.children[5].textContent.trim();

                const currentPage = <?php echo $page; ?>;

                document.getElementById('edit_staff_id').value = staffId;
                document.getElementById('edit_first_name').value = firstName.split(" ")[0];
                document.getElementById('edit_last_name').value = lastName;
                document.getElementById('edit_username').value = username;
                document.getElementById('edit_phone').value = phoneNumber;
                document.getElementById('edit_email').value = emailAddress;
                document.getElementById('edit_usertype').value = userType;

                document.getElementById('edit_page').value = currentPage;

                document.getElementById('editModal').style.display = 'block';
            };
        });

        document.querySelectorAll('.delete-btn').forEach(function(deleteButton) {
            deleteButton.onclick = function() {
                const staffId = deleteButton.getAttribute('data-id');
                const currentPage = <?php echo $page; ?>;

                document.getElementById('delete_staff_id').value = staffId;
                document.getElementById('delete_page').value = currentPage;
                document.getElementById('deleteModal').style.display = 'block';
            };
        });

        document.querySelector('.cancel-delete-btn').onclick = function() {
            document.getElementById('deleteModal').style.display = 'none';
        };

        document.getElementById('okButton').onclick = function() {
            document.getElementById('messageModal').style.display = 'none';
        };
    </script>
    <script src="../../js/notif.js"></script>

</body>

</html>