<?php
/**
 * Training Sessions Management - Delete Session
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole(['admin', 'trainer']);

$sessionId = $_GET['id'] ?? null;

if (!$sessionId) {
    setMessage('Session ID is required', 'error');
    redirect(APP_URL . 'modules/sessions/index.php');
}

try {
    // Get session details
    $sessionStmt = $pdo->prepare("
        SELECT ts.*, t.user_id as trainer_user_id FROM training_sessions ts
        LEFT JOIN trainers t ON ts.trainer_id = t.trainer_id
        WHERE ts.session_id = ?
    ");
    $sessionStmt->execute([$sessionId]);
    $session = $sessionStmt->fetch();

    if (!$session) {
        setMessage('Session not found', 'error');
        redirect(APP_URL . 'modules/sessions/index.php');
    }

    // Check authorization - trainers can only delete their own sessions
    if ($_SESSION['user_type'] === 'trainer' && $_SESSION['user_id'] != $session['trainer_user_id']) {
        setMessage('You do not have permission to delete this session', 'error');
        redirect(APP_URL . 'modules/sessions/index.php');
    }

    // Delete attendees first (foreign key)
    $deleteAttendees = $pdo->prepare("DELETE FROM training_session_attendees WHERE session_id = ?");
    $deleteAttendees->execute([$sessionId]);

    // Delete session
    $deleteStmt = $pdo->prepare("DELETE FROM training_sessions WHERE session_id = ?");
    $deleteStmt->execute([$sessionId]);

    setMessage('Session deleted successfully!', 'success');
    redirect(APP_URL . 'modules/sessions/index.php');

} catch (Exception $e) {
    setMessage('Error deleting session: ' . $e->getMessage(), 'error');
    redirect(APP_URL . 'modules/sessions/index.php');
}
?>
