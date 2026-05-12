<?php
require_once 'config/config.php';

// Check if both tables exist
echo "Checking payment tables...\n\n";

// Check payments table
$result = $pdo->query("SELECT COUNT(*) as count FROM payments");
$paymentsCount = $result->fetch()['count'];
echo "Payments table: $paymentsCount records\n";

// Check invoice_payments table
try {
    $result = $pdo->query("SELECT COUNT(*) as count FROM invoice_payments");
    $invoicePaymentsCount = $result->fetch()['count'];
    echo "Invoice_payments table: $invoicePaymentsCount records\n";
} catch (Exception $e) {
    echo "Invoice_payments table: DOES NOT EXIST\n";
}

// Check schema of both tables
echo "\n\nPayments table structure:\n";
$result = $pdo->query('DESCRIBE payments')->fetchAll();
foreach($result as $col) {
    echo "  {$col['Field']} ({$col['Type']})\n";
}

echo "\n\nInvoice_payments table structure:\n";
try {
    $result = $pdo->query('DESCRIBE invoice_payments')->fetchAll();
    foreach($result as $col) {
        echo "  {$col['Field']} ({$col['Type']})\n";
    }
} catch (Exception $e) {
    echo "  Table does not exist\n";
}
