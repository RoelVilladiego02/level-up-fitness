<?php
/**
 * Index/Home Page - Redirect to Dashboard or Login
 * Level Up Fitness - Gym Management System
 */

require_once dirname(__FILE__) . '/config/config.php';
require_once dirname(__FILE__) . '/includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect based on login status
if (isLoggedIn()) {
    redirect(APP_URL . 'dashboard/');
} else {
    redirect(APP_URL . 'auth/login.php');
}
?>
