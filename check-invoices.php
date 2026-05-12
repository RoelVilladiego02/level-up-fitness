<?php
require_once 'config/config.php';

// Check invoices table structure
echo "Invoices Table Structure:\n";
echo str_repeat("─", 80) . "\n";
$result = $pdo->query('DESCRIBE invoices')->fetchAll();
foreach($result as $col) {
    echo $col['Field'] . ' (' . $col['Type'] . ') ' . ($col['Null'] == 'NO' ? 'NOT NULL' : 'nullable') . "\n";
}

// Check existing unpaid invoices
echo "\n\nUnpaid Invoices:\n";
echo str_repeat("─", 80) . "\n";
$stmt = $pdo->query("SELECT COUNT(*) as count FROM invoices WHERE invoice_status IN ('Pending', 'Unpaid', 'Outstanding')");
$unpaid = $stmt->fetch();
echo "Total unpaid invoices: " . $unpaid['count'] . "\n";

// Get a sample
$stmt = $pdo->query("SELECT * FROM invoices LIMIT 5");
$samples = $stmt->fetchAll();
if (!empty($samples)) {
    foreach ($samples as $inv) {
        echo json_encode($inv) . "\n";
    }
}

// Check invoice statuses
echo "\n\nInvoice Statuses Distribution:\n";
$stmt = $pdo->query("SELECT invoice_status, COUNT(*) as count FROM invoices GROUP BY invoice_status");
$statuses = $stmt->fetchAll();
foreach ($statuses as $status) {
    echo "  {$status['invoice_status']}: {$status['count']}\n";
}
