<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include "connect.php";

// Check if user is logged in and is a manager
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'manager') {
    error_log("Session check failed: " . print_r($_SESSION, true));
    header("Location: login.php");
    exit();
}

// Debug session
error_log("Current session data: " . print_r($_SESSION, true));

// Fetch staff from users table
$staff = [];
$staff_query = "SELECT * FROM users WHERE role = 'Staff' ORDER BY name";
try {
    $staff_result = $conn->query($staff_query);
    if ($staff_result === false) {
        error_log("Database Error (staff query): " . $conn->error);
    } else {
        while ($row = $staff_result->fetch_assoc()) {
            $staff[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching staff: " . $e->getMessage());
}

// Fetch customers from users table
$customers = [];
$customers_query = "SELECT * FROM users WHERE role = 'customer' ORDER BY name";
try {
    $customers_result = $conn->query($customers_query);
    if ($customers_result === false) {
        error_log("Database Error (customers query): " . $conn->error);
    } else {
        while ($row = $customers_result->fetch_assoc()) {
            $customers[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Error fetching customers: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manager Dashboard - Pharma-Corp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #2c9db7;
            --secondary-color: #858796;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fc;
        }

        .navbar {
            background-color: var(--primary-color);
        }

        .dashboard-header {
            background: linear-gradient(rgba(44, 157, 183, 0.1), rgba(44, 157, 183, 0.1));
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background-color: var(--primary-color);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1rem 1.5rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            border-top: none;
            color: var(--secondary-color);
        }

        .status-badge {
            padding: 0.5em 1em;
            border-radius: 30px;
            font-size: 0.85em;
        }

        .status-pending {
            background-color: #ffd700;
            color: #000;
        }

        .status-approved {
            background-color: #28a745;
            color: #fff;
        }

        .status-rejected {
            background-color: #dc3545;
            color: #fff;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Pharma-Corp</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="inventory-report.php">Inventory Report</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <h1 class="h3 mb-0 text-gray-800">Manager Dashboard</h1>
            <p class="mb-0">Welcome, <?php echo htmlspecialchars($_SESSION['name'] ?? 'Manager'); ?></p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container">
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Staff Members</h5>
                        <span class="badge bg-white text-primary"><?php echo count($staff); ?> Total</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($staff as $member): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($member['name']); ?></td>
                                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = '';
                                            switch($member['status']) {
                                                case 'pending':
                                                    $statusClass = 'bg-warning';
                                                    break;
                                                case 'approved':
                                                    $statusClass = 'bg-success';
                                                    break;
                                                case 'rejected':
                                                    $statusClass = 'bg-danger';
                                                    break;
                                                default:
                                                    $statusClass = 'bg-secondary';
                                            }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?> status-badge">
                                                <?php echo ucfirst(htmlspecialchars($member['status'])); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($staff)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No staff members found</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Registered Customers</h5>
                        <span class="badge bg-white text-primary"><?php echo count($customers); ?> Total</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($customers as $customer): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                                        <td>Customer</td>
                                       
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php if (empty($customers)): ?>
                                    <tr>
                                        <td colspan="3" class="text-center">No customers found</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
