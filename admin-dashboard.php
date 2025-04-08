<?php
session_start();
include "connect.php";

if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    error_log("Access denied to admin dashboard - Current role: " . ($_SESSION['role'] ?? 'no role set'));
    header("Location: login.php");
    exit();
}

$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT username FROM users WHERE email = ? AND LOWER(role) = 'admin'");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$admin_name = $admin['username'];

$result = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$user_roles = array();
$total_users = 0;
while ($row = $result->fetch_assoc()) {
    $user_roles[$row['role']] = $row['count'];
    $total_users += $row['count'];
}

$result = $conn->query("SELECT 
    COUNT(*) as total_medicines,
    SUM(stock_quantity * price_per_unit) as total_value,
    AVG(price_per_unit) as avg_price
    FROM Medicines");
$medicine_stats = $result->fetch_assoc();

$result = $conn->query("SELECT 
    COUNT(*) as total_orders,
    SUM(total_amount) as total_revenue,
    AVG(total_amount) as avg_order_value,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_orders,
    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders
    FROM orders");

if (!$result) {
    error_log("Query failed: " . $conn->error);
    $order_stats = [
        'total_orders' => 0,
        'total_revenue' => 0,
        'avg_order_value' => 0,
        'completed_orders' => 0,
        'pending_orders' => 0
    ];
} else {
    $order_stats = $result->fetch_assoc();
}

$result = $conn->query("SELECT COUNT(*) as low_stock FROM Medicines WHERE stock_quantity <= 10");
if (!$result) {
    error_log("Query failed: " . $conn->error);
    $low_stock = 0;
} else {
    $low_stock = $result->fetch_assoc()['low_stock'];
}

$current_month = date('Y-m');
$result = $conn->query("SELECT SUM(total_amount) as monthly_sales FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = '$current_month'");
if (!$result) {
    error_log("Query failed: " . $conn->error);
    $monthly_sales = 0;
} else {
    $monthly_sales = $result->fetch_assoc()['monthly_sales'] ?: 0;
}

$sales_data = array();
$sales_labels = array();
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $sales_labels[] = date('M', strtotime("-$i months"));
    
    $query = "SELECT COALESCE(SUM(si.quantity * si.price_per_unit), 0) as sales 
              FROM sales s 
              JOIN sales_items si ON s.sale_id = si.sale_id 
              WHERE DATE_FORMAT(s.sale_date, '%Y-%m') = ?";
    
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        error_log("Prepare failed: " . $conn->error);
        $sales_data[] = 0;
        continue;
    }
    
    $stmt->bind_param("s", $month);
    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error);
        $sales_data[] = 0;
        continue;
    }
    
    $result = $stmt->get_result();
    if ($result === false) {
        error_log("Get result failed: " . $stmt->error);
        $sales_data[] = 0;
        continue;
    }
    
    $row = $result->fetch_assoc();
    $sales_data[] = $row['sales'] ?: 0;
    
    $stmt->close();
}

$result = $conn->query("SELECT 
    SUM(CASE WHEN stock_quantity > 10 THEN 1 ELSE 0 END) as in_stock,
    SUM(CASE WHEN stock_quantity <= 10 AND stock_quantity > 0 THEN 1 ELSE 0 END) as low_stock,
    SUM(CASE WHEN stock_quantity = 0 THEN 1 ELSE 0 END) as out_of_stock
    FROM Medicines");
if (!$result) {
    error_log("Inventory query failed: " . $conn->error);
    $inventory_status = [
        'in_stock' => 0,
        'low_stock' => 0,
        'out_of_stock' => 0
    ];
} else {
    $inventory_status = $result->fetch_assoc();
}

$result = $conn->query("SELECT activity_type, description, created_at FROM activity_log ORDER BY created_at DESC LIMIT 3");
if (!$result) {
    error_log("Activity log query failed: " . $conn->error);
    $recent_activities = array();
} else {
    $recent_activities = array();
    while ($row = $result->fetch_assoc()) {
        $recent_activities[] = $row;
    }
}

$result = $conn->query("SELECT name, stock_quantity as quantity FROM Medicines WHERE stock_quantity <= 10 ORDER BY stock_quantity ASC LIMIT 3");
if (!$result) {
    error_log("Low stock alerts query failed: " . $conn->error);
    $low_stock_alerts = array();
} else {
    $low_stock_alerts = array();
    while ($row = $result->fetch_assoc()) {
        $low_stock_alerts[] = $row;
    }
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

      .about-section {
        background: linear-gradient(rgba(255, 255, 255, 0.9), rgba(255, 255, 255, 0.9)), url('pic/pattern.jpg');
        background-size: cover;
      }
      
      .feature-item {
        padding: 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
      }
      
      .feature-item:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(44, 157, 183, 0.2);
      }
      
      .about-image img {
        transition: all 0.3s ease;
      }
      
      .about-image img:hover {
        transform: scale(1.02);
      }
      
      .footer {
        background: linear-gradient(rgba(0, 0, 0, 0.9), rgba(0, 0, 0, 0.9)), url('pic/footer-bg.jpg');
        background-size: cover;
      }
      
      .footer a {
        text-decoration: none;
        transition: all 0.3s ease;
      }
      
      .footer a:hover {
        color: var(--primary-color) !important;
      }
      
      .social-links a {
        display: inline-block;
        width: 35px;
        height: 35px;
        line-height: 35px;
        text-align: center;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        margin-right: 10px;
        transition: all 0.3s ease;
      }
      
      .social-links a:hover {
        background: var(--primary-color);
        transform: translateY(-3px);
      }
    </style>
  </head>
  <body>
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
            <li class="nav-item">
              <a class="nav-link" href="inventory.php">
                <i class="fas fa-boxes"></i> Inventory
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="admin-staff-requests.php">
                <i class="fas fa-users"></i> Staff Requests
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="admin-user-roles.php">
                <i class="fas fa-user-shield"></i> Role Assignment
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#" onclick="logout()">
                <i class="fas fa-sign-out-alt"></i> Logout
              </a>
            </li>
          </ul>
        </div>
      </div>
    </nav>

    <script>
    function logout() {
        if (confirm("Are you sure you want to logout?")) {
            window.location.href = "logout.php";
        }
    }
    </script>

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

    <div class="main-content">
      <div class="container">
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
                      Staff: <?php echo $user_roles['Staff'] ?? 0; ?><br>
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
                      Total Value: ₹<?php echo number_format($medicine_stats['total_value'], 2); ?><br>
                      Avg Price: ₹<?php echo number_format($medicine_stats['avg_price'], 2); ?>
                    </div>
                  </div>
                  <div class="col-auto">
                    <i class="fas fa-box fa-2x text-gray-300"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>

        
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

    <!-- About Section -->
    <section class="about-section py-5 bg-light">
      <div class="container">
        <div class="row">
          <div class="col-lg-6">
            <h2 class="text-primary mb-4">About Pharma-Corp</h2>
            <p class="lead">Your Trusted Pharmacy Management System</p>
            <p>Pharma-Corp is a comprehensive pharmacy management system designed to streamline operations, manage inventory, and provide detailed analytics for your pharmacy business.</p>
            <div class="row mt-4">
              <div class="col-md-6">
                <div class="feature-item mb-4">
                  <i class="fas fa-chart-line text-primary fa-2x mb-3"></i>
                  <h5>Real-time Analytics</h5>
                  <p>Track sales, inventory, and business performance with detailed reports and visualizations.</p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="feature-item mb-4">
                  <i class="fas fa-boxes text-primary fa-2x mb-3"></i>
                  <h5>Inventory Management</h5>
                  <p>Efficiently manage your medicine stock with automated alerts and tracking.</p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="feature-item mb-4">
                  <i class="fas fa-users text-primary fa-2x mb-3"></i>
                  <h5>User Management</h5>
                  <p>Manage staff roles and permissions with a comprehensive user management system.</p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="feature-item mb-4">
                  <i class="fas fa-file-invoice text-primary fa-2x mb-3"></i>
                  <h5>Sales Tracking</h5>
                  <p>Monitor sales performance and generate detailed reports for better business decisions.</p>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-6">
            <div class="about-image">
              <img src="pic/7.jpg" alt="Pharmacy Management" class="img-fluid rounded shadow" style="width: 100%; height: auto; object-fit: cover;">
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer class="footer bg-dark text-white py-4">
      <div class="container">
        <div class="row">
          <div class="col-md-4">
            <h5>Pharma-Corp</h5>
            <p>Your trusted partner in pharmacy management solutions.</p>
            <div class="social-links">
              <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
              <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
              <a href="#" class="text-white me-2"><i class="fab fa-linkedin-in"></i></a>
            </div>
          </div>
          <div class="col-md-4">
            <h5>Quick Links</h5>
            <ul class="list-unstyled">
              <li><a href="inventory.php" class="text-white">Inventory</a></li>
              <li><a href="sales-report.php" class="text-white">Sales Reports</a></li>
              <li><a href="admin-staff-requests.php" class="text-white">Staff Management</a></li>
            </ul>
          </div>
          <div class="col-md-4">
            <h5>Contact Us</h5>
            <ul class="list-unstyled">
              <li><i class="fas fa-phone me-2"></i></li>
              <li><i class="fas fa-envelope me-2"></i> </li>
              <li><i class="fas fa-map-marker-alt me-2"></i></li>
            </ul>
          </div>
        </div>
        <hr class="my-4">
        <div class="row">
          <div class="col-md-6">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Pharma-Corp. All rights reserved.</p>
          </div>
          <div class="col-md-6 text-md-end">
            <a href="#" class="text-white me-3">Privacy Policy</a>
            <a href="#" class="text-white">Terms of Service</a>
          </div>
        </div>
      </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

    <script>
      // Sales Overview Chart
      const salesCtx = document.getElementById("salesChart").getContext("2d");
      new Chart(salesCtx, {
        type: "line",
        data: {
          labels: <?php echo json_encode($sales_labels); ?>,
          datasets: [
            {
              label: "Monthly Sales (₹)",
              data: <?php echo json_encode($sales_data); ?>,
              borderColor: "#2c9db7",
              backgroundColor: "rgba(44, 157, 183, 0.1)",
              tension: 0.1,
              fill: true
            },
          ],
        },
        options: {
          maintainAspectRatio: false,
          responsive: true,
          plugins: {
            legend: {
              display: true,
              position: 'top'
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  return '₹' + context.raw.toLocaleString('en-IN');
                }
              }
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              ticks: {
                callback: function(value) {
                  return '₹' + value.toLocaleString('en-IN');
                }
              }
            }
          }
        },
      });

      // Inventory Status Chart
      const inventoryCtx = document.getElementById("inventoryChart").getContext("2d");
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
              borderWidth: 1
            },
          ],
        },
        options: {
          maintainAspectRatio: false,
          responsive: true,
          plugins: {
            legend: {
              display: true,
              position: 'right'
            },
            tooltip: {
              callbacks: {
                label: function(context) {
                  const label = context.label || '';
                  const value = context.raw || 0;
                  const total = context.dataset.data.reduce((a, b) => a + b, 0);
                  const percentage = Math.round((value / total) * 100);
                  return `${label}: ${value} (${percentage}%)`;
                }
              }
            }
          }
        },
      });
    </script>
  </body>
</html>
