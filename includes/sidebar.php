<?php
/**
 * Sidebar Navigation Template with Role-Based Access Control
 * Level Up Fitness - Gym Management System
 * Include this file in your main layout pages
 */

global $pdo;
$userRole = $_SESSION['user_type'] ?? 'member';

// Get IDs for profile links
$memberId = null;
$trainerId = null;

if ($userRole === 'member' && isset($_SESSION['user_id'])) {
    try {
        $memberStmt = $pdo->prepare("SELECT member_id FROM members WHERE user_id = ? LIMIT 1");
        $memberStmt->execute([$_SESSION['user_id']]);
        $memberData = $memberStmt->fetch();
        $memberId = $memberData['member_id'] ?? null;
    } catch (Exception $e) {
        error_log('Error fetching member ID: ' . $e->getMessage());
    }
} elseif ($userRole === 'trainer' && isset($_SESSION['user_id'])) {
    try {
        $trainerStmt = $pdo->prepare("SELECT trainer_id FROM trainers WHERE user_id = ? LIMIT 1");
        $trainerStmt->execute([$_SESSION['user_id']]);
        $trainerData = $trainerStmt->fetch();
        $trainerId = $trainerData['trainer_id'] ?? null;
    } catch (Exception $e) {
        error_log('Error fetching trainer ID: ' . $e->getMessage());
    }
}
?>

<nav class="luf-sidebar col-md-3 col-lg-2 d-md-block">
    <div class="luf-sidebar__inner">

        <div class="luf-sidebar__brand">
            <span class="luf-sidebar__brand-icon"><i class="fas fa-bolt"></i></span>
            <span class="luf-sidebar__brand-name">LEVEL UP</span>
        </div>

        <div class="luf-sidebar__section">
            <span class="luf-sidebar__label">Main</span>
            <ul class="luf-sidebar__list">
                <li>
                    <a class="luf-sidebar__link" href="<?php echo APP_URL; ?>dashboard/">
                        <span class="luf-sidebar__link-icon"><i class="fas fa-home"></i></span>
                        <span>Dashboard</span>
                    </a>
                </li>
                <?php if ($userRole !== 'admin'): ?>
                <li>
                    <a class="luf-sidebar__link" href="<?php 
                        if ($userRole === 'member' && $memberId) {
                            echo APP_URL . 'modules/members/view.php?id=' . htmlspecialchars($memberId);
                        } elseif ($userRole === 'trainer' && $trainerId) {
                            echo APP_URL . 'modules/trainers/view.php?id=' . htmlspecialchars($trainerId);
                        } else {
                            echo '#';
                        }
                    ?>">
                        <span class="luf-sidebar__link-icon"><i class="fas fa-user-circle"></i></span>
                        <span>Profile</span>
                    </a>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <?php if ($userRole === 'admin'): ?>
        <div class="luf-sidebar__section">
            <span class="luf-sidebar__label">Management</span>
            <ul class="luf-sidebar__list">
                <li>
                    <a class="luf-sidebar__link" href="<?php echo APP_URL; ?>modules/members/">
                        <span class="luf-sidebar__link-icon"><i class="fas fa-users"></i></span>
                        <span>Members</span>
                    </a>
                </li>
                <li>
                    <a class="luf-sidebar__link" href="<?php echo APP_URL; ?>modules/trainers/">
                        <span class="luf-sidebar__link-icon"><i class="fas fa-user-tie"></i></span>
                        <span>Trainers</span>
                    </a>
                </li>
                <li>
                    <a class="luf-sidebar__link" href="<?php echo APP_URL; ?>modules/gyms/">
                        <span class="luf-sidebar__link-icon"><i class="fas fa-building"></i></span>
                        <span>Gym Information</span>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($userRole === 'admin' || $userRole === 'member'): ?>
        <div class="luf-sidebar__section">
            <span class="luf-sidebar__label">Member</span>
            <ul class="luf-sidebar__list">
                <?php if ($userRole === 'member'): ?>
                <li>
                    <a class="luf-sidebar__link" href="<?php echo APP_URL; ?>modules/trainers/my-trainer.php">
                        <span class="luf-sidebar__link-icon"><i class="fas fa-user-tie"></i></span>
                        <span>My Trainer</span>
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a class="luf-sidebar__link" href="<?php echo APP_URL; ?>modules/templates/">
                        <span class="luf-sidebar__link-icon"><i class="fas fa-heart"></i></span>
                        <span>Workout Templates</span>
                    </a>
                </li>
                <li>
                    <a class="luf-sidebar__link" href="<?php echo APP_URL; ?>modules/workouts/">
                        <span class="luf-sidebar__link-icon"><i class="fas fa-dumbbell"></i></span>
                        <span>Workout Plans</span>
                    </a>
                </li>
                <li>
                    <a class="luf-sidebar__link" href="<?php echo APP_URL; ?>modules/reservations/">
                        <span class="luf-sidebar__link-icon"><i class="fas fa-bookmark"></i></span>
                        <span>Reservations</span>
                    </a>
                </li>
                <li>
                    <a class="luf-sidebar__link" href="<?php echo APP_URL; ?>modules/sessions/">
                        <span class="luf-sidebar__link-icon"><i class="fas fa-calendar-alt"></i></span>
                        <span>Training Sessions</span>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($userRole === 'admin' || $userRole === 'trainer'): ?>
        <div class="luf-sidebar__section">
            <span class="luf-sidebar__label">Trainer</span>
            <ul class="luf-sidebar__list">
                <li>
                    <a class="luf-sidebar__link" href="<?php echo APP_URL; ?>modules/templates/">
                        <span class="luf-sidebar__link-icon"><i class="fas fa-heart"></i></span>
                        <span>Workout Templates</span>
                    </a>
                </li>
                <li>
                    <a class="luf-sidebar__link" href="<?php echo APP_URL; ?>modules/workouts/">
                        <span class="luf-sidebar__link-icon"><i class="fas fa-dumbbell"></i></span>
                        <span>Workout Plans</span>
                    </a>
                </li>
                <li>
                    <a class="luf-sidebar__link" href="<?php echo APP_URL; ?>modules/sessions/">
                        <span class="luf-sidebar__link-icon"><i class="fas fa-calendar-alt"></i></span>
                        <span>Training Sessions</span>
                    </a>
                </li>
                <li>
                    <a class="luf-sidebar__link" href="<?php echo APP_URL; ?>modules/attendance/<?php echo $userRole === 'member' ? 'my-attendance.php' : 'index.php'; ?>">
                        <span class="luf-sidebar__link-icon"><i class="fas fa-clipboard-check"></i></span>
                        <span><?php echo $userRole === 'member' ? 'My Attendance' : 'Attendance'; ?></span>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($userRole === 'admin'): ?>
        <div class="luf-sidebar__section">
            <span class="luf-sidebar__label">Finance</span>
            <ul class="luf-sidebar__list">
                <li>
                    <a class="luf-sidebar__link" href="<?php echo APP_URL; ?>modules/payments/">
                        <span class="luf-sidebar__link-icon"><i class="fas fa-money-bill-wave"></i></span>
                        <span>Payments</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="luf-sidebar__section">
            <span class="luf-sidebar__label">Reports</span>
            <ul class="luf-sidebar__list">
                <li>
                    <a class="luf-sidebar__link" href="<?php echo APP_URL; ?>modules/reports/members.php">
                        <span class="luf-sidebar__link-icon"><i class="fas fa-chart-bar"></i></span>
                        <span>Members Report</span>
                    </a>
                </li>
                <li>
                    <a class="luf-sidebar__link" href="<?php echo APP_URL; ?>modules/reports/revenue.php">
                        <span class="luf-sidebar__link-icon"><i class="fas fa-chart-line"></i></span>
                        <span>Revenue Report</span>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>

        <div class="luf-sidebar__footer">
            <a class="luf-sidebar__logout" href="<?php echo APP_URL; ?>auth/logout.php">
                <span class="luf-sidebar__link-icon"><i class="fas fa-sign-out-alt"></i></span>
                <span>Logout</span>
            </a>
        </div>

    </div>
</nav>