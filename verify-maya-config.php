<?php
/**
 * Maya Payment Configuration Verification
 * Tests that all credentials are loaded correctly
 */

require_once dirname(__FILE__) . '/config/config.php';
require_once dirname(__FILE__) . '/config/payment-gateway.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maya Configuration Verification</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; padding: 20px; }
        .container { max-width: 900px; margin-top: 20px; }
        .section { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .status-good { color: #28a745; }
        .status-warning { color: #ffc107; }
        .status-bad { color: #dc3545; }
        .config-item { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #eee; }
        .config-key { font-weight: bold; color: #555; }
        .config-value { font-family: monospace; word-break: break-all; }
        .masked { color: #999; font-style: italic; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">
            <i class="fas fa-cogs"></i> Maya Payment Configuration Check
        </h1>

        <div class="section">
            <h3>🔐 Environment Status</h3>
            <div class="config-item">
                <span class="config-key">.env File</span>
                <span class="<?php echo file_exists(dirname(__FILE__) . '/.env') ? 'status-good' : 'status-bad'; ?>">
                    <?php echo file_exists(dirname(__FILE__) . '/.env') ? '✓ Found' : '✗ Not Found'; ?>
                </span>
            </div>
            <div class="config-item">
                <span class="config-key">.env Readable</span>
                <span class="<?php echo is_readable(dirname(__FILE__) . '/.env') ? 'status-good' : 'status-bad'; ?>">
                    <?php echo is_readable(dirname(__FILE__) . '/.env') ? '✓ Yes' : '✗ No'; ?>
                </span>
            </div>
        </div>

        <div class="section">
            <h3>🎯 Sandbox Configuration</h3>
            <?php
            $config = require 'config/payment-gateway.php';
            $sandbox = $config['maya']['sandbox'] ?? [];
            ?>
            <div class="config-item">
                <span class="config-key">API Key</span>
                <span class="config-value <?php echo !empty($sandbox['api_key']) ? 'status-good' : 'status-bad'; ?>">
                    <?php 
                    if (!empty($sandbox['api_key'])) {
                        $key = $sandbox['api_key'];
                        echo substr($key, 0, 10) . '...' . substr($key, -10);
                        echo ' <span class="badge bg-success">Loaded</span>';
                    } else {
                        echo '<span class="status-bad">✗ Not configured</span>';
                    }
                    ?>
                </span>
            </div>
            <div class="config-item">
                <span class="config-key">API Secret</span>
                <span class="config-value <?php echo !empty($sandbox['api_secret']) ? 'status-good' : 'status-bad'; ?>">
                    <?php 
                    if (!empty($sandbox['api_secret'])) {
                        $secret = $sandbox['api_secret'];
                        echo substr($secret, 0, 10) . '...' . substr($secret, -10);
                        echo ' <span class="badge bg-success">Loaded</span>';
                    } else {
                        echo '<span class="status-bad">✗ Not configured</span>';
                    }
                    ?>
                </span>
            </div>
            <div class="config-item">
                <span class="config-key">Merchant ID</span>
                <span class="config-value <?php echo !empty($sandbox['merchant_id']) ? 'status-good' : 'status-bad'; ?>">
                    <?php 
                    echo !empty($sandbox['merchant_id']) ? htmlspecialchars($sandbox['merchant_id']) : '<span class="status-bad">✗ Not configured</span>';
                    ?>
                </span>
            </div>
            <div class="config-item">
                <span class="config-key">Mock Responses</span>
                <span class="config-value <?php echo !$sandbox['mock_responses'] ? 'status-good' : 'status-warning'; ?>">
                    <?php 
                    if ($sandbox['mock_responses']) {
                        echo '<span class="badge bg-warning">ENABLED (Testing Only)</span>';
                    } else {
                        echo '<span class="badge bg-success">DISABLED (Using Real API)</span>';
                    }
                    ?>
                </span>
            </div>
            <div class="config-item">
                <span class="config-key">API URL</span>
                <span class="config-value">
                    <?php echo htmlspecialchars($sandbox['api_url']); ?>
                </span>
            </div>
        </div>

        <div class="section">
            <h3>✅ Verification Results</h3>
            <?php
            $checks = [
                '.env file exists' => file_exists(dirname(__FILE__) . '/.env'),
                '.env is readable' => is_readable(dirname(__FILE__) . '/.env'),
                'Sandbox API Key loaded' => !empty($sandbox['api_key']) && $sandbox['api_key'] !== 'pk_test_sandbox_key_placeholder',
                'Sandbox API Secret loaded' => !empty($sandbox['api_secret']) && $sandbox['api_secret'] !== 'sk_test_sandbox_secret_placeholder',
                'Merchant ID set' => !empty($sandbox['merchant_id']),
                'Mock responses disabled' => !$sandbox['mock_responses'],
                'Enabled gateways include Maya' => in_array('maya', $config['enabled_gateways'] ?? []),
            ];
            
            $all_good = true;
            foreach ($checks as $check => $result) {
                if (!$result) $all_good = false;
                ?>
                <div class="config-item">
                    <span class="config-key"><?php echo htmlspecialchars($check); ?></span>
                    <span class="<?php echo $result ? 'status-good' : 'status-bad'; ?>">
                        <?php echo $result ? '✓ PASS' : '✗ FAIL'; ?>
                    </span>
                </div>
                <?php
            }
            ?>
        </div>

        <div class="section <?php echo $all_good ? 'bg-light-success' : 'bg-light-warning'; ?>">
            <h3>
                <?php echo $all_good ? '✅ ALL CHECKS PASSED' : '⚠️ SOME CHECKS FAILED'; ?>
            </h3>
            <?php if ($all_good): ?>
                <p class="text-success mb-0">
                    <strong>Great!</strong> Your Maya payment gateway is correctly configured with sandbox credentials.
                    You can now test payments!
                </p>
            <?php else: ?>
                <p class="text-warning mb-0">
                    <strong>Issues found:</strong> Please check the failed items above and ensure your .env file 
                    is properly configured with Maya sandbox credentials.
                </p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h3>📝 Next Steps</h3>
            <ol>
                <li>Go to <strong>Payments → Add Payment</strong></li>
                <li>Select a member and amount</li>
                <li>Choose <strong>"Maya (Online)"</strong> as payment method</li>
                <li>Click <strong>Record Payment</strong></li>
                <li>You'll be redirected to Maya's real checkout page</li>
                <li>Use Maya sandbox test cards to complete payment</li>
            </ol>
            
            <h5 class="mt-4">Test Card Numbers</h5>
            <table class="table table-sm">
                <tr>
                    <th>Card Number</th>
                    <th>Type</th>
                    <th>Result</th>
                </tr>
                <tr>
                    <td><code>4242 4242 4242 4242</code></td>
                    <td>Visa</td>
                    <td><span class="badge bg-success">Success</span></td>
                </tr>
                <tr>
                    <td><code>5555 5555 5555 4444</code></td>
                    <td>Mastercard</td>
                    <td><span class="badge bg-success">Success</span></td>
                </tr>
                <tr>
                    <td><code>4000 0000 0000 0002</code></td>
                    <td>Visa</td>
                    <td><span class="badge bg-danger">Decline</span></td>
                </tr>
            </table>
            
            <p class="text-muted small mt-3">
                <strong>Note:</strong> All test cards use expiry 12/25 and any 3-digit CVC.
            </p>
        </div>
    </div>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</body>
</html>
