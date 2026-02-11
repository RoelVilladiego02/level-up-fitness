<?php
require_once 'config/database.php';

try {
    echo "=== Updating Reservations Table Schema ===\n\n";
    
    // Add start_time and end_time columns if they don't exist
    $pdo->exec("ALTER TABLE reservations ADD COLUMN start_time TIME AFTER reservation_time");
    echo "✓ Added start_time column\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "✓ start_time column already exists\n";
    } else {
        echo "⚠ Error: " . $e->getMessage() . "\n";
    }
}

try {
    $pdo->exec("ALTER TABLE reservations ADD COLUMN end_time TIME AFTER start_time");
    echo "✓ Added end_time column\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "✓ end_time column already exists\n";
    } else {
        echo "⚠ Error: " . $e->getMessage() . "\n";
    }
}

try {
    $pdo->exec("ALTER TABLE reservations ADD COLUMN notes LONGTEXT AFTER end_time");
    echo "✓ Added notes column\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "✓ notes column already exists\n";
    } else {
        echo "⚠ Error: " . $e->getMessage() . "\n";
    }
}

// Verify columns
$result = $pdo->query("SHOW COLUMNS FROM reservations");
$columns = $result->fetchAll(PDO::FETCH_ASSOC);

echo "\n✓ Reservations table columns:\n";
foreach ($columns as $col) {
    echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
}

echo "\n✓ Migration complete!\n";

?>
