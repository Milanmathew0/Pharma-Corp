<?php
session_start();

// Check if user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Staff') {
    header("Location: login.php");
    exit();
}

include "connect.php";

// Check if Razorpay SDK is available
if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    $_SESSION['error'] = "Razorpay SDK not found. Please install it using Composer: composer require razorpay/razorpay";
    header("Location: sales-management.php");
    exit();
}

require_once "vendor/autoload.php"; // Require Razorpay SDK

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

// Razorpay credentials
$keyId = 'rzp_test_EcVADFYsegaGtO';
$keySecret = 'wnMdxWv3At90bmeWGsJ5tVQM';

$api = new Api($keyId, $keySecret);

// Check if sale_id is provided
if (!isset($_GET['sale_id'])) {
    $_SESSION['error'] = "Invalid request. No sale specified.";
    header("Location: sales-management.php");
    exit();
}

$sale_id = $_GET['sale_id'];

// Check if customer_name column exists
$check_column = $conn->query("SHOW COLUMNS FROM sales LIKE 'customer_name'");
$customer_name_exists = $check_column && $check_column->num_rows > 0;

// Get sale details with a query based on table structure
if ($customer_name_exists) {
    $sql = "SELECT s.sale_id, s.total_amount, s.customer_id, 
                  COALESCE(s.customer_name, u.name) as customer_name, 
                  u.email, u.phone 
           FROM sales s 
           LEFT JOIN users u ON s.customer_id = u.user_id 
           WHERE s.sale_id = ?";
} else {
    $sql = "SELECT s.sale_id, s.total_amount, s.customer_id, 
                  u.name as customer_name, 
                  u.email, u.phone 
           FROM sales s 
           LEFT JOIN users u ON s.customer_id = u.user_id 
           WHERE s.sale_id = ?";
}

$stmt = $conn->prepare($sql);

// Check if statement preparation was successful
if ($stmt === false) {
    $_SESSION['error'] = "Failed to prepare sale details query: " . $conn->error;
    header("Location: sales-management.php");
    exit();
}

$stmt->bind_param("i", $sale_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Sale not found.";
    header("Location: sales-management.php");
    exit();
}

$sale = $result->fetch_assoc();
$amount = $sale['total_amount'] * 100; // Convert to paise

// Create order in Razorpay
try {
    // Clear any existing order ID to create a fresh one
    unset($_SESSION['razorpay_order_id']);
    
    $orderData = [
        'receipt'         => 'sale_' . $sale_id,
        'amount'          => intval($amount), // Ensure it's an integer
        'currency'        => 'INR',
        'payment_capture' => 1 // Auto capture
    ];
    
    $razorpayOrder = $api->order->create($orderData);
    $_SESSION['razorpay_order_id'] = $razorpayOrder['id'];
} catch (Exception $e) {
    $_SESSION['error'] = "Failed to create Razorpay order: " . $e->getMessage();
    header("Location: sales-management.php");
    exit();
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
        // Check if payment_status column exists in sales table
        $check_column = $conn->query("SHOW COLUMNS FROM sales LIKE 'payment_status'");
        $payment_status_exists = $check_column && $check_column->num_rows > 0;
        
        $razorpay_columns_exist = false;
        $check_razorpay_columns = $conn->query("SHOW COLUMNS FROM sales LIKE 'razorpay_payment_id'");
        if ($check_razorpay_columns && $check_razorpay_columns->num_rows > 0) {
            $razorpay_columns_exist = true;
        }
        
        // Update sale payment status
        if ($payment_status_exists && $razorpay_columns_exist) {
            $sql = "UPDATE sales SET payment_status = 'completed', 
                    razorpay_payment_id = ?, razorpay_order_id = ? 
                    WHERE sale_id = ?";
            $stmt = $conn->prepare($sql);
            
            if ($stmt === false) {
                $_SESSION['error'] = "Failed to prepare payment update query: " . $conn->error;
                header("Location: sales-management.php");
                exit();
            }
            
            $stmt->bind_param("ssi", 
                $_POST['razorpay_payment_id'],
                $_POST['razorpay_order_id'],
                $sale_id
            );
        } else {
            // Without payment status columns, just update payment method to confirm it's paid
            $sql = "UPDATE sales SET payment_method = CONCAT(payment_method, ' (Paid)') WHERE sale_id = ?";
            $stmt = $conn->prepare($sql);
            
            if ($stmt === false) {
                $_SESSION['error'] = "Failed to prepare payment update query: " . $conn->error;
                header("Location: sales-management.php");
                exit();
            }
            
            $stmt->bind_param("i", $sale_id);
        }
        
        $stmt->execute();
        
        $_SESSION['success'] = "Payment successful! Sale has been completed.";
        header("Location: sales-management.php");
        exit;
    } else {
        // Handle payment failure
        if (isset($check_column) && $check_column->num_rows > 0) {
            // If payment_status column exists
            $sql = "UPDATE sales SET payment_status = 'failed' WHERE sale_id = ?";
            $stmt = $conn->prepare($sql);
            
            if ($stmt === false) {
                $_SESSION['error'] = "Failed to prepare payment update query: " . $conn->error;
                header("Location: sales-management.php");
                exit();
            }
            
            $stmt->bind_param("i", $sale_id);
            $stmt->execute();
        }
        
        $_SESSION['error'] = "Payment failed: " . ($error ?? "Unknown error");
        header("Location: sales-management.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Process Payment - Pharma Corp</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://checkout.razorpay.com/v1/checkout.js"></script>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="text-center">Complete Payment</h3>
                    </div>
                    <div class="card-body text-center">
                        <h4>Sale #<?php echo $sale_id; ?></h4>
                        <p class="mb-4"><strong>Amount:</strong> ₹<?php echo number_format($sale['total_amount'], 2); ?></p>
                        
                        <button id="rzp-button" class="btn btn-success btn-lg">Pay with Razorpay</button>
                        <a href="sales-management.php" class="btn btn-secondary mt-3">Cancel Payment</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var options = {
                "key": "<?php echo $keyId; ?>",
                "amount": <?php echo intval($amount); ?>, // Remove quotes, ensure it's a number
                "currency": "INR",
                "name": "Pharma Corp",
                "description": "Sale #<?php echo $sale_id; ?> Payment",
                "order_id": "<?php echo $_SESSION['razorpay_order_id']; ?>",
                "handler": function (response) {
                    document.getElementById('razorpay_payment_id').value = response.razorpay_payment_id;
                    document.getElementById('razorpay_order_id').value = response.razorpay_order_id;
                    document.getElementById('razorpay_signature').value = response.razorpay_signature;
                    document.getElementById('razorpay-form').submit();
                },
                "prefill": {
                    "name": "<?php echo htmlspecialchars($sale['customer_name'] ?? ''); ?>",
                    "email": "<?php echo htmlspecialchars($sale['email'] ?? ''); ?>",
                    "contact": "<?php echo htmlspecialchars($sale['phone'] ?? ''); ?>"
                },
                "theme": {
                    "color": "#2c9db7"
                }
            };
            
            var rzp = new Razorpay(options);
            document.getElementById('rzp-button').onclick = function(e) {
                e.preventDefault();
                rzp.open();
            }

            // Delay auto-opening to ensure page is fully loaded
            setTimeout(function() {
                document.getElementById('rzp-button').click();
            }, 2000); // Increased timeout to 2 seconds
        });
    </script>
    
    <form name='razorpay-form' id='razorpay-form' action="" method="POST">
        <input type="hidden" name="razorpay_payment_id" id="razorpay_payment_id">
        <input type="hidden" name="razorpay_order_id" id="razorpay_order_id" value="<?php echo $_SESSION['razorpay_order_id']; ?>">
        <input type="hidden" name="razorpay_signature" id="razorpay_signature">
        <input type="hidden" name="sale_id" value="<?php echo $sale_id; ?>">
    </form>
</body>
</html> 