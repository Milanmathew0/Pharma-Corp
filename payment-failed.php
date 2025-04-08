<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Failed - Pharma</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-danger text-white">
                        <h3 class="text-center">Payment Failed</h3>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-4">
                            <i class="fas fa-times-circle text-danger" style="font-size: 5rem;"></i>
                        </div>
                        <h4>Your payment could not be processed</h4>
                        <?php if(isset($_GET['error'])): ?>
                            <p class="text-danger"><?php echo htmlspecialchars($_GET['error']); ?></p>
                        <?php endif; ?>
                        <p>Please try again or contact support if the issue persists.</p>
                        <a href="payment.php" class="btn btn-primary mt-3">Try Again</a>
                        <a href="index.php" class="btn btn-secondary mt-3">Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://use.fontawesome.com/releases/v5.15.1/js/all.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html> 