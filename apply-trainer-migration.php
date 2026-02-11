<?php
require_once 'config/database.php';

try {
    // Add years_of_experience column
    $pdo->exec("ALTER TABLE trainers ADD COLUMN years_of_experience INT NOT NULL DEFAULT 0 AFTER specialization");
    
    echo "✓ Migration successful: Added years_of_experience column to trainers table\n";
    
    // Verify
    $result = $pdo->query("SHOW COLUMNS FROM trainers WHERE Field = 'years_of_experience'");
    $col = $result->fetch(PDO::FETCH_ASSOC);
    if ($col) {
        echo "✓ Verification passed: Column exists with type " . $col['Type'] . "\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>
