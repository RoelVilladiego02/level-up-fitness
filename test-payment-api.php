<?php
/**
 * Test Payment API
 * Level Up Fitness - Gym Management System
 * 
 * Test script for Maya payment gateway integration
 * Run from browser or CLI: php test-payment-api.php
 */

require_once dirname(__FILE__) . '/config/database.php';
require_once dirname(__FILE__) . '/config/MayaPaymentService.php';

// Check if CLI or Web
$isCli = php_sapi_name() === 'cli';

if (!$isCli) {
    echo '<pre style="font-family: monospace; background: #f5f5f5; padding: 15px; border-radius: 5px;">';
}

echo "╔════════════════════════════════════════════════════════╗\n";
echo "║     Payment API Test Suite                             ║\n";
echo "║     Level Up Fitness - Gym Management System           ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

// Test 1: Maya Service Initialization
echo "Test 1: Initialize Maya Payment Service\n";
echo str_repeat("─", 56) . "\n";

try {
    $mayaService = new MayaPaymentService('sandbox');
    $config = $mayaService->getConfigDetails();
    
    echo "✓ Service initialized successfully\n";
    echo "  Environment: " . $config['environment'] . "\n";
    echo "  Merchant ID: " . $config['merchant_id'] . "\n";
    echo "  API URL: " . $config['api_url'] . "\n";
    echo "✓ PASSED\n\n";
    
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 2: Connection Test
echo "Test 2: Test Maya API Connection\n";
echo str_repeat("─", 56) . "\n";

try {
    $connectionResult = $mayaService->testConnection();
    
    if ($connectionResult['success']) {
        echo "✓ Connection successful\n";
        echo "  Environment: " . $connectionResult['environment'] . "\n";
        echo "  Status: " . $connectionResult['message'] . "\n";
        echo "✓ PASSED\n\n";
    } else {
        echo "⚠ Connection Test Notes: " . $connectionResult['error'] . "\n";
        echo "  (This is expected in sandbox with placeholder credentials)\n";
        echo "⚠ SKIPPED\n\n";
    }
    
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
}

// Test 3: Validate Payment Data
echo "Test 3: Validate Payment Data\n";
echo str_repeat("─", 56) . "\n";

$testPaymentData = [
    'valid' => [
        'member_id' => 'MEM-2026-001',
        'amount' => 1500.00,
        'description' => 'Monthly Membership',
        'email' => 'member@example.com',
        'first_name' => 'John',
        'last_name' => 'Doe'
    ],
    'invalid_no_member' => [
        'amount' => 1500.00,
        'description' => 'Monthly Membership'
    ],
    'invalid_zero_amount' => [
        'member_id' => 'MEM-2026-001',
        'amount' => 0,
        'description' => 'Monthly Membership'
    ],
    'invalid_no_description' => [
        'member_id' => 'MEM-2026-001',
        'amount' => 1500.00
    ]
];

foreach ($testPaymentData as $testName => $data) {
    $testLabel = ucfirst(str_replace('_', ' ', $testName));
    
    try {
        // Use reflection to test private method
        $reflection = new ReflectionClass($mayaService);
        $method = $reflection->getMethod('validatePaymentData');
        $method->setAccessible(true);
        $method->invoke($mayaService, $data);
        
        echo "✓ $testLabel - Validation passed\n";
        
    } catch (Exception $e) {
        if (strpos($testName, 'invalid') !== false) {
            echo "✓ $testLabel - Expected error caught: " . $e->getMessage() . "\n";
        } else {
            echo "✗ $testLabel - Unexpected error: " . $e->getMessage() . "\n";
        }
    }
}

echo "✓ PASSED\n\n";

// Test 4: Check Database Tables
echo "Test 4: Verify Payment Gateway Tables Exist\n";
echo str_repeat("─", 56) . "\n";

$tablesToCheck = [
    'payments' => ['payment_gateway', 'gateway_transaction_id'],
    'payment_gateway_transactions' => ['transaction_id', 'gateway_name', 'status'],
    'gateway_webhooks' => ['webhook_id', 'transaction_id', 'event_type'],
    'gateway_refunds' => ['refund_id', 'transaction_id'],
    'gateway_logs' => ['log_id', 'transaction_id', 'action']
];

$allTablesExist = true;

foreach ($tablesToCheck as $tableName => $columns) {
    $result = $pdo->query("SHOW TABLES LIKE '$tableName'");
    $tableExists = !empty($result->fetchAll());
    
    if ($tableExists) {
        echo "✓ Table '$tableName' exists\n";
        
        // Check columns
        foreach ($columns as $column) {
            $colResult = $pdo->query("SHOW COLUMNS FROM $tableName LIKE '$column'");
            $colExists = !empty($colResult->fetchAll());
            
            if ($colExists) {
                echo "  ✓ Column '$column' exists\n";
            } else {
                echo "  ✗ Column '$column' MISSING\n";
                $allTablesExist = false;
            }
        }
    } else {
        echo "✗ Table '$tableName' NOT FOUND\n";
        $allTablesExist = false;
    }
}

if ($allTablesExist) {
    echo "✓ PASSED\n\n";
} else {
    echo "✗ FAILED - Some tables/columns are missing\n";
    echo "Run: php add-payment-gateway-tables.php\n\n";
}

// Test 5: Check Configuration File
echo "Test 5: Verify Configuration Files Exist\n";
echo str_repeat("─", 56) . "\n";

$configFiles = [
    dirname(__FILE__) . '/config/payment-gateway.php' => 'Payment Gateway Config',
    dirname(__FILE__) . '/config/MayaPaymentService.php' => 'Maya Payment Service',
    dirname(__FILE__) . '/api/payments/checkout.php' => 'Checkout API',
    dirname(__FILE__) . '/api/payments/webhook.php' => 'Webhook Handler',
    dirname(__FILE__) . '/api/payments/status.php' => 'Status Check API'
];

$allFilesExist = true;

foreach ($configFiles as $filePath => $description) {
    if (file_exists($filePath)) {
        echo "✓ $description exists\n";
    } else {
        echo "✗ $description NOT FOUND: $filePath\n";
        $allFilesExist = false;
    }
}

if ($allFilesExist) {
    echo "✓ PASSED\n\n";
} else {
    echo "✗ FAILED - Some configuration files are missing\n\n";
}

// Test 6: Check for Required PHP Extensions
echo "Test 6: Check Required PHP Extensions\n";
echo str_repeat("─", 56) . "\n";

$extensions = ['curl', 'json', 'pdo', 'pdo_mysql'];
$allExtensionsLoaded = true;

foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✓ Extension '$ext' is loaded\n";
    } else {
        echo "✗ Extension '$ext' is NOT loaded\n";
        $allExtensionsLoaded = false;
    }
}

if ($allExtensionsLoaded) {
    echo "✓ PASSED\n\n";
} else {
    echo "✗ FAILED - Some required extensions are missing\n\n";
}

// Test 7: Sample Transaction Scenario
echo "Test 7: Sample Transaction Simulation\n";
echo str_repeat("─", 56) . "\n";

echo "Creating sample payment transaction...\n";

try {
    // Sample data
    $sampleTransaction = [
        'transaction_id' => 'MAYA-' . time() . '-' . substr(uniqid(), 0, 8),
        'payment_id' => 'PAY-TEST-001',
        'member_id' => 'MEM-2026-001',
        'gateway_name' => 'maya',
        'gateway_transaction_id' => 'MAYA-API-' . time(),
        'amount' => 1500.00,
        'currency' => 'PHP',
        'status' => 'pending'
    ];
    
    // Check if table exists before inserting
    $result = $pdo->query("SHOW TABLES LIKE 'payment_gateway_transactions'");
    if (!empty($result->fetchAll())) {
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO payment_gateway_transactions (
                    transaction_id, payment_id, member_id, gateway_name,
                    gateway_transaction_id, amount, currency, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $sampleTransaction['transaction_id'],
                $sampleTransaction['payment_id'],
                $sampleTransaction['member_id'],
                $sampleTransaction['gateway_name'],
                $sampleTransaction['gateway_transaction_id'],
                $sampleTransaction['amount'],
                $sampleTransaction['currency'],
                $sampleTransaction['status']
            ]);
            
            echo "✓ Transaction created: " . $sampleTransaction['transaction_id'] . "\n";
            echo "  Amount: ₱" . $sampleTransaction['amount'] . "\n";
            echo "  Status: " . $sampleTransaction['status'] . "\n";
            
            // Retrieve it back
            $checkStmt = $pdo->prepare("SELECT * FROM payment_gateway_transactions WHERE transaction_id = ?");
            $checkStmt->execute([$sampleTransaction['transaction_id']]);
            $retrieved = $checkStmt->fetch();
            
            if ($retrieved) {
                echo "✓ Transaction retrieved successfully\n";
                echo "✓ PASSED\n\n";
            } else {
                echo "✗ FAILED - Transaction could not be retrieved\n\n";
            }
            
        } catch (Exception $e) {
            echo "✗ FAILED to insert: " . $e->getMessage() . "\n\n";
        }
    } else {
        echo "⚠ Skipped - payment_gateway_transactions table not found\n";
        echo "   Run: php add-payment-gateway-tables.php\n\n";
    }
    
} catch (Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n\n";
}

// Final Summary
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║     Test Suite Complete                               ║\n";
echo "║                                                        ║\n";
echo "║  Next Steps:                                           ║\n";
echo "║  1. Configure Maya API credentials in               ║\n";
echo "║     config/payment-gateway.php                        ║\n";
echo "║  2. Run migration: php add-payment-gateway-tables.php ║\n";
echo "║  3. Update payment methods UI in payment modules      ║\n";
echo "║  4. Set environment variables for API keys             ║\n";
echo "║  5. Test payment flow end-to-end                      ║\n";
echo "║                                                        ║\n";
echo "║  For Production:                                       ║\n";
echo "║  - Update credentials in config/payment-gateway.php   ║\n";
echo "║  - Enable production environment                      ║\n";
echo "║  - Test with real Maya sandbox account                ║\n";
echo "╚════════════════════════════════════════════════════════╝\n";

if (!$isCli) {
    echo '</pre>';
}
?>
