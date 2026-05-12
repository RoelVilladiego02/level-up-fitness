<?php
/**
 * Member Dashboard
 * Level Up Fitness - Gym Management System
 */

// Include required files
require_once dirname(dirname(__FILE__)) . '/includes/header.php';

// Require login and member role
requireLogin();
requireRole('member');

$userInfo = getUserInfo();

// Get member details
try {
    $memberStmt = $pdo->prepare("SELECT * FROM members WHERE user_id = ?");
    $memberStmt->execute([$userInfo['user_id']]);
    $member = $memberStmt->fetch();
} catch (Exception $e) {
    error_log('Error fetching member details: ' . $e->getMessage());
    $member = [];
}

// Get member's assigned workouts
try {
    $workoutsStmt = $pdo->prepare("
        SELECT w.* FROM workouts w
        WHERE w.member_id = ?
        ORDER BY w.created_at DESC
        LIMIT 5
    ");
    $workoutsStmt->execute([$member['member_id'] ?? null]);
    $workouts = $workoutsStmt->fetchAll();
} catch (Exception $e) {
    error_log('Error fetching workouts: ' . $e->getMessage());
    $workouts = [];
}

// Get member's upcoming sessions
try {
    $sessionsStmt = $pdo->prepare("
        SELECT s.*, t.trainer_name FROM sessions s
        LEFT JOIN trainers t ON s.trainer_id = t.trainer_id
        WHERE s.member_id = ? AND s.session_date >= CURDATE()
        ORDER BY s.session_date ASC, s.session_time ASC
        LIMIT 5
    ");
    $sessionsStmt->execute([$member['member_id'] ?? null]);
    $sessions = $sessionsStmt->fetchAll();
} catch (Exception $e) {
    error_log('Error fetching sessions: ' . $e->getMessage());
    $sessions = [];
}

// Get member's recent attendance
try {
    $attendanceStmt = $pdo->prepare("
        SELECT a.* FROM attendance a
        WHERE a.member_id = ?
        ORDER BY a.attendance_date DESC
        LIMIT 5
    ");
    $attendanceStmt->execute([$member['member_id'] ?? null]);
    $attendance = $attendanceStmt->fetchAll();
} catch (Exception $e) {
    error_log('Error fetching attendance: ' . $e->getMessage());
    $attendance = [];
}

// Get member's outstanding balance
try {
    $outstandingBalance = getMemberOutstandingBalance($member['member_id'] ?? null);
} catch (Exception $e) {
    error_log('Error fetching outstanding balance: ' . $e->getMessage());
    $outstandingBalance = ['outstanding_amount' => 0];
}

// Calculate membership status
$membershipStatus = 'Active';
$daysRemaining = 0;
if ($member && isset($member['join_date'], $member['membership_type'])) {
    $daysRemaining = getDaysUntilExpiry($member['join_date'], $member['membership_type']);
    if ($daysRemaining < 0) {
        $membershipStatus = 'Expired';
    } elseif ($daysRemaining < 7) {
        $membershipStatus = 'Expiring Soon';
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(__FILE__) . '/../includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            <!-- Top Navigation Bar -->
            <nav class="navbar navbar-expand-lg navbar-light bg-light mb-4 rounded">
                <div class="container-fluid">
                    <span class="navbar-brand">
                        <i class="fas fa-dumbbell" style="color: var(--primary-color);"></i> 
                        Level Up Fitness
                    </span>
                    <div class="d-flex align-items-center">
                        <span class="me-3">
                            Welcome, <strong><?php echo $userInfo['name']; ?></strong>
                        </span>
                    </div>
                </div>
            </nav>

            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-chart-line"></i> My Dashboard</h1>
                <p>Welcome back! Here's your fitness journey overview</p>
            </div>

            <!-- Membership Status Card -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card border-left-primary shadow-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="card-title"><i class="fas fa-id-card"></i> Membership Information</h5>
                                    <p class="mb-1"><strong>Member Since:</strong> <?php echo formatDate($member['join_date'] ?? date('Y-m-d')); ?></p>
                                    <p class="mb-0"><strong>Membership Type:</strong> <?php echo ucfirst($member['membership_type'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="col-md-4 text-end">
                                    <?php
                                    $statusColor = 'success';
                                    if ($membershipStatus === 'Expired') {
                                        $statusColor = 'danger';
                                    } elseif ($membershipStatus === 'Expiring Soon') {
                                        $statusColor = 'warning';
                                    }
                                    ?>
                                    <h6 class="mb-2">Status</h6>
                                    <span class="badge bg-<?php echo $statusColor; ?> p-2 text-white">
                                        <?php echo $membershipStatus; ?>
                                    </span>
                                    <?php if ($membershipStatus !== 'Expired'): ?>
                                        <p class="mt-2 text-muted small"><?php echo $daysRemaining; ?> days remaining</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="dashboard-card primary position-relative">
                        <h5>Total Workouts</h5>
                        <div class="number"><?php echo count($workouts); ?></div>
                        <div class="icon"><i class="fas fa-dumbbell"></i></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="dashboard-card success position-relative">
                        <h5>Upcoming Sessions</h5>
                        <div class="number"><?php echo count($sessions); ?></div>
                        <div class="icon"><i class="fas fa-calendar"></i></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="dashboard-card info position-relative">
                        <h5>Check-ins</h5>
                        <div class="number"><?php echo count($attendance); ?></div>
                        <div class="icon"><i class="fas fa-clipboard-check"></i></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="dashboard-card <?php echo ($outstandingBalance['outstanding_amount'] > 0) ? 'danger' : 'success'; ?> position-relative">
                        <h5>Balance Due</h5>
                        <div class="number"><?php echo formatCurrency($outstandingBalance['outstanding_amount']); ?></div>
                        <div class="icon"><i class="fas fa-credit-card"></i></div>
                        <?php if ($outstandingBalance['outstanding_amount'] > 0): ?>
                            <a href="<?php echo APP_URL; ?>modules/payments/pay.php" class="btn btn-sm btn-danger mt-2">
                                Pay Now
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Upcoming Sessions -->
            <div class="row mb-4">
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Upcoming Sessions</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($sessions)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Trainer</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($sessions as $session): ?>
                                                <tr>
                                                    <td><?php echo formatDate($session['session_date']); ?></td>
                                                    <td><?php echo date('h:i A', strtotime($session['session_time'])); ?></td>
                                                    <td><?php echo $session['trainer_name'] ?? 'Unassigned'; ?></td>
                                                    <td><span class="badge bg-success">Scheduled</span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <a href="<?php echo APP_URL; ?>modules/sessions/" class="btn btn-sm btn-primary">
                                    View All Sessions
                                </a>
                            <?php else: ?>
                                <p class="text-muted">No upcoming sessions scheduled.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Check-ins -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-history"></i> Recent Check-ins</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($attendance)): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach (array_slice($attendance, 0, 5) as $record): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo formatDate($record['attendance_date']); ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo date('h:i A', strtotime($record['check_in_time'] ?? '00:00:00')); ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-success">Present</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <a href="<?php echo APP_URL; ?>modules/attendance/" class="btn btn-sm btn-primary mt-3">
                                    View All Check-ins
                                </a>
                            <?php else: ?>
                                <p class="text-muted">No check-in records yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Workouts -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-dumbbell"></i> My Workout Plans</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($workouts)): ?>
                                <div class="row">
                                    <?php foreach (array_slice($workouts, 0, 3) as $workout): ?>
                                        <div class="col-md-4 mb-3">
                                            <div class="card h-100">
                                                <div class="card-body">
                                                    <h6 class="card-title"><?php echo $workout['workout_name'] ?? 'Unnamed Workout'; ?></h6>
                                                    <p class="card-text small text-muted">
                                                        <?php echo substr($workout['workout_description'] ?? 'No description', 0, 60); ?>...
                                                    </p>
                                                    <small class="text-muted">
                                                        Duration: <?php echo $workout['duration'] ?? 'N/A'; ?> mins
                                                    </small>
                                                </div>
                                                <div class="card-footer bg-white">
                                                    <a href="<?php echo APP_URL; ?>modules/workouts/view.php?id=<?php echo $workout['workout_id'] ?? ''; ?>" class="btn btn-sm btn-outline-primary">
                                                        View Details
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <a href="<?php echo APP_URL; ?>modules/workouts/" class="btn btn-sm btn-primary">
                                    View All Workouts
                                </a>
                            <?php else: ?>
                                <p class="text-muted">No workouts assigned yet. Contact your trainer!</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once dirname(dirname(__FILE__)) . '/includes/footer.php'; ?>
