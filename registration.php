<?php
// Start session for flash messages
session_start();

// Database connection
$host = "localhost";
$dbname = "pharma";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $user_id = trim($_POST['user_id']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $confirmPassword = trim($_POST['confirm-password']);

        // Enhanced server-side validation
        if (empty($user_id) || empty($username) || empty($email) || empty($password)) {
            $_SESSION['error'] = "All fields are required";
            header("Location: registration.php");
            exit();
        }

        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = "Please enter a valid email address";
            header("Location: registration.php");
            exit();
        }

        // Validate username (alphanumeric and underscores only, 3-20 characters)
        if (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
            $_SESSION['error'] = "Username must be 3-20 characters and can only contain letters, numbers, and underscores";
            header("Location: registration.php");
            exit();
        }

        // Validate user_id (alphanumeric, 5-10 characters)
        if (!preg_match('/^[a-zA-Z0-9]{5,10}$/', $user_id)) {
            $_SESSION['error'] = "User ID must be 5-10 characters and can only contain letters and numbers";
            header("Location: registration.php");
            exit();
        }

        // Validate password strength
        if (strlen($password) < 8) {
            $_SESSION['error'] = "Password must be at least 8 characters long";
            header("Location: registration.php");
            exit();
        }

        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
            $_SESSION['error'] = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character";
            header("Location: registration.php");
            exit();
        }

        // Check if passwords match
        if ($password !== $confirmPassword) {
            $_SESSION['error'] = "Passwords do not match";
            header("Location: registration.php");
            exit();
        }

        // Check if user_id already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE user_id = ?");
        $stmt->execute([$user_id]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = "User ID already exists";
            header("Location: registration.php");
            exit();
        }

        // Check if email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = "Email already exists";
            header("Location: registration.php");
            exit();
        }

        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert into database
        $sql = "INSERT INTO users (user_id, username, email, password) VALUES (:user_id, :username, :email, :password)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $user_id,
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword
        ]);

        // Set success message and redirect
        $_SESSION['success'] = "Registration successful! Please login.";
        header("Location: login.php");
        exit();
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Registration failed: " . $e->getMessage();
    header("Location: registration.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Registration</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background: url('pic/1.jpg') no-repeat center center fixed;
            background-size: cover;
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2.5rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            margin: 20px;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 10px 35px 10px 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s;
            box-sizing: border-box;
        }

        .form-group input:focus {
            border-color: #4299e1;
            outline: none;
        }

        .form-group i {
            position: absolute;
            right: 12px;
            top: 38px;
            color: #666;
        }

        .error-message {
            color: #e53e3e;
            font-size: 12px;
            margin-top: 5px;
            display: block;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #4299e1;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        button:hover {
            background-color: #3182ce;
        }

        .text-center {
            text-align: center;
            margin-top: 1rem;
        }

        h2 {
            text-align: center;
            margin-bottom: 1.5rem;
            color: #2d3748;
        }

        .password-strength {
            margin-top: 5px;
            height: 5px;
            border-radius: 3px;
            transition: all 0.3s;
        }

        .flash-message {
            padding: 12px;
            margin-bottom: 1rem;
            border-radius: 5px;
            text-align: center;
        }

        .alert {
            padding: 12px;
            margin-bottom: 1rem;
            border-radius: 5px;
            background-color: #fed7d7;
            color: #c53030;
        }

        a {
            color: #4299e1;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Create Account</h2>
        <?php
        // Display error message if any
        if (isset($_SESSION['error'])) {
            echo '<div class="flash-message alert">' . htmlspecialchars($_SESSION['error']) . '</div>';
            unset($_SESSION['error']);
        }
        // Display success message if any
        if (isset($_SESSION['success'])) {
            echo '<div class="flash-message">' . htmlspecialchars($_SESSION['success']) . '</div>';
            unset($_SESSION['success']);
        }
        ?>
        <form action="" method="post" id="registrationForm" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="user_id">User ID</label>
                <input type="text" id="user_id" name="user_id" required 
                       placeholder="Enter your user ID"
                       pattern="[a-zA-Z0-9]{5,10}"
                       title="User ID must be 5-10 characters and can only contain letters and numbers">
                <i class="fas fa-user"></i>
                <small class="error-message" id="userIdError"></small>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required 
                       placeholder="Choose a username"
                       pattern="[a-zA-Z0-9_]{3,20}"
                       title="Username must be 3-20 characters and can only contain letters, numbers, and underscores">
                <i class="fas fa-user-circle"></i>
                <small class="error-message" id="usernameError"></small>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required 
                       placeholder="Enter your email"
                       pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$"
                       title="Please enter a valid email address">
                <i class="fas fa-envelope"></i>
                <small class="error-message" id="emailError"></small>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" required 
                           placeholder="Create a password"
                           minlength="8"
                           title="Password must be at least 8 characters long">
                    <i class="fas fa-lock"></i>
                    <i class="fas fa-eye toggle-password"></i>
                </div>
                <small class="error-message" id="passwordError"></small>
                <div class="password-strength" id="passwordStrength"></div>
            </div>

            <div class="form-group">
                <label for="confirm-password">Confirm Password</label>
                <div class="password-container">
                    <input type="password" id="confirm-password" name="confirm-password" required 
                           placeholder="Confirm your password">
                    <i class="fas fa-lock"></i>
                    <i class="fas fa-eye toggle-password"></i>
                </div>
                <small class="error-message" id="confirmPasswordError"></small>
            </div>

            <button type="submit" name="submit">Create Account</button>
            <div class="text-center">
                Already have an account? <a href="login.php">Login</a>
            </div>
            <div class="text-center">
                Staff member? <a href="staff-reg.php">Staff Login</a>
            </div>
        </form>
    </div>

    <script>
        // Password toggle functionality
        document.querySelectorAll('.toggle-password').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const input = this.previousElementSibling.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });

        function validateForm() {
            let isValid = true;
            const userId = document.getElementById('user_id');
            const username = document.getElementById('username');
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm-password');

            // Reset all error messages
            document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
            document.querySelectorAll('input').forEach(el => el.classList.remove('error'));

            // User ID validation
            if (!userId.value.trim()) {
                document.getElementById('userIdError').textContent = 'User ID is required';
                userId.classList.add('error');
                isValid = false;
            } else if (!/^[a-zA-Z0-9]{5,10}$/.test(userId.value)) {
                document.getElementById('userIdError').textContent = 'User ID must be 5-10 characters and can only contain letters and numbers';
                userId.classList.add('error');
                isValid = false;
            }

            // Username validation
            if (!username.value.trim()) {
                document.getElementById('usernameError').textContent = 'Username is required';
                username.classList.add('error');
                isValid = false;
            } else if (!/^[a-zA-Z0-9_]{3,20}$/.test(username.value)) {
                document.getElementById('usernameError').textContent = 'Username must be 3-20 characters and can only contain letters, numbers, and underscores';
                username.classList.add('error');
                isValid = false;
            }

            // Email validation
            const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!email.value.trim()) {
                document.getElementById('emailError').textContent = 'Email is required';
                email.classList.add('error');
                isValid = false;
            } else if (!emailPattern.test(email.value)) {
                document.getElementById('emailError').textContent = 'Please enter a valid email address';
                email.classList.add('error');
                isValid = false;
            }

            // Password validation
            if (!password.value) {
                document.getElementById('passwordError').textContent = 'Password is required';
                password.classList.add('error');
                isValid = false;
            } else if (password.value.length < 8) {
                document.getElementById('passwordError').textContent = 'Password must be at least 8 characters long';
                password.classList.add('error');
                isValid = false;
            } else if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/.test(password.value)) {
                document.getElementById('passwordError').textContent = 'Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character';
                password.classList.add('error');
                isValid = false;
            }

            // Confirm password validation
            if (password.value !== confirmPassword.value) {
                document.getElementById('confirmPasswordError').textContent = 'Passwords do not match';
                confirmPassword.classList.add('error');
                isValid = false;
            }

            return isValid;
        }

        // Real-time validation
        function checkPasswordStrength(password) {
            const strengthDiv = document.getElementById('passwordStrength');
            const hasUpperCase = /[A-Z]/.test(password);
            const hasLowerCase = /[a-z]/.test(password);
            const hasNumbers = /\d/.test(password);
            const hasSpecialChar = /[@$!%*?&]/.test(password);
            const isLongEnough = password.length >= 8;

            let strength = 0;
            strength += hasUpperCase ? 1 : 0;
            strength += hasLowerCase ? 1 : 0;
            strength += hasNumbers ? 1 : 0;
            strength += hasSpecialChar ? 1 : 0;
            strength += isLongEnough ? 1 : 0;

            if (password.length === 0) {
                strengthDiv.textContent = '';
            } else if (strength < 3) {
                strengthDiv.textContent = 'Password Strength: Weak';
                strengthDiv.className = 'password-strength';
                strengthDiv.style.background = 'red';
            } else if (strength < 5) {
                strengthDiv.textContent = 'Password Strength: Medium';
                strengthDiv.className = 'password-strength';
                strengthDiv.style.background = 'orange';
            } else {
                strengthDiv.textContent = 'Password Strength: Strong';
                strengthDiv.className = 'password-strength';
                strengthDiv.style.background = 'green';
            }
        }

        // Add real-time validation listeners
        document.getElementById('user_id').addEventListener('input', function() {
            const error = document.getElementById('userIdError');
            if (this.value.trim() && !/^[a-zA-Z0-9]{5,10}$/.test(this.value)) {
                error.textContent = 'User ID must be 5-10 characters and can only contain letters and numbers';
                this.classList.add('error');
            } else {
                error.textContent = '';
                this.classList.remove('error');
            }
        });

        document.getElementById('username').addEventListener('input', function() {
            const error = document.getElementById('usernameError');
            if (this.value.trim() && !/^[a-zA-Z0-9_]{3,20}$/.test(this.value)) {
                error.textContent = 'Username must be 3-20 characters and can only contain letters, numbers, and underscores';
                this.classList.add('error');
            } else {
                error.textContent = '';
                this.classList.remove('error');
            }
        });

        document.getElementById('email').addEventListener('input', function() {
            const error = document.getElementById('emailError');
            const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (this.value.trim() && !emailPattern.test(this.value)) {
                error.textContent = 'Please enter a valid email address';
                this.classList.add('error');
            } else {
                error.textContent = '';
                this.classList.remove('error');
            }
        });

        document.getElementById('password').addEventListener('input', function() {
            checkPasswordStrength(this.value);
            const error = document.getElementById('passwordError');
            if (this.value && (this.value.length < 8 || !/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/.test(this.value))) {
                error.textContent = 'Password must be at least 8 characters with uppercase, lowercase, number, and special character';
                this.classList.add('error');
            } else {
                error.textContent = '';
                this.classList.remove('error');
            }
        });

        document.getElementById('confirm-password').addEventListener('input', function() {
            const error = document.getElementById('confirmPasswordError');
            const password = document.getElementById('password').value;
            if (this.value && this.value !== password) {
                error.textContent = 'Passwords do not match';
                this.classList.add('error');
            } else {
                error.textContent = '';
                this.classList.remove('error');
            }
        });
    </script>
</body>
</html>