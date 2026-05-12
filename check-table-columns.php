<?php
require 'config/database.php';

echo "=== invoice_payments columns ===\n";
$result = $pdo->query('DESCRIBE invoice_payments')->fetchAll(PDO::FETCH_ASSOC);
foreach($result as $row) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}

echo "\n=== payments columns ===\n";
$result = $pdo->query('DESCRIBE payments')->fetchAll(PDO::FETCH_ASSOC);
foreach($result as $row) {
    echo $row['Field'] . ' - ' . $row['Type'] . "\n";
}
?>
