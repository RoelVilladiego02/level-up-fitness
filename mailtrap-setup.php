<?php
/**
 * Mailtrap Setup and Testing Script
 * Level Up Fitness - Gym Management System
 * 
 * This script helps configure and test Mailtrap email service
 * Run this once to verify Mailtrap configuration
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
require_once dirname(__FILE__) . '/config/mailtrap.php';
require_once dirname(__FILE__) . '/config/MailtrapService.php';
require_once dirname(__FILE__) . '/includes/functions.php';
require_once dirname(__FILE__) . '/includes/email-notifications.php';

$results = [];
$testEmail = $_GET['test_email'] ?? $_SESSION['email'] ?? 'admin@levelupfitness.local';
$testMode = isset($_POST['action']);

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mailtrap Configuration & Testing - Level Up Fitness</title>
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
        .env-example { background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
        .instructions { background: #e3f2fd; padding: 15px; border-radius: 4px; margin: 15px 0; }
        .warning { background: #fff3cd; padding: 15px; border-radius: 4px; border-left: 4px solid #f39c12; margin: 15px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #4A90E2; color: white; }
    </style>
</head>
<body>
    <div class="setup-container">
        <h1><i class="fas fa-envelope"></i> Mailtrap Configuration & Testing</h1>
        <p class="text-muted">Configure and test your Mailtrap email service integration</p>

        <!-- Configuration Status -->
        <div class="config-section">
            <h2>📊 Current Configuration Status</h2>
            
            <div class="config-item">
                <strong>Service Status:</strong>
                <?php if (MAILTRAP_ENABLED): ?>
                    <span class="status-ok">✓ Enabled</span>
                <?php else: ?>
                    <span class="status-error">✗ Disabled</span>
                <?php endif; ?>
            </div>

            <div class="config-item">
                <strong>Mode:</strong>
                <?php if (MAILTRAP_SANDBOX_MODE): ?>
                    <span class="status-warning">🔒 Sandbox Mode (Testing)</span>
                <?php else: ?>
                    <span class="status-ok">🚀 Production Mode</span>
                <?php endif; ?>
            </div>

            <div class="config-item">
                <strong>API Token Status:</strong>
                <?php if (MAILTRAP_API_TOKEN && MAILTRAP_API_TOKEN !== 'YOUR_MAILTRAP_API_TOKEN'): ?>
                    <span class="status-ok">✓ Configured</span>
                    <small>(Token: <?php echo substr(MAILTRAP_API_TOKEN, 0, 10) . '...' . substr(MAILTRAP_API_TOKEN, -5); ?>)</small>
                <?php else: ?>
                    <span class="status-error">✗ Not Configured</span>
                <?php endif; ?>
            </div>

            <div class="config-item">
                <strong>Inbox ID Status:</strong>
                <?php if (MAILTRAP_INBOX_ID && MAILTRAP_INBOX_ID !== 'YOUR_INBOX_ID'): ?>
                    <span class="status-ok">✓ Configured</span>
                    <small>(ID: <?php echo MAILTRAP_INBOX_ID; ?>)</small>
                <?php else: ?>
                    <span class="status-error">✗ Not Configured</span>
                <?php endif; ?>
            </div>

            <div class="config-item">
                <strong>From Email:</strong>
                <code><?php echo MAILTRAP_FROM_EMAIL; ?></code>
            </div>

            <div class="config-item">
                <strong>Reply-To Email:</strong>
                <code><?php echo MAILTRAP_REPLY_TO_EMAIL; ?></code>
            </div>
        </div>

        <!-- Setup Instructions -->
        <?php if (!MAILTRAP_ENABLED || MAILTRAP_API_TOKEN === 'YOUR_MAILTRAP_API_TOKEN'): ?>
        <div class="instructions">
            <h3>⚙️ Setup Instructions</h3>
            <ol>
                <li><strong>Get Mailtrap Credentials:</strong>
                    <ul>
                        <li>Go to <a href="https://mailtrap.io" target="_blank">Mailtrap.io</a> and create an account</li>
                        <li>Create a new inbox</li>
                        <li>Go to Sending Domain settings</li>
                        <li>Copy your API Token and Inbox ID</li>
                    </ul>
                </li>
                <li><strong>Configure Environment Variables:</strong>
                    <p>Create or edit <code>.env</code> file in your root directory:</p>
                    <div class="env-example">
MAILTRAP_API_TOKEN=your_token_here
MAILTRAP_INBOX_ID=your_inbox_id
APP_ENV=development
                    </div>
                </li>
                <li><strong>Or configure directly in code:</strong>
                    <p>Edit <code>config/mailtrap.php</code> and replace the placeholder values</p>
                </li>
                <li><strong>Test the configuration:</strong>
                    <p>Use the testing form below</p>
                </li>
            </ol>
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

        <!-- Test Email Sending -->
        <div class="config-section">
            <h2>🧪 Test Email Sending</h2>
            
            <?php if ($testMode): ?>
                <?php
                $action = $_POST['action'] ?? '';
                
                if ($action === 'test_basic') {
                    $testEmail = sanitize($_POST['test_email'] ?? $_SESSION['email']);
                    $result = testMailtrapConfiguration($testEmail);
                    $results['basic'] = $result;
                }
                elseif ($action === 'test_payment') {
                    $testEmail = sanitize($_POST['test_email'] ?? $_SESSION['email']);
                    $result = sendPaymentConfirmationEmail($testEmail, 'Test User', [
                        'payment_id' => 'TEST-' . time(),
                        'amount' => 5000,
                        'payment_method' => 'Credit Card',
                        'status' => 'Success',
                        'payment_date' => date('M d, Y H:i A'),
                    ]);
                    $results['payment'] = $result;
                }
                elseif ($action === 'test_reservation') {
                    $testEmail = sanitize($_POST['test_email'] ?? $_SESSION['email']);
                    $result = sendReservationConfirmationEmail($testEmail, 'Test User', [
                        'reservation_id' => 'RES-' . time(),
                        'equipment_name' => 'Test Equipment',
                        'reservation_date' => 'May 15, 2026',
                        'start_time' => '09:00 AM',
                        'end_time' => '10:00 AM',
                        'duration' => 60,
                    ]);
                    $results['reservation'] = $result;
                }
                elseif ($action === 'test_welcome') {
                    $testEmail = sanitize($_POST['test_email'] ?? $_SESSION['email']);
                    $result = sendMemberWelcomeEmail($testEmail, 'Test User', [
                        'username' => 'testuser',
                        'member_id' => 'MEM-' . time(),
                        'membership_type' => 'Premium',
                        'membership_expiry' => date('M d, Y', strtotime('+1 month')),
                    ]);
                    $results['welcome'] = $result;
                }
                
                // Display results
                foreach ($results as $testType => $result) {
                    if ($result['success']) {
                        echo '<div class="test-result success">';
                        echo '<strong>✓ ' . ucfirst($testType) . ' Test Successful!</strong><br>';
                        echo 'Email sent to: <code>' . htmlspecialchars($testEmail) . '</code><br>';
                        if (!empty($result['message_id'])) {
                            echo 'Message ID: <code>' . htmlspecialchars($result['message_id']) . '</code>';
                        }
                        echo '</div>';
                    } else {
                        echo '<div class="test-result error">';
                        echo '<strong>✗ ' . ucfirst($testType) . ' Test Failed</strong><br>';
                        echo 'Error: ' . htmlspecialchars($result['message']);
                        echo '</div>';
                    }
                }
                ?>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label for="test_email">Test Email Address:</label>
                    <input type="email" id="test_email" name="test_email" value="<?php echo htmlspecialchars($testEmail); ?>" required>
                    <small>Email to send test messages to</small>
                </div>

                <div class="btn-group">
                    <button type="submit" name="action" value="test_basic" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Send Basic Test
                    </button>
                    <button type="submit" name="action" value="test_payment" class="btn btn-primary">
                        <i class="fas fa-receipt"></i> Send Payment Test
                    </button>
                    <button type="submit" name="action" value="test_reservation" class="btn btn-primary">
                        <i class="fas fa-calendar"></i> Send Reservation Test
                    </button>
                    <button type="submit" name="action" value="test_welcome" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> Send Welcome Test
                    </button>
                </div>
            </form>
        </div>

        <!-- Notification Triggers -->
        <div class="config-section">
            <h2>🔔 Notification Triggers</h2>
            <p>Emails are automatically sent when:</p>
            <ul>
                <li><strong>Payment Recorded:</strong> Admin records a payment → Member gets payment confirmation email</li>
                <li><strong>Reservation Created:</strong> Member/Admin creates reservation → Confirmation email sent</li>
                <li><strong>Member Registered:</strong> New member account created → Welcome email sent</li>
                <li><strong>Password Reset Requested:</strong> User requests password reset → Reset link sent</li>
                <li><strong>Membership Expiring:</strong> Scheduled task → Expiration reminder sent</li>
                <li><strong>Trainer Assigned:</strong> Trainer assigned to member → Assignment email sent</li>
                <li><strong>Workout Plan Created:</strong> Trainer creates plan → Notification sent</li>
                <li><strong>Class Reminder:</strong> Scheduled task → Class reminder sent</li>
                <li><strong>Reservation Cancelled:</strong> Member/Admin cancels → Cancellation email sent</li>
            </ul>
        </div>

        <!-- Help & Support -->
        <div class="config-section">
            <h2>❓ Help & Support</h2>
            <ul>
                <li><strong>Mailtrap Documentation:</strong> <a href="https://mailtrap.io/api/" target="_blank">https://mailtrap.io/api/</a></li>
                <li><strong>Email Templates Location:</strong> <code><?php echo EMAIL_TEMPLATE_DIR; ?></code></li>
                <li><strong>Configuration File:</strong> <code>config/mailtrap.php</code></li>
                <li><strong>Email Functions:</strong> <code>includes/email-notifications.php</code></li>
                <li><strong>Mailtrap Service Class:</strong> <code>config/MailtrapService.php</code></li>
            </ul>

            <p><a href="<?php echo APP_URL; ?>dashboard/admin-dashboard.php" class="btn btn-primary">← Back to Dashboard</a></p>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
