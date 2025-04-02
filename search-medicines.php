 <?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Add cache control headers to prevent back button access
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include "connect.php";

// Create cart table if it doesn't exist
$create_cart_table = "CREATE TABLE IF NOT EXISTS cart (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50),
    medicine_id INT,
    quantity INT NOT NULL,
    added_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (medicine_id) REFERENCES Medicines(medicine_id)
)";

if (!$conn->query($create_cart_table)) {
    die("Error creating cart table: " . $conn->error);
}

// Handle adding to cart
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "Please login to add items to cart";
        header("Location: login.php");
        exit();
    }

    $medicine_id = $_POST['medicine_id'];
    $quantity = $_POST['quantity'];
    $user_id = $_SESSION['user_id'];

    // Check if medicine exists and has enough stock
    $check_stock = $conn->prepare("SELECT stock_quantity FROM Medicines WHERE medicine_id = ?");
    if ($check_stock === false) {
        die("Error preparing statement: " . $conn->error);
    }
    
    $check_stock->bind_param("i", $medicine_id);
    $check_stock->execute();
    $result = $check_stock->get_result();
    $medicine = $result->fetch_assoc();
    $check_stock->close();

    if ($medicine && $medicine['stock_quantity'] >= $quantity) {
        // Check if item already in cart
        $check_cart = $conn->prepare("SELECT cart_id, quantity FROM cart WHERE user_id = ? AND medicine_id = ?");
        if ($check_cart === false) {
            die("Error preparing statement: " . $conn->error);
        }
        
        $check_cart->bind_param("si", $user_id, $medicine_id);
        $check_cart->execute();
        $cart_result = $check_cart->get_result();
        $check_cart->close();
        
        if ($cart_result->num_rows > 0) {
            // Update existing cart item
            $cart_item = $cart_result->fetch_assoc();
            $new_quantity = $cart_item['quantity'] + $quantity;
            $update_cart = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ?");
            if ($update_cart === false) {
                die("Error preparing statement: " . $conn->error);
            }
            
            $update_cart->bind_param("ii", $new_quantity, $cart_item['cart_id']);
            $update_cart->execute();
            $update_cart->close();
        } else {
            // Add new cart item
            $add_to_cart = $conn->prepare("INSERT INTO cart (user_id, medicine_id, quantity) VALUES (?, ?, ?)");
            if ($add_to_cart === false) {
                die("Error preparing statement: " . $conn->error);
            }
            
            $add_to_cart->bind_param("sii", $user_id, $medicine_id, $quantity);
            $add_to_cart->execute();
            $add_to_cart->close();
        }
        $_SESSION['message'] = "Added to cart successfully!";
    } else {
        $_SESSION['error'] = "Not enough stock available!";
    }
    
    // Redirect back to the same page with the search term
    $search_term = isset($_GET['search']) ? '?search=' . urlencode($_GET['search']) : '';
    header("Location: search-medicines.php" . $search_term);
    exit();
}

// Initialize search variables
$search_term = isset($_GET['search']) ? $_GET['search'] : '';

// Prepare base query
$sql = "SELECT * FROM Medicines WHERE 1=1";
$params = [];
$types = "";

// Add search conditions
if (!empty($search_term)) {
    $search_term = "%$search_term%";
    $sql .= " AND (name LIKE ? OR company LIKE ? OR batch_number LIKE ?)";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
    $types .= "sss";
}

$sql .= " ORDER BY name";

// Execute query
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    die("Error preparing statement: " . $conn->error);
}
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Medicines - Pharma Corp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h2>Search Medicines</h2>
                <p class="text-muted">Find the medicines you need</p>
            </div>
            <div class="col-auto d-flex gap-2">
                <a href="customer-dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    
                    <a href="customer-profile.php" class="btn btn-info">
                        <i class="bi bi-person"></i> My Profile
                    </a>
                    <a href="logout.php" class="btn btn-danger">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?= $_SESSION['message'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?= $_SESSION['error'] ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Search Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" name="search" class="form-control" placeholder="Search by name, company, or batch number" value="<?= htmlspecialchars($search_term) ?>">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i> Search
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Results -->
        <div class="card">
            <div class="card-body">
                <?php if ($result && $result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Medicine Name</th>
                                    <th>Company</th>
                                    <th>Batch Number</th>
                                    <th>Expiry Date</th>
                                    <th>Price/Unit</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($medicine = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($medicine['name']) ?></td>
                                        <td><?= htmlspecialchars($medicine['company']) ?></td>
                                        <td><?= htmlspecialchars($medicine['batch_number']) ?></td>
                                        <td>
                                            <?php 
                                            if (isset($medicine['expiry_date'])) {
                                                $expiry_date = new DateTime($medicine['expiry_date']);
                                                $today = new DateTime();
                                                $expired = $expiry_date < $today;
                                                $class = $expired ? 'text-danger' : 'text-dark';
                                                echo "<span class='$class'>" . $expiry_date->format('Y-m-d') . "</span>";
                                            } else {
                                                echo "<span class='text-muted'>Not available</span>";
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (isset($medicine['price_per_unit'])): ?>
                                                ₹<?= number_format($medicine['price_per_unit'], 2) ?>
                                            <?php else: ?>
                                                <span class="text-muted">Not set</span>
                                            <?php endif; ?>
                                        </td>
                                        
                                        <td>
                                            <?php if (isset($medicine['stock_quantity']) && $medicine['stock_quantity'] > 0 && !$expired): ?>
                                                <form method="POST" class="d-flex gap-2">
                                                    <input type="hidden" name="medicine_id" value="<?= $medicine['medicine_id'] ?>">
                                                    <input type="number" name="quantity" value="1" min="1" max="<?= $medicine['stock_quantity'] ?>" class="form-control form-control-sm" style="width: 70px;">
                                                    
                                                </form>
                                            <?php elseif ($expired): ?>
                                                <span class="badge bg-danger">Expired</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Out of Stock</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-search display-4 text-muted"></i>
                        <p class="mt-3">No medicines found matching your search criteria.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
