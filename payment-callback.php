<?php
session_start();
require_once "connect.php";
require_once "vendor/autoload.php"; // Require Razorpay SDK

use Razorpay\Api\Api;
use Razorpay\Api\Errors\SignatureVerificationError;

// Razorpay credentials
$keyId = 'rzp_test_EcVADFYsegaGtO';
$keySecret = 'wnMdxWv3At90bmeWGsJ5tVQM';
$api = new Api($keyId, $keySecret);

// Verify payment
if ($_SERVER["REQUEST_METHOD"] == "POST") {
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
        
        // Get the sale_id from the order_id
        $sale_id = $_POST['sale_id'] ?? $_SESSION['razorpay_sale_id'] ?? null;
        
        if ($sale_id) {
            // No need to update orders table as we're using sales table
            $_SESSION['success'] = "Payment successful! Sale completed.";
        } else {
            $_SESSION['success'] = "Payment successful! But couldn't find associated sale.";
        }
        
        // Redirect to success page
        header("Location: sales-management.php");
        exit;
    } else {
        // Payment failed
        $sql = "UPDATE payments SET status = 'failed', notes = ? WHERE razorpay_order_id = ?";
        $stmt = $conn->prepare($sql);
        $error_message = isset($error) ? $error : "Signature verification failed";
        $stmt->bind_param("ss", $error_message, $_POST['razorpay_order_id']);
        $stmt->execute();
        
        $_SESSION['error'] = "Payment verification failed. Please try again.";
        header("Location: sales-management.php");
        exit;
    }
}

// If reached here, redirect to sales management
header("Location: sales-management.php");
exit;
?> 