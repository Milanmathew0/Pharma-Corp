<?php
session_start();
include "connect.php";

// Check if user is logged in as staff or as the customer who owns the prescription
if (!isset($_SESSION['user_id']) || 
    ($_SESSION['role'] !== 'Staff' && $_SESSION['status'] !== 'approved')) {
    header("Location: login.php");
    exit();
}

if(isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // Get the prescription info
    $query = "SELECT * FROM prescriptions WHERE id = $id";
    $result = mysqli_query($conn, $query);
    
    if($result && mysqli_num_rows($result) > 0) {
        $prescription = mysqli_fetch_assoc($result);
        
        // If user is customer, verify they own this prescription
        if($_SESSION['role'] === 'Customer' && $prescription['user_id'] !== $_SESSION['user_id']) {
            echo "Access denied";
            exit();
        }
        
        // Output the image with proper headers
        header("Content-Type: image/jpeg");
        echo $prescription['file_content'];
        exit;
    } else {
        echo "Prescription not found";
    }
} else {
    echo "No prescription ID specified";
}
?> 