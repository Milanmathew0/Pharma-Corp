<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Add cache control headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Pharma Corp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c9db7;
            --secondary-color: #858796;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: url('pic/3.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            min-height: 100vh;
        }

        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.95);
            z-index: 0;
        }

        .navbar {
            background-color: var(--primary-color);
            padding: 1rem 2rem;
            position: relative;
            z-index: 1;
        }

        .container {
            position: relative;
            z-index: 1;
            padding: 2rem 15px;
        }

        .card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            transition: all 0.3s ease;
            border: 1px solid rgba(44, 157, 183, 0.1);
            overflow: hidden;
        }

        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(44, 157, 183, 0.2);
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            border-bottom: none;
        }

        .card-header i {
            font-size: 1.8rem;
            margin-right: 10px;
        }

        .card-header h2 {
            margin: 0;
            font-size: 1.5rem;
            color: white;
        }

        .card-content {
            padding: 1.5rem;
        }

        .order-list li, .notification-list li {
            background: rgba(44, 157, 183, 0.05);
            border-radius: 10px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .order-list li:hover, .notification-list li:hover {
            background: rgba(44, 157, 183, 0.1);
        }

        .status {
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 500;
        }

        .status-pending {
            background: #fff7ed;
            color: #f97316;
        }

        .status-available {
            background: #f0fdf4;
            color: #22c55e;
        }

        .status-processing {
            background: #fef3c7;
            color: #f59e0b;
        }

        .medicines-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .medicine-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .medicine-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(44, 157, 183, 0.2);
        }

        .btn {
            background: var(--primary-color);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: var(--secondary-color);
            transform: translateY(-2px);
        }

        .feature-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 25px rgba(44, 157, 183, 0.2);
        }

        .icon-large {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .medicines-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <h1><i class="fas fa-clinic-medical"></i> Pharma-Corp</h1>
        <div class="nav-links">
            <a href="search-medicines.php" class="nav-link">
                <i class="fas fa-search"></i> Search Medicines
            </a>
            <a href="#" class="nav-link">
                <i class="fas fa-shopping-cart"></i> Cart
            </a>
            <a href="#" class="nav-link">
                <i class="fas fa-history"></i> Orders
            </a>
        </div>
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span>Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Customer') ?></span>
            <a href="customer-profile.php" class="btn btn-info">
                <i class="bi bi-person"></i> My Profile
            </a>
            <a href="logout.php" class="btn btn-danger">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </nav>

    <div class="container">
        <!-- Order Tracking -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-truck"></i>
                <h2>Order Tracking</h2>
            </div>
            <div class="card-content">
                <ul class="order-list">
                    <li>
                        <span><i class="fas fa-box"></i> Order #1234 - Amoxicillin</span>
                        <span class="status status-pending">
                            <i class="fas fa-clock"></i> Pending
                        </span>
                    </li>
                    <li>
                        <span><i class="fas fa-box"></i> Order #1235 - Paracetamol</span>
                        <span class="status status-available">
                            <i class="fas fa-check"></i> Available
                        </span>
                    </li>
                    <li>
                        <span><i class="fas fa-box"></i> Order #1236 - Aspirin</span>
                        <span class="status status-processing">
                            <i class="fas fa-cog fa-spin"></i> Processing
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Notifications -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-bell"></i>
                <h2>Notifications</h2>
            </div>
            <div class="card-content">
                <ul class="notification-list">
                    <li class="new-notification">
                        <span>
                            <i class="fas fa-clock"></i>
                            Refill reminder: Your prescription for Metformin is due
                        </span>
                        <small>Just now</small>
                    </li>
                    <li>
                        <span>
                            <i class="fas fa-check-circle"></i>
                            Your requested medicine Amoxicillin is now available
                        </span>
                        <small>2h ago</small>
                    </li>
                    <li>
                        <span>
                            <i class="fas fa-info-circle"></i>
                            New stock alert: Vitamin C supplements are back in stock
                        </span>
                        <small>5h ago</small>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Profile Management -->
        <div class="card">
            <div class="card-header">
                <i class="fas fa-shopping-cart"></i>
                <h2>Available Medicines</h2>
            </div>
            <div class="card-content">
                <div class="medicines-grid">
                    <div class="medicine-card">
                        <i class="fas fa-pills"></i>
                        <h3>Paracetamol</h3>
                        <p>Stock: 150</p>
                        <button class="btn">
                            <i class="fas fa-shopping-cart"></i> Purchase
                        </button>
                    </div>
                    <div class="medicine-card">
                        <i class="fas fa-capsules"></i>
                        <h3>Aspirin</h3>
                        <p>Stock: 200</p>
                        <button class="btn">
                            <i class="fas fa-shopping-cart"></i> Purchase
                        </button>
                    </div>
                    <div class="medicine-card">
                        <i class="fas fa-tablets"></i>
                        <h3>Vitamin C</h3>
                        <p>Stock: 100</p>
                        <button class="btn">
                            <i class="fas fa-shopping-cart"></i> Purchase
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feature Cards -->
        <div class="row g-4">
            <!-- Search Medicines -->
            <div class="col-md-4">
                <div class="card h-100 feature-card">
                    <div class="card-body text-center">
                        <i class="bi bi-search icon-large text-primary"></i>
                        <h5 class="card-title">Search Medicines</h5>
                        <p class="card-text">Find and browse available medicines in our inventory.</p>
                        <a href="search-medicines.php" class="btn btn-primary">
                            <i class="bi bi-search"></i> Search Now
                        </a>
                    </div>
                </div>
            </div>

            <!-- View Cart -->
            <div class="col-md-4">
                <div class="card h-100 feature-card">
                    <div class="card-body text-center">
                        <i class="bi bi-cart icon-large text-success"></i>
                        <h5 class="card-title">Shopping Cart</h5>
                        <p class="card-text">View and manage items in your shopping cart.</p>
                        <a href="view_cart.php" class="btn btn-success">
                            <i class="bi bi-cart"></i> View Cart
                        </a>
                    </div>
                </div>
            </div>

            <!-- My Profile -->
            <div class="col-md-4">
                <div class="card h-100 feature-card">
                    <div class="card-body text-center">
                        <i class="bi bi-person-circle icon-large text-info"></i>
                        <h5 class="card-title">My Profile</h5>
                        <p class="card-text">View and update your personal information.</p>
                        <a href="customer-profile.php" class="btn btn-info">
                            <i class="bi bi-person"></i> Manage Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>