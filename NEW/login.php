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

if ($mysqli) {
    $mysqli->close();
}
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="icon" href="favicon.png" type="image/png">
    <link rel="stylesheet" href="styles/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>


<body>
    <header>
        <div onclick="location.href='index.php';" style="cursor:pointer;">
            <img src="favicon.png" alt="Logo" class="logo">
        </div>
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

                </div>
                <div class="form-submit">
                    <button type="submit" class="submit-button-log">Login</button>
                </div>
            </form>
        </div>

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
                        <span id="usernameMessage" style="color: red; display: none;">Username is already taken</span>
                    </div>
                    <div class="form-group" style="position: relative;">
                        <label class="form-label">Password</label>

                        <div class="password-container">
                            <input
                                type="password"
                                name="password"
                                class="form-input"
                                id="registerPasswordField"
                                placeholder="Enter your password"
                                required />
                            <span id="registerPasswordToggle" class="password-toggle">
                                <i class="fa fa-eye"></i>
                            </span>
                        </div>
                        <div class="password-requirements">
                            <p>Password must contain:</p>
                            <ul>
                                <li class="uppercase unmet">At least 1 upper case letter</li>
                                <li class="lowercase unmet">At least 1 lower case letter</li>
                                <li class="special unmet">At least 1 special character</li>
                                <li class="number unmet">At least 1 number</li>
                                <li class="eightmin unmet">Minimum of 8 characters</li>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <div class="password-container">
                            <input type="password" name="confirm_password" class="form-input" id="confirmPasswordField" placeholder="Confirm your password" required>
                            <span id="confirmPasswordToggle" class="password-toggle"><i class="fa fa-eye"></i></span>
                        </div>
                        <span id="passwordMatchMessage" class="password-match-message" style="display: none;"></span>
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
            const passwordMatchMessage = document.getElementById('passwordMatchMessage');
            const passwordRequirements = document.querySelector('.password-requirements');
            const registerButton = document.querySelector('.submit-button'); // Assuming this is your register button
            const requirements = {
                uppercase: /[A-Z]/,
                lowercase: /[a-z]/,
                special: /[!@#$%^&*(),.?":{}|<>]/,
                number: /[0-9]/,
                eightmin: /.{8,}/,
            };

            // Function to update password requirements
            const updatePasswordRequirements = () => {
                const value = registerPasswordField.value;
                const requirementItems = document.querySelectorAll('.password-requirements li');

                Object.keys(requirements).forEach((key, index) => {
                    if (requirements[key].test(value)) {
                        requirementItems[index].classList.add('met');
                        requirementItems[index].classList.remove('unmet');
                    } else {
                        requirementItems[index].classList.add('unmet');
                        requirementItems[index].classList.remove('met');
                    }
                });
            };

            // Show password requirements when focusing on the Password field
            registerPasswordField.addEventListener('focus', () => {
                passwordRequirements.style.display = 'block';
            });

            // Hide password requirements when clicking outside password fields (except Confirm Password and Register Password)
            document.addEventListener('click', (event) => {
                if (
                    event.target !== registerPasswordField &&
                    event.target !== confirmPasswordField &&
                    !passwordRequirements.contains(event.target)
                ) {
                    passwordRequirements.style.display = 'none';
                }
            });

            // Update password requirements as user types in the Password field
            registerPasswordField.addEventListener('input', updatePasswordRequirements);

            // Function to check if passwords meet the requirements and match
            const checkPasswordMatch = () => {
                const passwordValue = registerPasswordField.value;
                const confirmPasswordValue = confirmPasswordField.value;

                // Check if password meets all requirements and confirm passwords match
                const isPasswordValid = passwordValue.length >= 8 &&
                    requirements.uppercase.test(passwordValue) &&
                    requirements.lowercase.test(passwordValue) &&
                    requirements.special.test(passwordValue) &&
                    requirements.number.test(passwordValue);

                if (isPasswordValid) {
                    if (passwordValue === confirmPasswordValue) {
                        passwordMatchMessage.textContent = 'Password Match';
                        passwordMatchMessage.style.display = 'inline';
                        passwordMatchMessage.classList.remove('error');
                        registerButton.disabled = false; // Enable the button when passwords match and are valid
                    } else {
                        passwordMatchMessage.textContent = 'Passwords do not match';
                        passwordMatchMessage.style.display = 'inline';
                        passwordMatchMessage.classList.add('error');
                        registerButton.disabled = true; // Disable the button if passwords don't match
                    }
                } else {
                    passwordMatchMessage.style.display = 'none'; // Hide the message if password doesn't meet requirements
                    registerButton.disabled = true; // Disable the button if password is invalid
                }
            };

            // Ensure that the message shows even when the user clicks outside or focuses on other fields
            confirmPasswordField.addEventListener('input', checkPasswordMatch);

            // Always show the password match message while typing in the Confirm Password field
            confirmPasswordField.addEventListener('focus', () => {
                if (registerPasswordField.value && confirmPasswordField.value) {
                    checkPasswordMatch(); // Recheck on focus to update message if necessary
                }
            });

            // Optionally hide the message when the Confirm Password field loses focus
            confirmPasswordField.addEventListener('blur', () => {
                checkPasswordMatch(); // Ensure message remains visible on blur
            });

            registerButton.disabled = true;

        });

        document.querySelector('input[name="username"]').addEventListener('input', function() {
            let username = this.value;

            // Show message only if the username is not empty
            const usernameMessage = document.getElementById('usernameMessage');

            if (username.length > 0) {
                // Make an AJAX call to check if username exists
                fetch('check_username.php?username=' + username)
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            usernameMessage.style.display = 'inline'; // Show message if username exists
                        } else {
                            usernameMessage.style.display = 'none'; // Hide message if username is available
                        }
                    })
                    .catch(error => console.error('Error:', error));
            } else {
                usernameMessage.style.display = 'none'; // Hide message if username is empty
            }
        });
    </script>

</body>

</html>