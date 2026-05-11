<?php
/**
 * API Bootstrap
 * Level Up Fitness - Gym Management System
 * 
 * Initialize dependencies for API endpoints without HTML output
 */

// Start output buffering to catch any unexpected output
ob_start();

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Determine log file location
$logDir = dirname(dirname(__FILE__)) . '/backend/logs/';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}
ini_set('error_log', $logDir . 'php-api-errors.log');

// Set JSON response header early
header('Content-Type: application/json');

// Custom error handler
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $error_msg = "[$errno] $errstr in $errfile on line $errline";
    error_log($error_msg);
    
    // Clear output buffer if exists
    if (ob_get_length()) ob_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'code' => 'SERVER_ERROR',
        'details' => $error_msg
    ]);
    exit;
});

// Custom exception handler
set_exception_handler(function($exception) {
    error_log('Exception: ' . $exception->getMessage());
    error_log('Stack: ' . $exception->getTraceAsString());
    
    // Clear output buffer if exists
    if (ob_get_length()) ob_clean();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'code' => 'EXCEPTION',
        'details' => $exception->getMessage()
    ]);
    exit;
});

try {
    // Load configuration
    require_once dirname(dirname(__FILE__)) . '/config/config.php';
    require_once dirname(dirname(__FILE__)) . '/config/database.php';
    require_once dirname(dirname(__FILE__)) . '/includes/functions.php';
    require_once dirname(dirname(__FILE__)) . '/includes/email-notifications.php';

    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Session timeout check
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_destroy();
        http_response_code(401);
        // Clear output buffer
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'error' => 'Session expired', 'code' => 'SESSION_EXPIRED']);
        exit;
    }
    $_SESSION['last_activity'] = time();
    
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'error' => 'Unauthorized', 'code' => 'UNAUTHORIZED']);
        exit;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    if (ob_get_length()) ob_clean();
    error_log('API Init Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Failed to initialize API',
        'code' => 'INIT_ERROR',
        'details' => $e->getMessage()
    ]);
    exit;
}
