<?php
session_start();

include("connection.php"); 

$formSubmitted = false;
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $formSubmitted = true;
    $username = trim($_POST["username"]);
    $password = trim($_POST["password"]);

    $sql = "SELECT * FROM login WHERE username=?";
    $stmt = $mysqli->prepare($sql); 
    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();

            // Verify the entered password with the hashed password from the database
            if (password_verify($password, $row['password'])) {
                // Password is correct, proceed with login
                $_SESSION["username"] = $username;
                error_log("Password verified, user type: " . $row["usertype"]);

                // Redirect based on user type
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
                // Password is incorrect
                error_log("Password verification failed");
                $errorMessage = "<strong> ERROR: </strong> <br> Incorrect username or password. Please try again.";
            }
        } else {
            // Username does not exist or multiple entries found
            error_log("Username not found or multiple entries");
            $errorMessage = "<strong> ERROR: </strong> <br> Incorrect username or password. Please try again.";
        }

        $stmt->close();
    } else {
        $errorMessage = "<strong> ERROR: </strong> <br> Failed to prepare statement.";
        error_log("Failed to prepare SQL statement: " . $mysqli->error);
    }
}

$mysqli->close();
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="icon" href="favicon.png" type="image/png">
    <link rel="stylesheet" href="../styles/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>


<body>
    <header>
        <div onclick="location.href='index.php';" style="cursor:pointer;">            
        <img src="../../favicon.png" alt="Logo" class="logo"></div>
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

                        <div class="password-requirements" style="display: none;">
                            <p>Password must include:</p>
                            <ul>
                                <li class="uppercase">An uppercase letter</li>
                                <li class="lowercase">A lowercase letter</li>
                                <li class="special">A special character</li>
                                <li class="number">A number</li>
                                <li class="eightmin">At least 8 characters</li>
                            </ul>
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
        <p>Loading...</p>
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
            <button class="message-button" id="okSuccessButton">OK</button>
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

            const loginFormElement = document.querySelector('form[action="#"]');
            loginFormElement.addEventListener('submit', (event) => {
                event.preventDefault(); // Prevent immediate form submission
                showLoadingScreen();

                // Now submit the form via AJAX
                loginFormElement.submit(); // Submit form after showing loading screen
            });

            const registrationForm = document.querySelector('form[action="register.php"]');
            registrationForm.addEventListener('submit', (event) => {
                event.preventDefault(); // Prevent immediate form submission
                showLoadingScreen();

                // Now submit the form via AJAX
                registrationForm.submit(); // Submit form after showing loading screen
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
    const registerPasswordField = document.getElementById('registerPasswordField');
    const confirmPasswordField = document.getElementById('confirmPasswordField');
    const passwordRequirements = document.querySelector('.password-requirements');
    const requirements = {
        uppercase: /[A-Z]/,
        lowercase: /[a-z]/,
        special: /[!@#$%^&*(),.?":{}|<>]/,
        number: /[0-9]/,
        eightmin: /.{8,}/,
    };

    const updatePasswordRequirements = () => {
        const value = registerPasswordField.value;
        const requirementItems = document.querySelectorAll('.password-requirements li');

        // Check uppercase
        if (requirements.uppercase.test(value)) {
            requirementItems[0].classList.add('met');
            requirementItems[0].classList.remove('unmet');
        } else {
            requirementItems[0].classList.add('unmet');
            requirementItems[0].classList.remove('met');
        }

        // Check lowercase
        if (requirements.lowercase.test(value)) {
            requirementItems[1].classList.add('met');
            requirementItems[1].classList.remove('unmet');
        } else {
            requirementItems[1].classList.add('unmet');
            requirementItems[1].classList.remove('met');
        }

        // Check special character
        if (requirements.special.test(value)) {
            requirementItems[2].classList.add('met');
            requirementItems[2].classList.remove('unmet');
        } else {
            requirementItems[2].classList.add('unmet');
            requirementItems[2].classList.remove('met');
        }

        // Check number
        if (requirements.number.test(value)) {
            requirementItems[3].classList.add('met');
            requirementItems[3].classList.remove('unmet');
        } else {
            requirementItems[3].classList.add('unmet');
            requirementItems[3].classList.remove('met');
        }

        // Check minimum length
        if (requirements.eightmin.test(value)) {
            requirementItems[4].classList.add('met');
            requirementItems[4].classList.remove('unmet');
        } else {
            requirementItems[4].classList.add('unmet');
            requirementItems[4].classList.remove('met');
        }
    };

    // Show password requirements when focusing on the password field
    registerPasswordField.addEventListener('focus', () => {
        passwordRequirements.style.display = 'block';
    });

    // Hide password requirements when losing focus (only if not typing)
    registerPasswordField.addEventListener('blur', () => {
        if (registerPasswordField.value === '') {
            passwordRequirements.style.display = 'none';
        }
    });

    registerPasswordField.addEventListener('input', updatePasswordRequirements);

    confirmPasswordField.addEventListener('input', () => {
        if (confirmPasswordField.value === registerPasswordField.value) {
            confirmPasswordField.classList.remove('error');
        } else {
            confirmPasswordField.classList.add('error');
        }
    });

    const registrationForm = document.querySelector('form[action="register.php"]');
    registrationForm.addEventListener('submit', (event) => {
        event.preventDefault(); // Prevent immediate form submission

        showLoadingScreen(); // Show loading screen

        const password = registerPasswordField.value;
        const confirmPassword = confirmPasswordField.value;
        let allMet = true;

        // Validate password requirements after loading screen
        Object.keys(requirements).forEach((key) => {
            if (!requirements[key].test(password)) {
                allMet = false;
            }
        });

        if (!allMet) {
            hideLoadingScreen(); // Hide loading screen when validation fails
            event.preventDefault(); // Prevent form submission

            // Display a detailed error message in the error modal
            const errorModal = document.getElementById('errorModal');
            const errorMessage = errorModal.querySelector('p');
            errorMessage.innerHTML = "<strong>ERROR:</strong> <br> Please ensure your password meets all requirements.";

            errorModal.style.display = 'block';
        } else {
            // If all requirements are met, submit the form
            registrationForm.submit();
        }
    });
});

    </script>

</body>

</html>