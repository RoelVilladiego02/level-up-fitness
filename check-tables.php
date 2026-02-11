<?php
require_once 'config/database.php';

echo "=== Checking Database Tables ===\n\n";

$tables = ['reservations', 'equipment'];

foreach ($tables as $table) {
    try {
        $result = $pdo->query("SHOW COLUMNS FROM $table");
        if ($result) {
            $columns = $result->fetchAll(PDO::FETCH_ASSOC);
            echo "✓ Table '$table' EXISTS with columns:\n";
            foreach ($columns as $col) {
                echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
            }
        }
    } catch (Exception $e) {
        echo "✗ Table '$table' MISSING: " . $e->getMessage() . "\n";
    }
    echo "\n";
}
?>
