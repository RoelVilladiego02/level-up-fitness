<?php
/**
 * Fix payment_gateway_transactions - Make payment_id properly nullable
 */

require 'config/database.php';

echo "🔧 Fixing payment_id in payment_gateway_transactions...\n\n";

try {
    // Drop the problematic FK
    echo "1️⃣ Dropping FK constraint...\n";
    $pdo->exec("ALTER TABLE payment_gateway_transactions DROP FOREIGN KEY fk_payment_gateway_invoice_payments");
    echo "✓ Dropped FK\n\n";
    
    // Make payment_id nullable
    echo "2️⃣ Making payment_id nullable...\n";
    $pdo->exec("ALTER TABLE payment_gateway_transactions MODIFY COLUMN payment_id VARCHAR(50) NULL");
    echo "✓ payment_id is now nullable\n\n";
    
    echo "✓ Fixed! payment_gateway_transactions will now accept NULL payment_id\n";
    echo "Transaction records can be created before invoice_payments records exist.\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
