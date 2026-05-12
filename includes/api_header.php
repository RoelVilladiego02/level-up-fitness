<?php
// ==================== CLEAN API HEADER ====================
ob_start();           // Start output buffering
header('Content-Type: application/json');

// Prevent any HTML error output
ini_set('display_errors', 0);
error_reporting(E_ALL);

require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once dirname(dirname(__FILE__)) . '/config/database.php';
require_once dirname(dirname(__FILE__)) . '/includes/functions.php';
require_once dirname(dirname(__FILE__)) . '/includes/email-notifications.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Session timeout check
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
    session_destroy();
    echo json_encode(['success' => false, 'error' => 'Session expired']);
    exit;
}
$_SESSION['last_activity'] = time();
?>