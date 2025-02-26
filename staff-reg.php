<?php
session_start();
include "connect.php"; // Database connection

// First, add status column if it doesn't exist
$alterTable = "ALTER TABLE users 
               ADD COLUMN IF NOT EXISTS status ENUM('pending', 'approved', 'rejected') 
               DEFAULT 'pending' AFTER role";
try {
    $conn->query($alterTable);
} catch (Exception $e) {
    // Column might already exist, continue
}

$message = "";
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirmPassword = trim($_POST['confirm-password']);

    // Validate username
    if (empty($username)) {
        $errors[] = "Username is required";
    } elseif (!preg_match('/^[a-zA-Z0-9_]{3,20}$/', $username)) {
        $errors[] = "Username must be 3-20 characters and can only contain letters, numbers, and underscores";
    }

    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address";
    }

    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter, one lowercase letter, one number, and one special character";
    }

    // Validate confirm password
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Check if email already exists in users table
        $checkSql = "SELECT email FROM users WHERE email = ?";
        if ($stmt = $conn->prepare($checkSql)) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $message = "<div class='alert alert-danger'>Email already exists!</div>";
            } else {
                // Insert into users table
                $insertSql = "INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'Staff')";
                if ($insertStmt = $conn->prepare($insertSql)) {
                    $insertStmt->bind_param("sss", $username, $email, $hashedPassword);
                    
                    if ($insertStmt->execute()) {
                        $message = "<div class='alert alert-success'>Registration request submitted! Please wait for admin approval.</div>";
                    } else {
                        $message = "<div class='alert alert-danger'>Error occurred: " . $conn->error . "</div>";
                    }
                    $insertStmt->close();
                } else {
                    $message = "<div class='alert alert-danger'>Error preparing statement: " . $conn->error . "</div>";
                }
            }
            $stmt->close();
        } else {
            $message = "<div class='alert alert-danger'>Error checking email: " . $conn->error . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Registration Request</title>
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
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(10px);
            padding: 2.5rem;
            border-radius: 15px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 420px;
            margin: 20px;
            transform: translateY(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .container:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        h2 {
            color: #1a365d;
            margin-bottom: 35px;
            font-size: 36px;
            font-weight: 700;
            text-align: center;
            letter-spacing: -0.5px;
            position: relative;
            padding-bottom: 15px;
            background: linear-gradient(45deg, #1a365d, #4299e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: linear-gradient(45deg, #4299e1, #2b6cb0);
            border-radius: 4px;
        }

        .form-group {
            margin-bottom: 28px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 10px;
            color: #2d3748;
            font-weight: 500;
            font-size: 15px;
            transition: color 0.3s ease;
        }

        .form-group input {
            width: 100%;
            padding: 16px;
            padding-left: 45px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(248, 250, 252, 0.9);
        }

        .form-group input:focus {
            outline: none;
            border-color: #4299e1;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(66, 153, 225, 0.15);
        }

        .form-group i {
            position: absolute;
            left: 16px;
            top: 46px;
            color: #718096;
            transition: all 0.3s ease;
        }

        .form-group input:focus + i {
            color: #4299e1;
            transform: scale(1.1);
        }

        .error-message {
            color: #c53030;
            font-size: 0.875rem;
            margin-top: 8px;
            display: block;
            opacity: 0;
            transform: translateY(-10px);
            animation: fadeInUp 0.3s forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        button {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            background: linear-gradient(45deg, #4299e1, #2b6cb0);
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 12px rgba(66, 153, 225, 0.2);
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(66, 153, 225, 0.3);
            background: linear-gradient(45deg, #3182ce, #2c5282);
        }

        button:active {
            transform: translateY(0);
            box-shadow: 0 2px 8px rgba(66, 153, 225, 0.2);
        }

        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-weight: 500;
            animation: fadeIn 0.3s ease;
        }

        .alert-danger {
            background: #fff5f5;
            color: #c53030;
            border: 1px solid #feb2b2;
        }

        .alert-success {
            background: #f0fff4;
            color: #2f855a;
            border: 1px solid #9ae6b4;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .password-strength {
            margin-top: 0.5rem;
            font-size: 0.75rem;
        }

        .strength-weak { color: #c53030; }
        .strength-medium { color: #d69e2e; }
        .strength-strong { color: #2f855a; }

        .text-center {
            text-align: center;
        }

        .mt-3 {
            margin-top: 1rem;
        }

        a {
            color: #4299e1;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        a:hover {
            color: #2b6cb0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Staff Registration</h2>
        <?php 
        if (!empty($errors)) {
            foreach ($errors as $error) {
                echo "<div class='alert alert-danger'>" . htmlspecialchars($error) . "</div>";
            }
        } elseif (!empty($message)) {
            echo $message;
        }
        ?>
        <form action="" method="POST" id="staffRegForm" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" required 
                       pattern="[a-zA-Z0-9_]{3,20}"
                       title="Username must be 3-20 characters and can only contain letters, numbers, and underscores"
                       value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                <i class="fas fa-user"></i>
                <small class="error-message" id="usernameError"></small>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" required
                       pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                       title="Please enter a valid email address"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <i class="fas fa-envelope"></i>
                <small class="error-message" id="emailError"></small>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" required
                       minlength="8"
                       title="Password must be at least 8 characters with uppercase, lowercase, number, and special character">
                <i class="fas fa-lock"></i>
                <small class="error-message" id="passwordError"></small>
                <div class="password-strength" id="passwordStrength"></div>
            </div>

            <div class="form-group">
                <label for="confirm-password">Confirm Password</label>
                <input type="password" name="confirm-password" id="confirm-password" required>
                <i class="fas fa-lock"></i>
                <small class="error-message" id="confirmPasswordError"></small>
            </div>

            <button type="submit">Submit Request</button>
        </form>

        <div class="text-center mt-3">
            <p>Your request will be reviewed by an administrator.</p>
            <p>Already registered? <a href="login.php">Login here</a></p>
        </div>
    </div>

    <script>
        function validateForm() {
            let isValid = true;
            const username = document.getElementById('username');
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm-password');

            // Reset all error messages
            document.querySelectorAll('.error-message').forEach(el => el.textContent = '');
            document.querySelectorAll('input').forEach(el => el.classList.remove('error'));

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

        // Password strength checker
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
                strengthDiv.className = 'password-strength strength-weak';
            } else if (strength < 5) {
                strengthDiv.textContent = 'Password Strength: Medium';
                strengthDiv.className = 'password-strength strength-medium';
            } else {
                strengthDiv.textContent = 'Password Strength: Strong';
                strengthDiv.className = 'password-strength strength-strong';
            }
        }

        // Real-time validation
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
