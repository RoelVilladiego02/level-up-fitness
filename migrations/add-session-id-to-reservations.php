<?php
/**
 * Migration: Add session_id to Reservations Table
 * Level Up Fitness - Gym Management System
 * 
 * This migration adds a foreign key to training_sessions to enforce
 * that every approved reservation must have an associated training session.
 */

require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once dirname(dirname(__FILE__)) . '/config/database.php';

echo "Starting migration: Add session_id to reservations table...\n";

try {
    // Check if session_id column already exists
    $checkStmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'reservations' AND COLUMN_NAME = 'session_id'");
    $columnExists = $checkStmt->rowCount() > 0;

    if ($columnExists) {
        echo "✓ session_id column already exists in reservations table\n";
    } else {
        echo "Adding session_id column to reservations table...\n";
        
        $sql = "ALTER TABLE reservations 
                ADD COLUMN session_id INT NULL,
                ADD FOREIGN KEY (session_id) REFERENCES training_sessions(session_id) ON DELETE SET NULL";
        
        $pdo->exec($sql);
        echo "✓ session_id column and foreign key added successfully\n";
    }

    // Add index for session_id if it doesn't exist
    $indexCheckStmt = $pdo->query("SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_NAME = 'reservations' AND COLUMN_NAME = 'session_id' AND INDEX_NAME = 'idx_session_id'");
    $indexExists = $indexCheckStmt->rowCount() > 0;

    if ($indexExists) {
        echo "✓ Index on session_id already exists\n";
    } else {
        echo "Adding index on session_id...\n";
        $pdo->exec("ALTER TABLE reservations ADD INDEX idx_session_id (session_id)");
        echo "✓ Index on session_id created successfully\n";
    }

    echo "\n✓ Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

?>
