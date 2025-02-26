<?php
session_start();
include "connect.php";

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff' || $_SESSION['status'] !== 'approved') {
    header("Location: login.php");
    exit();
}

// Initialize prescriptions array
$prescriptions = [];

// First, check the structure of users table
$userTableCheck = $conn->query("SHOW COLUMNS FROM users");
if (!$userTableCheck) {
    $_SESSION['error'] = "Error checking users table structure: " . $conn->error;
} else {
    $primaryKeyColumn = null;
    while ($row = $userTableCheck->fetch_assoc()) {
        if ($row['Key'] == 'PRI') {
            $primaryKeyColumn = $row['Field'];
            break;
        }
    }

    if (!$primaryKeyColumn) {
        $_SESSION['error'] = "Could not find primary key in users table";
    } else {
        // Check if prescriptions table exists
        $tableExists = $conn->query("SHOW TABLES LIKE 'prescriptions'");
        if ($tableExists->num_rows == 0) {
            // Drop the table if it exists with wrong structure
            $conn->query("DROP TABLE IF EXISTS prescriptions");
            
            // Create prescriptions table with the correct foreign key
            $create_table = "CREATE TABLE prescriptions (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                file_name VARCHAR(255) NOT NULL,
                file_path VARCHAR(255) NOT NULL,
                file_type VARCHAR(50) NOT NULL,
                upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
                notes TEXT,
                FOREIGN KEY (user_id) REFERENCES users($primaryKeyColumn) ON DELETE CASCADE
            ) ENGINE=InnoDB";

            if (!$conn->query($create_table)) {
                $_SESSION['error'] = "Error creating prescriptions table: " . $conn->error;
            }
        }
    }
}

// Handle prescription status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['prescription_id'])) {
    $prescription_id = $_POST['prescription_id'];
    $status = $_POST['status'];
    $notes = $_POST['notes'] ?? '';

    $stmt = $conn->prepare("UPDATE prescriptions SET status = ?, notes = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ssi", $status, $notes, $prescription_id);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = "Prescription status updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating prescription status: " . $stmt->error;
        }
    } else {
        $_SESSION['error'] = "Error preparing update statement: " . $conn->error;
    }
    
    header("Location: manage-prescriptions.php");
    exit();
}

// Get all prescriptions with user details using the correct primary key
$query = "SELECT p.*, u.name as customer_name, u.email as customer_email,
          DATE_FORMAT(p.upload_date, '%M %d, %Y %h:%i %p') as formatted_date 
          FROM prescriptions p 
          JOIN users u ON p.user_id = u.$primaryKeyColumn 
          ORDER BY p.upload_date DESC";

$result = $conn->query($query);

if ($result) {
    $prescriptions = $result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Prescriptions - Staff Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            background-color: #2c9db7;
            color: white;
            border-radius: 15px 15px 0 0 !important;
            padding: 1.5rem;
        }
        .prescription-item {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            margin-bottom: 1rem;
            padding: 1rem;
            background-color: white;
            transition: all 0.3s ease;
        }
        .prescription-item:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 500;
        }
        .status-pending {
            background-color: #fff7ed;
            color: #f97316;
        }
        .status-approved {
            background-color: #f0fdf4;
            color: #22c55e;
        }
        .status-rejected {
            background-color: #fef2f2;
            color: #ef4444;
        }
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            cursor: pointer;
        }
        .modal-body img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="mb-0">Manage Prescriptions</h3>
                <a href="staff-dashboard.php" class="btn btn-light">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($prescriptions)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-file-earmark-medical display-1 text-muted"></i>
                        <p class="mt-3">No prescriptions found</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($prescriptions as $prescription): ?>
                        <div class="prescription-item">
                            <div class="row align-items-center">
                                <div class="col-md-3">
                                    <?php
                                    $fileExt = strtolower(pathinfo($prescription['file_name'], PATHINFO_EXTENSION));
                                    if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif'])):
                                    ?>
                                        <img src="<?= htmlspecialchars($prescription['file_path']) ?>" 
                                             class="preview-image"
                                             data-bs-toggle="modal"
                                             data-bs-target="#imageModal<?= $prescription['id'] ?>"
                                             alt="Prescription">
                                    <?php else: ?>
                                        <a href="<?= htmlspecialchars($prescription['file_path']) ?>" 
                                           class="btn btn-outline-primary" 
                                           target="_blank">
                                            <i class="bi bi-file-pdf"></i> View PDF
                                        </a>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <h5><?= htmlspecialchars($prescription['customer_name']) ?></h5>
                                    <p class="text-muted mb-2">
                                        <i class="bi bi-envelope"></i> <?= htmlspecialchars($prescription['customer_email']) ?>
                                    </p>
                                    <p class="mb-2">
                                        <i class="bi bi-calendar"></i> 
                                        Uploaded: <?= $prescription['formatted_date'] ?>
                                    </p>
                                    <p class="mb-0">
                                        <span class="status-badge status-<?= $prescription['status'] ?>">
                                            <?= ucfirst($prescription['status']) ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-3">
                                    <button type="button" 
                                            class="btn btn-primary w-100 mb-2"
                                            data-bs-toggle="modal"
                                            data-bs-target="#updateModal<?= $prescription['id'] ?>">
                                        Update Status
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Image Modal -->
                        <?php if (in_array($fileExt, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                        <div class="modal fade" id="imageModal<?= $prescription['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Prescription Image</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body text-center">
                                        <img src="<?= htmlspecialchars($prescription['file_path']) ?>" 
                                             class="img-fluid"
                                             alt="Prescription">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Update Status Modal -->
                        <div class="modal fade" id="updateModal<?= $prescription['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Update Prescription Status</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form action="" method="POST">
                                        <div class="modal-body">
                                            <input type="hidden" name="prescription_id" value="<?= $prescription['id'] ?>">
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Status</label>
                                                <select name="status" class="form-select" required>
                                                    <option value="pending" <?= $prescription['status'] === 'pending' ? 'selected' : '' ?>>
                                                        Pending
                                                    </option>
                                                    <option value="approved" <?= $prescription['status'] === 'approved' ? 'selected' : '' ?>>
                                                        Approved
                                                    </option>
                                                    <option value="rejected" <?= $prescription['status'] === 'rejected' ? 'selected' : '' ?>>
                                                        Rejected
                                                    </option>
                                                </select>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <label class="form-label">Notes</label>
                                                <textarea name="notes" class="form-control" rows="3"
                                                          placeholder="Add any notes about this prescription"><?= htmlspecialchars($prescription['notes'] ?? '') ?></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Update Status</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
