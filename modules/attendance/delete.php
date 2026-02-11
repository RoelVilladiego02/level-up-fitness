<?php
/**
 * Class Attendance - Delete Attendance Record
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$attendanceId = sanitize($_GET['id'] ?? '');

if (empty($attendanceId)) {
    setMessage('Invalid attendance ID', 'error');
    redirect(APP_URL . 'modules/attendance/');
}

try {
    // Get attendance info first
    $stmt = $pdo->prepare("
        SELECT ca.*, c.class_name, m.member_name
        FROM class_attendance ca
        JOIN classes c ON ca.class_id = c.class_id
        JOIN members m ON ca.member_id = m.member_id
        WHERE ca.attendance_id = ?
    ");
    $stmt->execute([$attendanceId]);
    $attendance = $stmt->fetch();

    if (!$attendance) {
        setMessage('Attendance record not found', 'error');
        redirect(APP_URL . 'modules/attendance/');
    }

    // If confirmed
    if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
        // Delete attendance record
        $deleteStmt = $pdo->prepare("DELETE FROM class_attendance WHERE attendance_id = ?");
        $deleteStmt->execute([$attendanceId]);

        logAction($_SESSION['user_id'], 'DELETE_ATTENDANCE', 'Attendance', 'Deleted attendance: ' . $attendanceId);

        setMessage('Attendance record deleted successfully', 'success');
        redirect(APP_URL . 'modules/attendance/');
    }

    // If cancel
    if (isset($_GET['confirm']) && $_GET['confirm'] == 'no') {
        redirect(APP_URL . 'modules/attendance/');
    }

} catch (Exception $e) {
    setMessage('Error: ' . $e->getMessage(), 'error');
    redirect(APP_URL . 'modules/attendance/');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Delete - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h5>
                    </div>
                    <div class="card-body">
                        <p class="lead">Are you sure you want to delete this attendance record?</p>
                        
                        <div class="alert alert-danger">
                            <strong>Attendance ID:</strong> <?php echo htmlspecialchars($attendance['attendance_id']); ?><br>
                            <strong>Class:</strong> <?php echo htmlspecialchars($attendance['class_name']); ?><br>
                            <strong>Member:</strong> <?php echo htmlspecialchars($attendance['member_name']); ?><br>
                            <strong>Date:</strong> <?php echo formatDate($attendance['attendance_date']); ?><br>
                            <strong>Status:</strong> <?php echo htmlspecialchars($attendance['attendance_status']); ?>
                        </div>

                        <p class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            This action cannot be undone.
                        </p>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?php echo APP_URL; ?>modules/attendance/" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <a href="?id=<?php echo urlencode($attendanceId); ?>&confirm=yes" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Delete Record
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
