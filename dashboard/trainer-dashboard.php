<?php
/**
 * Trainer Dashboard
 * Level Up Fitness - Gym Management System
 */

// Include required files
require_once dirname(dirname(__FILE__)) . '/includes/header.php';

// Require login and trainer role
requireLogin();
requireRole('trainer');

$userInfo = getUserInfo();

// Get trainer details
try {
    $trainerStmt = $pdo->prepare("SELECT * FROM trainers WHERE user_id = ?");
    $trainerStmt->execute([$userInfo['user_id']]);
    $trainer = $trainerStmt->fetch();
} catch (Exception $e) {
    error_log('Error fetching trainer details: ' . $e->getMessage());
    $trainer = [];
}

// Get trainer's assigned sessions
try {
    $sessionsStmt = $pdo->prepare("
        SELECT s.*, m.member_name FROM sessions s
        LEFT JOIN members m ON s.member_id = m.member_id
        WHERE s.trainer_id = ?
        ORDER BY s.session_date DESC
        LIMIT 10
    ");
    $sessionsStmt->execute([$trainer['trainer_id'] ?? null]);
    $allSessions = $sessionsStmt->fetchAll();
    
    // Separate upcoming and past sessions
    $upcomingSessions = array_filter($allSessions, function($s) {
        return strtotime($s['session_date']) >= strtotime(date('Y-m-d'));
    });
    $pastSessions = array_filter($allSessions, function($s) {
        return strtotime($s['session_date']) < strtotime(date('Y-m-d'));
    });
} catch (Exception $e) {
    error_log('Error fetching sessions: ' . $e->getMessage());
    $upcomingSessions = [];
    $pastSessions = [];
}

// Get trainer's assigned members
try {
    $membersStmt = $pdo->prepare("
        SELECT DISTINCT m.* FROM members m
        INNER JOIN sessions s ON m.member_id = s.member_id
        WHERE s.trainer_id = ?
        ORDER BY m.member_name ASC
    ");
    $membersStmt->execute([$trainer['trainer_id'] ?? null]);
    $members = $membersStmt->fetchAll();
} catch (Exception $e) {
    error_log('Error fetching members: ' . $e->getMessage());
    $members = [];
}

// Get today's attendance for trainer's sessions
try {
    $todayAttendanceStmt = $pdo->prepare("
        SELECT a.*, m.member_name FROM attendance a
        INNER JOIN members m ON a.member_id = m.member_id
        INNER JOIN sessions s ON a.member_id = s.member_id
        WHERE s.trainer_id = ? AND a.attendance_date = CURDATE()
        ORDER BY a.check_in_time DESC
    ");
    $todayAttendanceStmt->execute([$trainer['trainer_id'] ?? null]);
    $todayAttendance = $todayAttendanceStmt->fetchAll();
} catch (Exception $e) {
    error_log('Error fetching today attendance: ' . $e->getMessage());
    $todayAttendance = [];
}

// Get total members count
$totalMembers = count($members);

// Get today's sessions count
$todaySessionsCount = count(array_filter($upcomingSessions, function($s) {
    return strtotime($s['session_date']) === strtotime(date('Y-m-d'));
}));

// Get this week's sessions count
$thisWeekSessionsCount = count(array_filter($upcomingSessions, function($s) {
    $sessionDate = strtotime($s['session_date']);
    $today = strtotime(date('Y-m-d'));
    $weekEnd = strtotime(date('Y-m-d', strtotime('+7 days')));
    return $sessionDate >= $today && $sessionDate <= $weekEnd;
}));
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
                    TRAINING
                </h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>modules/sessions/">
                            <i class="fas fa-calendar"></i> My Sessions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>modules/attendance/">
                            <i class="fas fa-clipboard-check"></i> Attendance
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>modules/workouts/">
                            <i class="fas fa-dumbbell"></i> Workout Plans
                        </a>
                    </li>
                </ul>

                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    MEMBERS
                </h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>modules/members/">
                            <i class="fas fa-users"></i> My Members
                        </a>
                    </li>
                </ul>

                <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
                    ACCOUNT
                </h6>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>modules/trainers/view.php?id=<?php echo $trainer['trainer_id'] ?? ''; ?>">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                    </li>
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
                <h1><i class="fas fa-chart-line"></i> Trainer Dashboard</h1>
                <p>Manage your sessions, members, and training activities</p>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="dashboard-card primary position-relative">
                        <h5>Total Members</h5>
                        <div class="number"><?php echo $totalMembers; ?></div>
                        <div class="icon"><i class="fas fa-users"></i></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="dashboard-card success position-relative">
                        <h5>Today's Sessions</h5>
                        <div class="number"><?php echo $todaySessionsCount; ?></div>
                        <div class="icon"><i class="fas fa-calendar"></i></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="dashboard-card info position-relative">
                        <h5>This Week</h5>
                        <div class="number"><?php echo $thisWeekSessionsCount; ?></div>
                        <div class="icon"><i class="fas fa-calendar-alt"></i></div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-3 mb-3">
                    <div class="dashboard-card warning position-relative">
                        <h5>Check-ins Today</h5>
                        <div class="number"><?php echo count($todayAttendance); ?></div>
                        <div class="icon"><i class="fas fa-clipboard-check"></i></div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Sessions and Today's Attendance -->
            <div class="row mb-4">
                <div class="col-lg-8 mb-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-calendar-alt"></i> Upcoming Sessions</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($upcomingSessions)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Member</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($upcomingSessions, 0, 10) as $session): ?>
                                                <tr>
                                                    <td><?php echo formatDate($session['session_date']); ?></td>
                                                    <td><?php echo date('h:i A', strtotime($session['session_time'])); ?></td>
                                                    <td><?php echo $session['member_name'] ?? 'Unassigned'; ?></td>
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

                <!-- Today's Check-ins -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-clipboard-check"></i> Today's Check-ins</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($todayAttendance)): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($todayAttendance as $record): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo $record['member_name']; ?></strong>
                                                <br>
                                                <small class="text-muted">
                                                    <?php echo date('h:i A', strtotime($record['check_in_time'] ?? '00:00:00')); ?>
                                                </small>
                                            </div>
                                            <span class="badge bg-success">Present</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted">No check-ins recorded for today.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- My Members -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-users"></i> My Members</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($members)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Member Name</th>
                                                <th>Email</th>
                                                <th>Phone</th>
                                                <th>Membership Type</th>
                                                <th>Join Date</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach (array_slice($members, 0, 10) as $member): ?>
                                                <tr>
                                                    <td><?php echo $member['member_name'] ?? 'N/A'; ?></td>
                                                    <td><?php echo $member['email'] ?? 'N/A'; ?></td>
                                                    <td><?php echo $member['phone'] ?? 'N/A'; ?></td>
                                                    <td><?php echo ucfirst($member['membership_type'] ?? 'N/A'); ?></td>
                                                    <td><?php echo formatDate($member['join_date'] ?? date('Y-m-d')); ?></td>
                                                    <td>
                                                        <a href="<?php echo APP_URL; ?>modules/members/view.php?id=<?php echo $member['member_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php if (count($members) > 10): ?>
                                    <a href="<?php echo APP_URL; ?>modules/members/" class="btn btn-sm btn-primary">
                                        View All Members
                                    </a>
                                <?php endif; ?>
                            <?php else: ?>
                                <p class="text-muted">No members assigned yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once dirname(dirname(__FILE__)) . '/includes/footer.php'; ?>
