<?php
session_start();

// Check if user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    header("Location: login.php");
    exit();
}

include "connect.php";

// Process sale
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_sale'])) {
    $customer_id = $_POST['customer_id'] === 'walkin' ? null : $_POST['customer_id'];
    $medicine_id = $_POST['medicine_id'];
    $quantity = $_POST['quantity'];
    $payment_method = $_POST['payment_method'];
    $walkin_name = isset($_POST['walkin_name']) ? $_POST['walkin_name'] : null;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Check stock availability
        $stock_query = "SELECT stock_quantity, price FROM medicines WHERE medicine_id = ?";
        $stmt = $conn->prepare($stock_query);
        
        // Check if statement preparation was successful
        if ($stmt === false) {
            throw new Exception("Failed to prepare stock query: " . $conn->error);
        }
        
        $stmt->bind_param("i", $medicine_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $medicine = $result->fetch_assoc();
        
        if ($medicine && $medicine['stock_quantity'] >= $quantity) {
            $total_amount = $quantity * $medicine['price'];
            
            // Create sale record - let's check if customer_name column exists
            $check_column_query = "SHOW COLUMNS FROM sales LIKE 'customer_name'";
            $column_result = $conn->query($check_column_query);
            
            if ($column_result && $column_result->num_rows > 0) {
                // The column exists
                $sale_query = "INSERT INTO sales (customer_id, staff_id, total_amount, payment_method, sale_date, customer_name) 
                              VALUES (?, ?, ?, ?, NOW(), ?)";
                $stmt = $conn->prepare($sale_query);
                $stmt->bind_param("iidss", $customer_id, $_SESSION['user_id'], $total_amount, $payment_method, $walkin_name);
            } else {
                // The column doesn't exist
                $sale_query = "INSERT INTO sales (customer_id, staff_id, total_amount, payment_method, sale_date) 
                              VALUES (?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($sale_query);
                $stmt->bind_param("iids", $customer_id, $_SESSION['user_id'], $total_amount, $payment_method);
            }
            
            // Check if statement preparation was successful
            if ($stmt === false) {
                throw new Exception("Failed to prepare sale query: " . $conn->error);
            }
            
            $stmt->execute();
            $sale_id = $conn->insert_id;
            
            // Create sale item record
            $item_query = "INSERT INTO sale_items (sale_id, medicine_id, quantity, price) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($item_query);
            
            // Check if statement preparation was successful
            if ($stmt === false) {
                throw new Exception("Failed to prepare sale item query: " . $conn->error);
            }
            
            $stmt->bind_param("iiid", $sale_id, $medicine_id, $quantity, $medicine['price']);
            $stmt->execute();
            
            // Update stock
            $update_stock = "UPDATE medicines SET stock_quantity = stock_quantity - ? WHERE medicine_id = ?";
            $stmt = $conn->prepare($update_stock);
            
            // Check if statement preparation was successful
            if ($stmt === false) {
                throw new Exception("Failed to prepare stock update query: " . $conn->error);
            }
            
            $stmt->bind_param("ii", $quantity, $medicine_id);
            $stmt->execute();
            
            $conn->commit();
            
            // For Razorpay payment, redirect to payment handler
            if ($payment_method === 'razorpay') {
                $_SESSION['razorpay_sale_id'] = $sale_id;
                $_SESSION['razorpay_amount'] = $total_amount;
                $_SESSION['success'] = "Sale created, redirecting to payment...";
                header("Location: sales-payment.php?sale_id=" . $sale_id);
                exit();
            }
            
            $_SESSION['success'] = "Sale processed successfully!";
        } else {
            throw new Exception("Insufficient stock!");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error processing sale: " . $e->getMessage();
    }
    
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get all customers
$customers_query = "SELECT user_id, username, email FROM users WHERE role = 'Customer'";
$customers = $conn->query($customers_query);

// Get all medicines
$medicines_query = "SELECT medicine_id AS id, name, price, stock_quantity FROM medicines ORDER BY name ASC";
$medicines = $conn->query($medicines_query);

// Check if query was successful
if (!$medicines) {
    $_SESSION['error'] = "Database query error: " . $conn->error;
    $medicine_count = 0;
} else {
    // Check if any medicines exist
    $medicine_count = $medicines->num_rows;
}

// Check if sales tables exist and create them if they don't
$tables_created = false;

// First check if the users table exists and has the expected structure
$check_users_table = $conn->query("SHOW TABLES LIKE 'users'");
if ($check_users_table->num_rows == 0) {
    $_SESSION['error'] = "Cannot create sales tables: users table doesn't exist.";
} else {
    $check_sales_table = $conn->query("SHOW TABLES LIKE 'sales'");
    $check_sale_items_table = $conn->query("SHOW TABLES LIKE 'sale_items'");

    if ($check_sales_table->num_rows == 0 || $check_sale_items_table->num_rows == 0) {
        // Start transaction for table creation
        $conn->begin_transaction();
        
        try {
            // Create sales table if it doesn't exist
            if ($check_sales_table->num_rows == 0) {
                $create_sales_table = "CREATE TABLE sales (
                    sale_id INT AUTO_INCREMENT PRIMARY KEY,
                    customer_id INT NULL,
                    staff_id INT NOT NULL,
                    total_amount DECIMAL(10,2) NOT NULL,
                    payment_method VARCHAR(50) NOT NULL,
                    sale_date DATETIME NOT NULL,
                    customer_name VARCHAR(100) NULL
                )";
                
                if (!$conn->query($create_sales_table)) {
                    throw new Exception("Failed to create sales table: " . $conn->error);
                }
            }
            
            // Create sale_items table if it doesn't exist
            if ($check_sale_items_table->num_rows == 0) {
                $create_sale_items_table = "CREATE TABLE sale_items (
                    item_id INT AUTO_INCREMENT PRIMARY KEY,
                    sale_id INT NOT NULL,
                    medicine_id INT NOT NULL,
                    quantity INT NOT NULL,
                    price DECIMAL(10,2) NOT NULL,
                    FOREIGN KEY (sale_id) REFERENCES sales(sale_id)
                )";
                
                if (!$conn->query($create_sale_items_table)) {
                    throw new Exception("Failed to create sale_items table: " . $conn->error);
                }
            }
            
            $conn->commit();
            $tables_created = true;
            $_SESSION['success'] = "Sales tables have been created. You can now process sales.";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['error'] = $e->getMessage();
        }
    }
}

// Get recent sales (only if tables exist now)
$have_sales_table = true;
$recent_sales = null;

if (!$tables_created && ($check_sales_table->num_rows == 0 || $check_sale_items_table->num_rows == 0)) {
    $have_sales_table = false;
} else {
    // Only try to query if the tables exist
    $recent_sales_query = "SELECT 
                          s.sale_id, 
                          DATE(s.sale_date) as sale_date, 
                          IF(s.customer_id IS NULL, 'Walk-in Customer', u.name) as customer_name,
                          m.name as medicine_name,
                          si.quantity,
                          si.price,
                          (si.price * si.quantity) as item_total
                      FROM 
                          sales s 
                          LEFT JOIN users u ON s.customer_id = u.user_id 
                          JOIN sale_items si ON s.sale_id = si.sale_id 
                          JOIN medicines m ON si.medicine_id = m.medicine_id 
                      ORDER BY 
                          s.sale_date DESC, s.sale_id DESC
                      LIMIT 20";
    $recent_sales = $conn->query($recent_sales_query);

    if (!$recent_sales) {
        $_SESSION['error'] = "Recent sales query error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Management - Pharma Corp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #2c9db7;
            color: white;
            border-radius: 10px 10px 0 0 !important;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .btn-primary {
            background-color: #2c9db7;
            border-color: #2c9db7;
        }
        .btn-primary:hover {
            background-color: #248ca3;
            border-color: #248ca3;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="staff-dashboard.php">Pharma Corp</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="staff-dashboard.php">
                    <i class="bi bi-house-door"></i> Dashboard
                </a>
                <a class="nav-link" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= htmlspecialchars($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="row">
            <!-- New Sale Form -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">New Sale</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" id="saleForm">
                            <div class="mb-3">
                                <label for="customer" class="form-label">Customer</label>
                                <select class="form-select" name="customer_id" id="customer_id" required>
                                    <option value="walkin">Non-registered Customer / Walk-in</option>
                                    <?php while($customer = $customers->fetch_assoc()): ?>
                                        <option value="<?= $customer['user_id'] ?>">
                                            <?= htmlspecialchars($customer['username']) ?> 
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3" id="walkin_name_div" style="display:none;">
                                <label for="walkin_name" class="form-label">Customer Name</label>
                                <input type="text" class="form-control" name="walkin_name" id="walkin_name">
                            </div>

                            <div class="mb-3">
                                <label for="medicine" class="form-label">Medicine</label>
                                <select class="form-select medicine-select" name="medicine_id" id="medicine" required>
                                    <option value="">Select Medicine</option>
                                    <?php if ($medicine_count > 0): ?>
                                        <?php while($medicine = $medicines->fetch_assoc()): ?>
                                            <option value="<?= $medicine['id'] ?>" 
                                                    data-price="<?= $medicine['price'] ?>"
                                                    data-stock="<?= $medicine['stock_quantity'] ?>">
                                                <?= htmlspecialchars($medicine['name']) ?> 
                                                (Stock: <?= $medicine['stock_quantity'] ?>) - 
                                                ₹<?= number_format($medicine['price'], 2) ?>
                                            </option>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <option value="" disabled>No medicines found in database</option>
                                    <?php endif; ?>
                                </select>
                                <?php if ($medicine_count == 0): ?>
                                    <div class="form-text text-danger">No medicines found. Please add medicines to the inventory first.</div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" class="form-control" name="quantity" id="quantity" 
                                       min="1" required>
                                <div class="form-text" id="stockWarning"></div>
                            </div>

                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment Method</label>
                                <select class="form-select" name="payment_method" id="payment_method" required>
                                    <option value="cash" selected>Cash</option>
                                    <option value="razorpay">Razorpay (Online)</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Total Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text">₹</span>
                                    <input type="text" class="form-control" id="totalAmount" readonly>
                                </div>
                            </div>

                            <button type="submit" name="process_sale" class="btn btn-primary">
                                Process Sale
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Recent Sales -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Recent Sales</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!$have_sales_table): ?>
                            <div class="alert alert-info">
                                No sales records exist yet. Process your first sale to start tracking sales.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Customer</th>
                                            <th>Medicine</th>
                                            <th>Quantity</th>
                                            <th>Amount</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        if ($recent_sales && $recent_sales->num_rows > 0): 
                                            $current_sale_id = 0;
                                            $displayed_sales = [];
                                            
                                            while($sale = $recent_sales->fetch_assoc()):
                                                // Create a unique key for this sale item
                                                $sale_key = $sale['sale_id'] . '-' . $sale['medicine_name'];
                                                
                                                // Only display if we haven't shown this exact item before
                                                if (!in_array($sale_key, $displayed_sales)):
                                                    $displayed_sales[] = $sale_key;
                                                    
                                                    // Calculate item total if not provided by the query
                                                    if (!isset($sale['item_total'])) {
                                                        $sale['item_total'] = $sale['price'] * $sale['quantity'];
                                                    }
                                        ?>
                                                <tr>
                                                    <td><?= date('M d, Y', strtotime($sale['sale_date'])) ?></td>
                                                    <td><?= htmlspecialchars($sale['customer_name']) ?></td>
                                                    <td><?= htmlspecialchars($sale['medicine_name']) ?></td>
                                                    <td><?= $sale['quantity'] ?></td>
                                                    <td>₹<?= number_format($sale['item_total'], 2) ?></td>
                                                    <td>
                                                        <a href="generate-receipt.php?sale_id=<?= $sale['sale_id'] ?>" 
                                                           class="btn btn-sm btn-outline-primary" 
                                                           target="_blank">
                                                            <i class="bi bi-download"></i> Receipt
                                                        </a>
                                                    </td>
                                                </tr>
                                        <?php 
                                                endif;
                                            endwhile; 
                                        else: 
                                        ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No recent sales found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show/hide walk-in name field based on customer selection
            const customerSelect = document.getElementById('customer_id');
            const walkinNameDiv = document.getElementById('walkin_name_div');
            const walkinNameInput = document.getElementById('walkin_name');
            
            function toggleWalkinName() {
                if (customerSelect.value === 'walkin') {
                    walkinNameDiv.style.display = 'block';
                    walkinNameInput.required = true;
                } else {
                    walkinNameDiv.style.display = 'none';
                    walkinNameInput.required = false;
                }
            }
            
            customerSelect.addEventListener('change', toggleWalkinName);
            
            // Initialize the display state
            toggleWalkinName();
            
            // Handle payment method changes
            const paymentMethodSelect = document.getElementById('payment_method');
            const saleForm = document.getElementById('saleForm');
            
            paymentMethodSelect.addEventListener('change', function() {
                if (this.value === 'razorpay' && customerSelect.value === 'walkin') {
                    alert('Online payments require a registered customer. Please select a registered customer or choose Cash payment for walk-in customers.');
                    this.value = 'cash';
                }
            });
            
            saleForm.addEventListener('submit', function(e) {
                if (paymentMethodSelect.value === 'razorpay' && customerSelect.value === 'walkin') {
                    e.preventDefault();
                    alert('Online payments require a registered customer. Please select a registered customer or choose Cash payment for walk-in customers.');
                    paymentMethodSelect.value = 'cash';
                    return false;
                }
            });
            
            // Initialize Select2
            $(document).ready(function() {
                $('.medicine-select').select2({
                    placeholder: 'Search and select medicine',
                    width: '100%'
                });
                
                // Update total when Select2 changes
                $('.medicine-select').on('select2:select', function (e) {
                    updateTotal();
                });

                // Initialize Select2 for customers dropdown
                $('.customer-select').select2({
                    placeholder: 'Search and select customer',
                    width: '100%'
                });
            });

            const medicineSelect = document.getElementById('medicine');
            const quantityInput = document.getElementById('quantity');
            const totalAmountInput = document.getElementById('totalAmount');
            const stockWarning = document.getElementById('stockWarning');

            function updateTotal() {
                const selectedOption = medicineSelect.options[medicineSelect.selectedIndex];
                const price = selectedOption ? parseFloat(selectedOption.dataset.price) : 0;
                const quantity = parseInt(quantityInput.value) || 0;
                const total = price * quantity;
                totalAmountInput.value = total.toFixed(2);

                // Check stock
                if (selectedOption) {
                    const stock = parseInt(selectedOption.dataset.stock);
                    if (quantity > stock) {
                        stockWarning.textContent = `Warning: Only ${stock} items in stock`;
                        stockWarning.className = 'text-danger';
                    } else {
                        stockWarning.textContent = '';
                    }
                }
            }

            medicineSelect.addEventListener('change', function() {
                const selectedOption = medicineSelect.options[medicineSelect.selectedIndex];
                if (selectedOption) {
                    const stock = parseInt(selectedOption.dataset.stock);
                    quantityInput.max = stock;
                    quantityInput.value = '1';
                }
                updateTotal();
            });

            quantityInput.addEventListener('input', updateTotal);
        });
    </script>
</body>
</html>
