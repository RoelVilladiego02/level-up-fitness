<?php
require_once 'config/config.php';

// Get payments table structure
$result = $pdo->query('DESCRIBE payments')->fetchAll();
echo "Payments table structure:\n";
echo str_repeat("─", 80) . "\n";
foreach($result as $col) {
    echo $col['Field'] . ' (' . $col['Type'] . ') ' . ($col['Null'] == 'NO' ? 'NOT NULL' : 'nullable') . PHP_EOL;
}

// Also check members table
echo "\n\nMembers in database:\n";
echo str_repeat("─", 80) . "\n";
$members = $pdo->query('SELECT member_id, member_name, membership_type, status FROM members LIMIT 5')->fetchAll();
if (!empty($members)) {
    foreach ($members as $m) {
        echo "ID: {$m['member_id']}, Name: {$m['member_name']}, Type: {$m['membership_type']}, Status: {$m['status']}\n";
    }
    echo "\nTotal members: ";
    $count = $pdo->query('SELECT COUNT(*) as cnt FROM members')->fetch();
    echo $count['cnt'] . "\n";
}
