<?php
/**
 * Dashboard - Main System Overview
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(__FILE__)) . '/includes/header.php';

requireLogin();

try {
    // Members Stats
    $memberStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'Inactive' THEN 1 ELSE 0 END) as inactive,
            SUM(CASE WHEN status = 'Suspended' THEN 1 ELSE 0 END) as suspended
        FROM members
    ");
    $memberStmt->execute();
    $memberStats = $memberStmt->fetch();

    // Trainers Stats
    $trainerStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active
        FROM trainers
    ");
    $trainerStmt->execute();
    $trainerStats = $trainerStmt->fetch();

    // Classes Stats
    $classStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN class_status = 'Active' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN class_status = 'Inactive' THEN 1 ELSE 0 END) as inactive
        FROM classes
    ");
    $classStmt->execute();
    $classStats = $classStmt->fetch();

    // Sessions Stats
    $sessionStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN session_status = 'Scheduled' THEN 1 ELSE 0 END) as scheduled,
            SUM(CASE WHEN session_status = 'Completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN session_status = 'Cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM sessions
    ");
    $sessionStmt->execute();
    $sessionStats = $sessionStmt->fetch();

    // Attendance Stats
    $attendanceStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN attendance_status = 'Present' THEN 1 ELSE 0 END) as present,
            SUM(CASE WHEN attendance_status = 'Absent' THEN 1 ELSE 0 END) as absent
        FROM class_attendance
    ");
    $attendanceStmt->execute();
    $attendanceStats = $attendanceStmt->fetch();

    // Payments Stats
    $paymentStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(amount) as total_revenue,
            SUM(CASE WHEN payment_status = 'Completed' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN payment_status = 'Pending' THEN 1 ELSE 0 END) as pending
        FROM payments
    ");
    $paymentStmt->execute();
    $paymentStats = $paymentStmt->fetch();

    // Workouts Stats
    $workoutStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Active' THEN 1 ELSE 0 END) as active
        FROM workouts
    ");
    $workoutStmt->execute();
    $workoutStats = $workoutStmt->fetch();

    // Reservations Stats
    $reservationStmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Confirmed' THEN 1 ELSE 0 END) as confirmed,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending
        FROM reservations
    ");
    $reservationStmt->execute();
    $reservationStats = $reservationStmt->fetch();

    // Recent Activity
    $activityStmt = $pdo->prepare("
        SELECT * FROM activity_log 
        ORDER BY activity_date DESC 
        LIMIT 10
    ");
    $activityStmt->execute();
    $recentActivity = $activityStmt->fetchAll();

    // Upcoming Sessions
    $upcomingStmt = $pdo->prepare("
        SELECT s.*, m.member_name, t.trainer_name
        FROM sessions s
        LEFT JOIN members m ON s.member_id = m.member_id
        LEFT JOIN trainers t ON s.trainer_id = t.trainer_id
        WHERE s.session_date >= CURDATE() 
        AND s.session_status = 'Scheduled'
        ORDER BY s.session_date, s.session_time
        LIMIT 5
    ");
    $upcomingStmt->execute();
    $upcomingSessions = $upcomingStmt->fetchAll();

    // Today's Classes
    $classesStmt = $pdo->prepare("
        SELECT c.*, t.trainer_name,
               COUNT(DISTINCT ca.member_id) as enrolled_count
        FROM classes c
        LEFT JOIN trainers t ON c.trainer_id = t.trainer_id
        LEFT JOIN class_attendance ca ON c.class_id = ca.class_id
        WHERE c.class_schedule LIKE ?
        AND c.class_status = 'Active'
        GROUP BY c.class_id
        ORDER BY c.class_name
    ");
    $classesStmt->execute(['%' . date('l') . '%']);
    $todaysClasses = $classesStmt->fetchAll();

} catch (Exception $e) {
    setMessage('Error loading dashboard: ' . $e->getMessage(), 'error');
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(__FILE__)) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <h1><i class="fas fa-chart-line"></i> Dashboard</h1>
                <p>System overview and key metrics</p>
            </div>

            <?php displayMessage(); ?>

            <!-- Key Metrics Row 1 -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-users"></i> Total Members</h6>
                            <h3><?php echo $memberStats['total']; ?></h3>
                            <small>Active: <?php echo $memberStats['active']; ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-dumbbell"></i> Trainers</h6>
                            <h3><?php echo $trainerStats['total']; ?></h3>
                            <small>Active: <?php echo $trainerStats['active']; ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-list-check"></i> Classes</h6>
                            <h3><?php echo $classStats['total']; ?></h3>
                            <small>Active: <?php echo $classStats['active']; ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-dollar-sign"></i> Revenue</h6>
                            <h3>$<?php echo number_format($paymentStats['total_revenue'] ?? 0, 0); ?></h3>
                            <small>Completed: <?php echo $paymentStats['completed']; ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Key Metrics Row 2 -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-danger text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-heartbeat"></i> Sessions</h6>
                            <h3><?php echo $sessionStats['total']; ?></h3>
                            <small>Scheduled: <?php echo $sessionStats['scheduled']; ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-secondary text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-clipboard-check"></i> Attendance</h6>
                            <h3><?php echo $attendanceStats['total']; ?></h3>
                            <small>Present: <?php echo $attendanceStats['present']; ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-indigo text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-fitness-gym"></i> Workouts</h6>
                            <h3><?php echo $workoutStats['total']; ?></h3>
                            <small>Active: <?php echo $workoutStats['active']; ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-teal text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-calendar-check"></i> Reservations</h6>
                            <h3><?php echo $reservationStats['total']; ?></h3>
                            <small>Confirmed: <?php echo $reservationStats['confirmed']; ?></small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content Row -->
            <div class="row">
                <!-- Upcoming Sessions -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <div class="float-end">
                                <a href="<?php echo APP_URL; ?>modules/sessions/" class="btn btn-sm btn-primary">
                                    <i class="fas fa-arrow-right"></i> View All
                                </a>
                            </div>
                            <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Upcoming Sessions</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($upcomingSessions)): ?>
                                <div class="alert alert-info m-3">
                                    <i class="fas fa-info-circle"></i> No upcoming sessions scheduled.
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($upcomingSessions as $session): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1">
                                                    <i class="fas fa-user"></i> 
                                                    <?php echo htmlspecialchars($session['member_name'] ?? 'N/A'); ?>
                                                </h6>
                                                <small class="text-muted"><?php echo formatDate($session['session_date']); ?></small>
                                            </div>
                                            <p class="mb-1">
                                                <i class="fas fa-clock"></i> <?php echo substr($session['session_time'], 0, 5); ?> - 
                                                <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($session['trainer_name'] ?? 'Unassigned'); ?>
                                            </p>
                                            <span class="badge badge-<?php echo strtolower(str_replace('Scheduled', 'success', str_replace('Completed', 'primary', str_replace('Cancelled', 'danger', $session['session_status'])))); ?>">
                                                <?php echo $session['session_status']; ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Today's Classes -->
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <div class="float-end">
                                <a href="<?php echo APP_URL; ?>modules/classes/" class="btn btn-sm btn-primary">
                                    <i class="fas fa-arrow-right"></i> View All
                                </a>
                            </div>
                            <h5 class="mb-0"><i class="fas fa-calendar-day"></i> Today's Classes (<?php echo date('l'); ?>)</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($todaysClasses)): ?>
                                <div class="alert alert-info m-3">
                                    <i class="fas fa-info-circle"></i> No classes scheduled for today.
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($todaysClasses as $class): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h6 class="mb-1">
                                                    <i class="fas fa-dumbbell"></i> 
                                                    <?php echo htmlspecialchars($class['class_name']); ?>
                                                </h6>
                                                <span class="badge bg-info"><?php echo $class['enrolled_count']; ?>/<?php echo $class['max_capacity']; ?></span>
                                            </div>
                                            <p class="mb-1">
                                                <i class="fas fa-clock"></i> <?php echo htmlspecialchars($class['class_schedule']); ?> - 
                                                <i class="fas fa-user-tie"></i> <?php echo htmlspecialchars($class['trainer_name'] ?? 'Unassigned'); ?>
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card">
                <div class="card-header bg-light">
                    <div class="float-end">
                        <a href="<?php echo APP_URL; ?>activity-log/" class="btn btn-sm btn-primary">
                            <i class="fas fa-arrow-right"></i> View All
                        </a>
                    </div>
                    <h5 class="mb-0"><i class="fas fa-history"></i> Recent Activity</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentActivity)): ?>
                        <div class="alert alert-info m-3">
                            <i class="fas fa-info-circle"></i> No recent activity.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>Action</th>
                                        <th>Module</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentActivity as $activity): ?>
                                        <tr>
                                            <td><?php echo formatDate($activity['activity_date']); ?></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?php echo htmlspecialchars($activity['action']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($activity['module_name']); ?></td>
                                            <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-link"></i> Quick Links</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-md-2 col-sm-4 col-6">
                                    <a href="<?php echo APP_URL; ?>modules/members/" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-users"></i><br>Members
                                    </a>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6">
                                    <a href="<?php echo APP_URL; ?>modules/trainers/" class="btn btn-outline-success w-100">
                                        <i class="fas fa-dumbbell"></i><br>Trainers
                                    </a>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6">
                                    <a href="<?php echo APP_URL; ?>modules/classes/" class="btn btn-outline-info w-100">
                                        <i class="fas fa-list-check"></i><br>Classes
                                    </a>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6">
                                    <a href="<?php echo APP_URL; ?>modules/sessions/" class="btn btn-outline-warning w-100">
                                        <i class="fas fa-calendar-alt"></i><br>Sessions
                                    </a>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6">
                                    <a href="<?php echo APP_URL; ?>modules/payments/" class="btn btn-outline-danger w-100">
                                        <i class="fas fa-dollar-sign"></i><br>Payments
                                    </a>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6">
                                    <a href="<?php echo APP_URL; ?>modules/reports/" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-chart-line"></i><br>Reports
                                    </a>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6">
                                    <a href="<?php echo APP_URL; ?>modules/workouts/" class="btn btn-outline-primary w-100">
                                        <i class="fas fa-heartbeat"></i><br>Workouts
                                    </a>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6">
                                    <a href="<?php echo APP_URL; ?>modules/attendance/" class="btn btn-outline-info w-100">
                                        <i class="fas fa-clipboard-check"></i><br>Attendance
                                    </a>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6">
                                    <a href="<?php echo APP_URL; ?>modules/reservations/" class="btn btn-outline-success w-100">
                                        <i class="fas fa-calendar-check"></i><br>Reservations
                                    </a>
                                </div>
                                <div class="col-md-2 col-sm-4 col-6">
                                    <a href="<?php echo APP_URL; ?>modules/gyms/" class="btn btn-outline-warning w-100">
                                        <i class="fas fa-building"></i><br>Gyms
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<?php require_once dirname(dirname(__FILE__)) . '/includes/footer.php'; ?>
