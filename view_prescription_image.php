<?php
session_start();
include "connect.php";

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['Admin', 'Staff', 'Manager'])) {
    die("Unauthorized access");
}

if (!isset($_GET['id'])) {
    die("No prescription ID specified");
}

$id = intval($_GET['id']);

// Get the prescription file
$query = "SELECT file_name, file_content FROM prescriptions WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    if (!empty($row['file_content'])) {
        // Determine content type based on file extension
        $file_extension = strtolower(pathinfo($row['file_name'], PATHINFO_EXTENSION));
        switch ($file_extension) {
            case 'pdf':
                header('Content-Type: application/pdf');
                break;
            case 'jpg':
            case 'jpeg':
                header('Content-Type: image/jpeg');
                break;
            case 'png':
                header('Content-Type: image/png');
                break;
            default:
                header('Content-Type: application/octet-stream');
        }
        
        header('Content-Disposition: inline; filename="' . $row['file_name'] . '"');
        echo $row['file_content'];
    } else {
        die("No file content found");
    }
} else {
    die("Prescription not found");
}
?> 