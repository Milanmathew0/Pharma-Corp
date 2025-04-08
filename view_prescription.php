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
        
        // Check file extension to determine content type
        $file_name = $prescription['file_name'];
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        // Output the file with appropriate content type header
        switch($file_extension) {
            case 'pdf':
                header("Content-Type: application/pdf");
                break;
            case 'jpg':
            case 'jpeg':
                header("Content-Type: image/jpeg");
                break;
            case 'png':
                header("Content-Type: image/png");
                break;
            case 'gif':
                header("Content-Type: image/gif");
                break;
            default:
                header("Content-Type: application/octet-stream");
                break;
        }
        
        // Add content disposition header for all file types
        header("Content-Disposition: inline; filename=\"" . basename($file_name) . "\"");
        
        // Output the file content
        echo $prescription['file_content'];
        exit;
    } else {
        echo "Prescription not found";
    }
} else {
    echo "No prescription ID specified";
}
?> 