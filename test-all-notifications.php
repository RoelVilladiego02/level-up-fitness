<?php
/**
 * Comprehensive Notification Test Suite
 * Tests all email notification types and integrations
 * Level Up Fitness - Gym Management System
 */

require_once dirname(__FILE__) . '/config/config.php';
require_once dirname(__FILE__) . '/config/database.php';
require_once dirname(__FILE__) . '/includes/functions.php';
require_once dirname(__FILE__) . '/includes/email-notifications.php';

session_start();

// Require admin login
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . APP_URL . 'auth/login.php');
    exit;
}

if ($_SESSION['user_type'] !== 'admin') {
    die('Only admins can access this page');
}

$testResults = [];
$testEmail = $_SESSION['email'];

// Handle test requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = sanitize($_POST['action'] ?? '');
    $testEmail = sanitize($_POST['test_email'] ?? $_SESSION['email']);
    
    if (!isValidEmail($testEmail)) {
        $testResults['error'] = 'Invalid test email address';
    } else {
        try {
            switch ($action) {
                case 'test_payment':
                    $testResults['payment'] = sendPaymentConfirmationEmail($testEmail, 'Test Member', [
                        'payment_id' => 'TEST-PAY-' . date('YmdHis'),
                        'amount' => 5000.00,
                        'payment_method' => 'Credit Card',
                        'status' => 'Paid',
                        'payment_date' => date('M d, Y H:i A'),
                        'membership_type' => 'Premium',
                        'membership_start' => date('M d, Y'),
                        'membership_end' => date('M d, Y', strtotime('+1 month')),
                    ]);
                    break;

                case 'test_reservation':
                    $testResults['reservation'] = sendReservationConfirmationEmail($testEmail, 'Test Member', [
                        'reservation_id' => 'TEST-RES-' . date('YmdHis'),
                        'equipment_name' => 'Treadmill 01',
                        'reservation_date' => date('M d, Y', strtotime('+2 days')),
                        'start_time' => '09:00 AM',
                        'end_time' => '10:00 AM',
                        'duration' => 60,
                        'trainer_name' => 'Coach John',
                        'gym_address' => 'Level Up Fitness',
                        'cancellation_deadline' => date('M d, Y', strtotime('+1 day')),
                    ]);
                    break;

                case 'test_welcome':
                    $testResults['welcome'] = sendMemberWelcomeEmail($testEmail, 'Test Member', [
                        'username' => 'testmember',
                        'member_id' => 'TEST-MEM-' . date('YmdHis'),
                        'membership_type' => 'Premium',
                        'membership_expiry' => date('M d, Y', strtotime('+1 year')),
                        'trainer_name' => 'Coach John',
                        'trainer_email' => 'coach@levelupfitness.local',
                    ]);
                    break;

                case 'test_password_reset':
                    $resetToken = bin2hex(random_bytes(16));
                    $testResults['password_reset'] = sendPasswordResetEmail($testEmail, 'Test Member', $resetToken, 24);
                    break;

                case 'test_membership_expiring':
                    $testResults['membership_expiring'] = sendMembershipExpiringEmail($testEmail, 'Test Member', [
                        'membership_type' => 'Premium',
                        'expiration_date' => date('Y-m-d', strtotime('+5 days')),
                    ]);
                    break;

                case 'test_trainer_assignment':
                    $testResults['trainer_assignment'] = sendTrainerAssignmentEmail($testEmail, 'Test Member', [
                        'trainer_name' => 'Coach John',
                        'trainer_email' => 'coach@levelupfitness.local',
                        'trainer_phone' => '09123456789',
                        'trainer_specialization' => 'Strength & Conditioning',
                        'trainer_bio' => 'Experienced fitness trainer with 10+ years',
                        'session_date' => date('M d, Y', strtotime('+3 days')),
                        'session_time' => '10:00 AM',
                        'session_location' => 'Studio A',
                    ]);
                    break;

                case 'test_workout_plan':
                    $testResults['workout_plan'] = sendWorkoutPlanEmail($testEmail, 'Test Member', [
                        'plan_name' => 'Strength Building Program',
                        'trainer_name' => 'Coach John',
                        'trainer_email' => 'coach@levelupfitness.local',
                        'duration_weeks' => 12,
                        'focus_area' => 'Upper Body Strength',
                        'difficulty_level' => 'Intermediate',
                        'sessions_per_week' => 4,
                        'description' => 'Comprehensive strength training program for upper body development',
                        'plan_id' => 'TEST-PLAN-' . date('YmdHis'),
                    ]);
                    break;

                case 'test_class_reminder':
                    $testResults['class_reminder'] = sendClassReminderEmail($testEmail, 'Test Member', [
                        'class_name' => 'Advanced HIIT Training',
                        'trainer_name' => 'Coach Maria',
                        'class_date' => date('M d, Y', strtotime('+1 day')),
                        'start_time' => '06:00 PM',
                        'end_time' => '07:00 PM',
                        'class_location' => 'Studio B',
                        'current_participants' => 12,
                        'max_capacity' => 20,
                        'description' => 'High intensity interval training for cardio and fat burning',
                        'class_id' => 'TEST-CLASS-' . date('YmdHis'),
                    ]);
                    break;

                case 'test_reservation_cancelled':
                    $testResults['reservation_cancelled'] = sendReservationCancellationEmail($testEmail, 'Test Member', [
                        'reservation_id' => 'TEST-RES-' . date('YmdHis'),
                        'equipment_name' => 'Treadmill 01',
                        'reservation_date' => date('M d, Y'),
                        'start_time' => '09:00 AM',
                        'end_time' => '10:00 AM',
                        'reason' => 'Cancelled by admin due to maintenance',
                        'cancellation_date' => date('M d, Y H:i A'),
                        'refund_amount' => 500.00,
                        'refund_days' => 3,
                        'cancellation_fee' => 100.00,
                    ]);
                    break;

                case 'test_all':
                    // Send all tests
                    $tests = ['test_payment', 'test_reservation', 'test_welcome', 'test_password_reset', 
                              'test_membership_expiring', 'test_trainer_assignment', 'test_workout_plan', 
                              'test_class_reminder', 'test_reservation_cancelled'];
                    
                    foreach ($tests as $test) {
                        $_POST['action'] = $test;
                        header('Location: ' . APP_URL . 'test-all-notifications.php?action=' . $test);
                    }
                    break;
            }
        } catch (Exception $e) {
            $testResults['error'] = 'Error: ' . $e->getMessage();
            error_log('Notification test error: ' . $e->getMessage());
        }
    }
}

// Get all available members for integration testing
$members = [];
try {
    $stmt = $pdo->prepare("
        SELECT m.member_id, m.member_name, m.email, u.user_id, m.status 
        FROM members m 
        LEFT JOIN users u ON m.user_id = u.user_id 
        WHERE m.status = 'Active' 
        ORDER BY m.member_name 
        LIMIT 10
    ");
    $stmt->execute();
    $members = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Error loading members: ' . $e->getMessage());
}

// Get recent notifications from database
$recentNotifications = [];
try {
    $stmt = $pdo->prepare("
        SELECT n.*, u.email, u.full_name
        FROM notifications n
        LEFT JOIN users u ON n.user_id = u.user_id
        ORDER BY n.created_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    $recentNotifications = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Error loading recent notifications: ' . $e->getMessage());
}

// Get notification stats
$stats = [
    'total_sent' => 0,
    'total_read' => 0,
    'total_unread' => 0,
    'by_type' => []
];

try {
    // Total stats
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE is_deleted = 0");
    $stmt->execute();
    $result = $stmt->fetch();
    $stats['total_sent'] = $result['count'] ?? 0;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE is_read = 1 AND is_deleted = 0");
    $stmt->execute();
    $result = $stmt->fetch();
    $stats['total_read'] = $result['count'] ?? 0;
    
    $stats['total_unread'] = $stats['total_sent'] - $stats['total_read'];
    
    // By type
    $stmt = $pdo->prepare("
        SELECT notification_type, COUNT(*) as count 
        FROM notifications 
        WHERE is_deleted = 0 
        GROUP BY notification_type 
        ORDER BY count DESC
    ");
    $stmt->execute();
    $typeResults = $stmt->fetchAll();
    foreach ($typeResults as $row) {
        $stats['by_type'][$row['notification_type']] = $row['count'];
    }
} catch (Exception $e) {
    error_log('Error loading notification stats: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 Notification Test Suite - Level Up Fitness</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; }
        .test-container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .test-section { 
            background: white; 
            border-radius: 8px; 
            padding: 20px; 
            margin-bottom: 20px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .test-button { margin: 5px; }
        .success-badge { background: #28a745; color: white; padding: 8px 15px; border-radius: 4px; }
        .error-badge { background: #dc3545; color: white; padding: 8px 15px; border-radius: 4px; }
        .pending-badge { background: #ffc107; color: black; padding: 8px 15px; border-radius: 4px; }
        .stat-card { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            color: white; 
            padding: 20px; 
            border-radius: 8px; 
            text-align: center;
            margin-bottom: 15px;
        }
        .stat-card h3 { font-size: 28px; font-weight: bold; margin: 0; }
        .stat-card p { font-size: 14px; margin: 5px 0 0 0; opacity: 0.9; }
        .notification-item {
            border-left: 4px solid #667eea;
            padding: 12px;
            margin-bottom: 10px;
            background: #f9f9f9;
            border-radius: 4px;
        }
        .notification-item.read {
            opacity: 0.7;
        }
        .test-result {
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
            border-left: 4px solid;
        }
        .test-result.success {
            background: #d4edda;
            border-color: #28a745;
            color: #155724;
        }
        .test-result.error {
            background: #f8d7da;
            border-color: #dc3545;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="test-container">
        <div class="test-section">
            <h1><i class="fas fa-vial"></i> 🧪 Notification Test Suite</h1>
            <p class="text-muted">Test all email notification types and integrations</p>
        </div>

        <!-- Statistics -->
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <h3><?php echo $stats['total_sent']; ?></h3>
                    <p>Total Notifications Sent</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <h3><?php echo $stats['total_unread']; ?></h3>
                    <p>Unread Notifications</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <h3><?php echo $stats['total_read']; ?></h3>
                    <p>Read Notifications</p>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                    <h3><?php echo count($stats['by_type']); ?></h3>
                    <p>Notification Types</p>
                </div>
            </div>
        </div>

        <!-- Test Results -->
        <?php if (!empty($testResults)): ?>
        <div class="test-section alert alert-info">
            <h5><i class="fas fa-clipboard-check"></i> Test Results</h5>
            <?php foreach ($testResults as $type => $result): ?>
                <?php 
                    $success = is_array($result) && ($result['success'] ?? false) === true;
                    $message = is_array($result) ? ($result['message'] ?? 'Test completed') : $result;
                ?>
                <div class="test-result <?php echo $success ? 'success' : 'error'; ?>">
                    <strong><?php echo ucwords(str_replace('_', ' ', $type)); ?>:</strong>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Individual Tests -->
        <div class="test-section">
            <h3><i class="fas fa-envelope"></i> Test Individual Email Notifications</h3>
            <p class="text-muted mb-3">Enter your test email address and click any test button:</p>
            
            <form method="POST" class="mb-4">
                <div class="row align-items-end">
                    <div class="col-md-8">
                        <label for="test_email" class="form-label">Test Email Address</label>
                        <input type="email" class="form-control" id="test_email" name="test_email" 
                               value="<?php echo htmlspecialchars($testEmail); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" name="action" value="test_all" class="btn btn-success w-100">
                            <i class="fas fa-play"></i> Test All
                        </button>
                    </div>
                </div>
            </form>

            <div class="row">
                <form method="POST" class="col-md-6 mb-3">
                    <input type="hidden" name="test_email" value="<?php echo htmlspecialchars($testEmail); ?>">
                    <div class="btn-group w-100" role="group">
                        <button type="submit" name="action" value="test_payment" class="btn btn-primary test-button" title="💳 Payment Confirmation">
                            <i class="fas fa-credit-card"></i> Payment
                        </button>
                        <button type="submit" name="action" value="test_reservation" class="btn btn-primary test-button" title="📅 Reservation Confirmation">
                            <i class="fas fa-calendar"></i> Reservation
                        </button>
                        <button type="submit" name="action" value="test_welcome" class="btn btn-primary test-button" title="👋 Welcome Email">
                            <i class="fas fa-user-plus"></i> Welcome
                        </button>
                    </div>
                </form>

                <form method="POST" class="col-md-6 mb-3">
                    <input type="hidden" name="test_email" value="<?php echo htmlspecialchars($testEmail); ?>">
                    <div class="btn-group w-100" role="group">
                        <button type="submit" name="action" value="test_password_reset" class="btn btn-warning test-button" title="🔐 Password Reset">
                            <i class="fas fa-lock"></i> Password Reset
                        </button>
                        <button type="submit" name="action" value="test_membership_expiring" class="btn btn-warning test-button" title="⏰ Membership Expiring">
                            <i class="fas fa-hourglass-end"></i> Expiring
                        </button>
                        <button type="submit" name="action" value="test_trainer_assignment" class="btn btn-warning test-button" title="👨‍🏫 Trainer Assignment">
                            <i class="fas fa-user-tie"></i> Trainer
                        </button>
                    </div>
                </form>

                <form method="POST" class="col-md-6">
                    <input type="hidden" name="test_email" value="<?php echo htmlspecialchars($testEmail); ?>">
                    <div class="btn-group w-100" role="group">
                        <button type="submit" name="action" value="test_workout_plan" class="btn btn-info test-button" title="📋 Workout Plan">
                            <i class="fas fa-dumbbell"></i> Workout Plan
                        </button>
                        <button type="submit" name="action" value="test_class_reminder" class="btn btn-info test-button" title="🎯 Class Reminder">
                            <i class="fas fa-bell"></i> Class Reminder
                        </button>
                        <button type="submit" name="action" value="test_reservation_cancelled" class="btn btn-danger test-button" title="❌ Reservation Cancelled">
                            <i class="fas fa-times-circle"></i> Cancelled
                        </button>
                    </div>
                </form>

                <form method="POST" class="col-md-6">
                    <input type="hidden" name="test_email" value="<?php echo htmlspecialchars($testEmail); ?>">
                    <div class="btn-group w-100" role="group">
                        <button type="submit" name="action" value="test_all" class="btn btn-success test-button" title="Run all tests">
                            <i class="fas fa-play-circle"></i> Run All Tests
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Notification Statistics by Type -->
        <div class="test-section">
            <h3><i class="fas fa-chart-pie"></i> Notifications by Type</h3>
            <?php if (!empty($stats['by_type'])): ?>
            <div class="row">
                <?php foreach ($stats['by_type'] as $type => $count): ?>
                <div class="col-md-4 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title"><?php echo ucwords(str_replace('_', ' ', $type)); ?></h6>
                            <p class="card-text" style="font-size: 24px; font-weight: bold; margin: 0;">
                                <?php echo $count; ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-muted">No notifications sent yet</p>
            <?php endif; ?>
        </div>

        <!-- Recent Notifications -->
        <div class="test-section">
            <h3><i class="fas fa-history"></i> Recent Notifications</h3>
            <?php if (!empty($recentNotifications)): ?>
            <div class="notification-list">
                <?php foreach ($recentNotifications as $notif): ?>
                <div class="notification-item <?php echo $notif['is_read'] ? 'read' : ''; ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <strong><?php echo htmlspecialchars($notif['title'] ?? 'Notification'); ?></strong>
                            <p class="mb-1 text-muted" style="font-size: 14px;">
                                <?php echo htmlspecialchars($notif['message'] ?? ''); ?>
                            </p>
                            <small class="text-muted">
                                <i class="fas fa-tag"></i> <?php echo ucwords(str_replace('_', ' ', $notif['notification_type'])); ?> | 
                                <i class="fas fa-clock"></i> <?php echo formatDate($notif['created_at']); ?>
                            </small>
                        </div>
                        <span class="badge <?php echo $notif['is_read'] ? 'bg-secondary' : 'bg-primary'; ?>">
                            <?php echo $notif['is_read'] ? 'Read' : 'Unread'; ?>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <p class="text-muted">No notifications yet</p>
            <?php endif; ?>
        </div>

        <!-- Available Members for Integration Testing -->
        <div class="test-section">
            <h3><i class="fas fa-users"></i> Active Members for Integration Testing</h3>
            <?php if (!empty($members)): ?>
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Member ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($members as $member): ?>
                        <tr>
                            <td><code><?php echo htmlspecialchars($member['member_id']); ?></code></td>
                            <td><?php echo htmlspecialchars($member['member_name']); ?></td>
                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                            <td><span class="badge bg-success"><?php echo htmlspecialchars($member['status']); ?></span></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="test_email" value="<?php echo htmlspecialchars($member['email']); ?>">
                                    <button type="submit" name="action" value="test_payment" class="btn btn-sm btn-outline-primary" title="Send test payment email">
                                        <i class="fas fa-envelope"></i> Test
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <p class="text-muted">No active members found</p>
            <?php endif; ?>
        </div>

        <!-- Documentation -->
        <div class="test-section alert alert-info">
            <h5><i class="fas fa-info-circle"></i> Documentation</h5>
            <ul>
                <li><strong>Payment Notification:</strong> Sent when admin records a payment</li>
                <li><strong>Reservation Notification:</strong> Sent when member/admin creates a reservation</li>
                <li><strong>Welcome Email:</strong> Sent when new member is registered</li>
                <li><strong>Password Reset:</strong> Sent when user requests password reset</li>
                <li><strong>Membership Expiring:</strong> Sent when membership is about to expire</li>
                <li><strong>Trainer Assignment:</strong> Sent when trainer is assigned to member</li>
                <li><strong>Workout Plan:</strong> Sent when trainer creates workout plan</li>
                <li><strong>Class Reminder:</strong> Sent before scheduled class</li>
                <li><strong>Reservation Cancelled:</strong> Sent when reservation is cancelled</li>
            </ul>
            <p>
                <a href="<?php echo APP_URL; ?>docs/NOTIFICATION_SYSTEM_GUIDE.md" target="_blank" class="btn btn-sm btn-info">
                    <i class="fas fa-book"></i> Full Documentation
                </a>
                <a href="<?php echo APP_URL; ?>dashboard/" class="btn btn-sm btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </p>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
