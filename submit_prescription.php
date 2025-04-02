<?php
session_start();
include "connect.php";

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Customer') {
    echo json_encode(["success" => false, "message" => "User not logged in or unauthorized"]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $prescription_details = mysqli_real_escape_string($conn, $_POST['prescription_details']);
    $file_path = null;
    
    // Use the extracted text if provided, otherwise use prescription details
    $extracted_text = isset($_POST['extracted_text']) ? 
        mysqli_real_escape_string($conn, $_POST['extracted_text']) : 
        $prescription_details;

    // Make sure the uploads directory exists
    $upload_dir = 'uploads/prescriptions/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Handle file upload if a file was submitted
    if (isset($_FILES['prescription_file']) && $_FILES['prescription_file']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
        $file_type = $_FILES['prescription_file']['type'];
        
        if (in_array($file_type, $allowed_types)) {
            $file_extension = pathinfo($_FILES['prescription_file']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('prescription_') . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            if (!move_uploaded_file($_FILES['prescription_file']['tmp_name'], $file_path)) {
                echo json_encode(["success" => false, "message" => "Error uploading file"]);
                exit();
            }

            // Get file content for the database
            $file_content = file_get_contents($file_path);
        } else {
            echo json_encode(["success" => false, "message" => "Invalid file type. Please upload an image or PDF"]);
            exit();
        }
    } else {
        $file_name = '';
        $file_content = '';
    }

    // Insert prescription into database
    $query = "INSERT INTO prescriptions (user_id, file_name, file_content, extracted_text, upload_date, status) 
              VALUES (?, ?, ?, ?, NOW(), 'pending')";
    
    $stmt = $conn->prepare($query);

    if ($stmt === false) {
        error_log("Prepare failed: (" . $conn->errno . ") " . $conn->error);
        echo json_encode(["success" => false, "message" => "Database error: Failed to prepare statement"]);
        exit();
    }

    // Bind parameters
    $null = NULL;
    if (empty($file_content)) {
        $stmt->bind_param("ssss", $user_id, $file_name, $null, $extracted_text);
    } else {
        $stmt->bind_param("ssss", $user_id, $file_name, $file_content, $extracted_text);
    }
    
    if (!$stmt->execute()) {
        error_log("Execute failed: (" . $stmt->errno . ") " . $stmt->error);
        echo json_encode(["success" => false, "message" => "Database error: Failed to execute statement"]);
        exit();
    }

    echo json_encode(["success" => true, "message" => "Prescription submitted successfully"]);
    exit();
}

// If not a POST request
echo json_encode(["success" => false, "message" => "Invalid request method"]);
exit();
?> 