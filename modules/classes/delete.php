<?php
/**
 * Classes - Delete Class
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$classId = sanitize($_GET['id'] ?? '');

if (empty($classId)) {
    setMessage('Invalid class ID', 'error');
    redirect(APP_URL . 'modules/classes/');
}

try {
    // Get class info first
    $stmt = $pdo->prepare("SELECT * FROM classes WHERE class_id = ?");
    $stmt->execute([$classId]);
    $class = $stmt->fetch();

    if (!$class) {
        setMessage('Class not found', 'error');
        redirect(APP_URL . 'modules/classes/');
    }

    // If confirmed
    if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
        // Delete class attendance records
        $deleteAttendanceStmt = $pdo->prepare("DELETE FROM class_attendance WHERE class_id = ?");
        $deleteAttendanceStmt->execute([$classId]);

        // Delete class
        $deleteStmt = $pdo->prepare("DELETE FROM classes WHERE class_id = ?");
        $deleteStmt->execute([$classId]);

        logAction($_SESSION['user_id'], 'DELETE_CLASS', 'Classes', 'Deleted class: ' . $classId);

        setMessage('Class deleted successfully', 'success');
        redirect(APP_URL . 'modules/classes/');
    }

    // If cancel
    if (isset($_GET['confirm']) && $_GET['confirm'] == 'no') {
        redirect(APP_URL . 'modules/classes/view.php?id=' . $classId);
    }

} catch (Exception $e) {
    setMessage('Error: ' . $e->getMessage(), 'error');
    redirect(APP_URL . 'modules/classes/');
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
                        <p class="lead">Are you sure you want to delete this class?</p>
                        
                        <div class="alert alert-danger">
                            <strong>Class ID:</strong> <?php echo htmlspecialchars($class['class_id']); ?><br>
                            <strong>Class Name:</strong> <?php echo htmlspecialchars($class['class_name']); ?><br>
                            <strong>Schedule:</strong> <?php echo htmlspecialchars($class['class_schedule']); ?><br>
                            <strong>Capacity:</strong> <?php echo htmlspecialchars($class['max_capacity']); ?>
                        </div>

                        <p class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            This will also delete all class attendance records. This action cannot be undone.
                        </p>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?php echo APP_URL; ?>modules/classes/view.php?id=<?php echo $classId; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <a href="?id=<?php echo urlencode($classId); ?>&confirm=yes" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Delete Class
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
