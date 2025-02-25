<?php
include "connect.php";

// Create staff_users table
$create_staff_users = "CREATE TABLE IF NOT EXISTS staff_users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    is_approved TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($create_staff_users)) {
    echo "staff_users table created successfully\n";
} else {
    echo "Error creating staff_users table: " . $conn->error . "\n";
}

// Create staff_requests table if it doesn't exist
$create_staff_requests = "CREATE TABLE IF NOT EXISTS staff_requests (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($create_staff_requests)) {
    echo "staff_requests table created successfully\n";
} else {
    echo "Error creating staff_requests table: " . $conn->error . "\n";
}

// Create trigger to move approved staff to staff_users
$create_trigger = "CREATE TRIGGER IF NOT EXISTS after_staff_approval
AFTER UPDATE ON staff_requests
FOR EACH ROW
BEGIN
    IF NEW.status = 'approved' AND OLD.status = 'pending' THEN
        INSERT INTO staff_users (name, email, password, phone, is_approved)
        VALUES (NEW.name, NEW.email, NEW.password, NEW.phone, 1)
        ON DUPLICATE KEY UPDATE
        name = NEW.name,
        password = NEW.password,
        phone = NEW.phone,
        is_approved = 1;
    END IF;
END";

if ($conn->query($create_trigger)) {
    echo "Trigger created successfully\n";
} else {
    echo "Error creating trigger: " . $conn->error . "\n";
}

echo "Setup complete!";
?>
