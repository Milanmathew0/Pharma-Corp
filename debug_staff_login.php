<?php
$host = 'localhost';
$dbname = 'pharma';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create staff_requests table if not exists
    $sql = file_get_contents('create_staff_requests_table.sql');
    $pdo->exec($sql);
    echo "Staff requests table created or already exists.<br>";
    
    // Check if the staff email exists in users table
    $email = isset($_GET['email']) ? $_GET['email'] : '';
    if ($email) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            echo "User found in users table:<br>";
            echo "Role: " . htmlspecialchars($user['role']) . "<br>";
            echo "Email: " . htmlspecialchars($user['email']) . "<br>";
            
            // Check staff_requests table
            $staff_stmt = $pdo->prepare("SELECT * FROM staff_requests WHERE email = ?");
            $staff_stmt->execute([$email]);
            $staff_request = $staff_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($staff_request) {
                echo "Staff request found:<br>";
                echo "Status: " . htmlspecialchars($staff_request['status']) . "<br>";
            } else {
                echo "No staff request found for this email.<br>";
                
                // Create a test staff request
                echo "Creating test staff request...<br>";
                $insert_stmt = $pdo->prepare("INSERT INTO staff_requests (email, name, status) VALUES (?, ?, 'approved')");
                $insert_stmt->execute([$email, $user['name']]);
                echo "Test staff request created with approved status.<br>";
            }
        } else {
            echo "User not found in users table.<br>";
        }
    } else {
        echo "Please provide an email address in the URL parameter (e.g., debug_staff_login.php?email=staff@example.com)";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
