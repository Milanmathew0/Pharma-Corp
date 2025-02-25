<?php
session_start();
include "connect.php";

// Check if user is admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Get admin name from session
$admin_name = isset($_SESSION['admin_name']) ? $_SESSION['admin_name'] : 'Admin';

// Handle request approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $request_id = $_POST['request_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        // Start transaction
        $conn->begin_transaction();
        try {
            // Get request details
            $stmt = $conn->prepare("SELECT * FROM staff_requests WHERE id = ?");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $request = $result->fetch_assoc();
            
            
            // Update request status
            $updateSql = "UPDATE staff_requests SET status = 'approved' WHERE id = ?";
            $stmt = $conn->prepare($updateSql);
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            $_SESSION['message'] = "Staff request approved successfully!";
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $_SESSION['error'] = "Error approving request: " . $e->getMessage();
        }
    } elseif ($action === 'reject') {
        // Update request status to rejected
        $stmt = $conn->prepare("UPDATE staff_requests SET status = 'rejected' WHERE id = ?");
        $stmt->bind_param("i", $request_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Staff request rejected!";
        } else {
            $_SESSION['error'] = "Error rejecting request!";
        }
    } elseif ($action === 'remove') {
        // Start transaction
        $conn->begin_transaction();
        try {
            // Get request details
            $stmt = $conn->prepare("SELECT * FROM staff_requests WHERE id = ?");
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $request = $result->fetch_assoc();
            
            
            
            // Delete from staff_requests table
            $deleteRequestSql = "DELETE FROM staff_requests WHERE id = ?";
            $stmt = $conn->prepare($deleteRequestSql);
            $stmt->bind_param("i", $request_id);
            $stmt->execute();
            
            // Commit transaction
            $conn->commit();
            $_SESSION['message'] = "Staff member removed successfully!";
        } catch (Exception $e) {
            // Rollback on error
            $conn->rollback();
            $_SESSION['error'] = "Error removing staff member: " . $e->getMessage();
        }
    }
    
    header("Location: admin-staff-requests.php");
    exit();
}

// Get all requests
$result = $conn->query("SELECT * FROM staff_requests ORDER BY created_at DESC");
$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Staff Requests</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/img/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            padding: 100px 0;
            color: white;
            margin-bottom: 40px;
        }
        .navbar {
            background-color: rgba(255, 255, 255, 0.95) !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .navbar-brand {
            font-weight: bold;
            color: #0d6efd !important;
        }
        .nav-link {
            color: #333 !important;
            font-weight: 500;
        }
        .nav-link:hover {
            color: #0d6efd !important;
        }
        .table {
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            border-radius: 10px;
            overflow: hidden;
        }
        .table thead {
            background-color: #f8f9fa;
        }
        .btn {
            border-radius: 5px;
            padding: 8px 20px;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .alert {
            border-radius: 10px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">PharmaCorp</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin-dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin-staff-requests.php">Staff Requests</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($admin_name) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#profile">Profile</a></li>
                            <li><a class="dropdown-item" href="#settings">Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="login.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <h1 class="display-4">Staff Registration Requests</h1>
            <p class="lead">Manage staff registration requests and approvals</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container mt-5">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?= $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?= $_SESSION['error']; ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Request Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $request): ?>
                            <tr>
                                <td><?= htmlspecialchars($request['username']) ?></td>
                                <td><?= htmlspecialchars($request['email']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $request['status'] === 'pending' ? 'warning' : 
                                                       ($request['status'] === 'approved' ? 'success' : 'danger') ?>">
                                        <?= ucfirst($request['status']) ?>
                                    </span>
                                </td>
                                <td><?= date('Y-m-d H:i', strtotime($request['created_at'])) ?></td>
                                <td>
                                    <?php if ($request['status'] === 'pending'): ?>
                                    <form action="" method="POST" class="d-inline">
                                        <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                        <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">
                                            <i class="bi bi-check-lg"></i> Approve
                                        </button>
                                        <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">
                                            <i class="bi bi-x-lg"></i> Reject
                                        </button>
                                    </form>
                                    <?php elseif ($request['status'] === 'approved'): ?>
                                    <form action="" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to remove this staff member? This action cannot be undone.');">
                                        <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                                        <button type="submit" name="action" value="remove" class="btn btn-danger btn-sm">
                                            <i class="bi bi-trash"></i> Remove Staff
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($requests)): ?>
                            <tr>
                                <td colspan="5" class="text-center">No registration requests found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
