<?php
/**
 * Unenroll from Training Session - Member Action
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole('member');

$message = getMessage();
$sessionId = $_GET['id'] ?? null;

if (!$sessionId) {
    setMessage('Session ID is required', 'error');
    redirect('modules/sessions/index.php');
}

try {
    // Get member_id for current user
    $memberStmt = $pdo->prepare("SELECT member_id FROM members WHERE user_id = ?");
    $memberStmt->execute([$_SESSION['user_id']]);
    $memberData = $memberStmt->fetch();

    if (!$memberData) {
        setMessage('Member profile not found', 'error');
        redirect('modules/sessions/index.php');
    }

    $memberId = $memberData['member_id'];

    // Check if enrolled
    $checkStmt = $pdo->prepare("
        SELECT attendee_id FROM training_session_attendees 
        WHERE session_id = ? AND member_id = ?
    ");
    $checkStmt->execute([$sessionId, $memberId]);
    if (!$checkStmt->fetch()) {
        setMessage('You are not enrolled in this session', 'error');
        redirect('modules/sessions/view.php?id=' . $sessionId);
    }

    // Check if session has already started
    $sessionCheckStmt = $pdo->prepare("
        SELECT session_date, session_time FROM training_sessions 
        WHERE session_id = ?
    ");
    $sessionCheckStmt->execute([$sessionId]);
    $session = $sessionCheckStmt->fetch();

    if ($session) {
        $sessionDateTime = strtotime($session['session_date'] . ' ' . $session['session_time']);
        if (time() > $sessionDateTime) {
            setMessage('Cannot unenroll from sessions that have already started', 'error');
            redirect('modules/sessions/view.php?id=' . $sessionId);
        }
    }

    // Unenroll from session
    $unenrollStmt = $pdo->prepare("
        DELETE FROM training_session_attendees 
        WHERE session_id = ? AND member_id = ?
    ");
    $unenrollStmt->execute([$sessionId, $memberId]);

    // Log activity
    logActivity('Unenrolled from training session', 'training_sessions', $sessionId);

    setMessage('Successfully unenrolled from the session!', 'success');
    redirect('modules/sessions/view.php?id=' . $sessionId);

} catch (Exception $e) {
    setMessage('Error unenrolling from session: ' . $e->getMessage(), 'error');
    redirect('modules/sessions/index.php');
}
?>
