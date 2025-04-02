<?php
session_start();
include "connect.php";

// Check if user has appropriate permissions
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Admin', 'Staff'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!isset($_FILES['inventoryFile']) || $_FILES['inventoryFile']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['inventoryFile'];
$fileName = $file['name'];
$fileType = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Validate file type
$allowedTypes = ['csv', 'xlsx', 'xls'];
if (!in_array($fileType, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type']);
    exit;
}

// Process the file based on type
require 'vendor/autoload.php'; // Make sure you have PhpSpreadsheet installed

try {
    if ($fileType === 'csv') {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Csv();
    } else {
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
    }
    
    $spreadsheet = $reader->load($file['tmp_name']);
    $worksheet = $spreadsheet->getActiveSheet();
    $rows = $worksheet->toArray();
    
    // Remove header row
    array_shift($rows);
    
    // Begin transaction
    $conn->begin_transaction();
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    
    foreach ($rows as $row) {
        if (empty($row[0])) continue; // Skip empty rows
        
        // Validate data
        $name = trim($row[0]);
        $batchNumber = trim($row[1]);
        $quantity = intval($row[2]);
        $price = floatval($row[3]);
        
        if (empty($name) || empty($batchNumber) || $quantity < 0 || $price <= 0) {
            $errorCount++;
            $errors[] = "Invalid data in row: " . implode(', ', $row);
            continue;
        }
        
        // Check if medicine exists
        $stmt = $conn->prepare("SELECT medicine_id FROM Medicines WHERE name = ? AND batch_number = ?");
        $stmt->bind_param("ss", $name, $batchNumber);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing medicine
            $stmt = $conn->prepare("UPDATE Medicines SET stock_quantity = ?, price_per_unit = ? WHERE name = ? AND batch_number = ?");
            $stmt->bind_param("idss", $quantity, $price, $name, $batchNumber);
        } else {
            // Insert new medicine
            $stmt = $conn->prepare("INSERT INTO Medicines (name, batch_number, stock_quantity, price_per_unit) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssid", $name, $batchNumber, $quantity, $price);
        }
        
        if ($stmt->execute()) {
            $successCount++;
        } else {
            $errorCount++;
            $errors[] = "Error processing row: " . implode(', ', $row);
        }
    }
    
    // Commit transaction if there were no errors
    if ($errorCount === 0) {
        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => "Successfully processed $successCount items."
        ]);
    } else {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => "Processed $successCount items with $errorCount errors.\n" . implode("\n", $errors)
        ]);
    }
    
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error processing file: ' . $e->getMessage()
    ]);
}

$conn->close();
?> 