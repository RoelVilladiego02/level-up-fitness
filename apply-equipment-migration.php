<?php
require_once 'config/database.php';

try {
    echo "=== Applying Equipment & Reservations Migration ===\n\n";
    
    // Create equipment table
    $pdo->exec("CREATE TABLE IF NOT EXISTS equipment (
        equipment_id VARCHAR(50) PRIMARY KEY,
        equipment_name VARCHAR(255) NOT NULL,
        equipment_category VARCHAR(100) NOT NULL,
        description LONGTEXT,
        quantity INT NOT NULL DEFAULT 1,
        availability ENUM('Available', 'Maintenance', 'Out of Service') NOT NULL DEFAULT 'Available',
        location VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_equipment_category (equipment_category),
        INDEX idx_availability (availability)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Created equipment table\n";
    
    // Drop old reservations table
    $pdo->exec("DROP TABLE IF EXISTS reservations");
    echo "✓ Dropped old reservations table\n";
    
    // Create new reservations table with equipment_id
    $pdo->exec("CREATE TABLE reservations (
        reservation_id VARCHAR(50) PRIMARY KEY,
        member_id VARCHAR(50) NOT NULL,
        equipment_id VARCHAR(50),
        trainer_id VARCHAR(50),
        reservation_date DATE NOT NULL,
        reservation_time TIME NOT NULL,
        status ENUM('Confirmed', 'Cancelled', 'Completed') NOT NULL DEFAULT 'Confirmed',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
        FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE SET NULL,
        FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id) ON DELETE SET NULL,
        INDEX idx_member_id (member_id),
        INDEX idx_equipment_id (equipment_id),
        INDEX idx_trainer_id (trainer_id),
        INDEX idx_reservation_date (reservation_date),
        INDEX idx_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Created new reservations table with equipment_id\n";
    
    // Insert sample equipment
    $stmt = $pdo->prepare("INSERT INTO equipment (equipment_id, equipment_name, equipment_category, description, quantity, availability, location) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $equipment = [
        ['EQ001', 'Treadmill', 'Cardio', 'High-speed treadmill for running and walking', 5, 'Available', 'Cardio Area'],
        ['EQ002', 'Dumbbells Set', 'Weights', '10kg-50kg dumbbells', 20, 'Available', 'Weight Room'],
        ['EQ003', 'Bench Press', 'Weights', 'Olympic weight bench press', 3, 'Available', 'Bench Press Area'],
        ['EQ004', 'Rowing Machine', 'Cardio', 'Concept2 rowing machine', 2, 'Available', 'Cardio Area'],
        ['EQ005', 'Yoga Mat', 'Yoga', 'Premium yoga mats', 15, 'Available', 'Yoga Studio'],
    ];
    
    foreach ($equipment as $eq) {
        $stmt->execute($eq);
    }
    echo "✓ Inserted 5 sample equipment items\n";
    
    // Verify
    $eqCount = $pdo->query("SELECT COUNT(*) as count FROM equipment")->fetch()['count'];
    echo "\n✓ Migration successful!\n";
    echo "✓ Equipment table created with $eqCount sample items\n";
    echo "✓ Reservations table updated with equipment_id field\n";
    
} catch (Exception $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
}
?>
