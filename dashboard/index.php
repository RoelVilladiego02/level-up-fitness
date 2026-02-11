<?php
/**
 * Main Dashboard Router
 * Level Up Fitness - Gym Management System
 * Routes users to appropriate dashboard based on their role
 */

// Include required files
require_once dirname(dirname(__FILE__)) . '/includes/header.php';

// Require login
requireLogin();

$userInfo = getUserInfo();

// Route to appropriate dashboard based on user role
if ($userInfo['user_type'] === 'member') {
    require_once dirname(__FILE__) . '/member-dashboard.php';
} elseif ($userInfo['user_type'] === 'trainer') {
    require_once dirname(__FILE__) . '/trainer-dashboard.php';
} else {
    // Admin or default
    require_once dirname(__FILE__) . '/admin-dashboard.php';
}
