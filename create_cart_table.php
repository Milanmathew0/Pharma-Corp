<?php
include "connect.php";

// Create cart table
$create_cart_table = "CREATE TABLE IF NOT EXISTS cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50),
    medicine_id INT,
    quantity INT NOT NULL,
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES Medicines(medicine_id)
)";

if ($conn->query($create_cart_table) === TRUE) {
    echo "Cart table created successfully";
} else {
    echo "Error creating cart table: " . $conn->error;
}

$conn->close();
?>
