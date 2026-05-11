<?php
ob_start();
/**
 * Header Template
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once dirname(dirname(__FILE__)) . '/config/database.php';
require_once dirname(dirname(__FILE__)) . '/includes/functions.php';
require_once dirname(dirname(__FILE__)) . '/includes/email-notifications.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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

    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>assets/css/style.css" rel="stylesheet">

    <style>
    /* ─── Bell button ─────────────────────────────────────────── */
    #notifBellBtn {
        background: none;
        border: none;
        color: #fff;
        padding: 6px 10px;
        border-radius: 8px;
        cursor: pointer;
        position: relative;
        transition: background .15s;
        line-height: 1;
    }
    #notifBellBtn:hover { background: rgba(255,255,255,.15); }

    #notifBadge {
        position: absolute;
        top: 0;
        right: 0;
        transform: translate(30%, -30%);
        background: #dc3545;
        color: #fff;
        font-size: .65rem;
        font-weight: 700;
        min-width: 18px;
        height: 18px;
        border-radius: 9px;
        padding: 0 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        pointer-events: none;
    }
    #notifBadge.hidden { display: none; }

    /* ─── Panel — fixed to viewport, never clipped ─────────────── */
    #notifPanel {
        position: fixed;
        top: 56px;                        /* just below the navbar */
        right: 12px;
        width: 360px;
        max-width: calc(100vw - 24px);    /* stays inside viewport on mobile */
        max-height: 520px;
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 8px 40px rgba(0,0,0,.18);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        z-index: 1055;                    /* above navbar (Bootstrap uses 1030) */
        /* hidden state */
        opacity: 0;
        pointer-events: none;
        transform: translateY(-8px) scale(.97);
        transition: opacity .18s ease, transform .18s ease;
    }
    #notifPanel.open {
        opacity: 1;
        pointer-events: auto;
        transform: translateY(0) scale(1);
    }

    /* ─── Panel header ─────────────────────────────────────────── */
    .np-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px 16px 10px;
        border-bottom: 1px solid #f0f0f0;
        flex-shrink: 0;
    }
    .np-header h6 {
        margin: 0;
        font-weight: 700;
        font-size: .9rem;
        color: #212529;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .np-count {
        font-size: .68rem;
        background: #0d6efd;
        color: #fff;
        padding: 1px 7px;
        border-radius: 10px;
        font-weight: 700;
    }
    .np-header-actions { display: flex; gap: 4px; align-items: center; }

    .np-btn {
        border: none;
        background: none;
        color: #6c757d;
        font-size: .76rem;
        padding: 4px 9px;
        border-radius: 6px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-weight: 600;
        transition: background .15s, color .15s;
        white-space: nowrap;
    }
    .np-btn:hover { background: #f0f4ff; color: #0d6efd; }
    .np-btn.spinning i { animation: npSpin .55s linear; }
    @keyframes npSpin { to { transform: rotate(360deg); } }

    /* ─── Scrollable list ──────────────────────────────────────── */
    .np-list {
        overflow-y: auto;
        flex: 1;
        scrollbar-width: thin;
        scrollbar-color: #dee2e6 transparent;
    }
    .np-list::-webkit-scrollbar { width: 4px; }
    .np-list::-webkit-scrollbar-thumb { background: #dee2e6; border-radius: 4px; }

    /* ─── Notification item ────────────────────────────────────── */
    .np-item {
        display: flex;
        align-items: flex-start;
        gap: 11px;
        padding: 11px 16px;
        border-bottom: 1px solid #f3f4f6;
        cursor: pointer;
        transition: background .12s;
        position: relative;
        user-select: none;
    }
    .np-item:last-child { border-bottom: none; }
    .np-item:hover { background: #f8f9ff; }
    .np-item.unread { background: #f2f5ff; }
    .np-item.unread:hover { background: #eaefff; }

    /* blue dot */
    .np-dot {
        position: absolute;
        right: 14px;
        top: 50%;
        transform: translateY(-50%);
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #0d6efd;
        flex-shrink: 0;
    }
    .np-item:not(.unread) .np-dot { display: none; }

    /* icon bubble */
    .np-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: .82rem;
    }
    .np-icon.c-primary   { background:#dce8ff; color:#0d6efd; }
    .np-icon.c-success   { background:#d4f4e2; color:#198754; }
    .np-icon.c-warning   { background:#fff3cd; color:#e5a800; }
    .np-icon.c-danger    { background:#fde8e8; color:#dc3545; }
    .np-icon.c-info      { background:#d8f4f9; color:#0dcaf0; }
    .np-icon.c-secondary { background:#e9ecef; color:#6c757d; }

    .np-body { flex: 1; min-width: 0; padding-right: 18px; }
    .np-title {
        font-size: .83rem;
        font-weight: 700;
        color: #212529;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        margin-bottom: 2px;
    }
    .np-msg {
        font-size: .77rem;
        color: #6c757d;
        line-height: 1.4;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
    .np-time { font-size: .71rem; color: #adb5bd; margin-top: 3px; }

    /* ─── Empty state ──────────────────────────────────────────── */
    .np-empty {
        padding: 36px 16px;
        text-align: center;
        color: #adb5bd;
    }
    .np-empty i { font-size: 1.9rem; display: block; margin-bottom: 8px; }
    .np-empty p { margin: 0; font-size: .84rem; }

    /* ─── Skeleton loader ──────────────────────────────────────── */
    .np-skeleton {
        display: flex;
        gap: 11px;
        padding: 11px 16px;
        border-bottom: 1px solid #f3f4f6;
    }
    .sk-circle { width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0; }
    .sk-lines  { flex: 1; display: flex; flex-direction: column; gap: 6px; padding-top: 4px; }
    .sk-line   { height: 9px; border-radius: 4px; }
    .sk-line.w-50 { width: 50%; }
    .sk-line.w-75 { width: 75%; }
    .sk-line.w-35 { width: 35%; }
    /* shimmer applied to the containers */
    .sk-circle, .sk-line {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: npShimmer 1.4s infinite linear;
    }
    @keyframes npShimmer {
        0%   { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }

    /* ─── Panel footer ─────────────────────────────────────────── */
    .np-footer { border-top: 1px solid #f0f0f0; flex-shrink: 0; }
    .np-footer a {
        display: block;
        text-align: center;
        padding: 10px;
        font-size: .82rem;
        font-weight: 700;
        color: #0d6efd;
        text-decoration: none;
        transition: background .13s;
        border-radius: 0 0 14px 14px;
    }
    .np-footer a:hover { background: #f0f4ff; }
    </style>

    <script>window.APP_URL = "<?php echo APP_URL; ?>";</script>
</head>
<body>

<?php if (isset($_SESSION['user_id'])):
    $unreadCount         = getUnreadNotificationCount($_SESSION['user_id']);
    $unreadNotifications = getUnreadNotifications($_SESSION['user_id'], 5);

    $colorMap = [
        'primary'   => 'c-primary',
        'success'   => 'c-success',
        'warning'   => 'c-warning',
        'danger'    => 'c-danger',
        'info'      => 'c-info',
        'secondary' => 'c-secondary',
    ];
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
    <div class="container-fluid">
        <span class="navbar-brand">
            <i class="fas fa-bolt"></i> <?php echo APP_NAME; ?>
        </span>
        <div class="ms-auto d-flex align-items-center gap-3">

            <!-- Plain button — no href, no form, no data-bs-toggle -->
            <button type="button" id="notifBellBtn"
                    aria-label="Toggle notifications"
                    aria-expanded="false"
                    aria-controls="notifPanel">
                <i class="fas fa-bell fa-lg"></i>
                <span id="notifBadge" class="<?php echo $unreadCount > 0 ? '' : 'hidden'; ?>">
                    <?php echo $unreadCount > 99 ? '99+' : $unreadCount; ?>
                </span>
            </button>

        </div>
    </div>
</nav>

<!-- Panel lives at body root so nothing clips it -->
<div id="notifPanel" role="dialog" aria-modal="false" aria-label="Notifications">

    <div class="np-header">
        <h6>
            <i class="fas fa-bell text-primary"></i>
            Notifications
            <span class="np-count" id="npCount"
                  style="<?php echo $unreadCount > 0 ? '' : 'display:none'; ?>">
                <?php echo $unreadCount; ?>
            </span>
        </h6>
        <div class="np-header-actions">
            <button type="button" class="np-btn" id="npRefreshBtn" onclick="npRefresh()">
                <i class="fas fa-rotate-right"></i> Refresh
            </button>
            <button type="button" class="np-btn" id="npMarkAllBtn" onclick="npMarkAll()"
                    style="<?php echo $unreadCount > 0 ? '' : 'display:none'; ?>">
                <i class="fas fa-check-double"></i> All read
            </button>
        </div>
    </div>

    <div class="np-list" id="npList">
        <?php if (count($unreadNotifications) > 0): ?>
            <?php foreach ($unreadNotifications as $n):
                $bubble = $colorMap[$n['icon_color']] ?? 'c-primary';
            ?>
            <div class="np-item unread" id="npi-<?php echo $n['notification_id']; ?>"
                 onclick="npMarkOne(<?php echo $n['notification_id']; ?>, this)">
                <div class="np-icon <?php echo $bubble; ?>">
                    <i class="fas fa-<?php echo htmlspecialchars($n['notification_icon']); ?>"></i>
                </div>
                <div class="np-body">
                    <div class="np-title"><?php echo htmlspecialchars($n['notification_title']); ?></div>
                    <div class="np-msg"><?php echo htmlspecialchars($n['notification_message']); ?></div>
                    <div class="np-time">
                        <i class="fas fa-clock me-1"></i><?php echo timeAgo($n['created_at']); ?>
                    </div>
                </div>
                <span class="np-dot" aria-hidden="true"></span>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="np-empty">
                <i class="fas fa-bell-slash"></i>
                <p>You're all caught up!</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="np-footer">
        <a href="<?php echo APP_URL; ?>modules/notifications/">
            View all notifications <i class="fas fa-arrow-right ms-1"></i>
        </a>
    </div>
</div><!-- #notifPanel -->

<script>
(function () {
    'use strict';

    var _count = <?php echo (int)$unreadCount; ?>;
    var _open  = false;

    var bell    = document.getElementById('notifBellBtn');
    var panel   = document.getElementById('notifPanel');
    var badge   = document.getElementById('notifBadge');
    var npCount = document.getElementById('npCount');
    var markBtn = document.getElementById('npMarkAllBtn');

    /* ── open / close ───────────────────────────────────────────── */
    bell.addEventListener('click', function (e) {
        e.stopPropagation();
        _open = !_open;
        panel.classList.toggle('open', _open);
        bell.setAttribute('aria-expanded', String(_open));
    });

    document.addEventListener('click', function (e) {
        if (_open && !panel.contains(e.target) && e.target !== bell && !bell.contains(e.target)) {
            _close();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && _open) { _close(); bell.focus(); }
    });

    function _close() {
        _open = false;
        panel.classList.remove('open');
        bell.setAttribute('aria-expanded', 'false');
    }

    /* ── badge ──────────────────────────────────────────────────── */
    function _updateBadge(n) {
        _count = Math.max(0, n);
        var label = _count > 99 ? '99+' : String(_count);
        badge.textContent   = label;
        npCount.textContent = label;
        var show = _count > 0;
        badge.classList.toggle('hidden', !show);
        npCount.style.display = show ? '' : 'none';
        if (markBtn) markBtn.style.display = show ? '' : 'none';
    }

    /* ── mark one ───────────────────────────────────────────────── */
    window.npMarkOne = function (id, el) {
        fetch(window.APP_URL + 'api/notifications.php', {
            method : 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body   : 'action=mark_read&notification_id=' + id
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.success) {
                el.classList.remove('unread');
                _updateBadge(_count - 1);
                if (!document.querySelector('#npList .np-item.unread')) _showEmpty();
            }
        })
        .catch(function () {});
    };

    /* ── mark all ───────────────────────────────────────────────── */
    window.npMarkAll = function () {
        fetch(window.APP_URL + 'api/notifications.php', {
            method : 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body   : 'action=mark_all_read'
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.success) {
                document.querySelectorAll('#npList .np-item').forEach(function (el) {
                    el.classList.remove('unread');
                });
                _updateBadge(0);
                _showEmpty();
            }
        })
        .catch(function () {});
    };

    /* ── refresh ────────────────────────────────────────────────── */
    window.npRefresh = function () {
        var btn  = document.getElementById('npRefreshBtn');
        var list = document.getElementById('npList');
        btn.classList.add('spinning');
        setTimeout(function () { btn.classList.remove('spinning'); }, 600);
        list.innerHTML = _skeletons(3);

        fetch(window.APP_URL + 'api/notifications.php', {
            method : 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body   : 'action=get_unread&limit=5'
        })
        .then(function (r) { return r.json(); })
        .then(function (d) {
            if (d.success && d.notifications) {
                _renderItems(d.notifications);
                _updateBadge(d.unread_count != null ? d.unread_count : d.notifications.length);
            } else {
                _showEmpty();
            }
        })
        .catch(function () {
            list.innerHTML = '<div class="np-empty"><i class="fas fa-exclamation-circle"></i><p>Could not refresh. Try again.</p></div>';
        });
    };

    /* ── helpers ────────────────────────────────────────────────── */
    var _colorMap = {
        primary:'c-primary', success:'c-success', warning:'c-warning',
        danger:'c-danger',   info:'c-info',        secondary:'c-secondary'
    };

    function _renderItems(items) {
        var list = document.getElementById('npList');
        if (!items.length) { _showEmpty(); return; }
        list.innerHTML = items.map(function (n) {
            var bubble = _colorMap[n.icon_color] || 'c-primary';
            var msg    = (n.notification_message || '').length > 100
                         ? n.notification_message.slice(0, 100) + '…'
                         : (n.notification_message || '');
            return '<div class="np-item unread" id="npi-' + n.notification_id + '"'
                 + ' onclick="npMarkOne(' + n.notification_id + ', this)">'
                 + '<div class="np-icon ' + bubble + '">'
                 + '<i class="fas fa-' + _esc(n.notification_icon) + '"></i></div>'
                 + '<div class="np-body">'
                 + '<div class="np-title">' + _esc(n.notification_title) + '</div>'
                 + '<div class="np-msg">'   + _esc(msg) + '</div>'
                 + '<div class="np-time"><i class="fas fa-clock me-1"></i>' + _esc(n.time_ago || '') + '</div>'
                 + '</div>'
                 + '<span class="np-dot" aria-hidden="true"></span>'
                 + '</div>';
        }).join('');
    }

    function _showEmpty() {
        document.getElementById('npList').innerHTML =
            '<div class="np-empty"><i class="fas fa-bell-slash"></i><p>You\'re all caught up!</p></div>';
    }

    function _skeletons(n) {
        var s = '';
        for (var i = 0; i < n; i++) {
            s += '<div class="np-skeleton">'
               + '<div class="sk-circle"></div>'
               + '<div class="sk-lines">'
               + '<div class="sk-line w-50"></div>'
               + '<div class="sk-line w-75"></div>'
               + '<div class="sk-line w-35"></div>'
               + '</div></div>';
        }
        return s;
    }

    function _esc(str) {
        var d = document.createElement('div');
        d.textContent = String(str || '');
        return d.innerHTML;
    }

    /* backwards-compat aliases used by index.php */
    window.markNotificationRead     = window.npMarkOne;
    window.markAllNotificationsRead = window.npMarkAll;

})();
</script>

<?php endif; ?>