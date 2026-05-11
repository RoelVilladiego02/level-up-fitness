<?php
/**
 * Add updated_at column to payments table
 * Level Up Fitness - Gym Management System
 */

require_once dirname(__FILE__) . '/config/database.php';

try {
    echo "🔧 Adding updated_at column to payments table...\n";
    
    // Check if column already exists
    $checkStmt = $pdo->query("SHOW COLUMNS FROM payments WHERE Field = 'updated_at'");
    $columnExists = $checkStmt->fetch();
    
    if ($columnExists) {
        echo "✓ Column updated_at already exists in payments table\n";
    } else {
        // Add the column after created_at
        $pdo->exec("
            ALTER TABLE payments ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at
        ");
        echo "✓ Successfully added updated_at column to payments table\n";
    }
    
    echo "\n✅ Migration complete!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
