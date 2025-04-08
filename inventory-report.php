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

// Initialize variables for filtering
$filter_type = isset($_GET['filter_type']) ? $_GET['filter_type'] : 'all';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Prepare the SQL query based on filter
$sql = "SELECT * FROM Medicines";
$where_conditions = [];

if ($filter_type === 'expiring_soon') {
    $where_conditions[] = "expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 MONTH)";
} elseif ($filter_type === 'expired') {
    $where_conditions[] = "expiry_date < CURDATE()";
} elseif ($filter_type === 'low_stock') {
    $where_conditions[] = "stock_quantity <= 100";
}

if (!empty($start_date) && !empty($end_date)) {
    $where_conditions[] = "expiry_date BETWEEN '$start_date' AND '$end_date'";
}

if (!empty($where_conditions)) {
    $sql .= " WHERE " . implode(" AND ", $where_conditions);
}

$sql .= " ORDER BY name";
$result = $conn->query($sql);

// Calculate summary statistics
$total_value = 0;
$total_items = 0;
$low_stock_count = 0;
$expired_count = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Report - Pharma-Corp</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.css">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <header class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800">Inventory Report</h1>
            <p class="text-gray-600 mt-2">Generate and view inventory reports</p>
        </header>

       
        <!-- Report Table -->
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold text-gray-800">Inventory Report</h2>
                <button onclick="window.print()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    Print Report
                </button>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left">Medicine Name</th>
                            <th class="px-4 py-2 text-left">Company</th>
                            <th class="px-4 py-2 text-left">Batch Number</th>
                            <th class="px-4 py-2 text-left">Mfg. Date</th>
                            <th class="px-4 py-2 text-left">Expiry Date</th>
                            <th class="px-4 py-2 text-left">Stock</th>
                            <th class="px-4 py-2 text-left">Price/Unit</th>
                            <th class="px-4 py-2 text-left">Total Value</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if ($result->num_rows > 0):
                            while($row = $result->fetch_assoc()): 
                                $item_value = $row['stock_quantity'] * $row['price_per_unit'];
                                $total_value += $item_value;
                                $total_items += $row['stock_quantity'];
                                if ($row['stock_quantity'] <= 100) $low_stock_count++;
                                if (strtotime($row['expiry_date']) < time()) $expired_count++;
                        ?>
                            <tr class="border-t">
                                <td class="px-4 py-2"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($row['company']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($row['batch_number']); ?></td>
                                <td class="px-4 py-2"><?php echo (new DateTime($row['mfg_date']))->format('Y-m-d'); ?></td>
                                <td class="px-4 py-2">
                                    <?php 
                                    $expiry_date = new DateTime($row['expiry_date']);
                                    $today = new DateTime();
                                    $expired = $expiry_date < $today;
                                    $class = $expired ? 'text-red-600' : 'text-gray-800';
                                    echo "<span class='$class'>" . $expiry_date->format('Y-m-d') . "</span>";
                                    ?>
                                </td>
                                <td class="px-4 py-2"><?php echo $row['stock_quantity']; ?></td>
                                <td class="px-4 py-2">₹<?php echo number_format($row['price_per_unit'], 2); ?></td>
                                <td class="px-4 py-2">₹<?php echo number_format($item_value, 2); ?></td>
                            </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                            <tr>
                                <td colspan="8" class="px-4 py-2 text-center text-gray-500">No records found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="bg-gray-50 font-bold">
                        <tr>
                            <td colspan="5" class="px-4 py-2 text-right">Totals:</td>
                            <td class="px-4 py-2"><?php echo number_format($total_items); ?></td>
                            <td class="px-4 py-2">-</td>
                            <td class="px-4 py-2">₹<?php echo number_format($total_value, 2); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <!-- Summary Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mt-8">
                <div class="bg-blue-50 p-4 rounded">
                    <h3 class="font-semibold text-blue-700">Total Items</h3>
                    <p class="text-2xl font-bold text-blue-800"><?php echo number_format($total_items); ?></p>
                </div>
                <div class="bg-green-50 p-4 rounded">
                    <h3 class="font-semibold text-green-700">Total Value</h3>
                    <p class="text-2xl font-bold text-green-800">₹<?php echo number_format($total_value, 2); ?></p>
                </div>
                <div class="bg-yellow-50 p-4 rounded">
                    <h3 class="font-semibold text-yellow-700">Low Stock Items</h3>
                    <p class="text-2xl font-bold text-yellow-800"><?php echo $low_stock_count; ?></p>
                </div>
                <div class="bg-red-50 p-4 rounded">
                    <h3 class="font-semibold text-red-700">Expired Items</h3>
                    <p class="text-2xl font-bold text-red-800"><?php echo $expired_count; ?></p>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            body { background: white; }
            .container { max-width: none; padding: 0; }
            button { display: none; }
            .shadow { box-shadow: none; }
            .bg-white { background: white; }
            .rounded-lg { border-radius: 0; }
        }
    </style>
</body>
</html>

<?php
$conn->close();
?> 