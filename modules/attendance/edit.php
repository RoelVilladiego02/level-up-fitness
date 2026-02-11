<?php
/**
 * Class Attendance - Edit Attendance Record
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$attendanceId = sanitize($_GET['id'] ?? '');
$attendance = null;
$errors = [];
$formData = [];

// Load attendance record
if (!empty($attendanceId)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM class_attendance WHERE attendance_id = ?");
        $stmt->execute([$attendanceId]);
        $attendance = $stmt->fetch();
        
        if (!$attendance) {
            setMessage('Attendance record not found', 'error');
            redirect(APP_URL . 'modules/attendance/');
        }
        
        $formData = $attendance;
    } catch (Exception $e) {
        setMessage('Error loading attendance: ' . $e->getMessage(), 'error');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($attendanceId)) {
    $attendanceDate = sanitize($_POST['attendance_date'] ?? '');
    $formData['attendance_status'] = sanitize($_POST['attendance_status'] ?? '');

    // Validate
    if (empty($attendanceDate)) {
        $errors['attendance_date'] = 'Attendance date is required';
    }
    if (empty($formData['attendance_status'])) {
        $errors['attendance_status'] = 'Attendance status is required';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE class_attendance SET 
                    attendance_date = ?, attendance_status = ?
                WHERE attendance_id = ?
            ");
            $stmt->execute([$attendanceDate, $formData['attendance_status'], $attendanceId]);

            logAction($_SESSION['user_id'], 'EDIT_ATTENDANCE', 'Attendance', 'Updated attendance: ' . $attendanceId);

            setMessage('Attendance updated successfully', 'success');
            redirect(APP_URL . 'modules/attendance/');
        } catch (Exception $e) {
            setMessage('Error updating attendance: ' . $e->getMessage(), 'error');
        }
    }
}

// Get related information
$classInfo = null;
$memberInfo = null;
if ($attendance) {
    try {
        $classStmt = $pdo->prepare("SELECT * FROM classes WHERE class_id = ?");
        $classStmt->execute([$attendance['class_id']]);
        $classInfo = $classStmt->fetch();

        $memberStmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
        $memberStmt->execute([$attendance['member_id']]);
        $memberInfo = $memberStmt->fetch();
    } catch (Exception $e) {
        setMessage('Error loading related info: ' . $e->getMessage(), 'error');
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/attendance/" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-edit"></i> Edit Attendance</h1>
                <p>Update attendance record</p>
            </div>

            <?php displayMessage(); ?>

            <?php if ($attendance): ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Attendance Details - <?php echo htmlspecialchars($attendance['attendance_id']); ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" class="needs-validation" novalidate>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Class (Read-only)</label>
                                        <input type="text" class="form-control" disabled
                                               value="<?php echo htmlspecialchars($classInfo['class_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Member (Read-only)</label>
                                        <input type="text" class="form-control" disabled
                                               value="<?php echo htmlspecialchars($memberInfo['member_name'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="attendance_date" class="form-label">Attendance Date *</label>
                                        <input type="date" class="form-control <?php echo isset($errors['attendance_date']) ? 'is-invalid' : ''; ?>" 
                                               id="attendance_date" name="attendance_date" 
                                               value="<?php echo htmlspecialchars($attendance['attendance_date']); ?>" required>
                                        <?php if (isset($errors['attendance_date'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['attendance_date']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="attendance_status" class="form-label">Status *</label>
                                        <select class="form-select <?php echo isset($errors['attendance_status']) ? 'is-invalid' : ''; ?>" 
                                                id="attendance_status" name="attendance_status" required>
                                            <option value="Present" <?php echo ($formData['attendance_status'] ?? '') === 'Present' ? 'selected' : ''; ?>>Present</option>
                                            <option value="Absent" <?php echo ($formData['attendance_status'] ?? '') === 'Absent' ? 'selected' : ''; ?>>Absent</option>
                                        </select>
                                        <?php if (isset($errors['attendance_status'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['attendance_status']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="<?php echo APP_URL; ?>modules/attendance/" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Attendance
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Record Info</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Record ID:</strong><br>
                                <code><?php echo htmlspecialchars($attendance['attendance_id']); ?></code>
                            </p>
                            <hr>
                            <p>
                                <strong>Current Status:</strong><br>
                                <span class="badge badge-<?php echo strtolower(str_replace('Present', 'success', str_replace('Absent', 'danger', $attendance['attendance_status']))); ?>">
                                    <?php echo htmlspecialchars($attendance['attendance_status']); ?>
                                </span>
                            </p>
                            <hr>
                            <p>
                                <strong>Enrolled:</strong><br>
                                <?php echo formatDate($attendance['enrollment_date']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
