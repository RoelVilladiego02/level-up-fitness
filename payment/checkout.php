<?php
/**
 * Payment Checkout Initiation
 * Level Up Fitness - Gym Management System
 * 
 * This page handles the checkout flow:
 * 1. Receives payment details via GET
 * 2. Submits to API endpoint via POST
 * 3. Redirects to Maya payment gateway
 */

require_once dirname(dirname(__FILE__)) . '/includes/header.php';

// Require authentication
requireLogin();

// Get parameters
$paymentId = sanitize($_GET['payment_id'] ?? '');
$memberId = sanitize($_GET['member_id'] ?? '');
$gateway = sanitize($_GET['gateway'] ?? 'maya');
$amount = floatval($_GET['amount'] ?? 0);
$description = sanitize($_GET['description'] ?? 'Gym Membership Payment');

// Validate parameters
if (empty($paymentId) || empty($memberId) || empty($gateway) || $amount <= 0) {
    die('Invalid checkout parameters');
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Processing Payment - Level Up Fitness</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .processing-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            text-align: center;
            max-width: 400px;
            width: 90%;
        }
        
        .spinner-large {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .processing-container h3 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .processing-container p {
            color: #666;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .error-message {
            display: none;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-top: 20px;
            text-align: left;
            font-size: 13px;
        }
        
        .error-message i {
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="processing-container">
        <div class="spinner-large"></div>
        <h3><i class="fas fa-lock"></i> Secure Payment Processing</h3>
        <p>Redirecting to <?php echo ucfirst($gateway); ?>...</p>
        <p style="font-size: 12px; color: #999; margin-top: 15px;">
            Amount: <strong>₱<?php echo number_format($amount, 2); ?></strong>
        </p>
        <p style="font-size: 12px; color: #999;">
            Please wait while we prepare your payment.
        </p>
        
        <div class="error-message" id="errorMessage">
            <i class="fas fa-exclamation-circle"></i>
            <span id="errorText"></span>
        </div>
        
        <div style="margin-top: 20px; font-size: 12px; color: #999;">
            <p>If you are not redirected within 5 seconds, <a href="javascript:submitCheckout()">click here</a></p>
        </div>
    </div>

    <script>
        const APP_URL = "<?php echo APP_URL; ?>";
        
        // Auto-submit checkout request
        function submitCheckout() {
            const checkoutData = {
                payment_id: "<?php echo htmlspecialchars($paymentId); ?>",
                member_id: "<?php echo htmlspecialchars($memberId); ?>",
                gateway: "<?php echo htmlspecialchars($gateway); ?>",
                amount: <?php echo $amount; ?>,
                description: "<?php echo htmlspecialchars($description); ?>"
            };
            
            console.log('Submitting checkout:', checkoutData);
            
            fetch(APP_URL + 'api/payments/checkout.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(checkoutData)
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Checkout response:', data);
                
                if (data.success && data.data && data.data.checkout_url) {
                    // Redirect to Maya checkout
                    console.log('Redirecting to:', data.data.checkout_url);
                    window.location.href = data.data.checkout_url;
                } else {
                    // Show error
                    const errorMsg = data.error || 'Failed to process payment. Please try again.';
                    showError(errorMsg);
                }
            })
            .catch(error => {
                console.error('Checkout error:', error);
                showError('Network error: ' + error.message);
            });
        }
        
        function showError(message) {
            document.getElementById('errorText').textContent = message;
            document.getElementById('errorMessage').style.display = 'block';
            document.querySelector('.spinner-large').style.display = 'none';
            document.querySelector('h3').style.display = 'none';
            document.querySelector('p').style.display = 'none';
            document.querySelector('[style*="margin-top: 15px"]').style.display = 'none';
            document.querySelector('[style*="margin-top: 20px"]').innerHTML = 
                '<a href="' + APP_URL + 'modules/payments/" class="btn btn-primary btn-sm">' +
                '<i class="fas fa-arrow-left"></i> Back to Payments</a>';
        }
        
        // Start checkout immediately
        document.addEventListener('DOMContentLoaded', function() {
            submitCheckout();
        });
    </script>
</body>
</html>
