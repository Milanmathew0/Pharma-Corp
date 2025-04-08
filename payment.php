<?php
// Include config file and start session
session_start();
require_once "config.php"; // Database connection
require_once "vendor/autoload.php"; // Require Razorpay SDK

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

// Razorpay credentials
$keyId = 'rzp_test_EcVADFYsegaGtO';
$keySecret = 'wnMdxWv3At90bmeWGsJ5tVQM';

$api = new Api($keyId, $keySecret);

// Logic for payment processing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_order'])) {
    // Get total amount from cart or order summary
    $amount = $_POST['amount'] * 100; // Convert to paise (Razorpay expects amount in paise)
    $currency = 'INR';
    $receipt = 'order_' . time();
    
    // Create order in Razorpay
    $orderData = [
        'receipt'         => $receipt,
        'amount'          => $amount,
        'currency'        => $currency,
        'payment_capture' => 1 // Auto capture
    ];
    
    $razorpayOrder = $api->order->create($orderData);
    $razorpayOrderId = $razorpayOrder['id'];
    
    // Store initial payment record
    $user_id = $_SESSION['user_id'];
    $order_id = isset($_POST['order_id']) ? $_POST['order_id'] : null;
    $amount_in_rupees = $amount / 100; // Convert back to rupees for DB
    
    $sql = "INSERT INTO payments (user_id, order_id, amount, currency, razorpay_order_id, status) 
            VALUES (?, ?, ?, ?, ?, 'pending')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sidss", $user_id, $order_id, $amount_in_rupees, $currency, $razorpayOrderId);
    $stmt->execute();
    $payment_id = $conn->insert_id;
    
    // Pass data to frontend
    $_SESSION['razorpay_order_id'] = $razorpayOrderId;
    $_SESSION['payment_id'] = $payment_id;
}

// Verify payment after Razorpay callback
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['razorpay_payment_id'])) {
    $success = true;
    
    try {
        $attributes = [
            'razorpay_order_id' => $_POST['razorpay_order_id'],
            'razorpay_payment_id' => $_POST['razorpay_payment_id'],
            'razorpay_signature' => $_POST['razorpay_signature']
        ];
        
        $api->utility->verifyPaymentSignature($attributes);
    } catch(SignatureVerificationError $e) {
        $success = false;
        $error = 'Razorpay Error: ' . $e->getMessage();
    }
    
    if ($success) {
        // Update payment record
        $sql = "UPDATE payments 
                SET status = 'completed', 
                    razorpay_payment_id = ?, 
                    razorpay_signature = ? 
                WHERE razorpay_order_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", 
            $_POST['razorpay_payment_id'], 
            $_POST['razorpay_signature'], 
            $_POST['razorpay_order_id']
        );
        $stmt->execute();
        
        // Update the order status if needed
        if (isset($_POST['order_id'])) {
            $sql = "UPDATE orders SET status = 'Completed' WHERE order_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $_POST['order_id']);
            $stmt->execute();
        }
        
        // Redirect to success page
        header("Location: payment-success.php");
        exit;
    } else {
        // Update payment record as failed
        $sql = "UPDATE payments SET status = 'failed', notes = ? WHERE razorpay_order_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $error, $_POST['razorpay_order_id']);
        $stmt->execute();
        
        // Redirect to failure page
        header("Location: payment-failed.php?error=" . urlencode($error));
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Pharma</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="text-center">Payment</h3>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get cart total or order amount
                        // This should be replaced with your actual logic to get the total
                        $total = 0;
                        $order_id = null;
                        
                        if (isset($_GET['order_id'])) {
                            $order_id = $_GET['order_id'];
                            $sql = "SELECT total_amount FROM orders WHERE order_id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("i", $order_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            if ($row = $result->fetch_assoc()) {
                                $total = $row['total_amount'];
                            }
                        } else {
                            // Get cart total
                            $user_id = $_SESSION['user_id'];
                            $sql = "SELECT c.quantity, m.price_per_unit 
                                    FROM cart c 
                                    JOIN medicines m ON c.medicine_id = m.medicine_id 
                                    WHERE c.user_id = ?";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("s", $user_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            while ($row = $result->fetch_assoc()) {
                                $total += $row['quantity'] * $row['price_per_unit'];
                            }
                        }
                        ?>
                        
                        <div class="text-center mb-4">
                            <h4>Order Total: ₹<?php echo number_format($total, 2); ?></h4>
                        </div>
                        
                        <form id="payment-form" method="post" action="">
                            <input type="hidden" name="amount" value="<?php echo $total; ?>">
                            <?php if ($order_id): ?>
                                <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                            <?php endif; ?>
                            <div class="text-center">
                                <button type="submit" name="create_order" class="btn btn-primary">Proceed to Pay</button>
                            </div>
                        </form>
                        
                        <?php if (isset($_SESSION['razorpay_order_id'])): ?>
                            <div class="text-center mt-4">
                                <button id="rzp-button" class="btn btn-success">Pay with Razorpay</button>
                            </div>
                            
                            <script>
                                var options = {
                                    "key": "<?php echo $keyId; ?>",
                                    "amount": "<?php echo $amount; ?>",
                                    "currency": "INR",
                                    "name": "Pharma",
                                    "description": "Order Payment",
                                    "image": "your-logo-url.png",
                                    "order_id": "<?php echo $_SESSION['razorpay_order_id']; ?>",
                                    "handler": function (response) {
                                        document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                                        document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
                                        document.getElementById('razorpay_signature').value = response.razorpay_signature;
                                        document.getElementById('razorpay-form').submit();
                                    },
                                    "prefill": {
                                        "name": "<?php echo isset($_SESSION['name']) ? $_SESSION['name'] : ''; ?>",
                                        "email": "<?php echo isset($_SESSION['email']) ? $_SESSION['email'] : ''; ?>",
                                        "contact": "<?php echo isset($_SESSION['phone']) ? $_SESSION['phone'] : ''; ?>"
                                    },
                                    "theme": {
                                        "color": "#3399cc"
                                    }
                                };
                                var rzp = new Razorpay(options);
                                document.getElementById('rzp-button').onclick = function(e) {
                                    rzp.open();
                                    e.preventDefault();
                                }
                            </script>
                            
                            <form name='razorpay-form' id='razorpay-form' action="" method="POST">
                                <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
                                <input type="hidden" name="razorpay_order_id" id="razorpay_order_id" value="<?php echo $_SESSION['razorpay_order_id']; ?>">
                                <input type="hidden" name="razorpay_signature" id="razorpay_signature">
                                <?php if ($order_id): ?>
                                    <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                                <?php endif; ?>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 