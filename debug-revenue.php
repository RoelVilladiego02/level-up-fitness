<?php
require_once 'config/config.php';

// Check if there are any payment records
$stmt = $pdo->query('SELECT COUNT(*) as count FROM payments');
$count = $stmt->fetch();
echo 'Total payment records: ' . $count['count'] . PHP_EOL;

// Check payment statuses available
$stmt = $pdo->query('SELECT DISTINCT payment_status FROM payments');
$statuses = $stmt->fetchAll();
echo 'Payment statuses in database: ' . json_encode($statuses) . PHP_EOL;

// Check a sample payment record
$stmt = $pdo->query('SELECT * FROM payments LIMIT 1');
$sample = $stmt->fetch();
echo 'Sample payment record: ' . json_encode($sample, JSON_PRETTY_PRINT) . PHP_EOL;

// Check payments with Paid status
$stmt = $pdo->query('SELECT COUNT(*) as count, SUM(amount) as total FROM payments WHERE payment_status = "Paid"');
$paid = $stmt->fetch();
echo 'Paid payments: ' . json_encode($paid) . PHP_EOL;

// Check date filtering - sample dates
$today = date('Y-m-d');
$startOfMonth = date('Y-m-01');
echo "Today: $today, Start of month: $startOfMonth" . PHP_EOL;

// Test the exact query from the revenue report
$stmt = $pdo->prepare("
    SELECT SUM(CASE WHEN payment_status = 'Paid' THEN amount ELSE 0 END) as completed_revenue
    FROM payments
    WHERE DATE(payment_date) BETWEEN ? AND ?
");
$stmt->execute([$startOfMonth, $today]);
$result = $stmt->fetch();
echo 'Revenue query result: ' . json_encode($result) . PHP_EOL;
