<?php
$host = "localhost";
$user = "root";
$password = "";
$db = "system_db";

session_start();

try {
    $data = new mysqli($host, $user, $password, $db);
    if ($data->connect_error) {
        throw new Exception("Connection failed: " . $data->connect_error);
    }
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Error connecting to the database. Please check the logs for more details.");
}

$errorMessage = "";
$formSubmitted = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $formSubmitted = true;
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    $sql = "SELECT * FROM login WHERE username=?";
    $stmt = $data->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            if ($password === $row['password']) {
                $_SESSION["username"] = $username;
                error_log("Password verified, user type: " . $row["usertype"]);
                if ($row["usertype"] == "admin") {
                    header("Location: admin/admin.php");
                    exit();
                } elseif ($row["usertype"] == "staff") {
                    header("Location: staff/staff.php");
                    exit();
                } elseif ($row["usertype"] == "customer") {
                    header("Location: customer/customer.php");
                    exit();
                } elseif ($row["usertype"] == "delivery") {
                    header("Location: delivery/delivery.php");
                    exit();
                }
            } else {
                error_log("Password verification failed");
                $errorMessage = "<strong> ERROR: </strong> <br> Incorrect username or password. Please try again.";
            }
        } else {
            error_log("Username not found or multiple entries");
            $errorMessage = "<strong> ERROR: </strong> <br> Incorrect username or password. Please try again.";
        }

        $stmt->close();
    } else {
        $errorMessage = "<strong> ERROR: </strong> <br> Failed to prepare statement.";
        error_log("Failed to prepare SQL statement: " . $data->error);
    }
}

$data->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="../styles/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>


<body>
    <header>
        <div class="logo" onclick="location.href='homepage.php';" style="cursor:pointer;">RICE</div>
    </header>

    <div class="login-container">
        <div id="loginForm" class="login-form">
            <h3 class="welcome-message">Welcome to Escalona-Delen <br> Rice Dealer Website!</h3>
            <div class="login-toggle">
                <button id="loginBtn" class="login-button active">Login</button>
                <button id="registerBtn" class="register-button">Register</button>
            </div>
            <form id="loginForm" action="#" method="POST">
                <div class="form-fields">
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-input" placeholder="Enter your username" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="password-container">
                            <input type="password" name="password" class="form-input" id="loginPasswordField" placeholder="Enter your password" required>
                            <span id="loginPasswordToggle" class="password-toggle"><i class="fa fa-eye"></i></span>
                        </div>
                    </div>
                    <div class="form-options">
                        <label class="remember-checkbox">
                            <input type="checkbox">
                        </label>
                        <span class="remember-text">Remember me</span>
                        <a href="#" class="forgot-password" id="forgotPasswordLink">Forgot Password?</a>
                    </div>
                </div>
                <div class="form-submit">
                    <button type="submit" class="submit-button">Login</button>
                </div>
            </form>
        </div>

        <!-- FORGOT PASS DIALOG -->
        <!-- <div id="forgotPasswordModal" class="modal" style="display: none;">
            <div class="modal-content">
                <span class="close" id="closeForgotPasswordModal">&times;</span>
                <h2>Reset Password</h2>
                <form id="forgotPasswordForm" action="forgot_password.php" method="POST">
                    <label for="email">Enter your email address:</label>
                    <input type="email" name="email" id="resetEmail" required>
                    <button type="submit">Send Reset Link</button>
                </form>
                <p id="resetMessage"></p>
            </div>
        </div> -->

        <div id="registerForm" class="register-form" style="display:none;">
            <h3 class="welcome-message">Register to Escalona-Delen <br> Rice Dealer Website!</h3>
            <div class="login-toggle">
                <button id="loginBtn2" class="login-button">Login</button>
                <button id="registerBtn2" class="register-button active">Register</button>
            </div>
            <form action="register.php" method="POST">
                <div class="form-fields">
                    <div class="form-group-wrapper">
                        <div class="form-group">
                            <label class="form-label">First Name</label>
                            <input type="text" name="fname" class="form-input" placeholder="Enter your first name" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="lname" class="form-input" placeholder="Enter your last name" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-input" placeholder="Enter your username" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="password-container">
                            <input type="password" name="password" class="form-input" id="registerPasswordField" placeholder="Enter your password" required>
                            <span id="registerPasswordToggle" class="password-toggle"><i class="fa fa-eye"></i></span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <div class="password-container">
                            <input type="password" name="confirm_password" class="form-input" id="confirmPasswordField" placeholder="Confirm your password" required>
                            <span id="confirmPasswordToggle" class="password-toggle"><i class="fa fa-eye"></i></span>
                        </div>
                    </div>
                </div>
                <div class="form-submit">
                    <button type="submit" class="submit-button">Register</button>
                </div>
            </form>
        </div>
    </div>

    <div id="loadingScreen" class="loading-screen" style="display: none;">
        <div class="spinner"></div>
        <p>Processing your request...</p>
    </div>

    <div class="error-modal" id="errorModal" style="display:none;">
        <div class="error-content">
            <span class="close" id="closeErrorModal">&times;</span>
            <p><?php echo $errorMessage; ?></p>
            <button class="message-button" id="okButton">OK</button>
        </div>
    </div>

    <div class="success-modal" id="successModal" style="display:none;">
        <div class="success-content">
            <span class="close" id="closeSuccessModal">&times;</span>
            <p><?php echo $_SESSION["message"]; ?></p>
            <button id="okSuccessButton">OK</button>
        </div>
    </div>

    <script>
        
        document.addEventListener('DOMContentLoaded', () => {
            const passwordToggles = document.querySelectorAll('.password-toggle');

            passwordToggles.forEach(toggle => {
                toggle.addEventListener('click', () => {
                    const passwordField = toggle.previousElementSibling;
                    if (passwordField.type === 'password') {
                        passwordField.type = 'text';
                        toggle.innerHTML = '<i class="fa fa-eye-slash"></i>';
                    } else {
                        passwordField.type = 'password';
                        toggle.innerHTML = '<i class="fa fa-eye"></i>';
                    }
                });
            });

            const loadingScreen = document.getElementById('loadingScreen');
            const loginBtn = document.getElementById('loginBtn');
            const registerBtn = document.getElementById('registerBtn');
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');
            const loginBtn2 = document.getElementById('loginBtn2');
            const registerBtn2 = document.getElementById('registerBtn2');
            const errorModal = document.getElementById('errorModal');
            const closeErrorModal = document.getElementById('closeErrorModal');
            const okButton = document.getElementById('okButton');
            const successModal = document.getElementById('successModal');
            const closeSuccessModal = document.getElementById('closeSuccessModal');
            const okSuccessButton = document.getElementById('okSuccessButton');

            function toggleForms(showLogin) {
                if (showLogin) {
                    loginBtn.classList.add('active');
                    registerBtn.classList.remove('active');
                    loginForm.style.display = 'block';
                    registerForm.style.display = 'none';
                } else {
                    registerBtn.classList.add('active');
                    loginBtn.classList.remove('active');
                    registerForm.style.display = 'block';
                    loginForm.style.display = 'none';
                }
            }

            loginBtn.addEventListener('click', () => toggleForms(true));
            registerBtn.addEventListener('click', () => toggleForms(false));
            loginBtn2.addEventListener('click', () => toggleForms(true));
            registerBtn2.addEventListener('click', () => toggleForms(false));

            function showLoadingScreen() {
                loadingScreen.style.display = 'flex';
                document.body.classList.add('loading-open');
            }

            function hideLoadingScreen() {
                loadingScreen.style.display = 'none';
                document.body.classList.remove('loading-open');
            }

            <?php if ($formSubmitted && !empty($errorMessage)): ?>
                errorModal.style.display = 'block';
            <?php endif; ?>

            closeErrorModal.addEventListener('click', () => {
                errorModal.style.display = 'none';
            });

            okButton.addEventListener('click', () => {
                errorModal.style.display = 'none';
            });

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    errorModal.style.display = 'none';
                }
            });

            function showSuccessModal() {
                successModal.style.display = 'flex';
            }

            function hideSuccessModal() {
                successModal.style.display = 'none';
            }

            closeSuccessModal.addEventListener('click', hideSuccessModal);
            okSuccessButton.addEventListener('click', hideSuccessModal);

            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    hideSuccessModal();
                }
            });

            <?php if (!empty($_SESSION["message"])): ?>
                showSuccessModal();
                <?php unset($_SESSION["message"]); ?>
            <?php endif; ?>
        });
    </script>

</body>

</html>