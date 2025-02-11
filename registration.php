<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professional Registration</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-image: url('/api/placeholder/1920/1080');
            background-size: cover;
            background-position: center;
            padding: 20px;
            position: relative;
            color: #2d3748;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(66, 153, 225, 0.9), rgba(49, 130, 206, 0.85));
            animation: gradientBG 20s ease infinite;
            background-size: 400% 400%;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50% }
            50% { background-position: 100% 50% }
            100% { background-position: 0% 50% }
        }

        .container {
            position: relative;
            background: rgba(255, 255, 255, 0.95);
            padding: 40px;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
            width: 100%;
            max-width: 480px;
            backdrop-filter: blur(20px);
            transform: translateY(0);
            transition: all 0.4s ease;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .container:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 70px rgba(0, 0, 0, 0.5);
        }

        h2 {
            text-align: center;
            color: #1a365d;
            margin-bottom: 35px;
            font-size: 36px;
            font-weight: 700;
            letter-spacing: -0.5px;
            position: relative;
            padding-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: linear-gradient(45deg, #4299e1, #2b6cb0);
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #2d3748;
            font-weight: 500;
            font-size: 15px;
            transition: color 0.3s ease;
            letter-spacing: 0.3px;
        }

        .form-group input {
            width: 100%;
            padding: 16px;
            padding-left: 45px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: rgba(248, 250, 252, 0.8);
            color: #2d3748;
            font-weight: 400;
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
            transition: color 0.3s ease;
        }

        .form-group input:focus + i {
            color: #4299e1;
        }

        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 16px;
            top: 46px;
            cursor: pointer;
            color: #718096;
            transition: color 0.3s ease;
        }

        button {
            width: 100%;
            padding: 16px;
            background: linear-gradient(45deg, #4299e1, #2b6cb0);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s ease;
            box-shadow: 0 4px 12px rgba(43, 108, 176, 0.2);
            position: relative;
            overflow: hidden;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                120deg,
                transparent,
                rgba(255, 255, 255, 0.3),
                transparent
            );
            transition: 0.5s;
        }

        button:hover::before {
            left: 100%;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(43, 108, 176, 0.3);
            background: linear-gradient(45deg, #3182ce, #2c5282);
        }

        ::placeholder {
            color: #a0aec0;
            font-weight: 400;
        }

        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }
            
            h2 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Create Account</h2>
        <form action="connect.php" method="post">
            <div class="form-group">
                <label for="user_id">User ID</label>
                <input type="text" id="user_id" name="user_id" placeholder="Enter your user ID">
                <i class="fas fa-user"></i>
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required placeholder="Choose a username">
                <i class="fas fa-user-circle"></i>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required placeholder="Enter your email">
                <i class="fas fa-envelope"></i>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-container">
                    <input type="password" id="password" name="password" required placeholder="Create a password">
                    <i class="fas fa-lock"></i>
                    <i class="fas fa-eye toggle-password"></i>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm-password">Confirm Password</label>
                <div class="password-container">
                    <input type="password" id="confirm-password" name="confirm-password" required placeholder="Confirm your password">
                    <i class="fas fa-lock"></i>
                    <i class="fas fa-eye toggle-password"></i>
                </div>
            </div>

            <button type="submit" name="submit">Create Account</button>
            <div style="text-align: center; margin-top: 20px; font-size: 15px;">
                Already have an account? <a href="login.php" style="color: #4299e1; text-decoration: none; font-weight: 500; transition: color 0.3s ease;">Login</a>
            </div>
        </form>
    </div>

    <script>
        document.querySelectorAll('.toggle-password').forEach(toggle => {
            toggle.addEventListener('click', function() {
                const input = this.previousElementSibling.previousElementSibling;
                const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                input.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        });

        document.getElementById('confirm-password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity("Passwords do not match");
            } else {
                this.setCustomValidity("");
            }
        });
    </script>
</body>
</html>