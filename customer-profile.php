<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Add cache control headers
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include "connect.php";

// Create or update users table to include all required fields
$alter_users_table = "ALTER TABLE users 
    ADD COLUMN IF NOT EXISTS name VARCHAR(100),
    ADD COLUMN IF NOT EXISTS phone VARCHAR(20),
    ADD COLUMN IF NOT EXISTS address TEXT";
$conn->query($alter_users_table);

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Initialize empty values if not set
$user['name'] = $user['name'] ?? '';
$user['phone'] = $user['phone'] ?? '';
$user['address'] = $user['address'] ?? '';
$user['email'] = $user['email'] ?? $_SESSION['email'] ?? ''; // Initialize email from session if not in user data

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $errors = [];

    // Validate name
    if (empty($name)) {
        $errors[] = "Name is required";
    } elseif (strlen($name) > 100) {
        $errors[] = "Name cannot exceed 100 characters";
    } elseif (!preg_match('/^[a-zA-Z\s]+$/', $name)) {
        $errors[] = "Name can only contain letters and spaces";
    }

    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    } elseif (strlen($email) > 100) {
        $errors[] = "Email cannot exceed 100 characters";
    }

    // Validate phone
    if (empty($phone)) {
        $errors[] = "Phone number is required";
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = "Phone number must be 10 digits";
    }

    // Validate address
    if (empty($address)) {
        $errors[] = "Address is required";
    } elseif (strlen($address) > 500) {
        $errors[] = "Address cannot exceed 500 characters";
    }

    // Validate password if being changed
    if (!empty($new_password)) {
        if (empty($current_password)) {
            $errors[] = "Current password is required to set a new password";
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect";
        }
        
        // Validate new password strength
        if (strlen($new_password) < 8) {
            $errors[] = "New password must be at least 8 characters long";
        } elseif (!preg_match('/[A-Z]/', $new_password)) {
            $errors[] = "New password must contain at least one uppercase letter";
        } elseif (!preg_match('/[a-z]/', $new_password)) {
            $errors[] = "New password must contain at least one lowercase letter";
        } elseif (!preg_match('/[0-9]/', $new_password)) {
            $errors[] = "New password must contain at least one number";
        } elseif (!preg_match('/[^A-Za-z0-9]/', $new_password)) {
            $errors[] = "New password must contain at least one special character";
        }
    }

    // Check if email already exists for other users
    if ($email !== $user['email']) {
        $check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $check_email->bind_param("ss", $email, $user_id);
        $check_email->execute();
        if ($check_email->get_result()->num_rows > 0) {
            $errors[] = "Email already in use by another account";
        }
        $check_email->close();
    }

    if (empty($errors)) {
        if (!empty($new_password)) {
            // Update with new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ?, password = ? WHERE user_id = ?");
            $update->bind_param("ssssss", $name, $email, $phone, $address, $hashed_password, $user_id);
        } else {
            // Update without changing password
            $update = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ?, address = ? WHERE user_id = ?");
            $update->bind_param("sssss", $name, $email, $phone, $address, $user_id);
        }

        if ($update->execute()) {
            $_SESSION['message'] = "Profile updated successfully!";
            // Update session name
            $_SESSION['name'] = $name;
            header("Location: customer-profile.php");
            exit();
        } else {
            $errors[] = "Error updating profile: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Pharma Corp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h2>My Profile</h2>
            </div>
            <div class="col-auto d-flex gap-2">
                <a href="customer-dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                <a href="logout.php" class="btn btn-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $_SESSION['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" 
                                       value="<?= htmlspecialchars($user['name']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($user['email']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($user['phone']) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" 
                                          rows="3" required><?= htmlspecialchars($user['address']) ?></textarea>
                            </div>

                            <hr class="my-4">

                            <h5>Change Password</h5>
                            <p class="text-muted small">Leave blank to keep current password</p>

                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <div class="progress mt-2" style="height: 5px;">
                                    <div id="password-strength" class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <small id="password-feedback"></small>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const nameInput = document.getElementById('name');
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');
            const addressInput = document.getElementById('address');
            const newPasswordInput = document.getElementById('new_password');
            const currentPasswordInput = document.getElementById('current_password');
            
            // Custom validation for name
            nameInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
                if (this.value.length > 100) {
                    this.value = this.value.substring(0, 100);
                }
            });
            
            // Custom validation for phone
            phoneInput.addEventListener('input', function() {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value.length > 10) {
                    this.value = this.value.substring(0, 10);
                }
            });
            
            // Password strength validation
            newPasswordInput.addEventListener('input', function() {
                if (this.value) {
                    currentPasswordInput.required = true;
                    
                    let strength = 0;
                    const feedback = [];
                    
                    if (this.value.length >= 8) strength++;
                    else feedback.push('At least 8 characters');
                    
                    if (/[A-Z]/.test(this.value)) strength++;
                    else feedback.push('One uppercase letter');
                    
                    if (/[a-z]/.test(this.value)) strength++;
                    else feedback.push('One lowercase letter');
                    
                    if (/[0-9]/.test(this.value)) strength++;
                    else feedback.push('One number');
                    
                    if (/[^A-Za-z0-9]/.test(this.value)) strength++;
                    else feedback.push('One special character');
                    
                    const strengthMeter = document.getElementById('password-strength');
                    const strengthFeedback = document.getElementById('password-feedback');
                    
                    strengthMeter.style.width = (strength * 20) + '%';
                    strengthMeter.className = 'progress-bar';
                    
                    if (strength < 2) {
                        strengthMeter.classList.add('bg-danger');
                    } else if (strength < 4) {
                        strengthMeter.classList.add('bg-warning');
                    } else {
                        strengthMeter.classList.add('bg-success');
                    }
                    
                    strengthFeedback.innerHTML = feedback.length ? 
                        'Missing: ' + feedback.join(', ') : 
                        'Strong password!';
                } else {
                    currentPasswordInput.required = false;
                }
            });
            
            // Form validation
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });
        });
    </script>
</body>
</html>
