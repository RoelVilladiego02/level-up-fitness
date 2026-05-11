<?php
/**
 * Migration: Add Training Sessions Table
 * Level Up Fitness - Gym Management System
 * 
 * This migration ensures the training_sessions table exists
 */

require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once dirname(dirname(__FILE__)) . '/config/database.php';

echo "Starting migration: Add Training Sessions Table...\n";

try {
    // Check if training_sessions table exists
    $checkStmt = $pdo->query("SHOW TABLES LIKE 'training_sessions'");
    $tableExists = $checkStmt->rowCount() > 0;

    if ($tableExists) {
        echo "✓ training_sessions table already exists\n";
    } else {
        echo "Creating training_sessions table...\n";
        
        $sql = "CREATE TABLE training_sessions (
            session_id INT PRIMARY KEY AUTO_INCREMENT,
            session_name VARCHAR(255) NOT NULL,
            trainer_id VARCHAR(50) NOT NULL,
            gym_id VARCHAR(50) NOT NULL,
            session_date DATE NOT NULL,
            session_time TIME NOT NULL,
            duration INT NOT NULL COMMENT 'Duration in minutes',
            max_capacity INT NOT NULL DEFAULT 20,
            description LONGTEXT,
            status ENUM('Scheduled', 'Ongoing', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Scheduled',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (trainer_id) REFERENCES trainers(trainer_id) ON DELETE CASCADE,
            FOREIGN KEY (gym_id) REFERENCES gyms(gym_id) ON DELETE CASCADE,
            INDEX idx_session_date (session_date),
            INDEX idx_trainer_id (trainer_id),
            INDEX idx_gym_id (gym_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "✓ training_sessions table created successfully\n";
    }

    // Check if training_session_attendees table exists
    $checkStmt = $pdo->query("SHOW TABLES LIKE 'training_session_attendees'");
    $attendeesTableExists = $checkStmt->rowCount() > 0;

    if ($attendeesTableExists) {
        echo "✓ training_session_attendees table already exists\n";
    } else {
        echo "Creating training_session_attendees table...\n";
        
        $sql = "CREATE TABLE training_session_attendees (
            attendee_id INT PRIMARY KEY AUTO_INCREMENT,
            session_id INT NOT NULL,
            member_id VARCHAR(50) NOT NULL,
            check_in_time DATETIME,
            check_out_time DATETIME,
            attendance_status ENUM('Present', 'Absent', 'Late', 'Cancelled') NOT NULL DEFAULT 'Present',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (session_id) REFERENCES training_sessions(session_id) ON DELETE CASCADE,
            FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
            UNIQUE KEY unique_session_member (session_id, member_id),
            INDEX idx_session_id (session_id),
            INDEX idx_member_id (member_id),
            INDEX idx_attendance_status (attendance_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "✓ training_session_attendees table created successfully\n";
    }

    echo "\n✓ Migration completed successfully!\n";

} catch (PDOException $e) {
    echo "✗ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

?>
