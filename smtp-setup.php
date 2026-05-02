<?php
/**
 * SMTP Email Setup and Testing Script
 * Level Up Fitness - Gym Management System
 * 
 * This script helps configure and test SMTP email service
 * Run this once to verify SMTP configuration
 */

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die('Access denied. Only administrators can access this page.');
}

// Load configuration
require_once dirname(__FILE__) . '/config/config.php';
require_once dirname(__FILE__) . '/config/database.php';
require_once dirname(__FILE__) . '/config/smtp.php';
require_once dirname(__FILE__) . '/config/SMTPMailService.php';
require_once dirname(__FILE__) . '/includes/functions.php';
require_once dirname(__FILE__) . '/includes/email-notifications.php';

$results = [];
$testEmail = $_GET['test_email'] ?? $_SESSION['email'] ?? 'admin@levelupfitness.local';
$testMode = isset($_POST['action']);

if ($testMode && $_POST['action'] === 'test_connection') {
    $results['connection_test'] = SMTPMailService::testConnection();
}

if ($testMode && $_POST['action'] === 'send_test') {
    $testEmail = $_POST['test_email'] ?? $testEmail;
    $results['send_test'] = SMTPMailService::sendTest($testEmail);
}

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SMTP Email Configuration & Testing - Level Up Fitness</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .setup-container { max-width: 900px; margin: 40px auto; padding: 20px; }
        .config-section { background: #f8f9fa; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #4A90E2; }
        .config-item { margin: 15px 0; padding: 10px; background: white; border-radius: 4px; }
        .status-ok { color: #27ae60; font-weight: bold; }
        .status-error { color: #e74c3c; font-weight: bold; }
        .status-warning { color: #f39c12; font-weight: bold; }
        .test-result { margin: 15px 0; padding: 15px; border-radius: 4px; border-left: 4px solid #4A90E2; }
        .test-result.success { background: #d4edda; border-left-color: #27ae60; }
        .test-result.error { background: #f8d7da; border-left-color: #e74c3c; }
        .form-group { margin: 15px 0; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        .btn-group { display: flex; gap: 10px; margin-top: 20px; }
        .btn { padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #4A90E2; color: white; }
        .btn-success { background: #27ae60; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn:hover { opacity: 0.9; }
        code { background: #f0f0f0; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
        .env-example { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 4px; font-family: monospace; margin: 10px 0; overflow-x: auto; }
        .instructions { background: #e3f2fd; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .success-banner { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .warning { background: #fff3cd; padding: 15px; border-radius: 4px; border-left: 4px solid #f39c12; margin: 15px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #4A90E2; color: white; }
        h1 { color: #333; }
        h2 { color: #4A90E2; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="setup-container">
        <h1><i class="fas fa-envelope"></i> SMTP Email Configuration & Testing</h1>
        <p class="text-muted">Configure and test your SMTP email service (Mailtrap Sandbox)</p>

        <!-- Configuration Status -->
        <div class="config-section">
            <h2>📊 Current SMTP Configuration</h2>
            
            <div class="config-item">
                <strong>Service Status:</strong>
                <?php if (MAIL_ENABLED): ?>
                    <span class="status-ok">✓ Enabled</span>
                <?php else: ?>
                    <span class="status-error">✗ Disabled</span>
                <?php endif; ?>
            </div>

            <div class="config-item">
                <strong>SMTP Host:</strong>
                <code><?php echo htmlspecialchars(SMTP_HOST); ?></code>
            </div>

            <div class="config-item">
                <strong>SMTP Port:</strong>
                <code><?php echo SMTP_PORT; ?></code>
                <small>(TLS Port - Standard for Mailtrap)</small>
            </div>

            <div class="config-item">
                <strong>SMTP Username:</strong>
                <code><?php echo htmlspecialchars(substr(SMTP_USERNAME, 0, 5) . '...' . substr(SMTP_USERNAME, -3)); ?></code>
                <?php if (validateSmtpConfig()): ?>
                    <span class="status-ok">✓ Configured</span>
                <?php else: ?>
                    <span class="status-error">✗ Not Configured</span>
                <?php endif; ?>
            </div>

            <div class="config-item">
                <strong>SMTP Password:</strong>
                <code><?php echo htmlspecialchars(substr(SMTP_PASSWORD, 0, 5) . '...' . substr(SMTP_PASSWORD, -3)); ?></code>
                <?php if (validateSmtpConfig()): ?>
                    <span class="status-ok">✓ Configured</span>
                <?php else: ?>
                    <span class="status-error">✗ Not Configured</span>
                <?php endif; ?>
            </div>

            <div class="config-item">
                <strong>Encryption:</strong>
                <code><?php echo htmlspecialchars(SMTP_ENCRYPTION); ?></code>
            </div>

            <div class="config-item">
                <strong>From Email:</strong>
                <code><?php echo htmlspecialchars(MAIL_FROM_EMAIL); ?></code>
                <span> / <?php echo htmlspecialchars(MAIL_FROM_NAME); ?></span>
            </div>

            <div class="config-item">
                <strong>Reply-To Email:</strong>
                <code><?php echo htmlspecialchars(MAIL_REPLY_TO_EMAIL); ?></code>
                <span> / <?php echo htmlspecialchars(MAIL_REPLY_TO_NAME); ?></span>
            </div>

            <div class="config-item">
                <strong>Debug Mode:</strong>
                <?php if (MAIL_DEBUG): ?>
                    <span class="status-warning">🔍 Enabled (Development)</span>
                <?php else: ?>
                    <span class="status-ok">Disabled (Production)</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Setup Instructions -->
        <?php if (!validateSmtpConfig()): ?>
        <div class="instructions">
            <h3>⚙️ Setup Instructions</h3>
            <p>Your SMTP configuration is incomplete. Please set the following environment variables:</p>
            
            <p><strong>Option 1: Set Environment Variables</strong></p>
            <div class="env-example">
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=291e1c42b01af7
MAIL_PASSWORD=31a6dcc7c10c44
MAIL_ENCRYPTION=tls
            </div>

            <p><strong>Option 2: Edit config/smtp.php directly</strong></p>
            <p>Update the constants at the top of the file with your credentials from Mailtrap</p>

            <p><strong>Getting Mailtrap Credentials:</strong></p>
            <ol>
                <li>Go to <a href="https://mailtrap.io" target="_blank">Mailtrap.io</a> and log in</li>
                <li>Click on "Email Testing" → "Inboxes"</li>
                <li>Select your inbox</li>
                <li>Go to "SMTP Settings" tab</li>
                <li>Copy the Username and Password provided</li>
            </ol>
        </div>
        <?php else: ?>
        <div class="success-banner">
            <strong>✓ SMTP Configuration Complete!</strong> Your credentials are configured and ready to use.
        </div>
        <?php endif; ?>

        <!-- Email Templates Status -->
        <div class="config-section">
            <h2>📧 Email Templates</h2>
            <table>
                <thead>
                    <tr>
                        <th>Template</th>
                        <th>Status</th>
                        <th>Purpose</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $templates = [
                        ['name' => 'payment-confirmation', 'purpose' => 'Payment receipts'],
                        ['name' => 'reservation-confirmation', 'purpose' => 'Reservation confirmations'],
                        ['name' => 'member-welcome', 'purpose' => 'New member onboarding'],
                        ['name' => 'password-reset', 'purpose' => 'Password reset requests'],
                        ['name' => 'membership-expiring-soon', 'purpose' => 'Membership expiration reminders'],
                        ['name' => 'trainer-assignment', 'purpose' => 'Trainer assignments'],
                        ['name' => 'workout-plan-created', 'purpose' => 'Workout plan notifications'],
                        ['name' => 'class-reminder', 'purpose' => 'Class reminders'],
                        ['name' => 'reservation-cancelled', 'purpose' => 'Cancellation notifications'],
                    ];
                    
                    foreach ($templates as $template) {
                        $filePath = EMAIL_TEMPLATE_DIR . $template['name'] . '.html';
                        $exists = file_exists($filePath);
                        $status = $exists ? '<span class="status-ok">✓ Exists</span>' : '<span class="status-error">✗ Missing</span>';
                        echo "<tr>";
                        echo "<td><code>" . htmlspecialchars($template['name']) . "</code></td>";
                        echo "<td>" . $status . "</td>";
                        echo "<td>" . htmlspecialchars($template['purpose']) . "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Test SMTP Connection -->
        <div class="config-section">
            <h2>🧪 Test SMTP Connection</h2>
            
            <p>Test your SMTP connection to Mailtrap:</p>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="test_connection">
                <button type="submit" class="btn btn-primary">Test SMTP Connection</button>
            </form>

            <?php if (isset($results['connection_test'])): ?>
                <div class="test-result <?php echo $results['connection_test']['success'] ? 'success' : 'error'; ?>">
                    <strong><?php echo $results['connection_test']['success'] ? '✓ Success' : '✗ Failed'; ?>:</strong>
                    <?php echo htmlspecialchars($results['connection_test']['message']); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Send Test Email -->
        <div class="config-section">
            <h2>📮 Send Test Email</h2>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="test_email">Email Address:</label>
                    <input type="email" id="test_email" name="test_email" value="<?php echo htmlspecialchars($testEmail); ?>" required>
                    <small>Where to send the test email</small>
                </div>
                
                <div class="form-group">
                    <input type="hidden" name="action" value="send_test">
                    <button type="submit" class="btn btn-success">Send Test Email</button>
                </div>
            </form>

            <?php if (isset($results['send_test'])): ?>
                <div class="test-result <?php echo $results['send_test']['success'] ? 'success' : 'error'; ?>">
                    <strong><?php echo $results['send_test']['success'] ? '✓ Success' : '✗ Failed'; ?>:</strong>
                    <?php echo htmlspecialchars($results['send_test']['message']); ?>
                    
                    <?php if ($results['send_test']['success']): ?>
                        <p style="margin-top: 10px;">
                            <small>Message ID: <code><?php echo htmlspecialchars($results['send_test']['message_id']); ?></code></small>
                        </p>
                        <p>Check your email inbox for the test message. It may take a few seconds to arrive.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Help & Support -->
        <div class="config-section">
            <h2>❓ Help & Support</h2>
            
            <h3>Common Issues</h3>
            
            <p><strong>Q: Email connection failed?</strong></p>
            <p>A: Check that your SMTP credentials are correct. Go to Mailtrap dashboard and verify the Username and Password in the SMTP Settings.</p>
            
            <p><strong>Q: Getting "Authentication failed" error?</strong></p>
            <p>A: Verify your username and password. They should be different from your Mailtrap login credentials. Use the SMTP settings from your inbox.</p>
            
            <p><strong>Q: Email not arriving?</strong></p>
            <p>A: Check the Mailtrap inbox directly at <a href="https://mailtrap.io" target="_blank">mailtrap.io</a>. Emails sent through SMTP appear there.</p>
            
            <p><strong>Q: How do I know which credentials to use?</strong></p>
            <p>A: Log into Mailtrap → Click your inbox → SMTP Settings tab → Copy Username and Password shown there.</p>
            
            <hr>
            
            <h3>Resources</h3>
            <ul>
                <li><a href="https://mailtrap.io" target="_blank">Mailtrap Documentation</a></li>
                <li><a href="https://mailtrap.io/blog/smtp-server/" target="_blank">SMTP Server Setup Guide</a></li>
                <li><a href="https://github.com/PHPMailer/PHPMailer" target="_blank">PHPMailer GitHub</a></li>
            </ul>
        </div>
    </div>
</body>
</html>
