<?php
/**
 * Reports & Analytics - Dashboard
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole('admin');

// Get report date range
$startDate = sanitize($_GET['start_date'] ?? date('Y-m-01'));
$endDate = sanitize($_GET['end_date'] ?? date('Y-m-d'));

try {
    // Member Statistics
    $totalMembers = $pdo->query("SELECT COUNT(*) as count FROM members")->fetch()['count'];
    $activeMembers = $pdo->query("SELECT COUNT(*) as count FROM members WHERE status = 'Active'")->fetch()['count'];
    $newMembers = $pdo->prepare("SELECT COUNT(*) as count FROM members WHERE DATE(created_at) BETWEEN ? AND ?");
    $newMembers->execute([$startDate, $endDate]);
    $newMembersCount = $newMembers->fetch()['count'];

    // Revenue Statistics
    $totalRevenue = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE payment_status = 'Completed' AND DATE(payment_date) BETWEEN ? AND ?");
    $totalRevenue->execute([$startDate, $endDate]);
    $totalRevenueAmount = $totalRevenue->fetch()['total'] ?? 0;

    $averagePayment = $totalRevenueAmount > 0 ? ($totalRevenueAmount / $newMembersCount) : 0;

    // Trainer Statistics
    $totalTrainers = $pdo->query("SELECT COUNT(*) as count FROM trainers")->fetch()['count'];
    $activeTrainers = $pdo->query("SELECT COUNT(*) as count FROM trainers WHERE status = 'Active'")->fetch()['count'];

    // Session Statistics
    $totalSessions = $pdo->query("SELECT COUNT(*) as count FROM sessions")->fetch()['count'];
    $completedSessions = $pdo->query("SELECT COUNT(*) as count FROM sessions WHERE session_status = 'Completed'")->fetch()['count'];
    
    // Class Statistics
    $totalClasses = $pdo->query("SELECT COUNT(*) as count FROM classes")->fetch()['count'];
    $activeClasses = $pdo->query("SELECT COUNT(*) as count FROM classes WHERE class_status = 'Active'")->fetch()['count'];

    // Attendance Statistics
    $totalAttendance = $pdo->query("SELECT COUNT(*) as count FROM class_attendance")->fetch()['count'];
    $presentCount = $pdo->query("SELECT COUNT(*) as count FROM class_attendance WHERE attendance_status = 'Present'")->fetch()['count'];
    $absentCount = $pdo->query("SELECT COUNT(*) as count FROM class_attendance WHERE attendance_status = 'Absent'")->fetch()['count'];
    $attendanceRate = $totalAttendance > 0 ? round(($presentCount / $totalAttendance) * 100, 2) : 0;

    // Top Classes (by enrollment)
    $topClassesStmt = $pdo->prepare("
        SELECT c.class_name, COUNT(ca.member_id) as member_count
        FROM classes c
        LEFT JOIN class_attendance ca ON c.class_id = ca.class_id
        GROUP BY c.class_id
        ORDER BY member_count DESC
        LIMIT 5
    ");
    $topClassesStmt->execute();
    $topClasses = $topClassesStmt->fetchAll();

    // Payment Methods Distribution
    $paymentMethodsStmt = $pdo->prepare("
        SELECT payment_method, COUNT(*) as count, SUM(amount) as total
        FROM payments
        WHERE DATE(payment_date) BETWEEN ? AND ?
        GROUP BY payment_method
        ORDER BY count DESC
    ");
    $paymentMethodsStmt->execute([$startDate, $endDate]);
    $paymentMethods = $paymentMethodsStmt->fetchAll();

    // Membership Type Distribution
    $membershipStmt = $pdo->query("
        SELECT membership_type, COUNT(*) as count
        FROM members
        GROUP BY membership_type
    ");
    $membershipData = $membershipStmt->fetchAll();

    // Trainer Performance (sessions conducted)
    $trainerPerformanceStmt = $pdo->prepare("
        SELECT t.trainer_name, COUNT(s.session_id) as session_count
        FROM trainers t
        LEFT JOIN sessions s ON t.trainer_id = s.trainer_id AND DATE(s.session_date) BETWEEN ? AND ?
        WHERE t.status = 'Active'
        GROUP BY t.trainer_id
        ORDER BY session_count DESC
        LIMIT 5
    ");
    $trainerPerformanceStmt->execute([$startDate, $endDate]);
    $trainerPerformance = $trainerPerformanceStmt->fetchAll();

} catch (Exception $e) {
    setMessage('Error loading report data: ' . $e->getMessage(), 'error');
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <h1><i class="fas fa-chart-bar"></i> Reports & Analytics</h1>
                <p>View system statistics and performance metrics</p>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo htmlspecialchars($startDate); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo htmlspecialchars($endDate); ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="<?php echo APP_URL; ?>modules/reports/" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Reset
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <h5 class="mb-3">Member Statistics</h5>
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-users"></i> Total Members</h6>
                            <h3><?php echo $totalMembers; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-check"></i> Active</h6>
                            <h3><?php echo $activeMembers; ?></h3>
                            <small><?php echo round(($activeMembers / $totalMembers * 100), 1); ?>%</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-user-plus"></i> New Members</h6>
                            <h3><?php echo $newMembersCount; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-chart-line"></i> Membership Types</h6>
                            <h3><?php echo count($membershipData); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <h5 class="mb-3">Revenue Statistics</h5>
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-money-bill"></i> Total Revenue</h6>
                            <h3>$<?php echo number_format($totalRevenueAmount, 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-dollar-sign"></i> Avg Payment</h6>
                            <h3>$<?php echo number_format($averagePayment, 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-credit-card"></i> Payment Methods</h6>
                            <h3><?php echo count($paymentMethods); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-list"></i> Transactions</h6>
                            <h3><?php echo count($paymentMethods) > 0 ? array_sum(array_column($paymentMethods, 'count')) : 0; ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <h5 class="mb-3">Class & Session Statistics</h5>
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-dumbbell"></i> Total Classes</h6>
                            <h3><?php echo $totalClasses; ?></h3>
                            <small class="text-muted"><?php echo $activeClasses; ?> active</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-calendar-check"></i> Total Sessions</h6>
                            <h3><?php echo $totalSessions; ?></h3>
                            <small class="text-muted"><?php echo $completedSessions; ?> completed</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-users-cog"></i> Trainers</h6>
                            <h3><?php echo $totalTrainers; ?></h3>
                            <small class="text-muted"><?php echo $activeTrainers; ?> active</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-percentages"></i> Attendance Rate</h6>
                            <h3><?php echo $attendanceRate; ?>%</h3>
                            <small class="text-muted"><?php echo $presentCount; ?> / <?php echo $totalAttendance; ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-star"></i> Top Classes by Enrollment</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($topClasses)): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Class Name</th>
                                                <th class="text-end">Members</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($topClasses as $class): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                                    <td class="text-end">
                                                        <span class="badge bg-primary"><?php echo $class['member_count']; ?></span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mb-0">No class data available</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-money-bill"></i> Payment Methods</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($paymentMethods)): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Method</th>
                                                <th class="text-end">Count</th>
                                                <th class="text-end">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($paymentMethods as $method): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($method['payment_method']); ?></td>
                                                    <td class="text-end"><?php echo $method['count']; ?></td>
                                                    <td class="text-end">$<?php echo number_format($method['total'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mb-0">No payment data available</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-people-arrows"></i> Membership Distribution</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($membershipData)): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Membership Type</th>
                                                <th class="text-end">Count</th>
                                                <th class="text-end">%</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($membershipData as $membership): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($membership['membership_type']); ?></td>
                                                    <td class="text-end"><?php echo $membership['count']; ?></td>
                                                    <td class="text-end"><?php echo round(($membership['count'] / $totalMembers * 100), 1); ?>%</td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mb-0">No membership data available</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-dumbbell"></i> Trainer Performance</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($trainerPerformance)): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Trainer</th>
                                                <th class="text-end">Sessions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($trainerPerformance as $trainer): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($trainer['trainer_name']); ?></td>
                                                    <td class="text-end">
                                                        <span class="badge bg-primary"><?php echo $trainer['session_count']; ?></span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mb-0">No trainer data available</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
