<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include "connect.php";

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

// Get user's prescriptions
$prescriptions = [];
$stmt = $conn->prepare("SELECT p.*, DATE_FORMAT(p.upload_date, '%M %d, %Y %h:%i %p') as formatted_date 
                       FROM prescriptions p 
                       WHERE p.user_id = ? 
                       ORDER BY p.upload_date DESC 
                       LIMIT 5");

if ($stmt) {
    $stmt->bind_param("i", $_SESSION['user_id']);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $prescriptions = $result->fetch_all(MYSQLI_ASSOC);
    } else {
        error_log("Error executing prescription query: " . $stmt->error);
    }
} else {
    error_log("Error preparing prescription query: " . $conn->error);
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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

        .prescription-upload {
            border: 2px dashed var(--primary-color);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            background: rgba(44, 157, 183, 0.02);
            margin-bottom: 1.5rem;
        }

        .prescription-upload:hover {
            background: rgba(44, 157, 183, 0.08);
            border-color: #2589a0;
            transform: translateY(-2px);
        }

        .prescription-upload i {
            font-size: 2rem;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            transition: transform 0.3s ease;
        }

        .prescription-upload:hover i {
            transform: translateY(-5px);
        }

        .prescription-upload h4 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: var(--primary-color);
        }

        .prescription-upload p {
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }

        .prescription-upload .text-muted {
            font-size: 0.8rem;
        }

        .prescription-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .prescription-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.75rem 1rem;
            background: rgba(44, 157, 183, 0.05);
            border-radius: 8px;
            margin-bottom: 0.5rem;
            transition: all 0.2s ease;
        }

        .prescription-item:hover {
            background: rgba(44, 157, 183, 0.1);
            transform: translateX(5px);
        }

        .prescription-item i {
            font-size: 1.2rem;
            margin-right: 0.75rem;
            color: var(--primary-color);
        }

        .prescription-status {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .prescription-status i {
            font-size: 0.7rem;
            margin: 0;
        }

        .status-pending {
            background: #fff7ed;
            color: #f97316;
        }

        .status-approved {
            background: #f0fdf4;
            color: #22c55e;
        }

        .status-rejected {
            background: #fef2f2;
            color: #ef4444;
        }

        #uploadProgress {
            height: 6px;
            border-radius: 3px;
            margin-top: 1rem;
            display: none;
        }

        #uploadProgress .progress-bar {
            background-color: var(--primary-color);
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
        <h1 style="color: white;"><i class="fas fa-clinic-medical"></i> Pharma-Corp</h1>
        <div class="nav-links">
            <a href="search-medicines.php" class="nav-link d-inline-block me-3" style="color: white;"><i class="fas fa-search"></i> Search Medicines</a>
            <a href="#" class="nav-link d-inline-block me-3" style="color: white;"><i class="fas fa-shopping-cart"></i> Cart</a>
            <a href="#" class="nav-link d-inline-block me-3" style="color: white;"><i class="fas fa-history"></i> Orders</a>
        </div>
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span style="color: white;">Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Customer') ?></span>
            <a href="customer-profile.php" class="btn btn-info">
                <i class="bi bi-person"></i> My Profile
            </a>
            <a href="logout.php" class="btn btn-danger">
                <i class="bi bi-box-arrow-right"></i> Logout
            </a>
        </div>
    </nav>

    <div class="container">
        <!-- Prescription Upload Card -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-file-medical"></i>
                    <h2 class="d-inline-block ms-2 mb-0">Prescriptions</h2>
                </div>
                <div class="prescription-stats">
                    <?php if (!empty($prescriptions)): ?>
                        <span class="badge bg-primary"><?= count($prescriptions) ?> Recent</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-content p-3">
                <div class="prescription-upload" onclick="document.getElementById('prescriptionFile').click()">
                    <i class="fas fa-cloud-upload-alt"></i>
                    <h4>Drop your prescription here</h4>
                    <p class="text-muted">or click to browse files</p>
                    <input type="file" id="prescriptionFile" hidden accept=".jpg,.jpeg,.png,.gif,.pdf">
                </div>
                <div id="uploadProgress" class="progress">
                    <div class="progress-bar progress-bar-striped progress-bar-animated"></div>
                </div>
                
                <!-- Recent Prescriptions -->
                <?php if (!empty($prescriptions)): ?>
                    <h6 class="mt-3 mb-2">Recent Uploads</h6>
                    <ul class="prescription-list">
                        <?php foreach ($prescriptions as $prescription): ?>
                            <li class="prescription-item">
                                <div class="d-flex align-items-center">
                                    <i class="<?= strpos($prescription['file_type'], 'pdf') !== false ? 'fas fa-file-pdf' : 'fas fa-file-image' ?>"></i>
                                    <div>
                                        <div class="text-truncate" style="max-width: 200px;">
                                            <?= htmlspecialchars($prescription['file_name']) ?>
                                        </div>
                                        <small class="text-muted">
                                            <?= $prescription['formatted_date'] ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="prescription-status status-<?= $prescription['status'] ?>">
                                    <i class="fas fa-<?= $prescription['status'] === 'pending' ? 'clock' : ($prescription['status'] === 'approved' ? 'check' : 'times') ?>"></i>
                                    <?= ucfirst($prescription['status']) ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <div class="text-center text-muted mt-3">
                        <p>No prescriptions uploaded yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

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
                    <?php
                        $query = "SELECT * FROM medicines";
                        $result = $conn->query($query);
                        
                        if ($result) {
                            while ($medicine = $result->fetch_assoc()) {
                                // Determine icon based on medicine type or use a default
                                $icon = 'pills'; // Default icon
                                
                                echo '<div class="medicine-card">
                                        <i class="fas fa-'. htmlspecialchars($icon) .'"></i>
                                        <h3>'. htmlspecialchars($medicine['name']) .'</h3>
                                        <p>Stock: '. htmlspecialchars($medicine['stock_quantity']) .'</p>
                                        <button class="btn btn-primary">
                                            <i class="fas fa-shopping-cart"></i> Purchase
                                        </button>
                                    </div>';
                            }
                            $result->free();
                        } else {
                            echo '<div class="alert alert-info">No medicines available at the moment.</div>';
                        }
                    ?>
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
    <script>
        document.getElementById('prescriptionFile').addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                const file = e.target.files[0];
                const maxSize = 5 * 1024 * 1024; // 5MB

                if (file.size > maxSize) {
                    alert('File is too large. Maximum size is 5MB.');
                    return;
                }

                const formData = new FormData();
                formData.append('prescription', file);

                // Show progress bar
                const progressBar = document.querySelector('#uploadProgress');
                const progressBarInner = progressBar.querySelector('.progress-bar');
                progressBar.style.display = 'block';
                progressBarInner.style.width = '0%';

                fetch('upload_prescription.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload(); // Refresh to show new prescription
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error uploading file: ' + error.message);
                })
                .finally(() => {
                    progressBar.style.display = 'none';
                });
            }
        });

        // Drag and drop support
        const uploadArea = document.querySelector('.prescription-upload');

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults (e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            uploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            uploadArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            uploadArea.classList.add('bg-light');
        }

        function unhighlight(e) {
            uploadArea.classList.remove('bg-light');
        }

        uploadArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const file = dt.files[0];
            document.getElementById('prescriptionFile').files = dt.files;
            document.getElementById('prescriptionFile').dispatchEvent(new Event('change'));
        }
    </script>
</body>
</html>