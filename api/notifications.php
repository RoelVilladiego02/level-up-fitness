<?php
/**
 * Notifications API
 * Handles AJAX requests for notification operations
 * Level Up Fitness - Gym Management System
 */

// Start session and load dependencies
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once dirname(dirname(__FILE__)) . '/config/database.php';
require_once dirname(dirname(__FILE__)) . '/includes/functions.php';

// Set JSON response header
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = isset($_POST['action']) ? sanitize($_POST['action']) : '';
$response = ['success' => false, 'message' => 'Invalid action'];

try {
    switch ($action) {
        case 'mark_read':
            $notificationId = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
            if ($notificationId > 0) {
                if (markNotificationAsRead($notificationId, $_SESSION['user_id'])) {
                    $response = ['success' => true, 'message' => 'Notification marked as read'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to mark notification as read'];
                }
            }
            break;

        case 'mark_all_read':
            if (markAllNotificationsAsRead($_SESSION['user_id'])) {
                $response = ['success' => true, 'message' => 'All notifications marked as read'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to mark all as read'];
            }
            break;

        case 'delete':
            $notificationId = isset($_POST['notification_id']) ? intval($_POST['notification_id']) : 0;
            if ($notificationId > 0) {
                if (deleteNotification($notificationId, $_SESSION['user_id'])) {
                    $response = ['success' => true, 'message' => 'Notification deleted'];
                } else {
                    $response = ['success' => false, 'message' => 'Failed to delete notification'];
                }
            }
            break;

        case 'get_unread_count':
            $unreadCount = getUnreadNotificationCount($_SESSION['user_id']);
            $response = [
                'success' => true,
                'unread_count' => $unreadCount,
                'message' => 'Unread count retrieved'
            ];
            break;

        case 'get_unread':
            $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;
            $unreadNotifications = getUnreadNotifications($_SESSION['user_id'], $limit);
            $response = [
                'success' => true,
                'notifications' => $unreadNotifications,
                'message' => 'Unread notifications retrieved'
            ];
            break;

        default:
            $response = ['success' => false, 'message' => 'Unknown action'];
            break;
    }
} catch (Exception $e) {
    error_log('Notification API Error: ' . $e->getMessage());
    $response = ['success' => false, 'message' => 'An error occurred'];
}

echo json_encode($response);
?>
