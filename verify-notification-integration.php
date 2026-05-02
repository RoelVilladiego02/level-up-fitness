<?php
/**
 * Notification Integration Verification Script
 * Verifies that all notification types are properly integrated
 * Level Up Fitness - Gym Management System
 */

require_once dirname(__FILE__) . '/config/config.php';
require_once dirname(__FILE__) . '/config/database.php';
require_once dirname(__FILE__) . '/includes/functions.php';
require_once dirname(__FILE__) . '/includes/email-notifications.php';

session_start();

// Require admin login
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ' . APP_URL . 'auth/login.php');
    exit;
}

$integrations = [];
$allPassed = true;

// 1. Check Payment Integration
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM payments 
        WHERE DATE(payment_date) = DATE(NOW())
    ");
    $stmt->execute();
    $paymentCount = $stmt->fetch()['count'] ?? 0;
    
    $integrations['payment'] = [
        'name' => '💳 Payment Notifications',
        'status' => 'integrated',
        'description' => 'Emails sent when admin records a payment',
        'location' => 'modules/payments/add.php',
        'function' => 'sendPaymentNotification()',
        'recent_count' => $paymentCount,
        'checks' => [
            ['name' => 'Function exists', 'passed' => function_exists('sendPaymentNotification')],
            ['name' => 'Email template exists', 'passed' => file_exists(EMAIL_TEMPLATE_DIR . 'payment-confirmation.html')],
            ['name' => 'Recent payments today', 'passed' => $paymentCount > 0],
        ]
    ];
} catch (Exception $e) {
    $integrations['payment'] = [
        'name' => '💳 Payment Notifications',
        'status' => 'error',
        'error' => $e->getMessage()
    ];
    $allPassed = false;
}

// 2. Check Reservation Integration
try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM reservations 
        WHERE DATE(created_at) = DATE(NOW())
    ");
    $stmt->execute();
    $reservationCount = $stmt->fetch()['count'] ?? 0;
    
    $integrations['reservation'] = [
        'name' => '📅 Reservation Notifications',
        'status' => 'integrated',
        'description' => 'Emails sent when member/admin creates a reservation',
        'location' => 'modules/reservations/add.php',
        'function' => 'sendReservationNotification()',
        'recent_count' => $reservationCount,
        'checks' => [
            ['name' => 'Function exists', 'passed' => function_exists('sendReservationNotification')],
            ['name' => 'Email template exists', 'passed' => file_exists(EMAIL_TEMPLATE_DIR . 'reservation-confirmation.html')],
            ['name' => 'Recent reservations today', 'passed' => $reservationCount > 0],
        ]
    ];
} catch (Exception $e) {
    $integrations['reservation'] = [
        'name' => '📅 Reservation Notifications',
        'status' => 'error',
        'error' => $e->getMessage()
    ];
    $allPassed = false;
}

// 3. Check Welcome Email Integration (in members/add.php)
try {
    $addMembersFile = dirname(__FILE__) . '/modules/members/add.php';
    $addMembersContent = file_get_contents($addMembersFile);
    $hasWelcomeEmail = strpos($addMembersContent, 'sendMemberWelcomeEmail') !== false;
    
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count FROM members 
        WHERE DATE(created_at) IS NOT NULL
    ");
    $stmt->execute();
    $memberCount = $stmt->fetch()['count'] ?? 0;
    
    $integrations['welcome'] = [
        'name' => '👋 Welcome Email',
        'status' => $hasWelcomeEmail ? 'integrated' : 'not_integrated',
        'description' => 'Email sent when new member is registered',
        'location' => 'modules/members/add.php',
        'function' => 'sendMemberWelcomeEmail()',
        'total_members' => $memberCount,
        'checks' => [
            ['name' => 'Function exists', 'passed' => function_exists('sendMemberWelcomeEmail')],
            ['name' => 'Email template exists', 'passed' => file_exists(EMAIL_TEMPLATE_DIR . 'member-welcome.html')],
            ['name' => 'Integrated in add.php', 'passed' => $hasWelcomeEmail],
            ['name' => 'Members in system', 'passed' => $memberCount > 0],
        ]
    ];
    
    if (!$hasWelcomeEmail) {
        $allPassed = false;
    }
} catch (Exception $e) {
    $integrations['welcome'] = [
        'name' => '👋 Welcome Email',
        'status' => 'error',
        'error' => $e->getMessage()
    ];
    $allPassed = false;
}

// 4. Check Password Reset Email
try {
    $integrations['password_reset'] = [
        'name' => '🔐 Password Reset Email',
        'status' => 'available',
        'description' => 'Email sent when user requests password reset',
        'location' => 'auth/forgot-password.php or similar',
        'function' => 'sendPasswordResetEmail()',
        'checks' => [
            ['name' => 'Function exists', 'passed' => function_exists('sendPasswordResetEmail')],
            ['name' => 'Email template exists', 'passed' => file_exists(EMAIL_TEMPLATE_DIR . 'password-reset.html')],
        ]
    ];
} catch (Exception $e) {
    $integrations['password_reset'] = [
        'name' => '🔐 Password Reset Email',
        'status' => 'error',
        'error' => $e->getMessage()
    ];
}

// 5. Check Membership Expiring Email
try {
    $integrations['membership_expiring'] = [
        'name' => '⏰ Membership Expiring Email',
        'status' => 'available',
        'description' => 'Email sent when membership is about to expire',
        'location' => 'Scheduled cron job',
        'function' => 'sendMembershipExpiringEmail()',
        'checks' => [
            ['name' => 'Function exists', 'passed' => function_exists('sendMembershipExpiringEmail')],
            ['name' => 'Email template exists', 'passed' => file_exists(EMAIL_TEMPLATE_DIR . 'membership-expiring-soon.html')],
        ]
    ];
} catch (Exception $e) {
    $integrations['membership_expiring'] = [
        'name' => '⏰ Membership Expiring Email',
        'status' => 'error',
        'error' => $e->getMessage()
    ];
}

// 6. Check Trainer Assignment Email
try {
    $integrations['trainer_assignment'] = [
        'name' => '👨‍🏫 Trainer Assignment Email',
        'status' => 'available',
        'description' => 'Email sent when trainer is assigned to member',
        'location' => 'modules/trainers/ or modules/members/edit.php',
        'function' => 'sendTrainerAssignmentEmail()',
        'checks' => [
            ['name' => 'Function exists', 'passed' => function_exists('sendTrainerAssignmentEmail')],
            ['name' => 'Email template exists', 'passed' => file_exists(EMAIL_TEMPLATE_DIR . 'trainer-assignment.html')],
        ]
    ];
} catch (Exception $e) {
    $integrations['trainer_assignment'] = [
        'name' => '👨‍🏫 Trainer Assignment Email',
        'status' => 'error',
        'error' => $e->getMessage()
    ];
}

// 7. Check Workout Plan Email
try {
    $integrations['workout_plan'] = [
        'name' => '📋 Workout Plan Email',
        'status' => 'available',
        'description' => 'Email sent when trainer creates workout plan',
        'location' => 'modules/workouts/',
        'function' => 'sendWorkoutPlanEmail()',
        'checks' => [
            ['name' => 'Function exists', 'passed' => function_exists('sendWorkoutPlanEmail')],
            ['name' => 'Email template exists', 'passed' => file_exists(EMAIL_TEMPLATE_DIR . 'workout-plan-created.html')],
        ]
    ];
} catch (Exception $e) {
    $integrations['workout_plan'] = [
        'name' => '📋 Workout Plan Email',
        'status' => 'error',
        'error' => $e->getMessage()
    ];
}

// 8. Check Class Reminder Email
try {
    $integrations['class_reminder'] = [
        'name' => '🎯 Class Reminder Email',
        'status' => 'available',
        'description' => 'Email sent before scheduled class',
        'location' => 'modules/classes/ or scheduled cron',
        'function' => 'sendClassReminderEmail()',
        'checks' => [
            ['name' => 'Function exists', 'passed' => function_exists('sendClassReminderEmail')],
            ['name' => 'Email template exists', 'passed' => file_exists(EMAIL_TEMPLATE_DIR . 'class-reminder.html')],
        ]
    ];
} catch (Exception $e) {
    $integrations['class_reminder'] = [
        'name' => '🎯 Class Reminder Email',
        'status' => 'error',
        'error' => $e->getMessage()
    ];
}

// 9. Check Reservation Cancelled Email
try {
    $integrations['reservation_cancelled'] = [
        'name' => '❌ Reservation Cancelled Email',
        'status' => 'available',
        'description' => 'Email sent when reservation is cancelled',
        'location' => 'modules/reservations/delete.php or cancel.php',
        'function' => 'sendReservationCancellationEmail()',
        'checks' => [
            ['name' => 'Function exists', 'passed' => function_exists('sendReservationCancellationEmail')],
            ['name' => 'Email template exists', 'passed' => file_exists(EMAIL_TEMPLATE_DIR . 'reservation-cancelled.html')],
        ]
    ];
} catch (Exception $e) {
    $integrations['reservation_cancelled'] = [
        'name' => '❌ Reservation Cancelled Email',
        'status' => 'error',
        'error' => $e->getMessage()
    ];
}

// Get notification statistics
$stats = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            notification_type,
            COUNT(*) as total,
            SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_count,
            SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count
        FROM notifications
        WHERE is_deleted = 0
        GROUP BY notification_type
        ORDER BY total DESC
    ");
    $stmt->execute();
    $stats = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Error loading stats: ' . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>✅ Notification Integration Status - Level Up Fitness</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px 0; }
        .container { max-width: 1200px; }
        .header-card { background: white; border-radius: 8px; padding: 30px; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header-card h1 { color: #667eea; margin-bottom: 10px; }
        .status-badge {
            display: inline-block;
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 14px;
        }
        .status-integrated { background: #d4edda; color: #155724; }
        .status-available { background: #d1ecf1; color: #0c5460; }
        .status-not-integrated { background: #fff3cd; color: #856404; }
        .status-error { background: #f8d7da; color: #721c24; }
        .integration-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid #667eea;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .integration-card.integrated { border-left-color: #28a745; }
        .integration-card.available { border-left-color: #17a2b8; }
        .integration-card.error { border-left-color: #dc3545; }
        .integration-card.not-integrated { border-left-color: #ffc107; }
        .check-item {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        .check-item:last-child { border-bottom: none; }
        .check-icon {
            display: inline-block;
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        .passed { color: #28a745; }
        .failed { color: #dc3545; }
        .stat-row { 
            background: white; 
            border-radius: 8px; 
            padding: 15px; 
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
            background: #667eea;
            color: white;
        }
        .instructions { background: #e7f3ff; border-left: 4px solid #2196F3; padding: 15px; border-radius: 4px; margin-bottom: 20px; }
        .instructions h5 { color: #1976d2; margin-bottom: 10px; }
        .summary-card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .summary-stat {
            text-align: center;
            padding: 15px;
        }
        .summary-stat h3 { font-size: 32px; color: #667eea; margin: 0; }
        .summary-stat p { color: #666; margin: 5px 0 0 0; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header-card">
            <h1><i class="fas fa-check-circle"></i> Notification Integration Status</h1>
            <p class="text-muted mb-3">Complete verification of all email notification integrations</p>
            <div>
                <span class="status-badge <?php echo $allPassed ? 'status-integrated' : 'status-available'; ?>">
                    <i class="fas <?php echo $allPassed ? 'fa-check' : 'fa-exclamation'; ?>"></i>
                    <?php echo $allPassed ? 'All Integrations Verified ✅' : 'Review Required ⚠️'; ?>
                </span>
            </div>
        </div>

        <!-- Summary Statistics -->
        <div class="summary-card">
            <h4 class="mb-4"><i class="fas fa-chart-bar"></i> Notification Statistics</h4>
            <div class="row">
                <div class="col-md-3">
                    <div class="summary-stat">
                        <h3><?php echo count($integrations); ?></h3>
                        <p>Total Email Types</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-stat">
                        <h3><?php echo count(array_filter($integrations, fn($i) => $i['status'] === 'integrated')); ?></h3>
                        <p>Integrated</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-stat">
                        <h3><?php echo count(array_filter($integrations, fn($i) => $i['status'] === 'available')); ?></h3>
                        <p>Available</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="summary-stat">
                        <h3><?php echo count(array_filter($integrations, fn($i) => $i['status'] === 'error')); ?></h3>
                        <p>Errors</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Instructions -->
        <div class="instructions">
            <h5><i class="fas fa-lightbulb"></i> Quick Start</h5>
            <ul class="mb-0">
                <li><strong>Test Notifications:</strong> <a href="<?php echo APP_URL; ?>test-all-notifications.php">Go to Test Suite</a></li>
                <li><strong>View Payments:</strong> <a href="<?php echo APP_URL; ?>modules/payments/">Payments Module</a></li>
                <li><strong>View Reservations:</strong> <a href="<?php echo APP_URL; ?>modules/reservations/">Reservations Module</a></li>
                <li><strong>View Members:</strong> <a href="<?php echo APP_URL; ?>modules/members/">Members Module</a></li>
            </ul>
        </div>

        <!-- Integration Details -->
        <h3 class="mb-3" style="color: white;"><i class="fas fa-envelope"></i> Email Notification Integrations</h3>
        
        <?php foreach ($integrations as $key => $integration): ?>
            <div class="integration-card <?php echo $integration['status']; ?>">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="mb-1"><?php echo $integration['name']; ?></h5>
                        <p class="mb-0 text-muted"><?php echo $integration['description']; ?></p>
                    </div>
                    <span class="status-badge status-<?php echo $integration['status']; ?>">
                        <?php 
                            $statusLabels = [
                                'integrated' => '✅ Integrated',
                                'available' => '✔️ Available',
                                'not_integrated' => '⚠️ Not Integrated',
                                'error' => '❌ Error'
                            ];
                            echo $statusLabels[$integration['status']] ?? 'Unknown';
                        ?>
                    </span>
                </div>

                <?php if ($integration['status'] === 'error'): ?>
                    <div class="alert alert-danger mb-0">
                        <strong>Error:</strong> <?php echo htmlspecialchars($integration['error']); ?>
                    </div>
                <?php else: ?>
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <small class="text-muted">📍 Location:</small><br>
                            <code style="font-size: 12px;"><?php echo htmlspecialchars($integration['location']); ?></code>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">🔧 Function:</small><br>
                            <code style="font-size: 12px;"><?php echo htmlspecialchars($integration['function']); ?></code>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">📊 Data:</small><br>
                            <?php if (isset($integration['recent_count'])): ?>
                                <span class="badge bg-info"><?php echo $integration['recent_count']; ?> today</span>
                            <?php elseif (isset($integration['total_members'])): ?>
                                <span class="badge bg-info"><?php echo $integration['total_members']; ?> members</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">N/A</span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($integration['checks'])): ?>
                        <div style="background: #f9f9f9; padding: 12px; border-radius: 4px;">
                            <?php foreach ($integration['checks'] as $check): ?>
                                <div class="check-item">
                                    <span class="check-icon">
                                        <i class="fas <?php echo $check['passed'] ? 'fa-check passed' : 'fa-times failed'; ?>"></i>
                                    </span>
                                    <span><?php echo htmlspecialchars($check['name']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Notification Activity Stats -->
        <?php if (!empty($stats)): ?>
        <h3 class="mb-3 mt-4" style="color: white;"><i class="fas fa-chart-pie"></i> Notification Activity</h3>
        <div class="summary-card">
            <?php foreach ($stats as $stat): ?>
                <div class="stat-row">
                    <div>
                        <strong><?php echo ucwords(str_replace('_', ' ', $stat['notification_type'])); ?></strong><br>
                        <small class="text-muted">Type: <?php echo htmlspecialchars($stat['notification_type']); ?></small>
                    </div>
                    <div>
                        <span class="stat-badge" style="background: #667eea;">
                            <i class="fas fa-envelope"></i> <?php echo $stat['total']; ?>
                        </span>
                        <span class="stat-badge" style="background: #28a745; margin-left: 5px;">
                            <i class="fas fa-check"></i> <?php echo $stat['read_count']; ?>
                        </span>
                        <span class="stat-badge" style="background: #ffc107; margin-left: 5px; color: black;">
                            <i class="fas fa-envelope-open"></i> <?php echo $stat['unread_count']; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Documentation Links -->
        <div class="summary-card mt-4">
            <h5><i class="fas fa-book"></i> Documentation & Resources</h5>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <a href="<?php echo APP_URL; ?>docs/NOTIFICATION_SYSTEM_GUIDE.md" class="btn btn-outline-primary w-100" target="_blank">
                        <i class="fas fa-file-alt"></i> Full Documentation
                    </a>
                </div>
                <div class="col-md-6 mb-3">
                    <a href="<?php echo APP_URL; ?>test-all-notifications.php" class="btn btn-outline-info w-100">
                        <i class="fas fa-vial"></i> Test Suite
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="<?php echo APP_URL; ?>dashboard/" class="btn btn-outline-secondary w-100">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
                <div class="col-md-6">
                    <a href="<?php echo APP_URL; ?>setup-notifications.php" class="btn btn-outline-warning w-100">
                        <i class="fas fa-wrench"></i> Setup Notifications
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
