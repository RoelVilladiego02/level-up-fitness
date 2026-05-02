<?php
require_once 'config/config.php';
require_once 'config/database.php';

echo "Users table schema:\n";
$stmt = $pdo->prepare("DESCRIBE users");
$stmt->execute();
$columns = $stmt->fetchAll();

foreach ($columns as $col) {
    echo "  " . $col['Field'] . " - " . $col['Type'] . " - " . ($col['Null'] === 'YES' ? 'Nullable' : 'NOT NULL') . "\n";
}
?>