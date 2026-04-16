<?php
/**
 * Notification System Setup Script
 * Level Up Fitness - Gym Management System
 * 
 * This script sets up the notification database tables
 * Run this once to initialize the notifications system
 */

// Load configuration
require_once dirname(__FILE__) . '/config/config.php';
require_once dirname(__FILE__) . '/config/database.php';
require_once dirname(__FILE__) . '/includes/functions.php';

// Check if user is logged in as admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die('Access denied. Only admin can run setup.');
}

$errors = [];
$successes = [];

try {
    // Create notifications table
    $notificationsSQL = "
    CREATE TABLE IF NOT EXISTS notifications (
        notification_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        notification_type VARCHAR(50) NOT NULL COMMENT 'payment, reservation, account, system, etc',
        notification_title VARCHAR(255) NOT NULL,
        notification_message LONGTEXT NOT NULL,
        notification_icon VARCHAR(50) DEFAULT 'bell' COMMENT 'fa-icon name',
        icon_color VARCHAR(20) DEFAULT 'primary' COMMENT 'Bootstrap color class',
        related_entity_type VARCHAR(50) COMMENT 'payment, reservation, member, trainer, etc',
        related_entity_id VARCHAR(50) COMMENT 'ID of the related entity',
        action_url VARCHAR(500) COMMENT 'URL to view or act on notification',
        is_read TINYINT DEFAULT 0,
        read_at DATETIME NULL,
        email_sent TINYINT DEFAULT 0,
        email_sent_at DATETIME NULL,
        priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        expires_at DATETIME NULL COMMENT 'When notification should be auto-deleted',
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id),
        INDEX idx_notification_type (notification_type),
        INDEX idx_is_read (is_read),
        INDEX idx_created_at (created_at),
        INDEX idx_priority (priority),
        INDEX idx_user_read (user_id, is_read),
        INDEX idx_user_created (user_id, created_at),
        INDEX idx_unread_count (user_id, is_read, created_at DESC),
        INDEX idx_expiration (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($notificationsSQL);
    $successes[] = 'Notifications table created successfully';
    
    // Create notification preferences table
    $preferencesSQL = "
    CREATE TABLE IF NOT EXISTS notification_preferences (
        preference_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL UNIQUE,
        email_payments TINYINT DEFAULT 1,
        email_reservations TINYINT DEFAULT 1,
        email_account TINYINT DEFAULT 1,
        email_system TINYINT DEFAULT 1,
        in_app_payments TINYINT DEFAULT 1,
        in_app_reservations TINYINT DEFAULT 1,
        in_app_account TINYINT DEFAULT 1,
        in_app_system TINYINT DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        INDEX idx_user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($preferencesSQL);
    $successes[] = 'Notification preferences table created successfully';
    
    // Insert default preferences for existing users
    $insertPrefsSQL = "
    INSERT IGNORE INTO notification_preferences (user_id)
    SELECT user_id FROM users 
    WHERE user_id NOT IN (SELECT user_id FROM notification_preferences);
    ";
    
    $pdo->exec($insertPrefsSQL);
    $prefCount = $pdo->query("SELECT COUNT(*) FROM notification_preferences")->fetchColumn();
    $successes[] = "Default preferences set for $prefCount users";
    
} catch (Exception $e) {
    $errors[] = 'Database error: ' . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notification System Setup - Level Up Fitness</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0"><i class="fas fa-bell"></i> Notification System Setup</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($successes)): ?>
                            <div class="alert alert-success">
                                <h5>✓ Setup Completed Successfully!</h5>
                                <ul class="mb-0">
                                    <?php foreach ($successes as $success): ?>
                                        <li><?php echo htmlspecialchars($success); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <h5>✗ Setup Encountered Errors</h5>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="mt-4">
                            <h5>What's Been Installed:</h5>
                            <ul>
                                <li><strong>Notifications Table:</strong> Stores all in-app notifications for users</li>
                                <li><strong>Notification Preferences Table:</strong> Stores user preferences for notification types and delivery methods</li>
                                <li><strong>Database Functions:</strong> Added to includes/functions.php for managing notifications</li>
                                <li><strong>Notification Bell:</strong> Added to header.php with real-time unread count</li>
                                <li><strong>Notification Center:</strong> Created at /modules/notifications/ to view all notifications</li>
                                <li><strong>Payment Integration:</strong> Payments now send both email and in-app notifications</li>
                                <li><strong>Reservation Integration:</strong> Reservations now send both email and in-app notifications</li>
                            </ul>
                        </div>

                        <div class="mt-4">
                            <h5>Next Steps:</h5>
                            <ol>
                                <li>Users will see a notification bell icon in the header</li>
                                <li>When payments or reservations are created, notifications are sent automatically</li>
                                <li>Go to <a href="<?php echo APP_URL; ?>modules/notifications/">/modules/notifications/</a> to view all notifications</li>
                                <li>Notification preferences can be extended in /modules/notifications/ for customization</li>
                            </ol>
                        </div>

                        <div class="mt-4">
                            <a href="<?php echo APP_URL; ?>dashboard/" class="btn btn-primary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                            <a href="<?php echo APP_URL; ?>modules/notifications/" class="btn btn-info">
                                <i class="fas fa-bell"></i> View Notifications
                            </a>
                        </div>
                    </div>
                </div>

                <div class="alert alert-info mt-4">
                    <strong><i class="fas fa-info-circle"></i> Information:</strong><br>
                    The notification tables have been successfully created in your database. The system is now ready to send and manage notifications for payments, reservations, and other events.
                </div>
            </div>
        </div>
    </div>
</body>
</html>
