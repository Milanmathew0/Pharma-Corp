<?php
// Database connection details
$host = 'localhost';
$dbname = 'pharma';
$username = 'root';
$password = ''; // Empty password for root user

try {
    // Establish database connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create staff_requests table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS staff_requests (
        request_id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "Staff requests table created or already exists.\n";
    
    // Check if the table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM staff_requests");
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        echo "Table is empty. You need to add staff requests through the registration process.\n";
    } else {
        echo "Found {$count} staff requests in the table.\n";
        
        // Show all staff requests
        $stmt = $pdo->query("SELECT email, status, created_at FROM staff_requests ORDER BY request_id DESC");
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\nStaff Requests:\n";
        foreach ($requests as $request) {
            echo "Email: {$request['email']}, Status: {$request['status']}, Created: {$request['created_at']}\n";
        }
    }
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
