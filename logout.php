<?php
session_start();

// Log the logout if we have user information
if (isset($_SESSION['user_id'])) {
    require_once 'connect.php';
    
    // Check if login_logs table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'login_logs'");
    if ($table_check->num_rows == 0) {
        // Create login_logs table if it doesn't exist
        $create_table = "CREATE TABLE login_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL,
            role VARCHAR(20) NOT NULL,
            login_time DATETIME NOT NULL,
            status ENUM('login', 'logout') NOT NULL
        )";
        $conn->query($create_table);
    }
    
    try {
        $stmt = $conn->prepare("INSERT INTO login_logs (user_id, email, role, login_time, status) VALUES (?, ?, ?, NOW(), 'logout')");
        if ($stmt === false) {
            error_log("Prepare failed: " . $conn->error);
        } else {
            $stmt->bind_param("sss", $_SESSION['user_id'], $_SESSION['email'], $_SESSION['role']);
            $stmt->execute();
        }
    } catch (Exception $e) {
        error_log("Error logging logout: " . $e->getMessage());
    }
}

// Destroy all session data
$_SESSION = array();

if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-3600, '/');
}

session_destroy();

// Redirect to login page
header("Location: login.php?message=" . urlencode("You have been successfully logged out."));
exit();
?>
