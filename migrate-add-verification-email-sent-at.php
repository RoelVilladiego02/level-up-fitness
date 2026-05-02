<?php
/**
 * Add verification_email_sent_at column to users table
 * Level Up Fitness - Gym Management System
 */

require_once dirname(__FILE__) . '/config/config.php';
require_once dirname(__FILE__) . '/config/database.php';

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'verification_email_sent_at'");
    if ($stmt->rowCount() === 0) {
        // Add column
        $pdo->query("
            ALTER TABLE users 
            ADD COLUMN verification_email_sent_at DATETIME NULL DEFAULT NULL
            AFTER is_verified
        ");
        echo "✓ Column 'verification_email_sent_at' added to users table\n";
    } else {
        echo "✓ Column 'verification_email_sent_at' already exists\n";
    }
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
