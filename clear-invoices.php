<?php
require_once 'config/config.php';

echo "Clearing invoices table...\n";
$pdo->query('TRUNCATE invoices');
echo "✓ Invoices table cleared.\n\n";

// Also clear invoice_payments related to those invoices
$pdo->query('TRUNCATE invoice_payments');
echo "✓ Invoice payments table cleared.\n\n";
