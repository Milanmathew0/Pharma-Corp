<?php
session_start();
include "connect.php";

// Set error handling to catch all errors
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Function to handle fatal errors
function shutdown_handler() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        echo json_encode([
            "success" => false,
            "message" => "Fatal error: " . $error['message'] . " in " . $error['file'] . " on line " . $error['line']
        ]);
        exit();
    }
}
register_shutdown_function('shutdown_handler');

// Set content type to JSON
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    echo json_encode(["success" => false, "message" => "Unauthorized access"]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Check if we have the required tables
        $tables_check = $conn->query("SHOW TABLES LIKE 'sales'");
        if ($tables_check->num_rows == 0) {
            // Create sales table
            $conn->query("CREATE TABLE IF NOT EXISTS sales (
                id INT PRIMARY KEY AUTO_INCREMENT,
                customer_id INT NOT NULL,
                staff_id INT NOT NULL,
                sale_date DATETIME NOT NULL,
                total_amount DECIMAL(10,2) NOT NULL DEFAULT 0
            )");
            
            // Create sale_details table
            $conn->query("CREATE TABLE IF NOT EXISTS sale_details (
                id INT PRIMARY KEY AUTO_INCREMENT,
                sale_id INT NOT NULL,
                medicine_id INT NOT NULL,
                quantity INT NOT NULL,
                unit_price DECIMAL(10,2) NOT NULL,
                subtotal DECIMAL(10,2) NOT NULL
            )");
        }

        // Check if medicines table exists
        $medicines_check = $conn->query("SHOW TABLES LIKE 'medicines'");
        if ($medicines_check->num_rows == 0) {
            // Create medicines table if it doesn't exist
            $create_medicines = "CREATE TABLE IF NOT EXISTS medicines (
                id INT PRIMARY KEY AUTO_INCREMENT,
                name VARCHAR(255) NOT NULL,
                manufacturer VARCHAR(255) DEFAULT '',
                price DECIMAL(10,2) DEFAULT 0.00,
                stock_quantity INT DEFAULT 0,
                expiry_date DATE NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            if (!$conn->query($create_medicines)) {
                throw new Exception("Failed to create medicines table: " . $conn->error);
            }
            
            // Add a sample medicine
            $sample_medicine = "INSERT INTO medicines (name, manufacturer, price, stock_quantity, expiry_date) 
                               VALUES ('Sample Medicine', 'Sample Manufacturer', 10.00, 100, DATE_ADD(NOW(), INTERVAL 1 YEAR))";
            $conn->query($sample_medicine);
            
            error_log("Created medicines table with sample data");
        }
        
        // Check if the connection is still valid
        if ($conn->ping() === false) {
            // Try to reconnect
            $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
            if ($conn->connect_error) {
                throw new Exception("Database connection lost and reconnection failed: " . $conn->connect_error);
            }
            error_log("Database connection was lost but successfully reconnected");
        }
        
        // Check the structure of the medicines table
        $medicine_structure = $conn->query("DESCRIBE medicines");
        $medicine_columns = [];
        while ($row = $medicine_structure->fetch_assoc()) {
            $medicine_columns[] = $row['Field'];
        }
        
        // Debug the table structure
        error_log("Medicine table columns: " . print_r($medicine_columns, true));
        
        // Make sure required columns exist
        if (!in_array('id', $medicine_columns) || !in_array('stock_quantity', $medicine_columns)) {
            // Add missing columns
            if (!in_array('id', $medicine_columns)) {
                // Check if there's already a primary key
                $primary_key_query = "SHOW KEYS FROM medicines WHERE Key_name = 'PRIMARY'";
                $primary_key_result = $conn->query($primary_key_query);
                
                if ($primary_key_result->num_rows > 0) {
                    // There's already a primary key, find out what it is
                    $primary_key = $primary_key_result->fetch_assoc();
                    $primary_column = $primary_key['Column_name'];
                    
                    error_log("Found existing primary key: " . $primary_column);
                    
                    // Use the existing primary key as the ID for our queries
                    $id_column = $primary_column;
                } else {
                    // No primary key, try to add one
                    $add_id_result = $conn->query("ALTER TABLE medicines ADD COLUMN id INT PRIMARY KEY AUTO_INCREMENT FIRST");
                    if (!$add_id_result) {
                        error_log("Failed to add id column: " . $conn->error);
                        // Try to find any unique identifier column
                        foreach ($medicine_columns as $column) {
                            if (in_array($column, ['medicine_id', 'med_id', 'product_id'])) {
                                $id_column = $column;
                                error_log("Using $column as ID column");
                                break;
                            }
                        }
                        
                        if (!isset($id_column)) {
                            throw new Exception("Could not find or create an ID column in medicines table");
                        }
                    } else {
                        $id_column = 'id';
                    }
                }
            } else {
                $id_column = 'id';
            }
            
            if (!in_array('stock_quantity', $medicine_columns)) {
                $conn->query("ALTER TABLE medicines ADD COLUMN stock_quantity INT DEFAULT 0");
            }
            
            error_log("Added missing columns to medicines table");
        } else {
            $id_column = 'id';
        }
        
        // Add price column if it doesn't exist
        if (!in_array('price', $medicine_columns)) {
            $conn->query("ALTER TABLE medicines ADD COLUMN price DECIMAL(10,2) DEFAULT 0.00");
        }

        // Start transaction
        $conn->begin_transaction();

        // Debug all POST data
        error_log("POST data: " . print_r($_POST, true));
        
        $customer_id = intval($_POST['customer_id']);
        $medicines = json_decode($_POST['medicines'], true);
        
        // Debug - check what we received
        error_log("Raw customer_id: " . $_POST['customer_id']);
        error_log("Parsed customer_id: " . $customer_id);
        
        // Extract numeric part if the ID has a format like U0005
        if (preg_match('/^U(\d+)$/', $_POST['customer_id'], $matches)) {
            $customer_id = intval($matches[1]);
        }
        
        // Check if we have a valid customer ID after parsing
        if (!$customer_id || $customer_id <= 0) {
            throw new Exception("Invalid customer ID format: " . $_POST['customer_id']);
        }
        
        if (empty($medicines)) {
            throw new Exception("No medicines selected");
        }
        
        // Debug what we received
        error_log("Customer ID: " . $customer_id);
        error_log("Medicines: " . print_r($medicines, true));

        // Create sale record
        $sale_query = "INSERT INTO sales (customer_id, staff_id, sale_date, total_amount) VALUES (?, ?, NOW(), 0)";
        $stmt = $conn->prepare($sale_query);
        $stmt->bind_param("ii", $customer_id, $_SESSION['user_id']);
        
        if (!$stmt->execute()) {
            throw new Exception("Error creating sale record: " . $stmt->error);
        }
        
        $sale_id = $conn->insert_id;
        $total_amount = 0;

        // Process each medicine
        foreach ($medicines as $medicine) {
            // Verify stock availability
            $stock_query = "SELECT * FROM medicines WHERE $id_column = ?";
            $stmt = $conn->prepare($stock_query);
            if (!$stmt) {
                // Try a simpler query to see if prepared statements work at all
                $test_stmt = $conn->prepare("SELECT 1");
                if (!$test_stmt) {
                    throw new Exception("Database error: Cannot prepare any statements: " . $conn->error);
                }
                
                // Try a direct query as a fallback
                $direct_query = "SELECT * FROM medicines WHERE $id_column = " . intval($medicine['id']);
                $direct_result = $conn->query($direct_query);
                if (!$direct_result) {
                    throw new Exception("Database error: Both prepared and direct queries failed: " . $conn->error);
                }
                
                if ($direct_result->num_rows === 0) {
                    throw new Exception("Medicine not found with ID: " . $medicine['id']);
                }
                
                $med_data = $direct_result->fetch_assoc();
                // Continue with the fallback data
            } else {
                $stmt->bind_param("i", $medicine['id']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    throw new Exception("Medicine not found with ID: " . $medicine['id']);
                }
                
                $med_data = $result->fetch_assoc();
            }
            
            // Check if there's enough stock
            if ($med_data['stock_quantity'] < $medicine['quantity']) {
                throw new Exception("Insufficient stock for medicine ID: " . $medicine['id'] . 
                                   " (Available: " . $med_data['stock_quantity'] . 
                                   ", Requested: " . $medicine['quantity'] . ")");
            }
            
            $price = isset($med_data['price']) ? $med_data['price'] : 0;
            $subtotal = $price * $medicine['quantity'];
            $total_amount += $subtotal;

            // Add sale detail
            $detail_query = "INSERT INTO sale_details (sale_id, medicine_id, quantity, unit_price, subtotal) 
                           VALUES (?, ?, ?, ?, ?)";
            $detail_stmt = $conn->prepare($detail_query);
            if (!$detail_stmt) {
                throw new Exception("Error preparing detail query: " . $conn->error);
            }
            $detail_stmt->bind_param("iiidi", $sale_id, $medicine['id'], $medicine['quantity'], $price, $subtotal);
            
            if (!$detail_stmt->execute()) {
                throw new Exception("Error adding sale detail");
            }

            // Update stock
            $update_stock = "UPDATE medicines SET stock_quantity = stock_quantity - ? WHERE $id_column = ?";
            $update_stmt = $conn->prepare($update_stock);
            if (!$update_stmt) {
                throw new Exception("Error preparing update query: " . $conn->error);
            }
            $update_stmt->bind_param("ii", $medicine['quantity'], $medicine['id']);
            
            if (!$update_stmt->execute()) {
                throw new Exception("Error updating stock");
            }
        }

        // Update total amount
        $update_total = "UPDATE sales SET total_amount = ? WHERE id = ?";
        $total_stmt = $conn->prepare($update_total);
        if (!$total_stmt) {
            throw new Exception("Error preparing total update query: " . $conn->error);
        }
        $total_stmt->bind_param("di", $total_amount, $sale_id);
        
        if (!$total_stmt->execute()) {
            throw new Exception("Error updating total amount");
        }

        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            "success" => true,
            "message" => "Sale processed successfully",
            "sale_id" => $sale_id
        ]);

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method"
    ]);
}
?> 