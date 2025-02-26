<?php
session_start();
include "connect.php";

// Debug session
error_log("Admin staff requests - Session data: " . print_r($_SESSION, true));

// Check if user is admin
if (!isset($_SESSION['role']) || strtolower($_SESSION['role']) !== 'admin') {
    error_log("Access denied - Current role: " . ($_SESSION['role'] ?? 'no role set'));
    header("Location: login.php");
    exit();
}

// Get admin name from session
$admin_name = isset($_SESSION['name']) ? $_SESSION['name'] : 'Admin';

// Handle request approval/rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_POST['user_id'];
    $action = $_POST['action'];
    
    if ($action === 'approve') {
        // Update user status to approved
        $stmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE user_id = ? AND role = 'Staff'");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Staff request approved successfully!";
        } else {
            $_SESSION['error'] = "Error approving request!";
            error_log("Error approving request: " . $conn->error);
        }
    } elseif ($action === 'reject') {
        // Update user status to rejected
        $stmt = $conn->prepare("UPDATE users SET status = 'rejected' WHERE user_id = ? AND role = 'Staff'");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Staff request rejected!";
        } else {
            $_SESSION['error'] = "Error rejecting request!";
            error_log("Error rejecting request: " . $conn->error);
        }
    } elseif ($action === 'remove') {
        // Delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role = 'Staff'");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $_SESSION['message'] = "Staff member removed successfully!";
        } else {
            $_SESSION['error'] = "Error removing staff member!";
            error_log("Error removing staff member: " . $conn->error);
        }
    }
    
    header("Location: admin-staff-requests.php");
    exit();
}

// Get all staff requests
$result = $conn->query("SELECT * FROM users WHERE role = 'Staff' ORDER BY user_id DESC");
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
        .badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        .badge-pending {
            background-color: #ffc107;
            color: #000;
        }
        .badge-approved {
            background-color: #198754;
            color: #fff;
        }
        .badge-rejected {
            background-color: #dc3545;
            color: #fff;
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
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-5 pt-4">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($_SESSION['message']); ?>
                <?php unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($_SESSION['error']); ?>
                <?php unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Staff Registration Requests</h4>
            </div>
            <div class="card-body">
                <?php if (empty($requests)): ?>
                    <div class="alert alert-info">No staff registration requests found.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($request['user_id']) ?></td>
                                        <td><?= htmlspecialchars($request['name']) ?></td>
                                        <td><?= htmlspecialchars($request['email']) ?></td>
                                        <td>
                                            <span class="badge badge-<?= strtolower($request['status']) ?>">
                                                <?= ucfirst(htmlspecialchars($request['status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?= $request['user_id'] ?>">
                                                <?php if ($request['status'] === 'pending'): ?>
                                                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm">
                                                        <i class="bi bi-check-circle"></i> Approve
                                                    </button>
                                                    <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">
                                                        <i class="bi bi-x-circle"></i> Reject
                                                    </button>
                                                <?php elseif ($request['status'] === 'approved'): ?>
                                                    <button type="submit" name="action" value="remove" class="btn btn-danger btn-sm">
                                                        <i class="bi bi-trash"></i> Remove
                                                    </button>
                                                <?php endif; ?>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
