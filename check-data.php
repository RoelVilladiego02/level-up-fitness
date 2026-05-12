<?php
require 'config/database.php';

echo "MEMBERS:\n";
$members = $pdo->query('SELECT member_id, member_name FROM members')->fetchAll();
foreach ($members as $m) {
    echo "  {$m['member_id']} - {$m['member_name']}\n";
}

echo "\nINVOICES:\n";
$invoices = $pdo->query('SELECT invoice_id, member_id, description, amount FROM invoices ORDER BY member_id')->fetchAll();
foreach ($invoices as $i) {
    echo "  {$i['invoice_id']} | {$i['member_id']} | {$i['description']} | ₱{$i['amount']}\n";
}
