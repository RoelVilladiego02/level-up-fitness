<?php
/**
 * Global Configuration Settings
 * Level Up Fitness - Gym Management System
 */

// Include database connection
require_once dirname(__FILE__) . '/database.php';

// Session Settings
define('SESSION_TIMEOUT', 1800); // 30 minutes in seconds
define('SESSION_NAME', 'level_up_fitness_session');

// Application Settings
define('APP_NAME', 'Level Up Fitness');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/level-up-fitness/');

// Paths
define('BASE_PATH', dirname(dirname(__FILE__)));
define('INCLUDES_PATH', BASE_PATH . '/includes/');
define('MODULES_PATH', BASE_PATH . '/modules/');
define('ASSETS_PATH', BASE_PATH . '/assets/');

// Security Settings
define('USE_HTTPS', false); // Set to true in production
define('CSRF_TOKEN_LENGTH', 32);

// Color Scheme
define('COLOR_PRIMARY', '#4A90E2');
define('COLOR_SUCCESS', '#28A745');
define('COLOR_WARNING', '#FFC107');
define('COLOR_DANGER', '#DC3545');
define('COLOR_NEUTRAL', '#6C757D');

// User Roles
define('ROLE_ADMIN', 'admin');
define('ROLE_MEMBER', 'member');
define('ROLE_TRAINER', 'trainer');

// Membership Types
define('MEMBERSHIP_MONTHLY', 'Monthly');
define('MEMBERSHIP_QUARTERLY', 'Quarterly');
define('MEMBERSHIP_ANNUAL', 'Annual');

// Member Status
define('STATUS_ACTIVE', 'Active');
define('STATUS_INACTIVE', 'Inactive');
define('STATUS_EXPIRED', 'Expired');

// Payment Methods
define('PAYMENT_CASH', 'Cash');
define('PAYMENT_CARD', 'Card');
define('PAYMENT_GCASH', 'GCash');
define('PAYMENT_BANK_TRANSFER', 'Bank Transfer');

// Payment Status
define('PAYMENT_PAID', 'Paid');
define('PAYMENT_PENDING', 'Pending');
define('PAYMENT_OVERDUE', 'Overdue');

// ID Generation Prefixes
define('MEMBER_ID_PREFIX', 'M');
define('TRAINER_ID_PREFIX', 'T');
define('CLASS_ID_PREFIX', 'CLS');
define('WORKOUT_ID_PREFIX', 'WRK');
define('WORKOUT_PLAN_ID_PREFIX', 'WP');
define('SESSION_ID_PREFIX', 'SES');
define('PAYMENT_ID_PREFIX', 'PAY');
define('ATTENDANCE_ID_PREFIX', 'ATT');
define('RESERVATION_ID_PREFIX', 'RES');
define('GYM_ID_PREFIX', 'GYM');
define('EQUIPMENT_ID_PREFIX', 'EQP');

// Database Query Limits
define('ITEMS_PER_PAGE', 10);
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes

// Enable/Disable Features
define('ENABLE_EMAIL_NOTIFICATIONS', false);
define('ENABLE_SMS_NOTIFICATIONS', false);
define('ENABLE_PDF_EXPORT', false);

// Pagination
define('PAGINATION_LINKS', 5);

?>
