<?php
session_start();
include "connect.php";

// Check if user is logged in and is an admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    error_log("Access denied to admin dashboard - Current role: " . ($_SESSION['role'] ?? 'no role set'));
    header("Location: login.php");
    exit();
}

// Get admin name from database
$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT username FROM users WHERE email = ? AND LOWER(role) = 'admin'");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$admin_name = $admin['username'];

// Fetch total users count with role breakdown
$result = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$user_roles = array();
$total_users = 0; // Initialize total users counter
while ($row = $result->fetch_assoc()) {
    $user_roles[$row['role']] = $row['count'];
    $total_users += $row['count']; // Add each role count to total
}

// Fetch total medicines count and value
$result = $conn->query("SELECT 
    COUNT(*) as total_medicines,
    SUM(stock_quantity * price_per_unit) as total_value,
    AVG(price_per_unit) as avg_price
    FROM Medicines");
$medicine_stats = $result->fetch_assoc();

// Fetch order statistics
$result = $conn->query("SELECT 
    COUNT(*) as total_orders,
    SUM(total_amount) as total_revenue,
    AVG(total_amount) as avg_order_value,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders
    FROM orders");
$order_stats = $result->fetch_assoc();

// Fetch low stock items count (assuming threshold of 10)
$result = $conn->query("SELECT COUNT(*) as low_stock FROM Medicines WHERE stock_quantity <= 10");
$low_stock = $result->fetch_assoc()['low_stock'];

// Fetch monthly sales
$current_month = date('Y-m');
$result = $conn->query("SELECT SUM(total_amount) as monthly_sales FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = '$current_month'");
$monthly_sales = $result->fetch_assoc()['monthly_sales'] ?: 0;

// Fetch sales data for last 6 months
$sales_data = array();
$sales_labels = array();
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $sales_labels[] = date('M', strtotime("-$i months"));
    $result = $conn->query("SELECT SUM(total_amount) as sales FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = '$month'");
    $sales_data[] = $result->fetch_assoc()['sales'] ?: 0;
}

// Fetch inventory status
$result = $conn->query("SELECT 
    SUM(CASE WHEN stock_quantity > 10 THEN 1 ELSE 0 END) as in_stock,
    SUM(CASE WHEN stock_quantity <= 10 AND stock_quantity > 0 THEN 1 ELSE 0 END) as low_stock,
    SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock
    FROM Medicines");
$inventory_status = $result->fetch_assoc();

// Fetch recent activities
$result = $conn->query("SELECT activity_type, description, created_at FROM activity_log ORDER BY created_at DESC LIMIT 3");
$recent_activities = array();
while ($row = $result->fetch_assoc()) {
    $recent_activities[] = $row;
}

// Fetch low stock items for alerts
$result = $conn->query("SELECT name, stock_quantity as quantity FROM Medicines WHERE stock_quantity <= 10 ORDER BY stock_quantity ASC LIMIT 3");
$low_stock_alerts = array();
while ($row = $result->fetch_assoc()) {
    $low_stock_alerts[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Dashboard - Pharma-Corp</title>
    <link
      href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css"
      rel="stylesheet"
    />
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

      .main-content {
        padding: 2rem;
      }

      .dashboard-header {
        padding: 50px 0;
        background: linear-gradient(rgba(44, 157, 183, 0.1), rgba(44, 157, 183, 0.1)),
                    url('pic/3.jpg') no-repeat center center;
        background-size: cover;
        position: relative;
        color: white;
      }

      .dashboard-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 1;
      }

      .dashboard-header .container {
        position: relative;
        z-index: 2;
      }

      .feature-card {
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
        background: white;
        transition: all 0.3s ease;
        border: 1px solid rgba(44, 157, 183, 0.1);
      }

      .feature-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 8px 25px rgba(44, 157, 183, 0.2);
      }

      .chart-container {
        height: 300px;
        margin-bottom: 30px;
      }

      .activity-section {
        background: url('pic/4.jpg') no-repeat center center;
        background-size: cover;
        position: relative;
        padding: 50px 0;
      }

      .activity-section::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.95);
      }

      .activity-section .container {
        position: relative;
      }

      .activity-card {
        background: white;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
      }

      .activity-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(44, 157, 183, 0.2);
      }

      .alert {
        border-radius: 10px;
        margin-bottom: 15px;
      }

      .stats-card {
        background: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 30px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
        border-left: 4px solid var(--primary-color);
      }

      .stats-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(44, 157, 183, 0.2);
      }

      .stats-card i {
        color: var(--primary-color);
        font-size: 2rem;
        margin-bottom: 15px;
      }

      .notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        padding: 3px 6px;
        border-radius: 50%;
        background: #e74a3b;
        color: white;
        font-size: 0.7rem;
      }
    </style>
  </head>
  <body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
      <div class="container">
        <a class="navbar-brand" href="#">Admin Dashboard</a>
        <button
          class="navbar-toggler"
          type="button"
          data-bs-toggle="collapse"
          data-bs-target="#navbarNav"
        >
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
          <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                Staff Management
              </a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="admin-staff-requests.php">Staff Requests</a></li>
                <li><a class="dropdown-item" href="admin-user-roles.php">Role Assignment</a></li>
              </ul>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="inventory.php">
                <i class="fas fa-boxes"></i> Inventory
              </a>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                <i class="fas fa-chart-line"></i> Reports
              </a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="inventory-report.php">Inventory Reports</a></li>
                <li><a class="dropdown-item" href="sales-report.php">Sales Reports</a></li>
                <li><a class="dropdown-item" href="profit-report.php">Profit Analysis</a></li>
                <li><a class="dropdown-item" href="export-data.php">Export Data</a></li>
              </ul>
            </li>
            <li class="nav-item">
              <div class="position-relative d-inline-block">
                <a class="nav-link" href="#notifications">
                  <i class="fas fa-bell"></i>
                  <span class="notification-badge">3</span>
                </a>
              </div>
            </li>
            <li class="nav-item dropdown">
              <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                <i class="fas fa-user-circle"></i>
              </a>
              <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#profile">Profile</a></li>
                <li><a class="dropdown-item" href="#settings">Settings</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
    <a class="dropdown-item" href="#" onclick="logout()">Logout</a>
</li>

<script>
function logout() {
    if (confirm("Are you sure you want to logout?")) {
        window.location.href = "logout.php";
    }
}
</script>              </ul>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <!-- Dashboard Header -->
    <section class="dashboard-header">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-6">
            <h1>Welcome, <?php echo htmlspecialchars($admin_name); ?></h1>
            <p>Here's your overview for today</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Main Content -->
    <div class="main-content">
      <div class="container">
        <!-- Stats Cards -->
        <div class="row mb-4">
          <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
              <div class="card-body">
                <div class="row no-gutters align-items-center">
                  <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_users; ?></div>
                    <div class="small text-muted">
                      Admin: <?php echo $user_roles['Admin'] ?? 0; ?><br>
                      Customer: <?php echo $user_roles['Customer'] ?? 0; ?>
                    </div>
                  </div>
                  <div class="col-auto">
                    <i class="fas fa-users fa-2x text-gray-300"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
              <div class="card-body">
                <div class="row no-gutters align-items-center">
                  <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Medicines</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $medicine_stats['total_medicines']; ?></div>
                    <div class="small text-muted">
                      Total Value: $<?php echo number_format($medicine_stats['total_value'], 2); ?><br>
                      Avg Price: $<?php echo number_format($medicine_stats['avg_price'], 2); ?>
                    </div>
                  </div>
                  <div class="col-auto">
                    <i class="fas fa-box fa-2x text-gray-300"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
              <div class="card-body">
                <div class="row no-gutters align-items-center">
                  <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Orders</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $order_stats['total_orders']; ?></div>
                    <div class="small text-muted">
                      Completed: <?php echo $order_stats['completed_orders']; ?><br>
                      Pending: <?php echo $order_stats['pending_orders']; ?>
                    </div>
                  </div>
                  <div class="col-auto">
                    <i class="fas fa-shopping-cart fa-2x text-gray-300"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
              <div class="card-body">
                <div class="row no-gutters align-items-center">
                  <div class="col mr-2">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Revenue</div>
                    <div class="h5 mb-0 font-weight-bold text-gray-800">$<?php echo number_format($order_stats['total_revenue'], 2); ?></div>
                    <div class="small text-muted">
                      Avg Order: $<?php echo number_format($order_stats['avg_order_value'], 2); ?><br>
                      Monthly: $<?php echo number_format($monthly_sales, 2); ?>
                    </div>
                  </div>
                  <div class="col-auto">
                    <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Charts Row -->
        <div class="row">
          <div class="col-xl-8">
            <div class="feature-card">
              <h3 class="text-primary mb-4">Sales Overview</h3>
              <div class="chart-container">
                <canvas id="salesChart"></canvas>
              </div>
            </div>
          </div>
          <div class="col-xl-4">
            <div class="feature-card">
              <h3 class="text-primary mb-4">Inventory Status</h3>
              <div class="chart-container">
                <canvas id="inventoryChart"></canvas>
              </div>
            </div>
          </div>
        </div>

        <!-- Activity Section -->
        <section class="activity-section">
          <div class="container">
            <div class="row">
              <div class="col-xl-8">
                <div class="feature-card">
                  <h3 class="text-primary mb-4">Recent Activities</h3>
                  <?php foreach ($recent_activities as $activity): ?>
                  <div class="activity-card">
                    <div class="small text-gray-500"><?php echo date('F d, Y', strtotime($activity['created_at'])); ?></div>
                    <span><?php echo htmlspecialchars($activity['description']); ?></span>
                  </div>
                  <?php endforeach; ?>
                  <?php if (empty($recent_activities)): ?>
                  <div class="activity-card">
                    <span>No recent activities</span>
                  </div>
                  <?php endif; ?>
                </div>
              </div>
              <div class="col-xl-4">
                <div class="feature-card">
                  <h3 class="text-primary mb-4">Low Stock Alerts</h3>
                  <?php foreach ($low_stock_alerts as $alert): ?>
                  <div class="alert <?php echo $alert['quantity'] == 0 ? 'alert-danger' : 'alert-warning'; ?>">
                    <i class="fas <?php echo $alert['quantity'] == 0 ? 'fa-exclamation-triangle' : 'fa-exclamation-circle'; ?>"></i>
                    <?php echo htmlspecialchars($alert['name']); ?> - <?php echo $alert['quantity']; ?> units remaining
                  </div>
                  <?php endforeach; ?>
                  <?php if (empty($low_stock_alerts)): ?>
                  <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> All stock levels are healthy
                  </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

    <script>
      // Sales Chart
      const salesCtx = document.getElementById("salesChart").getContext("2d");
      new Chart(salesCtx, {
        type: "line",
        data: {
          labels: <?php echo json_encode($sales_labels); ?>,
          datasets: [
            {
              label: "Monthly Sales",
              data: <?php echo json_encode($sales_data); ?>,
              borderColor: "#2c9db7",
              tension: 0.1,
            },
          ],
        },
        options: {
          maintainAspectRatio: false,
          scales: {
            y: {
              beginAtZero: true,
            },
          },
        },
      });

      // Inventory Chart
      const inventoryCtx = document
        .getElementById("inventoryChart")
        .getContext("2d");
      new Chart(inventoryCtx, {
        type: "doughnut",
        data: {
          labels: ["In Stock", "Low Stock", "Out of Stock"],
          datasets: [
            {
              data: [
                <?php echo $inventory_status['in_stock']; ?>,
                <?php echo $inventory_status['low_stock']; ?>,
                <?php echo $inventory_status['out_of_stock']; ?>
              ],
              backgroundColor: ["#2c9db7", "#f6c23e", "#e74a3b"],
            },
          ],
        },
        options: {
          maintainAspectRatio: false,
        },
      });
    </script>
  </body>
</html>
