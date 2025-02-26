<?php
session_start();

// Database connection details
$host = 'localhost';
$dbname = 'pharma';
$username = 'root';
$password = ''; // Empty password for root user

try {
    // Establish database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = trim($_POST["email"]);
        $password = trim($_POST["password"]);
        $error = "";

        // Check if user exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Check if user is staff and verify status
            if ($user['role'] === 'Staff') {
                if ($user['status'] !== 'approved') {
                    $error = "Your staff account is pending approval";
                }
            }
            
            if (empty($error)) {
                // Login successful
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['status'] = $user['status'];
                
                // Debug information
                error_log("User Role: " . $user['role']);
                error_log("Session Data: " . print_r($_SESSION, true));
                
                // Redirect based on role
                switch($user['role']) {
                    case 'Admin':
                        header("Location: admin-dashboard.php");
                        break;
                    case 'Manager':
                        header("Location: manager-dashboard.php");
                        break;
                    case 'Staff':
                        header("Location: staff-dashboard.php");
                        break;
                    case 'Customer':
                        header("Location: customer-dashboard.php");
                        break;
                    default:
                        $error = "Invalid user role";
                        break;
                }
                if (empty($error)) {
                    exit();
                }
            }
        } else {
            $error = "Invalid email or password";
        }
    }
} catch(PDOException $e) {
    $error = "Connection failed: " . $e->getMessage();
    error_log("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Pharmacy Management System</title>
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
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            margin: 20px;
        }
        .logo { 
            text-align: center; 
            margin-bottom: 1.5rem; 
        }
        .logo img { 
            max-width: 150px; 
            height: auto; 
        }
        h2 { 
            color: #1a365d; 
            margin-bottom: 1.5rem; 
            text-align: center; 
        }
        .form-group { 
            margin-bottom: 1.25rem; 
        }
        .form-group label { 
            display: block; 
            margin-bottom: 0.5rem; 
            color: #2d3748; 
            font-weight: 500; 
        }
        .form-group input { 
            width: 100%; 
            padding: 0.75rem; 
            border: 2px solid #e2e8f0; 
            border-radius: 8px; 
            font-size: 1rem; 
            transition: border-color 0.2s ease;
        }
        .form-group input:focus { 
            border-color: #4299e1; 
            outline: none; 
            box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1); 
        }
        .password-container { 
            position: relative; 
        }
        .toggle-password { 
            position: absolute; 
            right: 1rem; 
            top: 50%; 
            transform: translateY(-50%); 
            cursor: pointer; 
            color: #4a5568; 
        }
        .btn-login { 
            width: 100%; 
            padding: 0.75rem; 
            background: #4299e1; 
            color: white; 
            border: none; 
            border-radius: 8px; 
            font-size: 1rem; 
            font-weight: 600; 
            cursor: pointer; 
            transition: background-color 0.2s ease; 
        }
        .btn-login:hover { 
            background: #2b6cb0; 
        }
        .error { 
            background: #fff5f5; 
            color: #c53030; 
            padding: 0.75rem; 
            border-radius: 8px; 
            margin-bottom: 1rem; 
            font-size: 0.875rem; 
            text-align: center; 
        }
        .register-link { 
            text-align: center; 
            margin-top: 1rem; 
            color: #4a5568; 
        }
        .register-link a { 
            color: #4299e1; 
            text-decoration: none; 
            font-weight: 500; 
        }
        .register-link a:hover { 
            text-decoration: underline; 
        }
        .error-message {
            color: #c53030;
            font-size: 0.75rem;
            margin-top: 0.25rem;
            display: block;
        }
        .form-group input.error {
            border-color: #c53030;
        }
        .form-group input.error:focus {
            box-shadow: 0 0 0 3px rgba(197, 48, 48, 0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <!-- Add your logo here -->
            <h2>Pharmacy Management System</h2>
        </div>
        
        <?php if (isset($error) && !empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" id="loginForm" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required 
                       placeholder="Enter your email"
                       pattern="[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}$"
                       title="Please enter a valid email address"
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                <small class="error-message" id="emailError"></small>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" required 
                           placeholder="Enter your password"
                           minlength="6"
                           title="Password must be at least 6 characters long">
                    <i class="fas fa-eye toggle-password"></i>
                </div>
                <small class="error-message" id="passwordError"></small>
            </div>

            <button type="submit" class="btn-login">Sign In</button>
            
            <div class="register-link">
                <p>Don't have an account? <a href="registration.php">Register</a></p>
                
            </div>
        </form>
    </div>

    <script>
        document.querySelector('.toggle-password').addEventListener('click', function() {
            const passwordField = document.getElementById('password');
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        function validateForm() {
            let isValid = true;
            const email = document.getElementById('email');
            const password = document.getElementById('password');
            const emailError = document.getElementById('emailError');
            const passwordError = document.getElementById('passwordError');

            // Reset error messages and styles
            emailError.textContent = '';
            passwordError.textContent = '';
            email.classList.remove('error');
            password.classList.remove('error');

            // Email validation
            const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
            if (!email.value.trim()) {
                emailError.textContent = 'Email is required';
                email.classList.add('error');
                isValid = false;
            } else if (!emailPattern.test(email.value)) {
                emailError.textContent = 'Please enter a valid email address';
                email.classList.add('error');
                isValid = false;
            }

            // Password validation
            if (!password.value) {
                passwordError.textContent = 'Password is required';
                password.classList.add('error');
                isValid = false;
            } else if (password.value.length < 6) {
                passwordError.textContent = 'Password must be at least 6 characters long';
                password.classList.add('error');
                isValid = false;
            }

            return isValid;
        }

        // Real-time validation
        document.getElementById('email').addEventListener('input', function() {
            if (this.value.trim()) {
                const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                if (!emailPattern.test(this.value)) {
                    document.getElementById('emailError').textContent = 'Please enter a valid email address';
                    this.classList.add('error');
                } else {
                    document.getElementById('emailError').textContent = '';
                    this.classList.remove('error');
                }
            }
        });

        document.getElementById('password').addEventListener('input', function() {
            if (this.value.length > 0 && this.value.length < 6) {
                document.getElementById('passwordError').textContent = 'Password must be at least 6 characters long';
                this.classList.add('error');
            } else {
                document.getElementById('passwordError').textContent = '';
                this.classList.remove('error');
            }
        });
    </script>
</body>
</html>