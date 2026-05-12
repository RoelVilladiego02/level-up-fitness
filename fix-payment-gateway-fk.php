<?php
/**
 * Fix Foreign Key Constraint in payment_gateway_transactions
 * 
 * The table was referencing the old 'payments' table, but we're using the new
 * 'invoice_payments' system. This script removes the problematic FK constraint.
 */

require 'config/database.php';

echo "🔧 Fixing payment_gateway_transactions foreign key constraints...\n\n";

try {
    // Drop the old FK constraint
    echo "1️⃣ Removing old FK constraint (payment_id → payments)...\n";
    $pdo->exec("
        ALTER TABLE payment_gateway_transactions 
        DROP FOREIGN KEY payment_gateway_transactions_ibfk_1
    ");
    echo "✓ Dropped old FK constraint\n\n";
    
    // Make payment_id nullable since it now references invoice_payments
    echo "2️⃣ Making payment_id nullable...\n";
    $pdo->exec("
        ALTER TABLE payment_gateway_transactions 
        MODIFY COLUMN payment_id VARCHAR(50) NULL COMMENT 'Reference to invoice payment'
    ");
    echo "✓ Updated payment_id column to NULL\n\n";
    
    // Add new FK constraint to invoice_payments if table exists
    echo "3️⃣ Adding new FK constraint to invoice_payments...\n";
    $checkTable = $pdo->query("SHOW TABLES LIKE 'invoice_payments'")->fetchAll();
    
    if (!empty($checkTable)) {
        $pdo->exec("
            ALTER TABLE payment_gateway_transactions 
            ADD CONSTRAINT fk_payment_gateway_invoice_payments 
            FOREIGN KEY (payment_id) REFERENCES invoice_payments(payment_id) 
            ON DELETE SET NULL
        ");
        echo "✓ Added FK constraint to invoice_payments\n";
    } else {
        echo "⚠ invoice_payments table not found, skipping FK to that table\n";
    }
    
    echo "\n✓ Payment gateway FK constraints fixed successfully!\n";
    echo "You can now process invoice payments through Maya gateway.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
