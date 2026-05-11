<?php
/**
 * Migration: Add Trainer Reservation Columns
 * Converts reservations table from equipment-based to trainer-based
 * 
 * This adds:
 * - trainer_id column (VARCHAR 50, nullable, foreign key to trainers)
 * - purpose column (VARCHAR 255, nullable)
 * 
 * Usage: php add-trainer-reservation-columns.php
 */

require_once 'config/database.php';

echo "\n";
echo "╔════════════════════════════════════════════════════════╗\n";
echo "║  Adding Trainer Reservation Columns                   ║\n";
echo "║  - trainer_id (for trainer-based reservations)        ║\n";
echo "║  - purpose (training session purpose)                 ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

try {
    // Check if trainer_id column exists
    $result = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'reservations' AND COLUMN_NAME = 'trainer_id'");
    $rows = $result->fetchAll();
    
    if (!empty($rows)) {
        echo "✓ trainer_id column already exists\n";
    } else {
        echo "⏳ Adding trainer_id column...\n";
        $pdo->exec("ALTER TABLE reservations ADD COLUMN trainer_id VARCHAR(50) NULL AFTER equipment_id");
        $pdo->exec("ALTER TABLE reservations ADD CONSTRAINT fk_reservations_trainer_id FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id) ON DELETE CASCADE");
        $pdo->exec("ALTER TABLE reservations ADD INDEX idx_trainer_id (trainer_id)");
        echo "✓ trainer_id column added successfully\n";
    }

    // Check if purpose column exists
    $result = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'reservations' AND COLUMN_NAME = 'purpose'");
    $rows = $result->fetchAll();
    
    if (!empty($rows)) {
        echo "✓ purpose column already exists\n";
    } else {
        echo "⏳ Adding purpose column...\n";
        $pdo->exec("ALTER TABLE reservations ADD COLUMN purpose VARCHAR(255) NULL AFTER end_time");
        echo "✓ purpose column added successfully\n";
    }

    echo "\n";
    echo "╔════════════════════════════════════════════════════════╗\n";
    echo "║  ✅ MIGRATION COMPLETE!                               ║\n";
    echo "║  Reservations table is ready for trainer-based        ║\n";
    echo "║  bookings with session purpose tracking              ║\n";
    echo "╚════════════════════════════════════════════════════════╝\n\n";

} catch (Exception $e) {
    echo "❌ Error during migration: " . $e->getMessage() . "\n\n";
    exit(1);
}
?>
