<?php
/**
 * Trainer Classes Management - Delete Class
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/includes/header.php';

requireLogin();
requireRole(['admin', 'trainer']);

$classId = $_GET['class_id'] ?? '';

try {
    // Get class data
    $stmt = $pdo->prepare("SELECT class_id, class_name, trainer_id FROM classes WHERE class_id = ?");
    $stmt->execute([$classId]);
    $class = $stmt->fetch();

    if (!$class) {
        setMessage('Class not found', 'error');
        redirect('index.php');
    }

    // Check authorization
    if ($_SESSION['user_type'] === 'trainer' && $class['trainer_id'] !== $_SESSION['user_id']) {
        setMessage('You do not have permission to delete this class', 'error');
        redirect('index.php');
    }

    // Delete class
    $deleteStmt = $pdo->prepare("DELETE FROM classes WHERE class_id = ?");
    $deleteStmt->execute([$classId]);

    // Delete class attendances
    $deleteAttendanceStmt = $pdo->prepare("DELETE FROM class_attendance WHERE class_id = ?");
    $deleteAttendanceStmt->execute([$classId]);

    // Log activity
    try {
        $logStmt = $pdo->prepare("INSERT INTO activity_log 
            (user_id, action, module, details, created_at)
            VALUES (?, 'DELETE', 'classes', ?, NOW())");
        $logStmt->execute([
            $_SESSION['user_id'],
            "Deleted class: " . $class['class_name']
        ]);
    } catch (Exception $e) {
        // Silently fail logging
    }

    setMessage('Class deleted successfully', 'success');
    redirect('index.php');

} catch (Exception $e) {
    setMessage('Error deleting class: ' . $e->getMessage(), 'error');
    redirect('index.php');
}
?>
