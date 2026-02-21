<?php
/**
 * Admin Dashboard
 * Level Up Fitness - Gym Management System
 */

// Include required files
require_once dirname(dirname(__FILE__)) . '/includes/header.php';

// Require login and admin role
requireLogin();
requireRole('admin');

// asdasdasd

$userInfo = getUserInfo();

// Get dashboard statistics
$stats = [
    'totalMembers' => 0,
    'activeTrainers' => 0,
    'todayRevenue' => 0,
    'pendingPayments' => 0,
    'activeMemberships' => 0,
    'paidPayments' => 0,
    'totalPayments' => 0
];

try {
    // Total members
    $membersStmt = $pdo->prepare("SELECT COUNT(*) as count FROM members");
    $membersStmt->execute();
    $stats['totalMembers'] = $membersStmt->fetch()['count'];
    
    // Active trainers
    $trainersStmt = $pdo->prepare("SELECT COUNT(*) as count FROM trainers WHERE status = 'Active'");
    $trainersStmt->execute();
    $stats['activeTrainers'] = $trainersStmt->fetch()['count'];
    
    // Today's revenue (payments made today)
    $todayRevenueStmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE payment_date = CURDATE() AND payment_status = 'Paid'");
    $todayRevenueStmt->execute();
    $result = $todayRevenueStmt->fetch();
    $stats['todayRevenue'] = $result['total'] ?? 0;
    
    // Pending payments
    $pendingStmt = $pdo->prepare("SELECT COUNT(*) as count FROM payments WHERE payment_status = 'Pending'");
    $pendingStmt->execute();
    $stats['pendingPayments'] = $pendingStmt->fetch()['count'];
    
    // Active memberships
    $activeMembersStmt = $pdo->prepare("SELECT COUNT(*) as count FROM members WHERE status = 'Active'");
    $activeMembersStmt->execute();
    $stats['activeMemberships'] = $activeMembersStmt->fetch()['count'];
    
    // Payment stats
    $paidPaymentsStmt = $pdo->prepare("SELECT COUNT(*) as count FROM payments WHERE payment_status = 'Paid'");
    $paidPaymentsStmt->execute();
    $stats['paidPayments'] = $paidPaymentsStmt->fetch()['count'];
    
    $totalPaymentsStmt = $pdo->prepare("SELECT COUNT(*) as count FROM payments");
    $totalPaymentsStmt->execute();
    $stats['totalPayments'] = $totalPaymentsStmt->fetch()['count'];
    
    // Get recent activities
    $activitiesStmt = $pdo->prepare("
        SELECT * FROM activity_log 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $activitiesStmt->execute();
    $recentActivities = $activitiesStmt->fetchAll();
} catch (Exception $e) {
    error_log('Error fetching dashboard stats: ' . $e->getMessage());
}

// Calculate percentages
$membershipPercentage = $stats['totalMembers'] > 0 ? round(($stats['activeMemberships'] / $stats['totalMembers']) * 100) : 0;
$paymentPercentage = $stats['totalPayments'] > 0 ? round(($stats['paidPayments'] / $stats['totalPayments']) * 100) : 0;
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar Navigation -->
        <nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
            <div class="position-sticky pt-3">
                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    MAIN MENU
                </h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link active" href="<?php echo APP_URL; ?>dashboard/">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                </ul>

                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    MANAGEMENT
                </h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>modules/members/">
                            <i class="fas fa-users"></i> Members
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>modules/trainers/">
                            <i class="fas fa-user-tie"></i> Trainers
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>modules/gyms/">
                            <i class="fas fa-building"></i> Gym Information
                        </a>
                    </li>
                </ul>

                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    OPERATIONS
                </h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>modules/workouts/">
                            <i class="fas fa-dumbbell"></i> Workout Plans
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>modules/sessions/">
                            <i class="fas fa-calendar-alt"></i> Sessions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>modules/attendance/">
                            <i class="fas fa-clipboard-check"></i> Attendance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>modules/reservations/">
                            <i class="fas fa-bookmark"></i> Reservations
                        </a>
                    </li>
                </ul>

                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    FINANCE
                </h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>modules/payments/">
                            <i class="fas fa-money-bill-wave"></i> Payments
                        </a>
                    </li>
                </ul>

                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    SETTINGS
                </h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>auth/logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

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
                        <a href="<?php echo APP_URL; ?>auth/logout.php" class="btn btn-sm btn-outline-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </nav>

            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-chart-line"></i> Dashboard</h1>
                <p>Welcome to <?php echo APP_NAME; ?></p>
            </div>

            <!-- Dashboard Cards -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="dashboard-card primary position-relative">
                        <h5>Total Members</h5>
                        <div class="number"><?php echo $stats['totalMembers']; ?></div>
                        <div class="icon"><i class="fas fa-users"></i></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="dashboard-card success position-relative">
                        <h5>Active Trainers</h5>
                        <div class="number"><?php echo $stats['activeTrainers']; ?></div>
                        <div class="icon"><i class="fas fa-user-tie"></i></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="dashboard-card warning position-relative">
                        <h5>Today's Revenue</h5>
                        <div class="number"><?php echo formatCurrency($stats['todayRevenue']); ?></div>
                        <div class="icon"><i class="fas fa-money-bill"></i></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="dashboard-card danger position-relative">
                        <h5>Pending Payments</h5>
                        <div class="number"><?php echo $stats['pendingPayments']; ?></div>
                        <div class="icon"><i class="fas fa-clock"></i></div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <a href="<?php echo APP_URL; ?>modules/members/add.php" class="btn btn-primary me-2">
                                <i class="fas fa-plus"></i> Add Member
                            </a>
                            <a href="<?php echo APP_URL; ?>modules/trainers/add.php" class="btn btn-success me-2">
                                <i class="fas fa-plus"></i> Add Trainer
                            </a>
                            <a href="<?php echo APP_URL; ?>modules/payments/add.php" class="btn btn-warning me-2">
                                <i class="fas fa-plus"></i> Record Payment
                            </a>
                            <a href="<?php echo APP_URL; ?>modules/attendance/checkin.php" class="btn btn-info me-2">
                                <i class="fas fa-sign-in-alt"></i> Check-In
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activities -->
            <div class="row">
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-history"></i> Recent Activities</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Activity</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($recentActivities)): ?>
                                            <?php foreach ($recentActivities as $activity): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($activity['action']); ?> - <?php echo htmlspecialchars($activity['module']); ?></td>
                                                    <td><?php echo formatDate($activity['created_at']); ?></td>
                                                    <td><?php echo date('h:i A', strtotime($activity['created_at'])); ?></td>
                                                    <td><span class="badge bg-success">Completed</span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">No activities recorded yet</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Quick Stats -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Quick Stats</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <p class="text-muted mb-1">Active Memberships</p>
                                <h6><?php echo $stats['activeMemberships']; ?> / <?php echo $stats['totalMembers']; ?> (<?php echo $membershipPercentage; ?>%)</h6>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar" style="width: <?php echo $membershipPercentage; ?>%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <p class="text-muted mb-1">Payment Collection Rate</p>
                                <h6><?php echo $paymentPercentage; ?>% / 100%</h6>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-success" style="width: <?php echo $paymentPercentage; ?>%"></div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <p class="text-muted mb-1">Trainer Utilization</p>
                                <h6><?php echo ($stats['activeTrainers'] > 0 ? round(($stats['activeTrainers'] / max(1, $stats['activeTrainers'])) * 100) : 0); ?>% / 100%</h6>
                                <div class="progress" style="height: 5px;">
                                    <div class="progress-bar bg-info" style="width: <?php echo ($stats['activeTrainers'] > 0 ? round(($stats['activeTrainers'] / max(1, $stats['activeTrainers'])) * 100) : 0); ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Info -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-cogs"></i> System Info</h5>
                        </div>
                        <div class="card-body">
                            <small>
                                <p><strong>Application:</strong> <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?></p>
                                <p><strong>User Role:</strong> <?php echo ucfirst($userInfo['user_type']); ?></p>
                                <p><strong>Login:</strong> <?php echo formatDate(date('Y-m-d'), 'M d, Y'); ?></p>
                                <p><strong>Database:</strong> Connected</p>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once dirname(dirname(__FILE__)) . '/includes/footer.php'; ?>
