<?php
/**
 * Notifications Center
 * Level Up Fitness - Gym Management System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/database.php';
require_once dirname(dirname(dirname(__FILE__))) . '/includes/functions.php';

if (!isset($_SESSION['user_id'])) {
    redirect(APP_URL . 'auth/login.php');
}

// ── Pagination & filter ────────────────────────────────────────────────────
$page   = isset($_GET['page'])   ? max(1, intval($_GET['page']))   : 1;
$filter = isset($_GET['filter']) ? sanitize($_GET['filter'])       : 'all';
$limit  = 20;
$offset = ($page - 1) * $limit;
$uid    = $_SESSION['user_id'];

// ── Handle bulk actions BEFORE any output ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'mark_all_read') {
        markAllNotificationsAsRead($uid);
        setMessage('All notifications marked as read.', 'success');
    } elseif ($_POST['action'] === 'delete_read') {
        deleteReadNotifications($uid);
        setMessage('Read notifications deleted.', 'success');
    }
    redirect(APP_URL . 'modules/notifications/?filter=' . urlencode($filter));
}

// ── Fetch notifications ────────────────────────────────────────────────────
$whereExtra = match ($filter) {
    'unread' => ' AND is_read = 0',
    'read'   => ' AND is_read = 1',
    default  => '',
};

$orderBy = $filter === 'read' ? 'created_at DESC' : 'priority DESC, created_at DESC';

$stmt = $pdo->prepare("
    SELECT * FROM notifications
    WHERE user_id = ? $whereExtra
    ORDER BY $orderBy
    LIMIT " . intval($limit) . " OFFSET " . intval($offset) . "
");
$stmt->execute([$uid]);
$notifications = $stmt->fetchAll();

$countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? $whereExtra");
$countStmt->execute([$uid]);
$totalCount = (int)($countStmt->fetch()['total'] ?? 0);
$totalPages = (int)ceil($totalCount / $limit);

// Single call for unread count — used in filter buttons AND action buttons
$unreadCount = getUnreadNotificationCount($uid);

include dirname(dirname(dirname(__FILE__))) . '/includes/header.php';
?>

<style>
/* ── Page layout ── */
.notif-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: .75rem;
}
.notif-page-header h1 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #212529;
    margin: 0;
}

/* ── Filter tabs ── */
.notif-filters {
    display: flex;
    gap: .35rem;
    flex-wrap: wrap;
}
.notif-filter-btn {
    border: 1.5px solid #0d6efd;
    background: none;
    color: #0d6efd;
    border-radius: 20px;
    padding: 5px 16px;
    font-size: .82rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s, color .15s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}
.notif-filter-btn.active,
.notif-filter-btn:hover {
    background: #0d6efd;
    color: #fff;
}
.notif-filter-btn .badge {
    font-size: .72rem;
    padding: 2px 6px;
    border-radius: 10px;
    background: rgba(255,255,255,.25);
}
.notif-filter-btn.active .badge { background: rgba(255,255,255,.3); }
.notif-filter-btn:not(.active) .badge { background: #0d6efd; color: #fff; }

/* ── Action bar ── */
.notif-action-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: .5rem;
    padding: .875rem 1.25rem;
    background: #fff;
    border-radius: 12px;
    border: 1px solid #e9ecef;
    margin-bottom: 1.25rem;
    box-shadow: 0 1px 4px rgba(0,0,0,.05);
}
.notif-action-bar .results-info {
    font-size: .82rem;
    color: #6c757d;
}
.notif-action-bar .actions { display: flex; gap: .5rem; flex-wrap: wrap; }
.btn-action {
    font-size: .8rem;
    padding: 5px 14px;
    border-radius: 8px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

/* ── Notification cards ── */
.notif-card {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 12px;
    padding: 14px 16px;
    margin-bottom: .625rem;
    transition: box-shadow .15s, border-color .15s;
    position: relative;
}
.notif-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.08); border-color: #d0d8ff; }
.notif-card.unread {
    background: #f4f7ff;
    border-left: 4px solid #0d6efd;
}
.notif-card.unread:hover { border-color: #0d6efd; }

/* Unread dot */
.notif-card.unread .notif-unread-dot {
    position: absolute;
    top: 16px;
    right: 16px;
    width: 9px;
    height: 9px;
    border-radius: 50%;
    background: #0d6efd;
}

/* Icon bubble */
.notif-card-icon {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    font-size: .95rem;
}
.notif-card-icon.c-primary   { background: #dce8ff; color: #0d6efd; }
.notif-card-icon.c-success   { background: #d4f4e2; color: #198754; }
.notif-card-icon.c-warning   { background: #fff3cd; color: #e5a800; }
.notif-card-icon.c-danger    { background: #fde8e8; color: #dc3545; }
.notif-card-icon.c-info      { background: #d8f4f9; color: #0dcaf0; }
.notif-card-icon.c-secondary { background: #e9ecef; color: #6c757d; }

.notif-card-body { flex: 1; min-width: 0; }
.notif-card-title {
    font-weight: 700;
    font-size: .9rem;
    color: #212529;
    margin-bottom: 3px;
    display: flex;
    align-items: center;
    gap: 6px;
    flex-wrap: wrap;
}
.notif-card-title .badge-new {
    font-size: .68rem;
    padding: 2px 7px;
    border-radius: 10px;
    background: #0d6efd;
    color: #fff;
    font-weight: 700;
    letter-spacing: .02em;
}
.notif-card-msg {
    font-size: .84rem;
    color: #495057;
    margin-bottom: 6px;
    line-height: 1.5;
}
.notif-card-time {
    font-size: .76rem;
    color: #adb5bd;
    margin-bottom: 8px;
}
.notif-card-actions { display: flex; gap: .35rem; flex-wrap: wrap; }
.btn-notif-action {
    font-size: .76rem;
    padding: 3px 10px;
    border-radius: 6px;
    font-weight: 600;
    border: 1.5px solid;
    background: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: background .15s, color .15s;
    text-decoration: none;
}
.btn-notif-action.btn-read   { border-color: #198754; color: #198754; }
.btn-notif-action.btn-read:hover   { background: #198754; color: #fff; }
.btn-notif-action.btn-view   { border-color: #0dcaf0; color: #0dcaf0; }
.btn-notif-action.btn-view:hover   { background: #0dcaf0; color: #fff; }
.btn-notif-action.btn-delete { border-color: #dc3545; color: #dc3545; }
.btn-notif-action.btn-delete:hover { background: #dc3545; color: #fff; }

/* ── Empty state ── */
.notif-empty-state {
    text-align: center;
    padding: 4rem 1rem;
    color: #adb5bd;
}
.notif-empty-state i { font-size: 3.5rem; margin-bottom: 1rem; display: block; }
.notif-empty-state h5 { color: #6c757d; font-weight: 700; margin-bottom: .5rem; }
.notif-empty-state p  { font-size: .88rem; margin: 0; }

/* ── Pagination ── */
.pagination .page-link {
    border-radius: 8px !important;
    margin: 0 2px;
    border: 1.5px solid #dee2e6;
    color: #0d6efd;
    font-size: .84rem;
    font-weight: 600;
    padding: 5px 12px;
    transition: background .15s;
}
.pagination .page-item.active .page-link {
    background: #0d6efd;
    border-color: #0d6efd;
    color: #fff;
}
.pagination .page-link:hover:not(.active) { background: #f0f4ff; }

/* ── Responsive tweaks ── */
@media (max-width: 576px) {
    .notif-card { flex-wrap: wrap; }
    .notif-card-icon { width: 36px; height: 36px; font-size: .85rem; }
    .notif-action-bar { flex-direction: column; align-items: flex-start; }
}
</style>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">

            <!-- Page header -->
            <div class="notif-page-header mt-3">
                <h1><i class="fas fa-bell me-2 text-primary"></i>Notifications</h1>

                <!-- Filter tabs -->
                <div class="notif-filters">
                    <a href="?filter=all"
                       class="notif-filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i> All
                        <span class="badge"><?php echo $totalCount; ?></span>
                    </a>
                    <a href="?filter=unread"
                       class="notif-filter-btn <?php echo $filter === 'unread' ? 'active' : ''; ?>">
                        <i class="fas fa-star"></i> Unread
                        <?php if ($unreadCount > 0): ?>
                            <span class="badge"><?php echo $unreadCount; ?></span>
                        <?php endif; ?>
                    </a>
                    <a href="?filter=read"
                       class="notif-filter-btn <?php echo $filter === 'read' ? 'active' : ''; ?>">
                        <i class="fas fa-check"></i> Read
                    </a>
                </div>
            </div>

            <?php displayMessage(); ?>

            <!-- Action bar -->
            <div class="notif-action-bar">
                <span class="results-info">
                    <?php echo number_format($totalCount); ?> notification<?php echo $totalCount !== 1 ? 's' : ''; ?>
                    <?php if ($page > 1 || $totalPages > 1): ?>
                        &mdash; page <?php echo $page; ?> of <?php echo $totalPages; ?>
                    <?php endif; ?>
                </span>
                <div class="actions">
                    <?php if ($unreadCount > 0): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="action" value="mark_all_read">
                            <button type="submit" class="btn btn-action btn-outline-success">
                                <i class="fas fa-check-double"></i> Mark all read
                            </button>
                        </form>
                    <?php endif; ?>
                    <form method="POST" style="display:inline;"
                          onsubmit="return confirm('Delete all read notifications?');">
                        <input type="hidden" name="action" value="delete_read">
                        <button type="submit" class="btn btn-action btn-outline-danger">
                            <i class="fas fa-trash-alt"></i> Delete read
                        </button>
                    </form>
                </div>
            </div>

            <!-- Notifications list -->
            <?php if (count($notifications) > 0): ?>

                <?php
                $colorClass = [
                    'primary'   => 'c-primary',
                    'success'   => 'c-success',
                    'warning'   => 'c-warning',
                    'danger'    => 'c-danger',
                    'info'      => 'c-info',
                    'secondary' => 'c-secondary',
                ];
                ?>

                <?php foreach ($notifications as $n): ?>
                    <?php $cc = $colorClass[$n['icon_color']] ?? 'c-primary'; ?>
                    <div class="notif-card <?php echo $n['is_read'] ? 'read' : 'unread'; ?>"
                         id="notif-card-<?php echo $n['notification_id']; ?>">

                        <?php if (!$n['is_read']): ?>
                            <span class="notif-unread-dot" aria-hidden="true"></span>
                        <?php endif; ?>

                        <div class="notif-card-icon <?php echo $cc; ?>">
                            <i class="fas fa-<?php echo htmlspecialchars($n['notification_icon']); ?>"></i>
                        </div>

                        <div class="notif-card-body">
                            <div class="notif-card-title">
                                <?php echo htmlspecialchars($n['notification_title']); ?>
                                <?php if (!$n['is_read']): ?>
                                    <span class="badge-new">New</span>
                                <?php endif; ?>
                            </div>
                            <div class="notif-card-msg">
                                <?php echo htmlspecialchars($n['notification_message']); ?>
                            </div>
                            <div class="notif-card-time">
                                <i class="fas fa-clock me-1"></i><?php echo timeAgo($n['created_at']); ?>
                            </div>
                            <div class="notif-card-actions">
                                <?php if (!$n['is_read']): ?>
                                    <button class="btn-notif-action btn-read"
                                            onclick="markReadInline(<?php echo $n['notification_id']; ?>, this)">
                                        <i class="fas fa-check"></i> Mark as read
                                    </button>
                                <?php endif; ?>
                                <?php if (!empty($n['action_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($n['action_url']); ?>"
                                       class="btn-notif-action btn-view">
                                        <i class="fas fa-arrow-right"></i> View
                                    </a>
                                <?php endif; ?>
                                <button class="btn-notif-action btn-delete"
                                        onclick="deleteNotificationInline(<?php echo $n['notification_id']; ?>, this)">
                                    <i class="fas fa-times"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Notifications pagination" class="mt-4">
                        <ul class="pagination justify-content-center flex-wrap">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1&filter=<?php echo urlencode($filter); ?>">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&filter=<?php echo urlencode($filter); ?>">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $page - 2);
                            $end   = min($totalPages, $page + 2);
                            for ($i = $start; $i <= $end; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link"
                                       href="?page=<?php echo $i; ?>&filter=<?php echo urlencode($filter); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&filter=<?php echo urlencode($filter); ?>">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $totalPages; ?>&filter=<?php echo urlencode($filter); ?>">
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="notif-empty-state">
                    <i class="fas fa-inbox text-muted"></i>
                    <h5>No notifications</h5>
                    <p>
                        <?php if ($filter === 'unread'): ?>
                            You have no unread notifications. <a href="?filter=all">View all</a>
                        <?php elseif ($filter === 'read'): ?>
                            No read notifications yet. <a href="?filter=all">View all</a>
                        <?php else: ?>
                            You're all caught up! Check back later.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<script>
/** Mark a single notification as read — DOM-only, no page reload */
function markReadInline(id, btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving…';

    fetch(window.APP_URL + 'api/notifications.php', {
        method : 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body   : 'action=mark_read&notification_id=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const card = document.getElementById('notif-card-' + id);
            if (card) {
                card.classList.remove('unread');
                card.classList.add('read');
                // Remove unread dot
                const dot = card.querySelector('.notif-unread-dot');
                if (dot) dot.remove();
                // Remove "New" badge
                const badge = card.querySelector('.badge-new');
                if (badge) badge.remove();
                // Remove the "Mark as read" button itself
                btn.remove();
            }
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check"></i> Mark as read';
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Mark as read';
    });
}

/** Delete a notification — removes card from DOM, no page reload */
function deleteNotificationInline(id, btn) {
    if (!confirm('Delete this notification?')) return;

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    fetch(window.APP_URL + 'api/notifications.php', {
        method : 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body   : 'action=delete&notification_id=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const card = document.getElementById('notif-card-' + id);
            if (card) {
                card.style.transition = 'opacity .2s, transform .2s';
                card.style.opacity    = '0';
                card.style.transform  = 'translateX(20px)';
                setTimeout(() => card.remove(), 220);
            }
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-times"></i> Delete';
        }
    })
    .catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-times"></i> Delete';
    });
}
</script>

<?php include dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>