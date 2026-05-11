<?php
require 'config/database.php';

$result = $pdo->query('DESCRIBE payments');
$columns = $result->fetchAll();

echo "Payments table columns:\n";
foreach ($columns as $col) {
    echo $col['Field'] . "\n";
}
