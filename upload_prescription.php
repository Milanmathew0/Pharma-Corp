<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

session_start();
include "connect.php";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to upload prescriptions']);
    exit();
}

$response = ['success' => false, 'message' => ''];

try {
    // Debug: Check users table structure
    $tableInfo = $conn->query("DESCRIBE users");
    if (!$tableInfo) {
        throw new Exception("Unable to get users table structure: " . $conn->error);
    }
    
    $userStructure = [];
    while ($row = $tableInfo->fetch_assoc()) {
        $userStructure[] = $row;
        if ($row['Field'] === 'user_id') {
            $userIdType = $row['Type'];
        }
    }

    // Debug: Output table structure
    error_log("Users table structure: " . print_r($userStructure, true));
    error_log("Session user_id: " . $_SESSION['user_id']);

    // First, check if the user exists in the users table
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    if (!$stmt) {
        throw new Exception("Database error preparing user check: " . $conn->error);
    }
    
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Invalid user session. User ID " . $_SESSION['user_id'] . " not found in database.");
    }
    
    $user = $result->fetch_assoc();
    error_log("Found user: " . print_r($user, true));
    $stmt->close();

    // Check if prescriptions table exists
    $tableExists = $conn->query("SHOW TABLES LIKE 'prescriptions'");
    if ($tableExists->num_rows == 0) {
        // Drop the table if it exists with wrong structure
        $conn->query("DROP TABLE IF EXISTS prescriptions");
        
        // Create prescriptions table with user_id as foreign key
        $create_table = "CREATE TABLE prescriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id $userIdType NOT NULL,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(255) NOT NULL,
            file_type VARCHAR(50) NOT NULL,
            upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            notes TEXT,
            INDEX idx_user_id (user_id),
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB";

        if (!$conn->query($create_table)) {
            throw new Exception("Database error creating table: " . $conn->error);
        }
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Invalid request method");
    }

    if (!isset($_FILES['prescription'])) {
        throw new Exception("No file uploaded");
    }

    $file = $_FILES['prescription'];
    if ($file['error'] !== 0) {
        throw new Exception("Upload error: " . $file['error']);
    }

    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/uploads/prescriptions/';
    if (!file_exists($uploadDir)) {
        if (!mkdir($uploadDir, 0777, true)) {
            throw new Exception("Failed to create upload directory");
        }
    }

    // Create .htaccess to prevent direct access
    $htaccess = $uploadDir . '.htaccess';
    if (!file_exists($htaccess)) {
        file_put_contents($htaccess, "Require all denied");
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception("Invalid file type. Please upload an image (JPG, PNG, GIF) or PDF");
    }

    if ($file['size'] > $maxSize) {
        throw new Exception("File is too large. Maximum size is 5MB");
    }

    // Generate unique filename
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $uniqueName = uniqid('prescription_') . '.' . $fileExt;
    $targetPath = 'uploads/prescriptions/' . $uniqueName;
    $fullPath = $uploadDir . $uniqueName;

    if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
        throw new Exception("Failed to move uploaded file");
    }

    // Save to database using the verified user_id
    $stmt = $conn->prepare("INSERT INTO prescriptions (user_id, file_name, file_path, file_type) VALUES (?, ?, ?, ?)");
    if (!$stmt) {
        // Clean up file if database prepare fails
        @unlink($fullPath);
        throw new Exception("Database error preparing insert: " . $conn->error);
    }

    error_log("Inserting prescription with user_id: " . $user['user_id']);
    $stmt->bind_param("isss", $user['user_id'], $file['name'], $targetPath, $file['type']);

    if (!$stmt->execute()) {
        // Clean up file if database insert fails
        @unlink($fullPath);
        throw new Exception("Database error inserting prescription: " . $stmt->error);
    }

    $response['success'] = true;
    $response['message'] = "Prescription uploaded successfully!";

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log("Prescription upload error: " . $e->getMessage());
}

echo json_encode($response);
