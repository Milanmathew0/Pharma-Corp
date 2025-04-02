<?php
session_start();
include "connect.php";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user_id']) && isset($_POST['new_role'])) {
    $user_id = $_POST['user_id'];
    $new_role = $_POST['new_role'];
    
    error_log("POST data received: " . print_r($_POST, true));
    
    if (!empty($user_id) && in_array($new_role, ['staff', 'manager'])) {
        $check_sql = "SELECT role FROM users WHERE user_id = '$user_id'";
        $check_result = $conn->query($check_sql);
        error_log("Check query: " . $check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            $update_sql = "UPDATE users SET role = '$new_role' WHERE user_id = '$user_id'";
            error_log("Update query: " . $update_sql);
            
            if ($conn->query($update_sql)) {
                $_SESSION['message'] = "User role updated successfully!";
                error_log("Role updated successfully for user ID: $user_id to $new_role");
            } else {
                $_SESSION['error'] = "Error updating user role: " . $conn->error;
                error_log("MySQL Error: " . $conn->error);
            }
        } else {
            $_SESSION['error'] = "User not found!";
            error_log("User not found with ID: $user_id");
        }
    } else {
        $_SESSION['error'] = "Invalid user ID or role! (ID: $user_id, Role: $new_role)";
        error_log("Validation failed - User ID: $user_id, Role: $new_role");
    }
    
    header("Location: admin-user-roles.php");
    exit();
}

$result = $conn->query("SELECT * FROM users WHERE role != 'Admin' ORDER BY username");
error_log("Fetching users query executed");

if ($result) {
    $users = $result->fetch_all(MYSQLI_ASSOC);
} else {
    $users = [];
    error_log("Error fetching users: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage User Roles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .role-badge {
            min-width: 80px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>User Role Management</h2>
            <a href="admin-dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

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

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Current Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $user['role'] === 'manager' ? 'primary' : 'success' ?> role-badge">
                                        <?= ucfirst($user['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to change this user\'s role?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <select name="new_role" class="form-select form-select-sm d-inline-block w-auto me-2">
                                            <option value="staff" <?php echo $user['role'] === 'staff' ? 'selected' : ''; ?>>Staff</option>
                                            <option value="manager" <?php echo $user['role'] === 'manager' ? 'selected' : ''; ?>>Manager</option>
                                        </select>
                                        <button type="submit" class="btn btn-warning btn-sm">
                                            <i class="bi bi-arrow-repeat"></i>
                                            Update Role
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="4" class="text-center">No users found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
