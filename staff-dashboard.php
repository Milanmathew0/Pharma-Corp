<?php
session_start();

// Check if user is logged in, is staff, and is approved
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff' || $_SESSION['status'] !== 'approved') {
    header("Location: login.php");
    exit();
}

// Add cache control headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include "connect.php";

// Initialize variables
$pendingCount = 0;

// Check if prescriptions table exists
$tableExists = $conn->query("SHOW TABLES LIKE 'prescriptions'");
if ($tableExists->num_rows > 0) {
    // Get pending prescriptions count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM prescriptions WHERE status = 'pending'");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result();
        $pendingCount = $result->fetch_assoc()['count'];
    }
}

// Fetch customers from users table where role is 'user'
$customers_query = "SELECT * FROM users WHERE role = 'customer' ORDER BY user_id";
$customers_result = $conn->query($customers_query);

if (!$customers_result) {
    die("Query failed: " . $conn->error);
}

// Get counts for stats
$customer_count_query = "SELECT COUNT(*) as count FROM users WHERE role = 'customer'";
$customer_count_result = $conn->query($customer_count_query);

if (!$customer_count_result) {
    die("Query failed: " . $conn->error);
}

$customer_count = $customer_count_result->fetch_assoc();

$low_stock_query = "SELECT COUNT(*) as count FROM medicines WHERE stock_quantity <= 10";
$low_stock_result = $conn->query($low_stock_query);

if (!$low_stock_result) {
    die("Query failed: " . $conn->error);
}

$low_stock = $low_stock_result->fetch_assoc();

$total_medicines_query = "SELECT COUNT(*) as count FROM medicines";
$total_medicines_result = $conn->query($total_medicines_query);

if (!$total_medicines_result) {
    die("Query failed: " . $conn->error);
}

$total_medicines = $total_medicines_result->fetch_assoc();

$expiring_soon_query = "SELECT COUNT(*) as count FROM medicines WHERE expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY)";
$expiring_soon_result = $conn->query($expiring_soon_query);

if (!$expiring_soon_result) {
    die("Query failed: " . $conn->error);
}

$expiring_soon = $expiring_soon_result->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Pharma Corp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c9db7;
            --secondary-color: #858796;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(rgba(44, 157, 183, 0.1), rgba(44, 157, 183, 0.1)),
                        url('pic/3.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            padding: 20px 0;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .text-muted {
            color: var(--secondary-color) !important;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(44, 157, 183, 0.2);
        }

        .card-header {
            background: linear-gradient(to right, var(--primary-color), #3ab7d3);
            color: white;
            border: none;
            padding: 15px 20px;
        }

        .card-header h5 {
            margin: 0;
            font-weight: 600;
        }

        .feature-card {
            padding: 30px;
            text-align: center;
            height: 100%;
        }

        .feature-card .icon-large {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            transition: transform 0.3s ease;
        }

        .feature-card:hover .icon-large {
            transform: scale(1.1);
        }

        .feature-card h5 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .btn {
            padding: 8px 20px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background: #248ca3;
            border-color: #248ca3;
            transform: translateY(-2px);
        }

        .table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th {
            background-color: #f8f9fc;
            border-bottom: 2px solid #e3e6f0;
            color: var(--primary-color);
            font-weight: 600;
        }

        .table td {
            vertical-align: middle;
        }

        .badge {
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 500;
        }

        .btn-group .btn {
            padding: 5px 10px;
            margin: 0 2px;
        }

        /* Stats cards */
        .stats-card {
            padding: 20px;
            text-align: center;
            border-radius: 15px;
            background: white;
            transition: all 0.3s ease;
        }

        .stats-card h6 {
            color: var(--secondary-color);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .stats-card h3 {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        .stats-card.danger h3 {
            color: #dc3545;
        }

        .stats-card.warning h3 {
            color: #ffc107;
        }

        /* DataTables customization */
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary-color) !important;
            color: white !important;
            border: none;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #248ca3 !important;
            color: white !important;
            border: none;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 2px solid #e3e6f0;
            border-radius: 10px;
            padding: 5px 10px;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(44, 157, 183, 0.25);
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h2>Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Staff Member') ?></h2>
                <p class="text-muted">Manage inventory and handle customer requests</p>
            </div>
            <div class="col-auto d-flex gap-2">
                <a href="logout.php" class="btn btn-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6>Total Customers</h6>
                        <h3><?= $customer_count['count'] ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card danger">
                    <div class="card-body">
                        <h6>Low Stock Items</h6>
                        <h3><?= $low_stock['count'] ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6>Total Medicines</h6>
                        <h3><?= $total_medicines['count'] ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card warning">
                    <div class="card-body">
                        <h6>Expiring Soon</h6>
                        <h3><?= $expiring_soon['count'] ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Customer List -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Customer List</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="customerTable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Fetch only customers (role = 'customer')
                                    $users_query = "SELECT user_id, name, email, phone FROM users WHERE role = 'customer' ORDER BY user_id";
                                    $users_result = $conn->query($users_query);
                                    
                                    if ($users_result) {
                                        while ($user = $users_result->fetch_assoc()): 
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['user_id']) ?></td>
                                        <td><?= htmlspecialchars($user['name']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= htmlspecialchars($user['phone']) ?></td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    } else {
                                        echo "<tr><td colspan='4' class='text-center'>Error loading customers: " . $conn->error . "</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Feature Cards -->
        <div class="row g-4">
            <!-- Manage Medicines -->
            <div class="col-md-4">
                <div class="card h-100 feature-card">
                    <div class="card-body text-center">
                        <i class="bi bi-capsule icon-large text-primary"></i>
                        <h5 class="card-title">Manage Medicines</h5>
                        <p class="card-text">Add, update, and manage medicine inventory and stock levels.</p>
                        <a href="staff-inventory.php" class="btn btn-primary">
                            <i class="bi bi-capsule"></i> Manage Medicines
                        </a>
                    </div>
                </div>
            </div>

            <!-- Manage Orders -->
            <div class="col-md-4">
                <div class="card h-100 feature-card">
                    <div class="card-body text-center">
                        <i class="bi bi-bag-check icon-large text-success"></i>
                        <h5 class="card-title">Manage Orders</h5>
                        <p class="card-text">Process customer orders, update status, and handle deliveries.</p>
                        <a href="staff-orders.php" class="btn btn-success">
                            <i class="bi bi-bag-check"></i> Process Orders
                        </a>
                    </div>
                </div>
            </div>

            <!-- Stock Management -->
            <div class="col-md-4">
                <div class="card h-100 feature-card">
                    <div class="card-body text-center">
                        <i class="bi bi-box-seam icon-large text-info"></i>
                        <h5 class="card-title">Stock Management</h5>
                        <p class="card-text">Monitor stock levels, handle expiry dates, and manage inventory.</p>
                        <a href="staff-stock.php" class="btn btn-info">
                            <i class="bi bi-box-seam"></i> Manage Stock
                        </a>
                    </div>
                </div>
            </div>

            <!-- Manage Prescriptions -->
            <div class="col-md-4">
                <div class="card h-100 feature-card">
                    <div class="card-body text-center">
                        <i class="bi bi-file-medical icon-large text-primary"></i>
                        <h5 class="card-title">Manage Prescriptions</h5>
                        <p class="card-text">Review and manage customer prescription uploads.</p>
                        <a href="manage-prescriptions.php" class="btn btn-primary">
                            <i class="bi bi-file-earmark-medical"></i> View Prescriptions
                            <?php if ($pendingCount > 0): ?>
                                <span class="badge bg-danger"><?= $pendingCount ?> Pending</span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#customerTable').DataTable({
                "pageLength": 10,
                "order": [[0, "asc"]],
                "language": {
                    "search": "Search customers:",
                    "lengthMenu": "Show _MENU_ customers per page",
                    "info": "Showing _START_ to _END_ of _TOTAL_ customers"
                }
            });
        });
    </script>
</body>
</html>
