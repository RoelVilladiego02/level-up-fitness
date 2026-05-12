<?php
require 'config/database.php';

// Disable FK checks temporarily
$pdo->exec("SET FOREIGN_KEY_CHECKS=0");

// Update invoices FIRST before member ID changes
$stmt = $pdo->prepare("UPDATE invoices SET member_id = ? WHERE member_id = ?");
$stmt->execute(['MEM1778599012252', 'M1778599012252']);

// Now fix Richlyn's member ID
$stmt = $pdo->prepare("UPDATE members SET member_id = ? WHERE member_name = ?");
$stmt->execute(['MEM1778599012252', 'Richlyn Villadiego']);

// Re-enable FK checks
$pdo->exec("SET FOREIGN_KEY_CHECKS=1");

echo "Fixed Richlyn's member ID from M1778599012252 to MEM1778599012252\n";
echo "Updated invoices to match new ID\n";

// Verify
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM invoices WHERE member_id = 'MEM1778599012252'");
$stmt->execute();
$result = $stmt->fetch();
echo "Invoices now linked: " . $result['cnt'] . "\n";
