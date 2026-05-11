<?php
/**
 * Migration: Add Payment Gateway Integration Tables
 * Level Up Fitness - Gym Management System
 * 
 * This migration creates tables for tracking payment gateway transactions
 * and manages the relationship between system payments and external payment processors
 */

require_once dirname(__FILE__) . '/config/database.php';

function runMigration() {
    global $pdo;
    
    echo "╔════════════════════════════════════════════════════╗\n";
    echo "║  Payment Gateway Integration Migration             ║\n";
    echo "║  Level Up Fitness System                           ║\n";
    echo "╚════════════════════════════════════════════════════╝\n\n";
    
    try {
        // Step 1: Update payments table - Add gateway columns
        echo "📋 Step 1: Updating payments table...\n";
        
        // Check if columns already exist
        $result = $pdo->query("SHOW COLUMNS FROM payments LIKE 'payment_gateway%'");
        $existingColumns = $result->fetchAll();
        
        if (empty($existingColumns)) {
            $pdo->exec("
                ALTER TABLE payments ADD COLUMN (
                    payment_gateway VARCHAR(50) DEFAULT 'manual' COMMENT 'Payment gateway (manual, maya, etc)',
                    gateway_transaction_id VARCHAR(100) NULL COMMENT 'External gateway transaction ID',
                    gateway_reference_number VARCHAR(100) NULL COMMENT 'Gateway reference/authorization number',
                    payment_attempt_count INT DEFAULT 0 COMMENT 'Number of payment attempts',
                    payment_retry_count INT DEFAULT 0 COMMENT 'Number of retries',
                    last_retry_at TIMESTAMP NULL COMMENT 'Last retry attempt timestamp'
                )
            ");
            echo "✓ Added payment gateway columns to payments table\n";
        } else {
            echo "✓ Payment gateway columns already exist\n";
        }
        
        // Add indexes for gateway columns
        $pdo->exec("
            ALTER TABLE payments ADD INDEX idx_gateway (payment_gateway),
                                   ADD INDEX idx_gateway_transaction (gateway_transaction_id)
        ");
        echo "✓ Added indexes for gateway columns\n\n";
        
        // Step 2: Create payment_gateway_transactions table
        echo "📋 Step 2: Creating payment_gateway_transactions table...\n";
        
        $result = $pdo->query("SHOW TABLES LIKE 'payment_gateway_transactions'");
        if (empty($result->fetchAll())) {
            $pdo->exec("
                CREATE TABLE payment_gateway_transactions (
                    transaction_id VARCHAR(100) PRIMARY KEY COMMENT 'Unique transaction identifier',
                    payment_id VARCHAR(50) NOT NULL COMMENT 'Reference to system payment',
                    member_id VARCHAR(50) NOT NULL COMMENT 'Member who made payment',
                    gateway_name VARCHAR(50) NOT NULL COMMENT 'Payment gateway (maya, manual, etc)',
                    gateway_transaction_id VARCHAR(100) UNIQUE COMMENT 'External gateway transaction ID',
                    gateway_reference_number VARCHAR(100) NULL COMMENT 'Gateway reference/auth code',
                    
                    amount DECIMAL(10, 2) NOT NULL COMMENT 'Transaction amount',
                    currency VARCHAR(3) DEFAULT 'PHP' COMMENT 'Currency code',
                    
                    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded') DEFAULT 'pending' COMMENT 'Transaction status',
                    payment_method VARCHAR(50) NULL COMMENT 'Payment method (credit card, maya wallet, etc)',
                    
                    request_data LONGTEXT NULL COMMENT 'Original payment request (JSON)',
                    response_data LONGTEXT NULL COMMENT 'Gateway response (JSON)',
                    webhook_data LONGTEXT NULL COMMENT 'Webhook callback data (JSON)',
                    
                    request_at TIMESTAMP NULL COMMENT 'When request was sent',
                    completed_at TIMESTAMP NULL COMMENT 'When transaction completed',
                    
                    attempt_count INT DEFAULT 1 COMMENT 'Number of attempts',
                    last_attempt_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Last attempt timestamp',
                    next_retry_at TIMESTAMP NULL COMMENT 'Scheduled retry time',
                    
                    error_code VARCHAR(50) NULL COMMENT 'Error code if failed',
                    error_message LONGTEXT NULL COMMENT 'Error message if failed',
                    
                    signature VARCHAR(255) NULL COMMENT 'Request/response signature for verification',
                    
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    FOREIGN KEY (payment_id) REFERENCES payments(payment_id) ON DELETE CASCADE,
                    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
                    
                    INDEX idx_payment_id (payment_id),
                    INDEX idx_member_id (member_id),
                    INDEX idx_gateway_name (gateway_name),
                    INDEX idx_status (status),
                    INDEX idx_created_at (created_at),
                    INDEX idx_gateway_transaction_id (gateway_transaction_id),
                    INDEX idx_request_at (request_at),
                    INDEX idx_completed_at (completed_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            echo "✓ Created payment_gateway_transactions table\n";
        } else {
            echo "✓ payment_gateway_transactions table already exists\n";
        }
        
        echo "\n";
        
        // Step 3: Create gateway_webhooks table
        echo "📋 Step 3: Creating gateway_webhooks table...\n";
        
        $result = $pdo->query("SHOW TABLES LIKE 'gateway_webhooks'");
        if (empty($result->fetchAll())) {
            $pdo->exec("
                CREATE TABLE gateway_webhooks (
                    webhook_id VARCHAR(100) PRIMARY KEY,
                    transaction_id VARCHAR(100) NOT NULL,
                    gateway_name VARCHAR(50) NOT NULL,
                    event_type VARCHAR(50) NOT NULL COMMENT 'Event type: payment_completed, payment_failed, etc',
                    
                    payload LONGTEXT NOT NULL COMMENT 'Webhook payload (JSON)',
                    signature VARCHAR(255) COMMENT 'Webhook signature',
                    signature_verified BOOLEAN DEFAULT FALSE,
                    
                    status ENUM('received', 'processing', 'processed', 'failed', 'retrying') DEFAULT 'received',
                    error_message LONGTEXT NULL,
                    
                    processed_at TIMESTAMP NULL,
                    retry_count INT DEFAULT 0,
                    next_retry_at TIMESTAMP NULL,
                    
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    FOREIGN KEY (transaction_id) REFERENCES payment_gateway_transactions(transaction_id) ON DELETE CASCADE,
                    
                    INDEX idx_transaction_id (transaction_id),
                    INDEX idx_gateway_name (gateway_name),
                    INDEX idx_event_type (event_type),
                    INDEX idx_status (status),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            echo "✓ Created gateway_webhooks table\n";
        } else {
            echo "✓ gateway_webhooks table already exists\n";
        }
        
        echo "\n";
        
        // Step 4: Create gateway_refunds table
        echo "📋 Step 4: Creating gateway_refunds table...\n";
        
        $result = $pdo->query("SHOW TABLES LIKE 'gateway_refunds'");
        if (empty($result->fetchAll())) {
            $pdo->exec("
                CREATE TABLE gateway_refunds (
                    refund_id VARCHAR(100) PRIMARY KEY,
                    transaction_id VARCHAR(100) NOT NULL,
                    payment_id VARCHAR(50) NOT NULL,
                    
                    amount DECIMAL(10, 2) NOT NULL,
                    currency VARCHAR(3) DEFAULT 'PHP',
                    reason VARCHAR(255),
                    notes LONGTEXT NULL,
                    
                    gateway_refund_id VARCHAR(100) UNIQUE NULL COMMENT 'External gateway refund ID',
                    status ENUM('pending', 'processing', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
                    
                    requested_by VARCHAR(50) COMMENT 'User ID who requested refund',
                    requested_at TIMESTAMP,
                    processed_at TIMESTAMP NULL,
                    
                    gateway_response LONGTEXT NULL COMMENT 'Gateway response (JSON)',
                    error_code VARCHAR(50) NULL,
                    error_message LONGTEXT NULL,
                    
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    
                    FOREIGN KEY (transaction_id) REFERENCES payment_gateway_transactions(transaction_id),
                    FOREIGN KEY (payment_id) REFERENCES payments(payment_id),
                    
                    INDEX idx_transaction_id (transaction_id),
                    INDEX idx_payment_id (payment_id),
                    INDEX idx_status (status),
                    INDEX idx_requested_at (requested_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            echo "✓ Created gateway_refunds table\n";
        } else {
            echo "✓ gateway_refunds table already exists\n";
        }
        
        echo "\n";
        
        // Step 5: Update payment methods in payments table
        echo "📋 Step 5: Updating payment method options...\n";
        
        // Check current ENUM values
        $result = $pdo->query("
            SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_NAME='payments' AND COLUMN_NAME='payment_method'
        ");
        $column = $result->fetch();
        $currentType = $column['COLUMN_TYPE'];
        
        // Add 'Online - Maya' if not present
        if (strpos($currentType, 'Online - Maya') === false) {
            // MySQL requires modifying ENUM with full list
            $pdo->exec("
                ALTER TABLE payments 
                MODIFY payment_method ENUM(
                    'Cash', 
                    'Card', 
                    'GCash', 
                    'Bank Transfer',
                    'Cheque',
                    'Online - Maya',
                    'Online - GCash',
                    'Online - Credit Card'
                ) NOT NULL DEFAULT 'Cash'
            ");
            echo "✓ Added online payment methods to payment_method ENUM\n";
        } else {
            echo "✓ Online payment methods already in payment_method ENUM\n";
        }
        
        echo "\n";
        
        // Step 6: Create gateway_logs table
        echo "📋 Step 6: Creating gateway_logs table...\n";
        
        $result = $pdo->query("SHOW TABLES LIKE 'gateway_logs'");
        if (empty($result->fetchAll())) {
            $pdo->exec("
                CREATE TABLE gateway_logs (
                    log_id INT PRIMARY KEY AUTO_INCREMENT,
                    transaction_id VARCHAR(100) NULL,
                    gateway_name VARCHAR(50) NOT NULL,
                    
                    action VARCHAR(100) NOT NULL COMMENT 'create_payment, check_status, webhook, retry, etc',
                    method VARCHAR(10) NOT NULL COMMENT 'GET, POST, PUT, WEBHOOK',
                    endpoint VARCHAR(255),
                    
                    request_data LONGTEXT NULL,
                    response_data LONGTEXT NULL,
                    
                    http_status_code INT NULL,
                    error_code VARCHAR(50) NULL,
                    error_message LONGTEXT NULL,
                    
                    duration_ms INT COMMENT 'Request duration in milliseconds',
                    
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    
                    FOREIGN KEY (transaction_id) REFERENCES payment_gateway_transactions(transaction_id) ON DELETE SET NULL,
                    
                    INDEX idx_transaction_id (transaction_id),
                    INDEX idx_gateway_name (gateway_name),
                    INDEX idx_action (action),
                    INDEX idx_created_at (created_at),
                    INDEX idx_error_code (error_code)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
            echo "✓ Created gateway_logs table\n";
        } else {
            echo "✓ gateway_logs table already exists\n";
        }
        
        echo "\n";
        
        // Success message
        echo "╔════════════════════════════════════════════════════╗\n";
        echo "║  ✓ Migration Completed Successfully!              ║\n";
        echo "╚════════════════════════════════════════════════════╝\n\n";
        
        echo "Summary:\n";
        echo "  ✓ Updated payments table with gateway columns\n";
        echo "  ✓ Created payment_gateway_transactions table\n";
        echo "  ✓ Created gateway_webhooks table\n";
        echo "  ✓ Created gateway_refunds table\n";
        echo "  ✓ Updated payment method ENUM with online options\n";
        echo "  ✓ Created gateway_logs table\n";
        echo "\n";
        
        echo "Next Steps:\n";
        echo "  1. Configure Maya API credentials in config/payment-gateway.php\n";
        echo "  2. Set environment variables for API keys\n";
        echo "  3. Update payment collection UI to include 'Online - Maya' option\n";
        echo "  4. Test webhook handling in /api/payments/webhook.php\n";
        echo "  5. Create payment page for customer checkout\n\n";
        
        return true;
        
    } catch (Exception $e) {
        echo "✗ Migration Error: " . $e->getMessage() . "\n";
        error_log("Payment Gateway Migration Error: " . $e->getMessage());
        return false;
    }
}

// Run migration if accessed directly
if (php_sapi_name() === 'cli' || basename($_SERVER['PHP_SELF'] ?? '') === basename(__FILE__)) {
    $success = runMigration();
    exit($success ? 0 : 1);
}

// Return for inclusion in other scripts
return 'MigrationComplete';
?>
