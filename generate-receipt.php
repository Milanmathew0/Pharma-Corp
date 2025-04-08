<?php
session_start();

// Check if user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    header("Location: login.php");
    exit();
}

include "connect.php";

// Check if sale_id is provided
if (!isset($_GET['sale_id']) || empty($_GET['sale_id'])) {
    die("Sale ID is required");
}

$sale_id = intval($_GET['sale_id']);

// Get sale information
$sale_query = "SELECT 
                s.sale_id, 
                s.sale_date, 
                s.total_amount, 
                s.payment_method,
                IF(s.customer_id IS NULL, 
                   'Walk-in Customer', 
                   u.name) as customer_name,
                COALESCE(u.email, '') as customer_email,
                COALESCE(u.phone, '') as customer_phone
              FROM 
                sales s 
                LEFT JOIN users u ON s.customer_id = u.user_id 
              WHERE 
                s.sale_id = ?";

$stmt = $conn->prepare($sale_query);
if ($stmt === false) {
    die("Error preparing sale query: " . $conn->error);
}
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$sale_result = $stmt->get_result();

if ($sale_result->num_rows == 0) {
    die("Sale not found");
}

$sale = $sale_result->fetch_assoc();

// Get sale items
$items_query = "SELECT 
                  si.quantity,
                  si.price,
                  m.name as medicine_name
                FROM 
                  sale_items si
                  JOIN medicines m ON si.medicine_id = m.medicine_id
                WHERE 
                  si.sale_id = ?";

$stmt = $conn->prepare($items_query);
if ($stmt === false) {
    die("Error preparing items query: " . $conn->error);
}
$stmt->bind_param("i", $sale_id);
$stmt->execute();
$items_result = $stmt->get_result();
$items = [];

while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
}

// Get store information from settings if available
$store_name = "Pharma Corp";
$store_address = "123 Pharmacy Street, Medical District";
$store_phone = "555-123-4567";
$store_email = "contact@pharmacorp.com";

// Settings query - if you have a settings table, otherwise use defaults
$settings_query = "SELECT * FROM settings LIMIT 1";
$settings_result = $conn->query($settings_query);
if ($settings_result && $settings_result->num_rows > 0) {
    $settings = $settings_result->fetch_assoc();
    $store_name = $settings['store_name'] ?? $store_name;
    $store_address = $settings['store_address'] ?? $store_address;
    $store_phone = $settings['store_phone'] ?? $store_phone;
    $store_email = $settings['store_email'] ?? $store_email;
}

// Set header for printable page
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #<?= $sale_id ?> - <?= htmlspecialchars($store_name) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 14px;
        }
        .receipt {
            max-width: 800px;
            margin: 0 auto;
            border: 1px solid #ddd;
            padding: 20px;
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .receipt-header h1 {
            margin: 0;
            color: #2c9db7;
        }
        .receipt-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .receipt-info div {
            flex: 1;
        }
        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .receipt-table th, .receipt-table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .receipt-total {
            text-align: right;
            font-weight: bold;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
        }
        .receipt-footer {
            text-align: center;
            margin-top: 30px;
            font-size: 12px;
            color: #777;
        }
        .btn {
            background-color: #2c9db7;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 20px;
            text-decoration: none;
            display: inline-block;
            margin-right: 10px;
        }
        .btn:hover {
            background-color: #248ca3;
        }
        .actions {
            text-align: center;
            margin-top: 20px;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                padding: 0;
            }
            .receipt {
                border: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="receipt-header">
            <h1><?= htmlspecialchars($store_name) ?></h1>
            <p><?= htmlspecialchars($store_address) ?></p>
            <p>Phone: <?= htmlspecialchars($store_phone) ?> | Email: <?= htmlspecialchars($store_email) ?></p>
            <h2>RECEIPT #<?= $sale_id ?></h2>
        </div>
        
        <div class="receipt-info">
            <div>
                <strong>Customer:</strong><br>
                <?= htmlspecialchars($sale['customer_name']) ?><br>
                <?php if (!empty($sale['customer_email'])): ?>
                Email: <?= htmlspecialchars($sale['customer_email']) ?><br>
                <?php endif; ?>
                <?php if (!empty($sale['customer_phone'])): ?>
                Phone: <?= htmlspecialchars($sale['customer_phone']) ?>
                <?php endif; ?>
            </div>
            <div>
                <strong>Sale Details:</strong><br>
                Date: <?= date('M d, Y h:i A', strtotime($sale['sale_date'])) ?><br>
                Payment Method: <?= ucfirst(htmlspecialchars($sale['payment_method'])) ?><br>
                Receipt ID: <?= $sale_id ?>
            </div>
        </div>
        
        <table class="receipt-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $grand_total = 0;
                foreach ($items as $item):
                    $item_total = $item['price'] * $item['quantity'];
                    $grand_total += $item_total;
                ?>
                <tr>
                    <td><?= htmlspecialchars($item['medicine_name']) ?></td>
                    <td>₹<?= number_format($item['price'], 2) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td>₹<?= number_format($item_total, 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="receipt-total">
            <p>Grand Total: ₹<?= number_format($sale['total_amount'], 2) ?></p>
        </div>
        
        <div class="receipt-footer">
            <p>Thank you for your purchase!</p>
            <p>For any questions regarding this receipt, please contact our customer service.</p>
        </div>
    </div>
    
    <div class="actions no-print">
        <button class="btn" onclick="window.print()">Print Receipt</button>
        <a href="sales-management.php" class="btn">Back to Sales</a>
    </div>
</body>
</html> 