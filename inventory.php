<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pharma";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add validation functions
function validateInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Create prescriptions table if it doesn't exist
$create_table = "CREATE TABLE IF NOT EXISTS prescriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(10) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
)";

if (!$conn->query($create_table)) {
    error_log("Error creating prescriptions table: " . $conn->error);
}

// Handle stock updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_stock'])) {
    $errors = [];
    
    // Validate medicine_id
    $medicine_id = validateInput($_POST['medicine_id']);
    if (!is_numeric($medicine_id) || $medicine_id <= 0) {
        $errors[] = "Invalid medicine ID";
    }
    
    // Validate new_quantity
    $new_quantity = validateInput($_POST['new_quantity']);
    if (!is_numeric($new_quantity) || $new_quantity < 0) {
        $errors[] = "Quantity must be a positive number";
    }
    
    // Check if medicine exists
    $check_sql = "SELECT * FROM Medicines WHERE medicine_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $medicine_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        $errors[] = "Medicine not found";
    }
    
    if (empty($errors)) {
        $update_sql = "UPDATE Medicines SET stock_quantity = ? WHERE medicine_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ii", $new_quantity, $medicine_id);
        
        if ($stmt->execute()) {
            $success_message = "Stock updated successfully!";
        } else {
            $error_message = "Error updating stock: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Handle prescription status updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_prescription'])) {
    $prescription_id = validateInput($_POST['prescription_id']);
    $new_status = validateInput($_POST['new_status']);
    $notes = validateInput($_POST['notes']);
    
    if (in_array($new_status, ['approved', 'rejected'])) {
        $update_sql = "UPDATE prescriptions SET status = ?, notes = ? WHERE id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssi", $new_status, $notes, $prescription_id);
        
        if ($stmt->execute()) {
            $success_message = "Prescription status updated successfully!";
        } else {
            $error_message = "Error updating prescription: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error_message = "Invalid status value";
    }
}

// Fetch all medicines
$sql = "SELECT * FROM Medicines ORDER BY name";
$result = $conn->query($sql);

            // Calculate summaries
            $total_items = $result->num_rows;
            
            $low_stock_sql = "SELECT COUNT(*) as count FROM Medicines WHERE stock_quantity <= 100";
            $low_stock_result = $conn->query($low_stock_sql);
            $low_stock = $low_stock_result->fetch_assoc()['count'];
            
            $expired_sql = "SELECT COUNT(*) as count FROM Medicines WHERE expiry_date <= CURDATE()";
            $expired_result = $conn->query($expired_sql);
            $expired = $expired_result->fetch_assoc()['count'];

// Fetch pending prescriptions
$pending_sql = "SELECT p.*, u.username, u.email, DATE_FORMAT(p.upload_date, '%M %d, %Y %h:%i %p') as formatted_date 
               FROM prescriptions p 
               JOIN users u ON p.user_id = u.user_id 
               ORDER BY p.upload_date DESC";
$prescriptions_result = $conn->query($pending_sql);
$prescriptions = [];
if ($prescriptions_result) {
    while ($row = $prescriptions_result->fetch_assoc()) {
        $prescriptions[] = $row;
    }
}

$pending_count_sql = "SELECT COUNT(*) as count FROM prescriptions WHERE status = 'pending'";
$pending_count_result = $conn->query($pending_count_sql);
$pending_count = $pending_count_result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management - Pharma-Corp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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

        .badge-pending {
            background-color: #ffc107;
            color: #000;
        }

        .badge-approved {
            background-color: #28a745;
            color: #fff;
        }

        .badge-rejected {
            background-color: #dc3545;
            color: #fff;
        }

        .prescription-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .nav-tabs .nav-link {
            border: none;
            color: var(--secondary-color);
            font-weight: 500;
            padding: 0.75rem 1.25rem;
        }

        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            border-bottom: 3px solid var(--primary-color);
        }

        .prescription-container {
            position: relative;
        }

        .prescription-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .prescription-container:hover .prescription-overlay {
            opacity: 1;
        }

        .btn-view {
            padding: 8px 16px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        /* DataTables customization */
        .dataTables_wrapper .dataTables_filter input {
            border: 2px solid #e3e6f0;
            border-radius: 8px;
            padding: 6px 12px;
            margin-left: 8px;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(44, 157, 183, 0.25);
        }

        .dataTables_wrapper .dataTables_length select {
            border: 2px solid #e3e6f0;
            border-radius: 8px;
            padding: 4px 8px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary-color) !important;
            color: white !important;
            border: none;
            border-radius: 4px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #248ca3 !important;
            color: white !important;
            border: none;
        }

        /* Custom search container */
        .custom-search-container {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            align-items: center;
        }

        .custom-search-input {
            flex-grow: 1;
            max-width: 300px;
            padding: 8px 12px;
            border: 2px solid #e3e6f0;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .custom-search-input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(44, 157, 183, 0.25);
        }

        /* Hide default DataTables search */
        .dataTables_filter {
            display: none;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Pharmacy Management</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Users Management
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="admin-staff-requests.php">Staff Requests</a></li>
                            <li><a class="dropdown-item" href="admin-user-roles.php">Role Assignment</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="inventory.php">
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
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
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
                    <h1>Inventory Management</h1>
                    <p>Monitor stock levels and manage prescriptions</p>
            </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container">
            <!-- Messages -->
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Medicines</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_items; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-pills fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Low Stock</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $low_stock; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Expired Items</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $expired; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar-times fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="stats-card">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Pending Prescriptions</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $pending_count; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-file-medical fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs for Inventory and Prescriptions -->
            <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory" type="button" role="tab" aria-controls="inventory" aria-selected="true">Inventory Management</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="prescriptions-tab" data-bs-toggle="tab" data-bs-target="#prescriptions" type="button" role="tab" aria-controls="prescriptions" aria-selected="false">
                        Prescriptions 
                        <?php if ($pending_count > 0): ?>
                            <span class="badge bg-danger"><?php echo $pending_count; ?></span>
                        <?php endif; ?>
                    </button>
                </li>
            </ul>
            
            <div class="tab-content" id="myTabContent">
                <!-- Inventory Tab -->
                <div class="tab-pane fade show active" id="inventory" role="tabpanel" aria-labelledby="inventory-tab">
                    <div class="feature-card">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h3 class="text-primary mb-0">Medicine Inventory</h3>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMedicineModal">
                                <i class="fas fa-plus"></i> Add New Medicine
                            </button>
                        </div>

                        <!-- Add custom search container -->
                        <div class="custom-search-container">
                            <input type="text" id="customSearch" class="custom-search-input" placeholder="Search medicines...">
                            <button id="searchBtn" class="btn btn-primary">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>

                        <table id="inventoryTable" class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Medicine Name</th>
                                    <th>Batch Number</th>
                                    <th>Expiry Date</th>
                                    <th>Current Stock</th>
                                    <th>Price/Unit</th>
                                    <th>Update Stock</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                // Reset result pointer as we used it earlier for counting
                                $result->data_seek(0);
                                while($row = $result->fetch_assoc()): 
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['batch_number']); ?></td>
                                        <td>
                                    <?php 
                                    $expiry_date = new DateTime($row['expiry_date']);
                                    $today = new DateTime();
                                    $expired = $expiry_date < $today;
                                                $class = $expired ? 'text-danger' : 'text-dark';
                                    echo "<span class='$class'>" . $expiry_date->format('Y-m-d') . "</span>";
                                    ?>
                                </td>
                                        <td>
                                    <?php
                                                $badge_class = 'bg-success';
                                    if ($row['stock_quantity'] <= 100) {
                                                    $badge_class = 'bg-warning';
                                    }
                                    if ($row['stock_quantity'] == 0) {
                                                    $badge_class = 'bg-danger';
                                    }
                                    ?>
                                                <span class="badge <?php echo $badge_class; ?>">
                                        <?php echo $row['stock_quantity']; ?> units
                                    </span>
                                </td>
                                        <td>₹<?php echo number_format($row['price_per_unit'], 2); ?></td>
                                        <td>
                                            <form method="POST" class="d-flex gap-2">
                                        <input type="hidden" name="medicine_id" value="<?php echo $row['medicine_id']; ?>">
                                        <input type="number" name="new_quantity" 
                                                           class="form-control form-control-sm w-75"
                                               min="0" value="<?php echo $row['stock_quantity']; ?>">
                                        <button type="submit" name="update_stock"
                                                            class="btn btn-warning btn-sm">
                                                        <i class="fas fa-sync-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Prescriptions Tab -->
                <div class="tab-pane fade" id="prescriptions" role="tabpanel" aria-labelledby="prescriptions-tab">
                    <div class="feature-card">
                        <h3 class="text-primary mb-4">Customer Prescriptions</h3>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Customer</th>
                                        <th>File Name</th>
                                        <th>Upload Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(empty($prescriptions)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No prescriptions found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach($prescriptions as $prescription): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($prescription['username']); ?><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($prescription['email']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($prescription['file_name']); ?></td>
                                                <td><?php echo $prescription['formatted_date']; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $prescription['status']; ?>">
                                                        <?php echo ucfirst($prescription['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    
                                                    
                                                    <?php if($prescription['status'] === 'pending'): ?>
                                                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#updateStatusModal<?php echo $prescription['id']; ?>">
                                                            <i class="fas fa-check"></i> Update Status
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            
                                            <!-- View Prescription Modal -->
                                            <div class="modal fade" id="viewPrescriptionModal<?php echo $prescription['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Prescription Details</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body text-center">
                                                            <img src="<?php echo $prescription['file_path']; ?>" class="prescription-image" alt="Prescription">
                                                            <div class="mt-3">
                                                                <p><strong>Uploaded by:</strong> <?php echo htmlspecialchars($prescription['username']); ?></p>
                                                                <p><strong>File:</strong> <?php echo htmlspecialchars($prescription['file_name']); ?></p>
                                                                <p><strong>Date:</strong> <?php echo $prescription['formatted_date']; ?></p>
                                                                <p><strong>Status:</strong> 
                                                                    <span class="badge badge-<?php echo $prescription['status']; ?>">
                                                                        <?php echo ucfirst($prescription['status']); ?>
                                                                    </span>
                                                                </p>
                                                                <?php if(!empty($prescription['notes'])): ?>
                                                                    <p><strong>Notes:</strong> <?php echo htmlspecialchars($prescription['notes']); ?></p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Update Status Modal -->
                                            <?php if($prescription['status'] === 'pending'): ?>
                                                <div class="modal fade" id="updateStatusModal<?php echo $prescription['id']; ?>" tabindex="-1" aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Update Prescription Status</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <form method="POST">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="prescription_id" value="<?php echo $prescription['id']; ?>">
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Prescription Status</label>
                                                                        <select name="new_status" class="form-select" required>
                                                                            <option value="approved">Approve</option>
                                                                            <option value="rejected">Reject</option>
                                                                        </select>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Notes (Optional)</label>
                                                                        <textarea name="notes" class="form-control" rows="3" placeholder="Add any notes or comments about this prescription"></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" name="update_prescription" class="btn btn-primary">Update Status</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Medicine Modal -->
    <div class="modal fade" id="addMedicineModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Medicine</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="add_medicine.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="medicineName" class="form-label">Medicine Name</label>
                            <input type="text" class="form-control" id="medicineName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="batchNumber" class="form-label">Batch Number</label>
                            <input type="text" class="form-control" id="batchNumber" name="batch_number" required>
                        </div>
                        <div class="mb-3">
                            <label for="expiryDate" class="form-label">Expiry Date</label>
                            <input type="date" class="form-control" id="expiryDate" name="expiry_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="stockQuantity" class="form-label">Stock Quantity</label>
                            <input type="number" class="form-control" id="stockQuantity" name="stock_quantity" min="0" required>
                        </div>
                        <div class="mb-3">
                            <label for="pricePerUnit" class="form-label">Price Per Unit</label>
                            <input type="number" class="form-control" id="pricePerUnit" name="price_per_unit" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Medicine</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
    $(document).ready(function() {
            // Client-side validation for stock updates
        $('form').on('submit', function(e) {
                if (!$(this).find('input[name="update_stock"]').length) {
                    return true; // Not a stock update form, skip validation
                }
                
            const quantityInput = $(this).find('input[name="new_quantity"]');
            const quantity = parseInt(quantityInput.val());
            let errors = [];

            // Validate quantity
            if (isNaN(quantity) || quantity < 0) {
                errors.push("Quantity must be a positive number");
                    quantityInput.addClass('is-invalid');
            } else {
                    quantityInput.removeClass('is-invalid');
            }

            // If there are errors, prevent form submission
            if (errors.length > 0) {
                e.preventDefault();
                alert(errors.join("\n"));
                return false;
            }

            // Confirmation dialog
            if (!confirm('Are you sure you want to update this stock quantity?')) {
                e.preventDefault();
                return false;
            }
        });

            // Tab handling
            const triggerTabList = document.querySelectorAll('#myTab button');
            triggerTabList.forEach(function (triggerEl) {
                const tabTrigger = new bootstrap.Tab(triggerEl);
                triggerEl.addEventListener('click', function (event) {
                    event.preventDefault();
                    tabTrigger.show();
                });
            });
            
            // Show prescriptions tab if there's a hash in URL
            if (window.location.hash === '#prescriptions') {
                const prescriptionsTab = document.querySelector('#prescriptions-tab');
                if (prescriptionsTab) {
                    bootstrap.Tab.getOrCreateInstance(prescriptionsTab).show();
                }
            }

        // Initialize DataTable
        var table = $('#inventoryTable').DataTable({
            "pageLength": 10,
            "order": [[0, "asc"]], // Sort by first column (Medicine Name) by default
            "language": {
                "lengthMenu": "Show _MENU_ medicines per page",
                "info": "Showing _START_ to _END_ of _TOTAL_ medicines",
                "infoEmpty": "Showing 0 to 0 of 0 medicines",
                "infoFiltered": "(filtered from _MAX_ total medicines)"
            },
            "columnDefs": [
                { "orderable": false, "targets": 5 } // Disable sorting on the Update Stock column
            ],
            "responsive": true,
            "initComplete": function() {
                $('.dataTables_length select').addClass('form-select form-select-sm');
            }
        });

        // Search button click handler
        $('#searchBtn').on('click', function() {
            const searchTerm = $('#customSearch').val();
            table.search(searchTerm).draw();
        });

        // Reset button click handler
        $('#resetBtn').on('click', function() {
            $('#customSearch').val('');
            table.search('').draw();
        });

        // Enter key handler for search input
        $('#customSearch').on('keypress', function(e) {
            if (e.which === 13) { // Enter key
                e.preventDefault();
                $('#searchBtn').click();
            }
        });
    });
    </script>
</body>
</html>

<?php
$conn->close();
?>