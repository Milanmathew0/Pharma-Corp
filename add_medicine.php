<?php
session_start();
include "connect.php";

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $manufacturer = mysqli_real_escape_string($conn, $_POST['manufacturer']);
    $price = floatval($_POST['price']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $expiry_date = mysqli_real_escape_string($conn, $_POST['expiry_date']);
    
    $query = "INSERT INTO medicines (name, manufacturer, price, stock_quantity, expiry_date) 
              VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssdis", $name, $manufacturer, $price, $stock_quantity, $expiry_date);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Medicine added successfully!";
    } else {
        $_SESSION['error'] = "Error adding medicine: " . $conn->error;
    }
    
    header("Location: add_medicine.php");
    exit();
}

$medicines_query = "SELECT * FROM medicines ORDER BY name ASC";
$medicines_result = mysqli_query($conn, $medicines_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Medicines - Pharma Corp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2c9db7;
        }

        body {
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

        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }

        .table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Medicines</h2>
            <a href="staff-dashboard.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $_SESSION['success'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Add New Medicine</h5>
            </div>
            <div class="card-body">
                <form action="add_medicine.php" method="POST">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="name" class="form-label">Medicine Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="manufacturer" class="form-label">Manufacturer</label>
                            <input type="text" class="form-control" id="manufacturer" name="manufacturer" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="price" class="form-label">Price</label>
                            <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="stock_quantity" class="form-label">Stock Quantity</label>
                            <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="expiry_date" class="form-label">Expiry Date</label>
                            <input type="date" class="form-control" id="expiry_date" name="expiry_date" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Add Medicine
                    </button>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">Available Medicines</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Manufacturer</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Expiry Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($medicine = mysqli_fetch_assoc($medicines_result)): ?>
                                <tr>
                                    <td><?= htmlspecialchars($medicine['name']) ?></td>
                                    <td><?= htmlspecialchars($medicine['manufacturer']) ?></td>
                                    <td>₹<?= number_format($medicine['price'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $medicine['stock_quantity'] <= 10 ? 'danger' : 'success' ?>">
                                            <?= $medicine['stock_quantity'] ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($medicine['expiry_date']) ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" onclick="editMedicine(<?= $medicine['id'] ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteMedicine(<?= $medicine['id'] ?>)">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editMedicine(id) {
            alert('Edit medicine with ID: ' + id);
        }

        function deleteMedicine(id) {
            if (confirm('Are you sure you want to delete this medicine?')) {
                alert('Delete medicine with ID: ' + id);
            }
        }
    </script>
</body>
</html> 