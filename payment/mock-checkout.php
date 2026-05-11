<?php
/**
 * Mock Maya Checkout Page
 * Simulates Maya payment gateway for testing
 * Only works in sandbox mode
 */

require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once dirname(dirname(__FILE__)) . '/config/database.php';
require_once dirname(dirname(__FILE__)) . '/includes/functions.php';

// Get transaction ID from URL
$transactionId = $_GET['transactionId'] ?? '';

if (empty($transactionId)) {
    die('Invalid transaction');
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maya Sandbox Checkout - Test Payment</title>
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
        
        .checkout-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            padding: 40px;
            max-width: 500px;
            width: 90%;
        }
        
        .checkout-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 20px;
        }
        
        .checkout-header h2 {
            color: #667eea;
            margin-bottom: 10px;
        }
        
        .sandbox-badge {
            display: inline-block;
            background: #fff3cd;
            color: #856404;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            margin-bottom: 15px;
            font-weight: bold;
        }
        
        .payment-method {
            margin: 20px 0;
        }
        
        .payment-method-option {
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .payment-method-option:hover {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .payment-method-option input[type="radio"] {
            margin-right: 10px;
        }
        
        .payment-method-option.selected {
            border-color: #667eea;
            background: #f8f9ff;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .button-group button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 13px;
        }
        
        .info-box strong {
            color: #1976D2;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <div class="checkout-header">
            <div class="sandbox-badge">
                <i class="fas fa-flask"></i> SANDBOX MODE - TEST ONLY
            </div>
            <h2><i class="fas fa-wallet"></i> Maya Checkout</h2>
            <p class="text-muted">This is a simulated payment for testing</p>
        </div>
        
        <div class="info-box">
            <strong>Transaction ID:</strong> <?php echo htmlspecialchars($transactionId); ?>
        </div>
        
        <form id="paymentForm" method="POST" action="">
            <div class="payment-method">
                <h5>Select Payment Method</h5>
                
                <label class="payment-method-option selected">
                    <input type="radio" name="paymentMethod" value="success" checked>
                    <strong><i class="fas fa-check-circle text-success"></i> Successful Payment</strong>
                    <small class="d-block text-muted">Simulates successful transaction</small>
                </label>
                
                <label class="payment-method-option">
                    <input type="radio" name="paymentMethod" value="failed">
                    <strong><i class="fas fa-times-circle text-danger"></i> Failed Payment</strong>
                    <small class="d-block text-muted">Simulates payment failure</small>
                </label>
                
                <label class="payment-method-option">
                    <input type="radio" name="paymentMethod" value="cancel">
                    <strong><i class="fas fa-ban text-warning"></i> Cancel Payment</strong>
                    <small class="d-block text-muted">User cancels the transaction</small>
                </label>
            </div>
            
            <div class="info-box">
                <strong><i class="fas fa-info-circle"></i> Test Tip:</strong> 
                Select an option above and click "Proceed" to test different payment scenarios.
            </div>
            
            <div class="button-group">
                <button type="submit" name="action" value="proceed" class="btn-success">
                    <i class="fas fa-arrow-right"></i> Proceed
                </button>
                <button type="submit" name="action" value="cancel" class="btn-danger">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>

    <script>
        // Update UI when payment method changes
        document.querySelectorAll('.payment-method-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.payment-method-option').forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');
                this.querySelector('input[type="radio"]').checked = true;
            });
        });
        
        // Handle form submission
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const action = document.querySelector('button[type="submit"]:focus').value;
            const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
            const transactionId = "<?php echo htmlspecialchars($transactionId); ?>";
            
            // Simulate processing
            if (action === 'proceed') {
                // Redirect to webhook simulation
                const webhookUrl = new URL(window.location.origin + '/level-up-fitness/api/payments/mock-webhook.php');
                webhookUrl.searchParams.append('transaction_id', transactionId);
                webhookUrl.searchParams.append('status', paymentMethod === 'success' ? 'completed' : (paymentMethod === 'failed' ? 'failed' : 'cancelled'));
                window.location.href = webhookUrl.toString();
            } else {
                // Cancel and go back
                window.location.href = document.referrer || window.location.origin + '/level-up-fitness/modules/payments/';
            }
        });
    </script>
</body>
</html>
