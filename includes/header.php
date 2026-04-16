<?php
/**
 * Header Template
 * Level Up Fitness - Gym Management System
 */

// Load configuration
require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once dirname(dirname(__FILE__)) . '/config/database.php';
require_once dirname(dirname(__FILE__)) . '/includes/functions.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout check
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
    session_destroy();
    redirect(APP_URL . 'auth/login.php');
}
$_SESSION['last_activity'] = time();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo APP_URL; ?>assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Notification Topbar -->
    <?php if (isset($_SESSION['user_id'])): 
        $unreadCount = getUnreadNotificationCount($_SESSION['user_id']);
        $unreadNotifications = getUnreadNotifications($_SESSION['user_id'], 5);
    ?>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container-fluid">
            <span class="navbar-brand">
                <i class="fas fa-bolt"></i> <?php echo APP_NAME; ?>
            </span>
            <div class="ms-auto d-flex align-items-center gap-3">
                <!-- Notification Bell -->
                <div class="notification-bell-container">
                    <button class="btn btn-link text-white position-relative" data-bs-toggle="dropdown" aria-expanded="false" id="notificationBell">
                        <i class="fas fa-bell fa-lg"></i>
                        <?php if ($unreadCount > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unreadCount > 99 ? '99+' : $unreadCount; ?>
                            </span>
                        <?php endif; ?>
                    </button>
                    
                    <!-- Notification Dropdown -->
                    <ul class="dropdown-menu dropdown-menu-end notification-dropdown" style="width: 350px; max-height: 500px; overflow-y: auto;">
                        <?php if (count($unreadNotifications) > 0): ?>
                            <?php foreach ($unreadNotifications as $notification): ?>
                                <li>
                                    <a class="dropdown-item notification-item unread" href="#" onclick="markNotificationRead(<?php echo $notification['notification_id']; ?>)">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="notification-title">
                                                    <i class="fas fa-<?php echo htmlspecialchars($notification['notification_icon']); ?> text-<?php echo htmlspecialchars($notification['icon_color']); ?>"></i>
                                                    <strong><?php echo htmlspecialchars($notification['notification_title']); ?></strong>
                                                </div>
                                                <div class="notification-message text-muted small">
                                                    <?php echo htmlspecialchars(substr($notification['notification_message'], 0, 80)); ?>
                                                    <?php if (strlen($notification['notification_message']) > 80): ?>...<?php endif; ?>
                                                </div>
                                                <div class="notification-time small text-muted mt-1">
                                                    <?php echo timeAgo($notification['created_at']); ?>
                                                </div>
                                            </div>
                                            <div class="notification-indicator">
                                                <span class="badge bg-primary"></span>
                                            </div>
                                        </div>
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                            <?php endforeach; ?>
                            <li>
                                <a class="dropdown-item text-center text-primary" href="<?php echo APP_URL; ?>modules/notifications/">
                                    View All Notifications
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button class="dropdown-item text-center text-muted" onclick="markAllNotificationsRead()">
                                    Mark All as Read
                                </button>
                            </li>
                        <?php else: ?>
                            <li class="dropdown-item text-center py-3">
                                <i class="fas fa-bell-slash text-muted"></i>
                                <p class="mb-0 text-muted">No new notifications</p>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- User Menu -->
                <div class="dropdown">
                    <button class="btn btn-link text-white dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle fa-lg"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>dashboard/">Profile</a></li>
                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>modules/notifications/">Notifications</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?php echo APP_URL; ?>auth/logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
