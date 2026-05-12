<?php
require_once 'config/config.php';

echo "Clearing invoice_payments table...\n";
$pdo->query('TRUNCATE invoice_payments');
echo "✓ Invoice payments table cleared.\n\n";

// Also clear invoices status to reset for fresh simulation
echo "Resetting invoice statuses to Pending...\n";
$pdo->query("UPDATE invoices SET invoice_status = 'Pending' WHERE invoice_status IN ('Paid', 'Partially Paid')");
echo "✓ Invoice statuses reset.\n\n";
