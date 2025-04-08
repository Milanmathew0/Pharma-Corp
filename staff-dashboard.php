<?php
session_start();

// Add these debugging statements at the top of your PHP file (after session_start())
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Handle prescription status updates via AJAX
if (isset($_POST['id']) && isset($_POST['status'])) {
    include "connect.php";
    
    // Sanitize inputs
    $id = intval($_POST['id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Only allow valid status values
    if (!in_array($status, ['approved', 'rejected', 'pending'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit;
    }
    
    // Update the prescription status in database
    $update_query = "UPDATE prescriptions SET status = '$status' WHERE id = $id";
    $result = mysqli_query($conn, $update_query);
    
    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
    
    // Important: exit after sending JSON response to prevent HTML output
    exit;
}

// Check if user is logged in, is staff, and is approved
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff' || $_SESSION['status'] !== 'approved') {
    header("Location: login.php");
    exit();
}

include "connect.php";



// Initialize variables
$pendingCount = 0;

// Initialize the variable before using it
// $users_result = mysqli_query($conn, "SELECT * FROM users");

// Fetch customers from users table where role is 'user'
$customers_query = "SELECT user_id, email, phone FROM users WHERE role = 'Customer' ORDER BY user_id";
$customers_result = mysqli_query($conn, $customers_query);

if (!$customers_result) {
    die("Query failed: " . mysqli_error($conn));
}

// Get counts for stats
$customer_count_query = "SELECT COUNT(*) as count FROM users WHERE role = 'Customer'";
$customer_count_result = $conn->query($customer_count_query);

if (!$customer_count_result) {
    die("Query failed: " . $conn->error);
}

$customer_count = $customer_count_result->fetch_assoc();

$low_stock_query = "SELECT COUNT(*) as count FROM medicines WHERE stock_quantity <= 10";
$low_stock_result = $conn->query($low_stock_query);

if (!$low_stock_result) {
    die("Query failed: " . $conn->error);
}

$low_stock = $low_stock_result->fetch_assoc();

$total_medicines_query = "SELECT COUNT(*) as count FROM medicines";
$total_medicines_result = $conn->query($total_medicines_query);

if (!$total_medicines_result) {
    die("Query failed: " . $conn->error);
}

$total_medicines = $total_medicines_result->fetch_assoc();

$expiring_soon_query = "SELECT COUNT(*) as count FROM medicines WHERE expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL 30 DAY)";
$expiring_soon_result = $conn->query($expiring_soon_query);

if (!$expiring_soon_result) {
    die("Query failed: " . $conn->error);
}

$expiring_soon = $expiring_soon_result->fetch_assoc();

// Fetch all prescriptions with user information
$all_prescriptions_query = "SELECT p.*, u.name, u.email, u.phone FROM prescriptions p 
                          LEFT JOIN users u ON p.user_id = u.user_id 
                          ORDER BY p.upload_date DESC";
$all_prescriptions_result = $conn->query($all_prescriptions_query);

if (!$all_prescriptions_result) {
    die("Query failed: " . $conn->error);
}




?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard - Pharma Corp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <script>
        function approvePrescription(id) {
            if (confirm('Are you sure you want to approve this prescription?')) {
                updatePrescriptionStatus(id, 'approved');
            }
        }

        function rejectPrescription(id) {
            if (confirm('Are you sure you want to reject this prescription?')) {
                updatePrescriptionStatus(id, 'rejected');
            }
        }
        
        function updatePrescriptionStatus(prescriptionId, status) {
            if (confirm('Are you sure you want to ' + status + ' this prescription?')) {
                console.log('Sending request to update prescription #' + prescriptionId + ' to status: ' + status);
                
                // Use XMLHttpRequest for simplicity
                var xhr = new XMLHttpRequest();
                xhr.open('POST', 'staff-dashboard.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                
                xhr.onload = function() {
                    if (xhr.status >= 200 && xhr.status < 300) {
                        console.log('Response text:', xhr.responseText);
                        try {
                            var data = JSON.parse(xhr.responseText);
                            if (data.success) {
                                alert('Status updated successfully!');
                                window.location.reload(true); // Force reload from server
                                } else {
                                alert('Error: ' + (data.message || 'Failed to update prescription status'));
                            }
                        } catch (e) {
                            console.error('Error parsing JSON:', e);
                            alert('Error parsing response');
                        }
                    } else {
                        console.error('Request failed with status:', xhr.status);
                        alert('Request failed');
                    }
                };
                
                xhr.onerror = function() {
                    console.error('Request error');
                    alert('Request error');
                };
                
                xhr.send('id=' + encodeURIComponent(prescriptionId) + '&status=' + encodeURIComponent(status));
            }
        }
    </script>
    <style>
        :root {
            --primary-color: #2c9db7;
            --secondary-color: #858796;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(rgba(44, 157, 183, 0.1), rgba(44, 157, 183, 0.1)),
                        url('pic/3.jpg') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            padding: 20px 0;
        }

        .container {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        h2 {
            color: var(--primary-color);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .text-muted {
            color: var(--secondary-color) !important;
        }

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(44, 157, 183, 0.2);
        }

        .card-header {
            background: linear-gradient(to right, var(--primary-color), #3ab7d3);
            color: white;
            border: none;
            padding: 15px 20px;
        }

        .card-header h5 {
            margin: 0;
            font-weight: 600;
        }

        .feature-card {
            padding: 30px;
            text-align: center;
            height: 100%;
        }

        .feature-card .icon-large {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            color: var(--primary-color);
            transition: transform 0.3s ease;
        }

        .feature-card:hover .icon-large {
            transform: scale(1.1);
        }

        .feature-card h5 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            font-weight: 600;
        }

        .btn {
            padding: 8px 20px;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background: #248ca3;
            border-color: #248ca3;
            transform: translateY(-2px);
        }

        .table {
            border-collapse: separate;
            border-spacing: 0;
        }

        .table th {
            background-color: #f8f9fc;
            border-bottom: 2px solid #e3e6f0;
            color: var(--primary-color);
            font-weight: 600;
        }

        .table td {
            vertical-align: middle;
        }

        .badge {
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 500;
        }

        .btn-group .btn {
            padding: 5px 10px;
            margin: 0 2px;
        }

        /* Stats cards */
        .stats-card {
            padding: 20px;
            text-align: center;
            border-radius: 15px;
            background: white;
            transition: all 0.3s ease;
        }

        .stats-card h6 {
            color: var(--secondary-color);
            font-size: 0.9rem;
            margin-bottom: 10px;
        }

        .stats-card h3 {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
        }

        .stats-card.danger h3 {
            color: #dc3545;
        }

        .stats-card.warning h3 {
            color: #ffc107;
        }

        /* DataTables customization */
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: var(--primary-color) !important;
            color: white !important;
            border: none;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #248ca3 !important;
            color: white !important;
            border: none;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 2px solid #e3e6f0;
            border-radius: 10px;
            padding: 5px 10px;
        }

        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(44, 157, 183, 0.25);
        }

        .prescription-text {
            padding: 8px;
            margin-bottom: 5px;
            background-color: #f8f9fa;
            border-radius: 4px;
            font-size: 0.9em;
            border-left: 3px solid var(--primary-color);
        }

        .extracted-text {
            white-space: pre-wrap;
            font-family: monospace;
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 4px;
            border-left: 3px solid var(--primary-color);
            margin: 0;
            max-height: 400px;
            overflow-y: auto;
        }

        .modal-lg {
            max-width: 800px;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4 align-items-center">
            <div class="col">
                <h2>Welcome, <?= htmlspecialchars($_SESSION['name'] ?? 'Staff Member') ?></h2>
                <p class="text-muted">Manage inventory and handle customer requests</p>
            </div>
            <div class="col-auto d-flex gap-2">
                <a href="logout.php" class="btn btn-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6>Total Customers</h6>
                        <h3><?= $customer_count['count'] ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card danger">
                    <div class="card-body">
                        <h6>Low Stock Items</h6>
                        <h3><?= $low_stock['count'] ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body">
                        <h6>Total Medicines</h6>
                        <h3><?= $total_medicines['count'] ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card warning">
                    <div class="card-body">
                        <h6>Expiring Soon</h6>
                        <h3><?= $expiring_soon['count'] ?></h3>
                    </div>
                </div>
            </div>
        </div>
        
       
        <!-- Customer List -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Customer List</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table id="customerTable" class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($customers_result) {
                                        while ($user = mysqli_fetch_assoc($customers_result)): 
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($user['user_id']) ?></td>
                                        <td><?= htmlspecialchars($user['email']) ?></td>
                                        <td><?= htmlspecialchars($user['phone']) ?></td>
                                    </tr>
                                    <?php 
                                        endwhile;
                                    } else {
                                        echo "<tr><td colspan='3' class='text-center'>Error loading customers: " . mysqli_error($conn) . "</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="row mt-4">
        <!-- Combined Prescriptions Section -->
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">All Prescriptions</h5>
                </div>
                <div class="card-body">
                    <?php
                    // Fetch all prescriptions with user information 
                    $all_prescriptions_query = "SELECT p.*, u.name, u.email, u.phone FROM prescriptions p 
                                              LEFT JOIN users u ON p.user_id = u.user_id 
                                              ORDER BY p.upload_date DESC";
                    $all_prescriptions_result = $conn->query($all_prescriptions_query);
                    
                    if ($all_prescriptions_result && $all_prescriptions_result->num_rows > 0) {
                    ?>
                    <div class="table-responsive">
                        <table id="prescriptionsTable" class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>File</th>
                                    <th>Extracted Text</th>                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($prescription = $all_prescriptions_result->fetch_assoc()): ?>
                                <tr data-status="<?= htmlspecialchars($prescription['status']) ?>">
                                    <td><?= date('M d, Y', strtotime($prescription['upload_date'])) ?></td>
                                    <td>
                                        <small><?= htmlspecialchars($prescription['email'] ?? '') ?></small>
                                    </td>
                                    <td>
                                        <?php if(!empty($prescription['file_name'])): ?>
                                        <a href="view_prescription.php?id=<?= $prescription['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                                            View File (<?= htmlspecialchars($prescription['file_name']) ?>)
                                        </a>
                                        <?php else: ?>
                                        No file
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if(!empty($prescription['extracted_text'])): ?>
                                        <div class="prescription-text" style="max-height: 100px; overflow-y: auto;">
                                            <?= nl2br(htmlspecialchars(substr($prescription['extracted_text'], 0, 200))) ?>
                                            <?= (strlen($prescription['extracted_text']) > 200) ? '...' : '' ?>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-muted">No text extracted</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php 
                                            echo match($prescription['status']) {
                                                'approved' => 'bg-success',
                                                'rejected' => 'bg-danger',
                                                'pending' => 'bg-warning',
                                                default => 'bg-secondary'
                                            };
                                        ?>">
                                            <?= ucfirst(htmlspecialchars($prescription['status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if($prescription['status'] == 'pending'): ?>
                                        <button type="button" class="btn btn-success btn-sm" 
                                                onclick="approvePrescription(<?= $prescription['id'] ?>)">
                                            Approve
                                        </button>
                                        <button type="button" class="btn btn-danger btn-sm" 
                                                onclick="rejectPrescription(<?= $prescription['id'] ?>)">
                                            Reject
                                        </button>
                                        <?php elseif($prescription['status'] == 'approved' || $prescription['status'] == 'rejected'): ?>
                                        <button type="button" class="btn btn-warning btn-sm" 
                                                onclick="updatePrescriptionStatus(<?= $prescription['id'] ?>, 'pending')">
                                            Mark as Pending
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php } else { ?>
                    <div class="alert alert-info">No prescriptions found.</div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Feature Cards -->
    <div class="row g-4">
        <!-- Sales Management -->
        <div class="col-md-4">
            <div class="card feature-card">
                <div class="icon-large">
                    <i class="bi bi-cash-register"></i>
                </div>
                <h5>Sales Management</h5>
                <p>Process sales and view recent transactions</p>
                <a href="sales-management.php" class="btn btn-primary">Manage Sales</a>
            </div>
        </div>
        
        <!-- Manage Medicines -->
        <div class="col-md-4">
            <div class="card h-100 feature-card">
                <div class="card-body text-center">
                    <i class="bi bi-capsule icon-large text-primary"></i>
                    <h5 class="card-title">Manage Medicines</h5>
                    <p class="card-text">Add, update, and manage medicine inventory and stock levels.</p>
                    <a href="staff-inventory.php" class="btn btn-primary">
                        <i class="bi bi-capsule"></i> Manage Medicines
                    </a>
                </div>
            </div>
        </div>

        

        <!-- Stock Management -->
        <div class="col-md-4">
            <div class="card h-100 feature-card">
                <div class="card-body text-center">
                    <i class="bi bi-box-seam icon-large text-info"></i>
                    <h5 class="card-title">Stock Management</h5>
                    <p class="card-text">Monitor stock levels, handle expiry dates, and manage inventory.</p>
                    <a href="staff-stock.php" class="btn btn-info">
                        <i class="bi bi-box-seam"></i> Manage Stock
                    </a>
                </div>
            </div>
        </div>

        
    </div>
</div>

<!-- Add Bootstrap JS and required scripts at the end of the body -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let medicines = <?php 
    $medicines_query = "SELECT id, name, price, stock_quantity, manufacturer, expiry_date 
        FROM medicines 
        WHERE stock_quantity > 0 
        ORDER BY name ASC";
    $medicines_result = mysqli_query($conn, $medicines_query);
    $medicines_array = [];
    while ($medicine = mysqli_fetch_assoc($medicines_result)) {
        $medicines_array[] = $medicine;
    }
    echo json_encode($medicines_array);
?>;

function addMedicineRow() {
    const container = document.getElementById('medicineRows');
    const rowId = Date.now();
    
    const row = document.createElement('div');
    row.className = 'row mb-2 medicine-row';
    row.setAttribute('data-row-id', rowId);
    row.innerHTML = `
        <div class="col-md-5">
            <select class="form-select medicine-select" onchange="updatePrice(${rowId})" required>
                <option value="">Select Medicine</option>
                ${medicines.map(med => `
                    <option value="${med.id}" data-price="${med.price}" data-stock="${med.stock_quantity}">
                        ${med.name} - ${med.manufacturer} (Stock: ${med.stock_quantity}) - ₹${med.price}
                    </option>
                `).join('')}
            </select>
        </div>
        <div class="col-md-3">
            <input type="number" class="form-control quantity-input" 
                min="1" value="1" onchange="updateTotal(${rowId})" required>
        </div>
        <div class="col-md-3">
            <input type="text" class="form-control" readonly value="₹0.00">
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-danger btn-sm" onclick="removeMedicineRow(this)">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    `;
    container.appendChild(row);
    updatePrice(rowId);
}

function updatePrice(rowId) {
    const row = document.querySelector(`[data-row-id="${rowId}"]`);
    if (!row) return;
    
    const select = row.querySelector('.medicine-select');
    if (!select.value) {
        row.querySelector('input[readonly]').value = '₹0.00';
        calculateTotal();
        return;
    }
    
    const option = select.selectedOptions[0];
    const quantity = parseInt(row.querySelector('.quantity-input').value);
    const price = parseFloat(option.dataset.price);
    
    row.querySelector('input[readonly]').value = `₹${(price * quantity).toFixed(2)}`;
    calculateTotal();
}

function updateTotal(rowId) {
    const row = document.querySelector(`[data-row-id="${rowId}"]`);
    if (!row) return;
    
    const select = row.querySelector('.medicine-select');
    if (!select.value) return;
    
    const option = select.selectedOptions[0];
    const quantity = parseInt(row.querySelector('.quantity-input').value);
    const stock = parseInt(option.dataset.stock);
    
    if (quantity > stock) {
        alert('Quantity cannot exceed available stock!');
        row.querySelector('.quantity-input').value = stock;
        updatePrice(rowId);
        return;
    }
    
    updatePrice(rowId);
}

function removeMedicineRow(button) {
    button.closest('.medicine-row').remove();
    calculateTotal();
}

function calculateTotal() {
    let total = 0;
    document.querySelectorAll('.medicine-row').forEach(row => {
        const price = parseFloat(row.querySelector('input[readonly]').value.replace('₹', '')) || 0;
        total += price;
    });
    document.getElementById('totalAmount').textContent = `₹${total.toFixed(2)}`;
}

function processSale() {
    const formData = new FormData();
    formData.append('customer_id', document.getElementById('customerSearch').value);
    
    const medicines = [];
    document.querySelectorAll('.medicine-row').forEach(row => {
        const medicineId = row.querySelector('.medicine-select').value;
        const quantity = row.querySelector('.quantity-input').value;
        if (medicineId && quantity) {
            medicines.push({ id: medicineId, quantity: quantity });
        }
    });
    
    formData.append('medicines', JSON.stringify(medicines));
    
    fetch('process_sale.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Sale completed successfully!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error processing sale');
    });
}

// Add initial medicine row
document.addEventListener('DOMContentLoaded', () => {
    addMedicineRow();
});

// Add this JavaScript right before the body closing tag
document.addEventListener('DOMContentLoaded', function() {
    // Set up filtering for prescriptions table
    document.getElementById('filter-all').addEventListener('click', function() {
        filterPrescriptions('all');
        setActiveFilter(this);
    });
    
    document.getElementById('filter-pending').addEventListener('click', function() {
        filterPrescriptions('pending');
        setActiveFilter(this);
    });
    
    document.getElementById('filter-approved').addEventListener('click', function() {
        filterPrescriptions('approved');
        setActiveFilter(this);
    });
    
    document.getElementById('filter-rejected').addEventListener('click', function() {
        filterPrescriptions('rejected');
        setActiveFilter(this);
    });
    
    function filterPrescriptions(status) {
        const rows = document.querySelectorAll('#prescriptionsTable tbody tr');
        rows.forEach(row => {
            if (status === 'all') {
                row.style.display = '';
            } else {
                if (row.getAttribute('data-status') === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            }
        });
    }
    
    function setActiveFilter(button) {
        // Remove active class from all filter buttons
        document.querySelectorAll('.btn-group button').forEach(btn => {
            btn.classList.remove('active');
        });
        // Add active class to clicked button
        button.classList.add('active');
    }
});
</script>
</body>
</html>