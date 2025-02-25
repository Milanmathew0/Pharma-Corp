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

// Handle quantity updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_quantity'])) {
        $cart_id = $_POST['cart_id'];
        $quantity = $_POST['quantity'];
        
        $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE cart_id = ? AND user_id = ?");
        $update->bind_param("iis", $quantity, $cart_id, $_SESSION['user_id']);
        if ($update->execute()) {
            $_SESSION['message'] = "Cart updated successfully!";
        } else {
            $_SESSION['error'] = "Error updating cart!";
        }
    } elseif (isset($_POST['remove_item'])) {
        $cart_id = $_POST['cart_id'];
        
        $delete = $conn->prepare("DELETE FROM cart WHERE cart_id = ? AND user_id = ?");
        $delete->bind_param("is", $cart_id, $_SESSION['user_id']);
        if ($delete->execute()) {
            $_SESSION['message'] = "Item removed from cart!";
        } else {
            $_SESSION['error'] = "Error removing item!";
        }
    }
    
    header("Location: view_cart.php");
    exit();
}

// Fetch cart items with medicine details
$sql = "SELECT c.*, m.name, m.price_per_unit, m.stock_quantity, m.expiry_date 
        FROM cart c 
        JOIN Medicines m ON c.medicine_id = m.medicine_id 
        WHERE c.user_id = ?
        ORDER BY c.added_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$total = 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - Pharma Corp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col">
                <h2>Your Cart</h2>
            </div>
            <div class="col-auto d-flex gap-2">
                <a href="search-medicines.php" class="btn btn-secondary">Continue Shopping</a>
                <a href="customer-profile.php" class="btn btn-info">
                    <i class="bi bi-person"></i> My Profile
                </a>
                <a href="logout.php" class="btn btn-danger">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
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

        <div class="card">
            <div class="card-body">
                <?php if ($result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Medicine</th>
                                    <th>Price/Unit</th>
                                    <th>Quantity</th>
                                    <th>Subtotal</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($item = $result->fetch_assoc()): 
                                    $subtotal = $item['quantity'] * $item['price_per_unit'];
                                    $total += $subtotal;
                                    
                                    // Check if medicine is still available
                                    $available = $item['stock_quantity'] > 0;
                                    $expired = new DateTime($item['expiry_date']) < new DateTime();
                                ?>
                                    <tr>
                                        <td>
                                            <?= htmlspecialchars($item['name']) ?>
                                            <?php if (!$available): ?>
                                                <span class="badge bg-danger">Out of Stock</span>
                                            <?php endif; ?>
                                            <?php if ($expired): ?>
                                                <span class="badge bg-danger">Expired</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>₹<?= number_format($item['price_per_unit'], 2) ?></td>
                                        <td>
                                            <form method="POST" class="d-flex gap-2 align-items-center">
                                                <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                                <input type="number" name="quantity" value="<?= $item['quantity'] ?>" 
                                                       min="1" max="<?= $item['stock_quantity'] ?>" 
                                                       class="form-control form-control-sm" style="width: 70px;"
                                                       <?= (!$available || $expired) ? 'disabled' : '' ?>>
                                                <button type="submit" name="update_quantity" class="btn btn-outline-primary btn-sm"
                                                        <?= (!$available || $expired) ? 'disabled' : '' ?>>
                                                    <i class="bi bi-arrow-clockwise"></i>
                                                </button>
                                            </form>
                                        </td>
                                        <td>₹<?= number_format($subtotal, 2) ?></td>
                                        <td>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="cart_id" value="<?= $item['cart_id'] ?>">
                                                <button type="submit" name="remove_item" class="btn btn-danger btn-sm">
                                                    <i class="bi bi-trash"></i> Remove
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                                <tr class="table-light">
                                    <td colspan="3" class="text-end fw-bold">Total:</td>
                                    <td class="fw-bold">₹<?= number_format($total, 2) ?></td>
                                    <td></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-end mt-3">
                        <a href="checkout.php" class="btn btn-primary">
                            <i class="bi bi-cart-check"></i> Proceed to Checkout
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="bi bi-cart-x display-4 text-muted"></i>
                        <p class="mt-3">Your cart is empty.</p>
                        <a href="search-medicines.php" class="btn btn-primary mt-2">
                            Start Shopping
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
