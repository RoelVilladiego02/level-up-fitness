<?php
/**
 * Sidebar Navigation Template with Role-Based Access Control
 * Level Up Fitness - Gym Management System
 * Include this file in your main layout pages
 */

$userRole = $_SESSION['user_type'] ?? 'member';
?>

<nav class="col-md-3 col-lg-2 d-md-block bg-light sidebar">
    <div class="position-sticky pt-3">
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>MAIN MENU</span>
        </h6>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL; ?>dashboard/">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
        </ul>

        <?php if ($userRole === 'admin'): ?>
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>MANAGEMENT</span>
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
        <?php endif; ?>

        <?php if ($userRole === 'admin' || $userRole === 'member'): ?>
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>MEMBER OPERATIONS</span>
        </h6>
        <ul class="nav flex-column">
            <?php if ($userRole === 'member'): ?>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL; ?>modules/trainers/my-trainer.php">
                    <i class="fas fa-user-tie"></i> My Trainer
                </a>
            </li>
            <?php endif; ?>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL; ?>modules/workouts/">
                    <i class="fas fa-dumbbell"></i> Workout Plans
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL; ?>modules/reservations/">
                    <i class="fas fa-bookmark"></i> Reservations
                </a>
            </li>
        </ul>
        <?php endif; ?>

        <?php if ($userRole === 'admin' || $userRole === 'trainer'): ?>
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>TRAINER OPERATIONS</span>
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
        </ul>
        <?php endif; ?>

        <?php if ($userRole === 'admin'): ?>
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>FINANCE</span>
        </h6>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL; ?>modules/payments/">
                    <i class="fas fa-money-bill-wave"></i> Payments
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>REPORTS</span>
        </h6>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL; ?>modules/reports/members.php">
                    <i class="fas fa-chart-bar"></i> Members Report
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="<?php echo APP_URL; ?>modules/reports/revenue.php">
                    <i class="fas fa-chart-line"></i> Revenue Report
                </a>
            </li>
        </ul>
        <?php endif; ?>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>SETTINGS</span>
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

<style>
.sidebar {
    position: relative;
    background-color: #f8f9fa;
    min-height: calc(100vh - 60px);
    overflow-y: auto;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
}

.sidebar .nav-link {
    color: #495057;
    padding: 0.75rem 1.5rem;
    font-weight: 500;
    border-left: 3px solid transparent;
    transition: all 0.3s ease;
}

.sidebar .nav-link:hover {
    color: var(--primary-color);
    background-color: rgba(74, 144, 226, 0.05);
    border-left-color: var(--primary-color);
}

.sidebar .nav-link.active {
    color: var(--primary-color);
    background-color: rgba(74, 144, 226, 0.1);
    border-left-color: var(--primary-color);
}

.sidebar .nav-link i {
    margin-right: 0.75rem;
    width: 1.25rem;
    text-align: center;
}

.sidebar-heading {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05rem;
    padding-top: 0.5rem;
}

/* Responsive: Hide sidebar on small screens */
@media (max-width: 768px) {
    .sidebar {
        display: none;
    }
    
    .sidebar.show {
        display: block;
        position: fixed;
        left: 0;
        top: 0;
        z-index: 999;
        width: 250px;
        height: 100vh;
        background-color: white;
        box-shadow: 0 0 10px rgba(0,0,0,0.2);
    }
}
</style>
