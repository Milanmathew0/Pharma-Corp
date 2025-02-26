<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "pharma";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Add validation functions
function validateInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Handle stock updates
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_stock'])) {
    $errors = [];
    
    // Validate medicine_id
    $medicine_id = validateInput($_POST['medicine_id']);
    if (!is_numeric($medicine_id) || $medicine_id <= 0) {
        $errors[] = "Invalid medicine ID";
    }
    
    // Validate new_quantity
    $new_quantity = validateInput($_POST['new_quantity']);
    if (!is_numeric($new_quantity) || $new_quantity < 0) {
        $errors[] = "Quantity must be a positive number";
    }
    
    // Check if medicine exists
    $check_sql = "SELECT * FROM Medicines WHERE medicine_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $medicine_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        $errors[] = "Medicine not found";
    }
    
    if (empty($errors)) {
        $update_sql = "UPDATE Medicines SET stock_quantity = ? WHERE medicine_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ii", $new_quantity, $medicine_id);
        
        if ($stmt->execute()) {
            $success_message = "Stock updated successfully!";
        } else {
            $error_message = "Error updating stock: " . $conn->error;
        }
        $stmt->close();
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// Fetch all medicines
$sql = "SELECT * FROM Medicines ORDER BY name";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacy Inventory Control</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Pharmacy Inventory Control</h1>
            <p class="text-gray-600 mt-2">Monitor and update medicine stock levels</p>
        </header>

        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Stock Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <?php
            // Calculate summaries
            $total_items = $result->num_rows;
            
            $low_stock_sql = "SELECT COUNT(*) as count FROM Medicines WHERE stock_quantity <= 100";
            $low_stock_result = $conn->query($low_stock_sql);
            $low_stock = $low_stock_result->fetch_assoc()['count'];
            
            $expired_sql = "SELECT COUNT(*) as count FROM Medicines WHERE expiry_date <= CURDATE()";
            $expired_result = $conn->query($expired_sql);
            $expired = $expired_result->fetch_assoc()['count'];
            ?>
            
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700">Total Medicines</h3>
                <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo $total_items; ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700">Low Stock Items</h3>
                <p class="text-3xl font-bold text-yellow-600 mt-2"><?php echo $low_stock; ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold text-gray-700">Expired Items</h3>
                <p class="text-3xl font-bold text-red-600 mt-2"><?php echo $expired; ?></p>
            </div>
        </div>

        <!-- Inventory Table -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left">Medicine Name</th>
                            <th class="px-4 py-2 text-left">Batch Number</th>
                            <th class="px-4 py-2 text-left">Expiry Date</th>
                            <th class="px-4 py-2 text-left">Current Stock</th>
                            <th class="px-4 py-2 text-left">Price/Unit</th>
                            <th class="px-4 py-2 text-left">Update Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr class="border-t">
                                <td class="px-4 py-2"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($row['batch_number']); ?></td>
                                <td class="px-4 py-2">
                                    <?php 
                                    $expiry_date = new DateTime($row['expiry_date']);
                                    $today = new DateTime();
                                    $expired = $expiry_date < $today;
                                    $class = $expired ? 'text-red-600' : 'text-gray-800';
                                    echo "<span class='$class'>" . $expiry_date->format('Y-m-d') . "</span>";
                                    ?>
                                </td>
                                <td class="px-4 py-2">
                                    <?php
                                    $stock_class = 'bg-green-100 text-green-800';
                                    if ($row['stock_quantity'] <= 100) {
                                        $stock_class = 'bg-yellow-100 text-yellow-800';
                                    }
                                    if ($row['stock_quantity'] == 0) {
                                        $stock_class = 'bg-red-100 text-red-800';
                                    }
                                    ?>
                                    <span class="px-2 py-1 rounded-full text-sm <?php echo $stock_class; ?>">
                                        <?php echo $row['stock_quantity']; ?> units
                                    </span>
                                </td>
                                <td class="px-4 py-2">$<?php echo number_format($row['price_per_unit'], 2); ?></td>
                                <td class="px-4 py-2">
                                    <form method="POST" class="flex gap-2">
                                        <input type="hidden" name="medicine_id" value="<?php echo $row['medicine_id']; ?>">
                                        <input type="number" name="new_quantity" 
                                               class="w-20 px-2 py-1 border rounded"
                                               min="0" value="<?php echo $row['stock_quantity']; ?>">
                                        <button type="submit" name="update_stock"
                                                class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600">
                                            Update
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        // Client-side validation
        $('form').on('submit', function(e) {
            const quantityInput = $(this).find('input[name="new_quantity"]');
            const quantity = parseInt(quantityInput.val());
            let errors = [];

            // Validate quantity
            if (isNaN(quantity) || quantity < 0) {
                errors.push("Quantity must be a positive number");
                quantityInput.addClass('border-red-500');
            } else {
                quantityInput.removeClass('border-red-500');
            }

            // If there are errors, prevent form submission
            if (errors.length > 0) {
                e.preventDefault();
                alert(errors.join("\n"));
                return false;
            }

            // Confirmation dialog
            if (!confirm('Are you sure you want to update this stock quantity?')) {
                e.preventDefault();
                return false;
            }
        });

        // Real-time validation on input
        $('input[name="new_quantity"]').on('input', function() {
            const quantity = parseInt($(this).val());
            if (isNaN(quantity) || quantity < 0) {
                $(this).addClass('border-red-500');
            } else {
                $(this).removeClass('border-red-500');
            }
        });
    });
    </script>
</body>
</html>

<?php
$conn->close();
?>