<?php
/**
 * Reusable PHP Functions
 * Level Up Fitness - Gym Management System
 */

// Load email notification functions
require_once dirname(__FILE__) . '/email-notifications.php';

/**
 * Generate unique IDs with prefix
 */
function generateID($prefix) {
    $timestamp = time();
    $random = rand(100, 999);
    return $prefix . $timestamp . $random;
}

/**
 * Sanitize user input
 */
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email format
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

/**
 * Validate phone number format
 * Supports: 09XXXXXXXXXX, +639XXXXXXXXXX, +63-9-XXXX-XXXX, (09) XXXX-XXXX, and other formats
 */
function isValidPhone($phone) {
    // Remove common formatting characters
    $cleaned = preg_replace('/[\s\-\(\)\.]+/', '', $phone);
    
    // Check if it's a valid international format or local format
    // Filipino: 09XXXXXXXXXX (11 digits) or +639XXXXXXXXXX (13 digits with +63)
    // International: +1-XXXXXXXXXX (10-15 digits is typical)
    if (preg_match('/^\+?[1-9]\d{7,14}$/', $cleaned)) {
        return true;
    }
    
    // Local format: starts with 09, exactly 11 digits
    if (preg_match('/^09\d{9}$/', $cleaned)) {
        return true;
    }
    
    return false;
}

/**
 * Hash password
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

/**
 * Verify password
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate CSRF Token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH / 2));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Redirect to page
 */
function redirect($location) {
    header('Location: ' . $location);
    exit();
}

/**
 * Set session message
 */
function setMessage($message, $type = 'info') {
    $_SESSION['message'] = [
        'text' => $message,
        'type' => $type // 'success', 'error', 'warning', 'info'
    ];
}

/**
 * Get session message
 */
function getMessage() {
    if (isset($_SESSION['message'])) {
        $message = $_SESSION['message'];
        unset($_SESSION['message']);
        return $message;
    }
    return null;
}

/**
 * Display flash message HTML
 */
function displayMessage() {
    $message = getMessage();
    if ($message) {
        $alertClass = 'alert-' . $message['type'];
        echo "<div class='alert {$alertClass} alert-dismissible fade show' role='alert'>
                {$message['text']}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
              </div>";
    }
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check user role
 */
function userHasRole($role) {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === $role;
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect(APP_URL . 'auth/login.php');
    }
}

/**
 * Require specific role
 */
function requireRole($role) {
    requireLogin();
    if (!userHasRole($role)) {
        die('Access denied: You do not have permission to access this page.');
    }
}

/**
 * Get user info from session
 */
function getUserInfo() {
    return [
        'user_id' => $_SESSION['user_id'] ?? null,
        'user_type' => $_SESSION['user_type'] ?? null,
        'email' => $_SESSION['email'] ?? null,
        'name' => $_SESSION['name'] ?? null
    ];
}

/**
 * Format date
 */
function formatDate($date, $format = 'M d, Y') {
    return date($format, strtotime($date));
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

/**
 * Time ago format (e.g., "2 minutes ago")
 */
function timeAgo($dateTime) {
    $time = strtotime($dateTime);
    $current_time = time();
    $diff = $current_time - $time;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return formatDate($dateTime, 'M d, Y');
    }
}

/**
 * Check membership expiry
 */
function isMembershipExpired($joinDate, $membershipType) {
    $currentDate = date('Y-m-d');
    
    switch($membershipType) {
        case MEMBERSHIP_MONTHLY:
            $expiryDate = date('Y-m-d', strtotime($joinDate . ' +1 month'));
            break;
        case MEMBERSHIP_QUARTERLY:
            $expiryDate = date('Y-m-d', strtotime($joinDate . ' +3 months'));
            break;
        case MEMBERSHIP_ANNUAL:
            $expiryDate = date('Y-m-d', strtotime($joinDate . ' +1 year'));
            break;
        default:
            return true;
    }
    
    return $currentDate > $expiryDate;
}

/**
 * Get membership expiry date
 */
function getMembershipExpiryDate($joinDate, $membershipType) {
    switch($membershipType) {
        case MEMBERSHIP_MONTHLY:
            return date('Y-m-d', strtotime($joinDate . ' +1 month'));
        case MEMBERSHIP_QUARTERLY:
            return date('Y-m-d', strtotime($joinDate . ' +3 months'));
        case MEMBERSHIP_ANNUAL:
            return date('Y-m-d', strtotime($joinDate . ' +1 year'));
        default:
            return null;
    }
}

/**
 * Log action (for audit trail)
 */
function logAction($userId, $action, $module, $details = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_log (user_id, action, module, details, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$userId, $action, $module, $details]);
    } catch (Exception $e) {
        error_log('Error logging action: ' . $e->getMessage());
    }
}

/**
 * Get days until membership expiry
 */
function getDaysUntilExpiry($joinDate, $membershipType) {
    $expiryDate = getMembershipExpiryDate($joinDate, $membershipType);
    if (!$expiryDate) return -1;
    
    $currentDate = new DateTime();
    $expiry = new DateTime($expiryDate);
    $interval = $currentDate->diff($expiry);
    
    return $interval->invert ? -$interval->days : $interval->days;
}

/**
 * Generate consistent status badge HTML
 * Maps various status values to consistent badge styling
 */
function generateStatusBadge($status) {
    $status = trim($status);
    
    // Standardize status mapping
    $statusMap = [
        // Membership & Account Status
        'Active' => ['class' => 'badge-active', 'text' => 'Active'],
        'Inactive' => ['class' => 'badge-inactive', 'text' => 'Inactive'],
        'Expired' => ['class' => 'badge-expired', 'text' => 'Expired'],
        'Pending' => ['class' => 'badge-pending', 'text' => 'Pending'],
        
        // Payment Status
        'Paid' => ['class' => 'badge-paid', 'text' => 'Paid'],
        'Overdue' => ['class' => 'badge-overdue', 'text' => 'Overdue'],
        'Unpaid' => ['class' => 'badge-inactive', 'text' => 'Unpaid'],
        
        // Session & Event Status
        'Scheduled' => ['class' => 'badge-info', 'text' => 'Scheduled'],
        'Ongoing' => ['class' => 'badge-warning', 'text' => 'Ongoing'],
        'Completed' => ['class' => 'badge-success', 'text' => 'Completed'],
        'Cancelled' => ['class' => 'badge-danger', 'text' => 'Cancelled'],
        
        // Attendance Status
        'Present' => ['class' => 'badge-success', 'text' => 'Present'],
        'Absent' => ['class' => 'badge-danger', 'text' => 'Absent'],
        'Late' => ['class' => 'badge-warning', 'text' => 'Late'],
        'Excused' => ['class' => 'badge-info', 'text' => 'Excused'],
    ];
    
    // Use mapped value or default to status as-is
    if (isset($statusMap[$status])) {
        $badge = $statusMap[$status];
        $class = $badge['class'];
        $text = $badge['text'];
    } else {
        // Fallback: use lowercase status with badge- prefix
        $class = 'badge-' . strtolower(str_replace(' ', '-', $status));
        $text = $status;
    }
    
    return '<span class="badge ' . htmlspecialchars($class) . '">' . htmlspecialchars($text) . '</span>';
}

/**
 * Generate Bootstrap status badge HTML (for bg-* utility classes)
 */
function generateBSStatusBadge($status) {
    $status = trim($status);
    
    $statusMap = [
        'Active' => ['class' => 'bg-success', 'text' => 'Active'],
        'Inactive' => ['class' => 'bg-secondary', 'text' => 'Inactive'],
        'Expired' => ['class' => 'bg-danger', 'text' => 'Expired'],
        'Pending' => ['class' => 'bg-warning', 'text' => 'Pending'],
        'Paid' => ['class' => 'bg-success', 'text' => 'Paid'],
        'Overdue' => ['class' => 'bg-danger', 'text' => 'Overdue'],
        'Unpaid' => ['class' => 'bg-secondary', 'text' => 'Unpaid'],
        'Scheduled' => ['class' => 'bg-info', 'text' => 'Scheduled'],
        'Ongoing' => ['class' => 'bg-warning', 'text' => 'Ongoing'],
        'Completed' => ['class' => 'bg-success', 'text' => 'Completed'],
        'Cancelled' => ['class' => 'bg-danger', 'text' => 'Cancelled'],
        'Present' => ['class' => 'bg-success', 'text' => 'Present'],
        'Absent' => ['class' => 'bg-danger', 'text' => 'Absent'],
        'Late' => ['class' => 'bg-warning', 'text' => 'Late'],
        'Excused' => ['class' => 'bg-info', 'text' => 'Excused'],
    ];
    
    if (isset($statusMap[$status])) {
        $badge = $statusMap[$status];
        return '<span class="badge ' . htmlspecialchars($badge['class']) . '">' . htmlspecialchars($badge['text']) . '</span>';
    }
    
    return '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
}

/**
 * Check if ID already exists in table
 * 
 * @param string $table Table name
 * @param string $idValue ID value to check
 * @param string|null $idColumn Optional column name (auto-detected if null)
 * @return bool True if ID exists, false otherwise
 */
function idExists($table, $idValue, $idColumn = null) {
    global $pdo;
    try {
        // Map table names to their primary key column
        $columnMap = [
            'members'  => 'member_id',
            'trainers' => 'trainer_id',
            'payments' => 'payment_id',
            'users' => 'user_id',
            'reservations' => 'reservation_id',
            'gyms' => 'gym_id',
            'equipment' => 'equipment_id',
            'classes' => 'class_id',
            'sessions' => 'session_id',
            'workouts' => 'workout_id',
            'attendance' => 'attendance_id'
        ];
        
        // Use provided column or map from table name, default to 'id' if not found
        $col = $idColumn ?? $columnMap[$table] ?? 'id';
        
        $stmt = $pdo->prepare("SELECT 1 FROM `" . $table . "` WHERE `" . $col . "` = ? LIMIT 1");
        $stmt->execute([$idValue]);
        return $stmt->rowCount() > 0;
    } catch (Exception $e) {
        error_log("Error checking ID existence in table '$table' column: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate unique ID with collision detection
 */
function generateUniqueID($prefix, $table = null) {
    $maxAttempts = 10;
    $attempt = 0;
    
    while ($attempt < $maxAttempts) {
        $id = generateID($prefix);
        
        // If no table specified, just return the ID
        if (is_null($table)) {
            return $id;
        }
        
        // Check if ID already exists
        if (!idExists($table, $id)) {
            return $id;
        }
        
        $attempt++;
    }
    
    // Fallback: use microtime for guaranteed uniqueness
    return $prefix . round(microtime(true) * 10000);
}

/**
 * Generate standardized pagination HTML
 */
function generatePagination($currentPage, $totalPages, $baseUrl = '') {
    $html = '';
    
    if ($totalPages <= 1) {
        return $html;
    }
    
    // Parse current URL if not provided
    if (empty($baseUrl)) {
        $baseUrl = $_SERVER['REQUEST_URI'];
        // Remove page parameter if it exists
        $baseUrl = preg_replace('/[?&]page=\d+/', '', $baseUrl);
        $separator = (strpos($baseUrl, '?') === false) ? '?' : '&';
    } else {
        $separator = (strpos($baseUrl, '?') === false) ? '?' : '&';
    }
    
    // Determine separator
    if (isset($_GET) && !empty($_GET)) {
        $separator = '&';
    } else {
        $separator = '?';
    }
    
    $pageLinks = PAGINATION_LINKS;  // Usually 5
    $html .= '<nav aria-label="Page navigation">';
    $html .= '<ul class="pagination justify-content-center">';
    
    // Previous button
    if ($currentPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($baseUrl) . $separator . 'page=1">First</a></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($baseUrl) . $separator . 'page=' . ($currentPage - 1) . '">Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">First</span></li>';
        $html .= '<li class="page-item disabled"><span class="page-link">Previous</span></li>';
    }
    
    // Page numbers
    $start = max(1, $currentPage - floor($pageLinks / 2));
    $end = min($totalPages, $start + $pageLinks - 1);
    
    if ($start > 1) {
        $html .= '<li class="page-item"><span class="page-link">...</span></li>';
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i === $currentPage) {
            $html .= '<li class="page-item active"><span class="page-link">' . $i . '</span></li>';
        } else {
            $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($baseUrl) . $separator . 'page=' . $i . '">' . $i . '</a></li>';
        }
    }
    
    if ($end < $totalPages) {
        $html .= '<li class="page-item"><span class="page-link">...</span></li>';
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($baseUrl) . $separator . 'page=' . ($currentPage + 1) . '">Next</a></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . htmlspecialchars($baseUrl) . $separator . 'page=' . $totalPages . '">Last</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next</span></li>';
        $html .= '<li class="page-item disabled"><span class="page-link">Last</span></li>';
    }
    
    $html .= '</ul>';
    $html .= '</nav>';
    
    return $html;
}

/**
 * Send email notification
 * Supports both PHP mail() and SMTP configuration
 */
function sendEmailNotification($toEmail, $subject, $messageBody, $type = 'html') {
    // Validate email
    if (!isValidEmail($toEmail)) {
        error_log('Invalid email address: ' . $toEmail);
        return false;
    }
    
    // Load Mailtrap service
    require_once dirname(__FILE__) . '/../config/MailtrapService.php';
    
    // Sanitize subject
    $subject = substr($subject, 0, 100);
    
    try {
        // Use Mailtrap API for email sending
        if ($type === 'html') {
            $result = MailtrapService::send($toEmail, $subject, $messageBody);
        } else {
            // For plain text emails, send as both HTML and text
            $result = MailtrapService::send(
                $toEmail, 
                $subject, 
                '<pre>' . htmlspecialchars($messageBody, ENT_QUOTES, 'UTF-8') . '</pre>',
                $messageBody
            );
        }
        
        if ($result['success']) {
            error_log('Email sent via Mailtrap to ' . $toEmail . ' with subject: ' . $subject);
            return true;
        } else {
            error_log('Failed to send email via Mailtrap to ' . $toEmail . ': ' . $result['message']);
            return false;
        }
    } catch (Exception $e) {
        error_log('Exception sending email via Mailtrap: ' . $e->getMessage());
        return false;
    }
}

/**
 * Send session registration email
 */
function sendSessionRegistrationEmail($email, $sessionName, $trainerName, $sessionDate, $sessionTime, $duration) {
    $subject = 'Level Up Fitness - Session Registration';
    
    $messageBody = "
Hello,

You have been registered for a training session!

--- SESSION DETAILS ---
Session: " . htmlspecialchars($sessionName) . "
Trainer: " . htmlspecialchars($trainerName) . "
Date: " . formatDate($sessionDate) . "
Time: " . $sessionTime . "
Duration: " . $duration . " minutes

Please arrive 10 minutes early to prepare.

Best regards,
Level Up Fitness Gym Management System
    ";
    
    return sendEmailNotification($email, $subject, $messageBody, 'text');
}

/**
 * ============================================
 * NOTIFICATION SYSTEM FUNCTIONS
 * ============================================
 * Handles both in-app and email notifications
 */

/**
 * Create a notification for a user
 * @param int $userId - User ID
 * @param string $type - Notification type (payment, reservation, account, system)
 * @param string $title - Notification title
 * @param string $message - Notification message
 * @param array $options - Additional options (icon, color, action_url, entity_type, entity_id, priority, expires_at)
 * @return int|false - Notification ID or false on failure
 */
function createNotification($userId, $type, $title, $message, $options = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (
                user_id, 
                notification_type, 
                notification_title, 
                notification_message, 
                notification_icon, 
                icon_color, 
                related_entity_type, 
                related_entity_id, 
                action_url, 
                priority, 
                expires_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $icon = $options['icon'] ?? 'bell';
        $color = $options['color'] ?? 'primary';
        $entityType = $options['entity_type'] ?? null;
        $entityId = $options['entity_id'] ?? null;
        $actionUrl = $options['action_url'] ?? null;
        $priority = $options['priority'] ?? 'normal';
        $expiresAt = $options['expires_at'] ?? null;
        
        $stmt->execute([
            $userId,
            $type,
            $title,
            $message,
            $icon,
            $color,
            $entityType,
            $entityId,
            $actionUrl,
            $priority,
            $expiresAt
        ]);
        
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        error_log('Error creating notification: ' . $e->getMessage());
        return false;
    }
}

/**
 * Send notification (in-app + email)
 * @param int $userId - User ID to notify
 * @param string $type - Notification type
 * @param string $title - Notification title
 * @param string $message - Notification message
 * @param array $emailData - Email data (subject, body, recipient_email)
 * @param array $options - Additional options
 * @return bool - Success status
 */
function sendNotification($userId, $type, $title, $message, $emailData = [], $options = []) {
    // Check user notification preferences
    if (!shouldSendNotification($userId, $type)) {
        return false;
    }
    
    // Create in-app notification
    $notificationId = createNotification($userId, $type, $title, $message, $options);
    
    if (!$notificationId) {
        error_log("Failed to create in-app notification for user $userId");
        return false;
    }
    
    // Send email if provided
    if (!empty($emailData['recipient_email'])) {
        $emailSent = sendEmailNotification(
            $emailData['recipient_email'],
            $emailData['subject'] ?? $title,
            $emailData['body'] ?? $message,
            'html'
        );
        
        // Mark email as sent in database
        if ($emailSent) {
            markNotificationEmailSent($notificationId);
        }
    }
    
    return true;
}

/**
 * Check if notification should be sent based on user preferences
 */
function shouldSendNotification($userId, $type) {
    global $pdo;
    
    try {
        // Get notification preference
        $typeKey = 'in_app_' . str_replace('-', '_', strtolower($type));
        
        $stmt = $pdo->prepare("SELECT * FROM notification_preferences WHERE user_id = ?");
        $stmt->execute([$userId]);
        $preference = $stmt->fetch();
        
        if (!$preference) {
            // Default: send all notifications if no preference is set
            return true;
        }
        
        // Check if this notification type is enabled
        return (bool) $preference[$typeKey] ?? true;
    } catch (Exception $e) {
        error_log('Error checking notification preference: ' . $e->getMessage());
        return true; // Default to sending
    }
}

/**
 * Mark notification email as sent
 */
function markNotificationEmailSent($notificationId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET email_sent = 1, email_sent_at = NOW() 
            WHERE notification_id = ?
        ");
        return $stmt->execute([$notificationId]);
    } catch (Exception $e) {
        error_log('Error updating notification email status: ' . $e->getMessage());
        return false;
    }
}

/**
 * Get user notifications
 * @param int $userId - User ID
 * @param bool $unreadOnly - Get only unread notifications
 * @param int $limit - Limit results
 * @param int $offset - Offset for pagination
 * @return array - Array of notifications
 */
function getUserNotifications($userId, $unreadOnly = false, $limit = 50, $offset = 0) {
    global $pdo;
    
    try {
        $query = "SELECT * FROM notifications WHERE user_id = ?";
        
        if ($unreadOnly) {
            $query .= " AND is_read = 0";
        }
        
        $query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$userId, $limit, $offset]);
        
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('Error fetching notifications: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get unread notification count
 */
function getUnreadNotificationCount($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as unread_count 
            FROM notifications 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        
        return $result['unread_count'] ?? 0;
    } catch (Exception $e) {
        error_log('Error counting unread notifications: ' . $e->getMessage());
        return 0;
    }
}

/**
 * Get unread notifications (for header dropdown)
 */
function getUnreadNotifications($userId, $limit = 10) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? AND is_read = 0 
            ORDER BY priority DESC, created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        
        return $stmt->fetchAll();
    } catch (Exception $e) {
        error_log('Error fetching unread notifications: ' . $e->getMessage());
        return [];
    }
}

/**
 * Mark notification as read
 */
function markNotificationAsRead($notificationId, $userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE notification_id = ? AND user_id = ?
        ");
        return $stmt->execute([$notificationId, $userId]);
    } catch (Exception $e) {
        error_log('Error marking notification as read: ' . $e->getMessage());
        return false;
    }
}

/**
 * Mark all notifications as read for user
 */
function markAllNotificationsAsRead($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE user_id = ? AND is_read = 0
        ");
        return $stmt->execute([$userId]);
    } catch (Exception $e) {
        error_log('Error marking all notifications as read: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete notification
 */
function deleteNotification($notificationId, $userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE notification_id = ? AND user_id = ?");
        return $stmt->execute([$notificationId, $userId]);
    } catch (Exception $e) {
        error_log('Error deleting notification: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete all read notifications for user
 */
function deleteReadNotifications($userId) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = ? AND is_read = 1");
        return $stmt->execute([$userId]);
    } catch (Exception $e) {
        error_log('Error deleting read notifications: ' . $e->getMessage());
        return false;
    }
}

/**
 * Send Payment Notification
 * Enhanced with in-app notification and Mailtrap emails
 */
function sendPaymentNotification($userId, $email, $paymentId, $amount, $paymentMethod, $status, $additionalData = []) {
    
    $title = 'Payment Confirmation';
    $message = 'Your payment of ₱' . number_format($amount, 2) . ' has been ' . strtolower($status);
    
    // Prepare payment data for email
    $paymentData = [
        'payment_id' => $paymentId,
        'amount' => $amount,
        'payment_method' => $paymentMethod,
        'status' => $status,
        'payment_date' => date('M d, Y H:i A'),
    ];
    
    // Merge with additional data
    if (!empty($additionalData)) {
        $paymentData = array_merge($paymentData, $additionalData);
    }
    
    // Get user name from database if not provided
    if (empty($paymentData['member_name'])) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $paymentData['member_name'] = $user['full_name'] ?? 'Member';
    }
    
    // Send email using Mailtrap with template
    $htmlEmail = sendPaymentConfirmationEmail($email, $paymentData['member_name'], $paymentData);
    
    // Create in-app notification
    return sendNotification(
        $userId,
        'payment',
        $title,
        $message,
        [],
        [
            'icon' => 'credit-card',
            'color' => 'success',
            'entity_type' => 'payment',
            'entity_id' => $paymentId,
            'action_url' => APP_URL . 'modules/payments/invoice.php?id=' . $paymentId,
            'priority' => 'normal'
        ]
    );
}

/**
 * Send Reservation Notification
 * Enhanced with in-app notification and Mailtrap emails
 */
function sendReservationNotification($userId, $email, $reservationId, $equipmentName, $reservationDate, $startTime, $endTime, $additionalData = []) {
    
    $title = 'Reservation Confirmed';
    $message = 'Your reservation for ' . $equipmentName . ' on ' . formatDate($reservationDate) . ' is confirmed.';
    
    // Prepare reservation data for email
    $reservationData = [
        'reservation_id' => $reservationId,
        'equipment_name' => $equipmentName,
        'reservation_date' => formatDate($reservationDate),
        'start_time' => $startTime,
        'end_time' => $endTime,
    ];
    
    // Calculate duration
    $startDateTime = strtotime($reservationDate . ' ' . $startTime);
    $endDateTime = strtotime($reservationDate . ' ' . $endTime);
    $duration = round(($endDateTime - $startDateTime) / 60);
    $reservationData['duration'] = $duration;
    
    // Merge with additional data
    if (!empty($additionalData)) {
        $reservationData = array_merge($reservationData, $additionalData);
    }
    
    // Get user name from database if not provided
    if (empty($reservationData['member_name'])) {
        global $pdo;
        $stmt = $pdo->prepare("SELECT full_name FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        $reservationData['member_name'] = $user['full_name'] ?? 'Member';
    }
    
    // Send email using Mailtrap with template
    $htmlEmail = sendReservationConfirmationEmail($email, $reservationData['member_name'], $reservationData);
    
    // Create in-app notification
    return sendNotification(
        $userId,
        'reservation',
        $title,
        $message,
        [],
        [
            'icon' => 'calendar-check',
            'color' => 'success',
            'entity_type' => 'reservation',
            'entity_id' => $reservationId,
            'action_url' => APP_URL . 'modules/reservations/view.php?id=' . $reservationId,
            'priority' => 'normal'
        ]
    );
}

/**
 * Generate an email verification token
 * 
 * @param int $userId The user ID to generate token for
 * @param int $expirationHours Hours until token expires (default 24)
 * @return string|false The verification token or false on error
 */
function generateVerificationToken($userId, $expirationHours = 24) {
    global $pdo;
    
    try {
        // Generate a secure random token
        $token = bin2hex(random_bytes(16)); // 32 character hex string
        
        // Calculate expiration time using UTC timezone to match MySQL UTC_TIMESTAMP()
        // Use gmdate to ensure UTC time, not server local time
        $expiresAt = gmdate('Y-m-d H:i:s', time() + ($expirationHours * 3600));
        
        // Store token in database
        $stmt = $pdo->prepare("
            INSERT INTO verification_tokens (user_id, token, token_type, expires_at, created_at)
            VALUES (?, ?, 'email_verification', ?, UTC_TIMESTAMP())
        ");
        
        if ($stmt->execute([$userId, $token, $expiresAt])) {
            return $token;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error generating verification token: " . $e->getMessage());
        return false;
    }
}

/**
 * Validate an email verification token
 * 
 * @param string $token The token to validate
 * @return int|false The user ID if token is valid, false otherwise
 */
function validateVerificationToken($token) {
    global $pdo;
    
    try {
        // Use UTC_TIMESTAMP() to match gmdate() used in generateVerificationToken()
        // This prevents timezone mismatch issues where tokens appear to expire immediately
        $stmt = $pdo->prepare("
            SELECT user_id FROM verification_tokens
            WHERE token = ? 
            AND token_type = 'email_verification'
            AND used_at IS NULL
            AND expires_at > UTC_TIMESTAMP()
            LIMIT 1
        ");
        
        if ($stmt->execute([$token])) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['user_id'] : false;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("Error validating verification token: " . $e->getMessage());
        return false;
    }
}

/**
 * Activate a user account by marking verification token as used
 * 
 * @param string $token The verification token
 * @return bool True if activation successful, false otherwise
 */
function activateUserByToken($token) {
    global $pdo;
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get the user ID from the token
        // Use UTC_TIMESTAMP() to match gmdate() used in generateVerificationToken()
        $stmt = $pdo->prepare("
            SELECT user_id FROM verification_tokens
            WHERE token = ? 
            AND token_type = 'email_verification'
            AND used_at IS NULL
            AND expires_at > UTC_TIMESTAMP()
            LIMIT 1
        ");
        
        if (!$stmt->execute([$token])) {
            $pdo->rollBack();
            return false;
        }
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$result) {
            $pdo->rollBack();
            return false;
        }
        
        $userId = (int)$result['user_id'];
        
        // Update user to mark as verified
        $updateStmt = $pdo->prepare("
            UPDATE users 
            SET is_verified = 1
            WHERE user_id = ?
        ");
        
        if (!$updateStmt->execute([$userId])) {
            $pdo->rollBack();
            return false;
        }
        
        // Update member status to Active
        $memberStmt = $pdo->prepare("
            UPDATE members
            SET status = 'Active'
            WHERE user_id = ?
        ");
        
        if (!$memberStmt->execute([$userId])) {
            $pdo->rollBack();
            return false;
        }
        
        // Mark token as used
        $tokenStmt = $pdo->prepare("
            UPDATE verification_tokens
            SET used_at = NOW()
            WHERE token = ?
        ");
        
        if (!$tokenStmt->execute([$token])) {
            $pdo->rollBack();
            return false;
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Log activity (if function exists)
        if (function_exists('logActivity')) {
            logActivity(
                $userId,
                'account_verification',
                'User verified email and activated account',
                [],
                [
                    'icon' => 'check-circle',
                    'color' => 'success',
                    'entity_type' => 'user',
                    'entity_id' => $userId,
                    'priority' => 'high'
                ]
            );
        }
        
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error activating user by token: " . $e->getMessage());
        return false;
    }
}

?>

