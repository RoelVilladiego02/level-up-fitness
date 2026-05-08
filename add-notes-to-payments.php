<?php
/**
 * Migration: Add notes column to payments table
 * Level Up Fitness - Gym Management System
 */

require_once dirname(__FILE__) . '/config/database.php';

try {
    echo "Adding 'notes' column to payments table...\n";
    
    // Check if column already exists
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as col_count 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'payments' 
        AND COLUMN_NAME = 'notes' 
        AND TABLE_SCHEMA = DATABASE()
    ");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result['col_count'] > 0) {
        echo "✓ Column 'notes' already exists in payments table.\n";
    } else {
        // Add the notes column
        $pdo->exec("
            ALTER TABLE payments 
            ADD COLUMN notes LONGTEXT NULL AFTER payment_date
        ");
        echo "✓ Successfully added 'notes' column to payments table.\n";
    }
    
    // Verify the addition
    $stmt = $pdo->prepare("
        SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'payments' 
        AND COLUMN_NAME = 'notes'
        AND TABLE_SCHEMA = DATABASE()
    ");
    $stmt->execute();
    $column = $stmt->fetch();
    
    if ($column) {
        echo "✓ Column details:\n";
        echo "  - Name: " . $column['COLUMN_NAME'] . "\n";
        echo "  - Type: " . $column['COLUMN_TYPE'] . "\n";
        echo "  - Nullable: " . ($column['IS_NULLABLE'] === 'YES' ? 'Yes' : 'No') . "\n";
        echo "\n✓ Migration completed successfully!\n";
    } else {
        echo "✗ Failed to verify column addition.\n";
    }
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
