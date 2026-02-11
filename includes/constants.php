<?php
/**
 * Application Constants & Configuration
 * Level Up Fitness - Gym Management System
 */

// Application Settings
define('APP_NAME', 'Level Up Fitness');
define('APP_URL', 'http://localhost:8000/');
define('ITEMS_PER_PAGE', 25);

// ID Prefixes for Auto-Generation
define('MEMBER_ID_PREFIX', 'MEM');
define('TRAINER_ID_PREFIX', 'TRN');
define('CLASS_ID_PREFIX', 'CLS');
define('SESSION_ID_PREFIX', 'SES');
define('ATTENDANCE_ID_PREFIX', 'ATT');
define('PAYMENT_ID_PREFIX', 'PAY');
define('WORKOUT_ID_PREFIX', 'WRK');
define('RESERVATION_ID_PREFIX', 'RES');
define('GYM_ID_PREFIX', 'GYM');
define('EQUIPMENT_ID_PREFIX', 'EQP');

// Database Settings (if not configured elsewhere)
if (!defined('DB_HOST')) {
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'level_up_fitness');
    define('DB_USER', 'root');
    define('DB_PASS', '');
}

// Timezone
date_default_timezone_set('America/New_York');

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Session Settings
if (!isset($_SESSION)) {
    session_start();
}
