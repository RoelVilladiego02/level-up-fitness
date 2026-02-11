<?php
/**
 * Logout Page
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once dirname(dirname(__FILE__)) . '/includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log the logout action
if (isset($_SESSION['user_id'])) {
    logAction($_SESSION['user_id'], 'LOGOUT', 'Authentication', 'User logged out');
}

// Destroy session
session_destroy();

// Redirect to login
redirect(APP_URL . 'auth/login.php');
?>
