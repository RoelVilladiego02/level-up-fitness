<?php
/**
 * Notifications Center
 * Level Up Fitness - Gym Management System
 */

// Start session and load dependencies
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/database.php';
require_once dirname(dirname(dirname(__FILE__))) . '/includes/functions.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    redirect(APP_URL . 'auth/login.php');
}

// Get pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$filter = isset($_GET['filter']) ? sanitize($_GET['filter']) : 'all'; // all, unread, read
$limit = 20;
$offset = ($page - 1) * $limit;

// Get notifications based on filter
$notifications = [];
$totalCount = 0;

if ($filter === 'unread') {
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? AND is_read = 0
        ORDER BY priority DESC, created_at DESC
        LIMIT " . intval($limit) . " OFFSET " . intval($offset) . "
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll();
    
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 0");
    $countStmt->execute([$_SESSION['user_id']]);
} elseif ($filter === 'read') {
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ? AND is_read = 1
        ORDER BY created_at DESC
        LIMIT " . intval($limit) . " OFFSET " . intval($offset) . "
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll();
    
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 1");
    $countStmt->execute([$_SESSION['user_id']]);
} else {
    $stmt = $pdo->prepare("
        SELECT * FROM notifications 
        WHERE user_id = ?
        ORDER BY priority DESC, created_at DESC
        LIMIT " . intval($limit) . " OFFSET " . intval($offset) . "
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $notifications = $stmt->fetchAll();
    
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ?");
    $countStmt->execute([$_SESSION['user_id']]);
}

$countResult = $countStmt->fetch();
$totalCount = $countResult['total'] ?? 0;
$totalPages = ceil($totalCount / $limit);

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'mark_all_read') {
            markAllNotificationsAsRead($_SESSION['user_id']);
            setMessage('All notifications marked as read', 'success');
            redirect(APP_URL . 'modules/notifications/?filter=' . $filter);
        } elseif ($_POST['action'] === 'delete_read') {
            deleteReadNotifications($_SESSION['user_id']);
            setMessage('Read notifications deleted', 'success');
            redirect(APP_URL . 'modules/notifications/?filter=' . $filter);
        }
    }
}

include dirname(dirname(dirname(__FILE__))) . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <h1><i class="fas fa-bell"></i> Notifications</h1>
            </div>

            <?php displayMessage(); ?>

            <!-- Filters and Actions -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="btn-group" role="group">
                                <a href="?filter=all" class="btn btn-sm <?php echo $filter === 'all' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    <i class="fas fa-list"></i> All (<?php echo getUnreadNotificationCount($_SESSION['user_id']); ?>)
                                </a>
                                <a href="?filter=unread" class="btn btn-sm <?php echo $filter === 'unread' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    <i class="fas fa-star"></i> Unread (<?php $unreadCount = getUnreadNotificationCount($_SESSION['user_id']); echo $unreadCount; ?>)
                                </a>
                                <a href="?filter=read" class="btn btn-sm <?php echo $filter === 'read' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                    <i class="fas fa-check"></i> Read
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <?php if ($unreadCount > 0): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="mark_all_read">
                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-check-double"></i> Mark All Read
                                    </button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="delete_read">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="fas fa-trash"></i> Delete Read
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications List -->
            <div class="notifications-list">
                <?php if (count($notifications) > 0): ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-card card mb-2 <?php echo $notification['is_read'] ? 'read' : 'unread'; ?>">
                            <div class="card-body p-3">
                                <div class="row align-items-start">
                                    <div class="col-auto">
                                        <div class="notification-icon text-<?php echo htmlspecialchars($notification['icon_color']); ?> fs-5">
                                            <i class="fas fa-<?php echo htmlspecialchars($notification['notification_icon']); ?>"></i>
                                        </div>
                                    </div>
                                    <div class="col">
                                        <div class="notification-header">
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($notification['notification_title']); ?>
                                                <?php if (!$notification['is_read']): ?>
                                                    <span class="badge bg-primary">New</span>
                                                <?php endif; ?>
                                            </h6>
                                            <small class="text-muted">
                                                <i class="fas fa-clock"></i> <?php echo timeAgo($notification['created_at']); ?>
                                            </small>
                                        </div>
                                        <p class="mb-2 text-muted">
                                            <?php echo htmlspecialchars($notification['notification_message']); ?>
                                        </p>
                                        <div class="notification-actions mt-2">
                                            <?php if (!$notification['is_read']): ?>
                                                <button class="btn btn-xs btn-link btn-outline-primary" onclick="markNotificationRead(<?php echo $notification['notification_id']; ?>)">
                                                    <i class="fas fa-check"></i> Mark as Read
                                                </button>
                                            <?php endif; ?>
                                            <?php if (!empty($notification['action_url'])): ?>
                                                <a href="<?php echo htmlspecialchars($notification['action_url']); ?>" class="btn btn-xs btn-link btn-outline-info">
                                                    <i class="fas fa-arrow-right"></i> View
                                                </a>
                                            <?php endif; ?>
                                            <button class="btn btn-xs btn-link btn-outline-danger" onclick="deleteNotification(<?php echo $notification['notification_id']; ?>)">
                                                <i class="fas fa-times"></i> Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1&filter=<?php echo $filter; ?>">First</a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&filter=<?php echo $filter; ?>">Previous</a>
                                    </li>
                                <?php endif; ?>

                                <?php
                                $start = max(1, $page - 2);
                                $end = min($totalPages, $page + 2);
                                
                                for ($i = $start; $i <= $end; $i++):
                                    ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>

                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&filter=<?php echo $filter; ?>">Next</a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?php echo $totalPages; ?>&filter=<?php echo $filter; ?>">Last</a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info text-center py-5">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <h5>No Notifications</h5>
                        <p>You're all caught up! Check back later for new notifications.</p>
                    </div>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<script>
function markNotificationRead(notificationId) {
    $.ajax({
        url: '<?php echo APP_URL; ?>api/notifications.php',
        method: 'POST',
        data: {
            action: 'mark_read',
            notification_id: notificationId
        },
        success: function(response) {
            if (response.success) {
                location.reload();
            }
        }
    });
}

function deleteNotification(notificationId) {
    if (confirm('Are you sure you want to delete this notification?')) {
        $.ajax({
            url: '<?php echo APP_URL; ?>api/notifications.php',
            method: 'POST',
            data: {
                action: 'delete',
                notification_id: notificationId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    }
}

function markAllNotificationsRead() {
    if (confirm('Mark all notifications as read?')) {
        $.ajax({
            url: '<?php echo APP_URL; ?>api/notifications.php',
            method: 'POST',
            data: {
                action: 'mark_all_read'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                }
            }
        });
    }
}
</script>

<?php include dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
