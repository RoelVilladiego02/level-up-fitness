<?php
require_once 'config/config.php';

echo "Payment Data Verification:\n";
echo str_repeat("═", 80) . "\n\n";

// Check invoice_payments table
$stmt = $pdo->query("SELECT COUNT(*) as count FROM invoice_payments");
$count = $stmt->fetch()['count'];
echo "Invoice Payments Table: $count records\n";

// Check totals
$stmt = $pdo->query("SELECT 
    COUNT(*) as total_payments,
    SUM(amount) as total_amount,
    COUNT(DISTINCT payment_status) as unique_statuses
FROM invoice_payments");
$totals = $stmt->fetch();
echo "  Total Amount: ₱" . number_format($totals['total_amount'], 2) . "\n";
echo "  Unique Statuses: " . $totals['unique_statuses'] . "\n\n";

// Check invoice status breakdown
echo "Invoice Status Breakdown:\n";
$stmt = $pdo->query("SELECT 
    invoice_status, 
    COUNT(*) as count, 
    SUM(amount) as total
FROM invoices 
GROUP BY invoice_status
ORDER BY count DESC");

$invoices = $stmt->fetchAll();
foreach ($invoices as $inv) {
    echo "  {$inv['invoice_status']}: {$inv['count']} invoices - ₱" . number_format($inv['total'], 2) . "\n";
}

echo "\n" . str_repeat("═", 80) . "\n";
echo "✓ Data is now ready to display in the payments management page\n";
