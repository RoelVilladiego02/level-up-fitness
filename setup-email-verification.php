<?php
/**
 * Database Migration - Add Email Verification Support
 * Creates verification_tokens table
 * Level Up Fitness - Gym Management System
 */

require_once dirname(__FILE__) . '/config/config.php';
require_once dirname(__FILE__) . '/config/database.php';

session_start();

// Check if user is admin (allow CLI execution for migrations)
$isCliMode = php_sapi_name() === 'cli';
if (!$isCliMode && (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin')) {
    die('Only admins can run migrations');
}

$errors = [];
$success = [];

try {
    // Check if table already exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'verification_tokens'");
    $tableExists = $stmt->rowCount() > 0;

    if ($tableExists) {
        $errors[] = 'verification_tokens table already exists';
    } else {
        // Create verification_tokens table
        $pdo->exec("
            CREATE TABLE verification_tokens (
                token_id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                token VARCHAR(255) NOT NULL UNIQUE,
                token_type ENUM('email_verification', 'password_reset') NOT NULL DEFAULT 'email_verification',
                expires_at TIMESTAMP NOT NULL,
                used_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
                INDEX idx_token (token),
                INDEX idx_user_id (user_id),
                INDEX idx_expires_at (expires_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
        
        $success[] = 'verification_tokens table created successfully';
    }

    // Add is_verified column to users table if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_verified'");
    $columnExists = $stmt->rowCount() > 0;

    if (!$columnExists) {
        $pdo->exec("
            ALTER TABLE users 
            ADD COLUMN is_verified TINYINT DEFAULT 0 AFTER password
        ");
        $success[] = 'is_verified column added to users table';
    } else {
        $errors[] = 'is_verified column already exists in users table';
    }

} catch (Exception $e) {
    $errors[] = 'Migration error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔐 Email Verification Setup - Level Up Fitness</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 40px 0; }
        .container { max-width: 600px; }
        .card { border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .success-item { background: #d4edda; border-left: 4px solid #28a745; color: #155724; padding: 12px; margin: 10px 0; border-radius: 4px; }
        .error-item { background: #f8d7da; border-left: 4px solid #dc3545; color: #721c24; padding: 12px; margin: 10px 0; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="card-body p-4">
                <h1 class="mb-3"><i class="fas fa-envelope-circle-check"></i> Email Verification Setup</h1>
                <p class="text-muted">Setting up email verification system...</p>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <h5><i class="fas fa-check-circle"></i> Success</h5>
                        <?php foreach ($success as $msg): ?>
                            <div class="success-item"><?php echo htmlspecialchars($msg); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-exclamation-circle"></i> Notice</h5>
                        <?php foreach ($errors as $msg): ?>
                            <div class="error-item"><?php echo htmlspecialchars($msg); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="alert alert-info mt-4">
                    <h5><i class="fas fa-info-circle"></i> Setup Complete</h5>
                    <ul class="mb-0">
                        <li>✅ Verification tokens table created</li>
                        <li>✅ User verification flag added</li>
                        <li>✅ Email verification system ready</li>
                    </ul>
                </div>

                <div class="mt-4">
                    <a href="<?php echo APP_URL; ?>modules/members/" class="btn btn-primary">
                        <i class="fas fa-users"></i> Go to Members
                    </a>
                    <a href="<?php echo APP_URL; ?>dashboard/" class="btn btn-secondary">
                        <i class="fas fa-home"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
