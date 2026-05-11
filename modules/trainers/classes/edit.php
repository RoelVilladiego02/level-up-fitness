<?php
/**
 * Trainer Classes Management - Edit Class
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/includes/header.php';

requireLogin();
requireRole(['admin', 'trainer']);

$message = getMessage();
$class = null;
$trainers = [];
$daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$classId = $_GET['class_id'] ?? '';

// Get trainers for dropdown (if admin)
if ($_SESSION['user_type'] === 'admin') {
    try {
        $trainerStmt = $pdo->prepare("SELECT trainer_id, trainer_name FROM trainers WHERE status = 'Active' ORDER BY trainer_name");
        $trainerStmt->execute();
        $trainers = $trainerStmt->fetchAll();
    } catch (Exception $e) {
        setMessage('Error loading trainers: ' . $e->getMessage(), 'error');
    }
}

// Load class data
try {
    $stmt = $pdo->prepare("SELECT c.*, t.trainer_name FROM classes c JOIN trainers t ON c.trainer_id = t.trainer_id WHERE c.class_id = ?");
    $stmt->execute([$classId]);
    $class = $stmt->fetch();

    if (!$class) {
        setMessage('Class not found', 'error');
        redirect('index.php');
    }

    // Check authorization
    if ($_SESSION['user_type'] === 'trainer' && $class['trainer_id'] !== $_SESSION['user_id']) {
        setMessage('You do not have permission to edit this class', 'error');
        redirect('index.php');
    }
} catch (Exception $e) {
    setMessage('Error loading class: ' . $e->getMessage(), 'error');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $className = sanitize($_POST['class_name'] ?? '');
    $trainerId = sanitize($_POST['trainer_id'] ?? '');
    $classDescription = sanitize($_POST['class_description'] ?? '');
    $scheduleDay = sanitize($_POST['schedule_day'] ?? '');
    $startTime = sanitize($_POST['start_time'] ?? '');
    $endTime = sanitize($_POST['end_time'] ?? '');
    $maxCapacity = intval($_POST['max_capacity'] ?? 20);
    $classStatus = sanitize($_POST['class_status'] ?? 'Active');
    $csrfToken = $_POST['csrf_token'] ?? '';

    // Validate CSRF token
    if (!verifyCSRFToken($csrfToken)) {
        setMessage('Security token validation failed', 'error');
    } else {
        // Validation
        $errors = [];
        if (empty($className)) $errors[] = 'Class name is required';
        if (empty($trainerId)) $errors[] = 'Trainer is required';
        if (empty($scheduleDay)) $errors[] = 'Schedule day is required';
        if (empty($startTime)) $errors[] = 'Start time is required';
        if (empty($endTime)) $errors[] = 'End time is required';
        if ($maxCapacity <= 0) $errors[] = 'Capacity must be greater than 0';
        if (!in_array($classStatus, ['Active', 'Inactive', 'Cancelled'])) $errors[] = 'Invalid status';

        // Check if trainer exists and is active
        if (empty($errors)) {
            $trainerCheckStmt = $pdo->prepare("SELECT trainer_id, status FROM trainers WHERE trainer_id = ?");
            $trainerCheckStmt->execute([$trainerId]);
            $trainerData = $trainerCheckStmt->fetch();

            if (!$trainerData) {
                $errors[] = 'Selected trainer not found';
            } elseif ($trainerData['status'] !== 'Active') {
                $errors[] = 'Selected trainer is not active';
            }
        }

        // For trainers, ensure they can only edit their own classes
        if ($_SESSION['user_type'] === 'trainer' && $trainerId !== $_SESSION['user_id']) {
            $errors[] = 'You can only edit your own classes';
        }

        if (!empty($errors)) {
            setMessage(implode(', ', $errors), 'error');
        } else {
            try {
                // Update class
                $updateStmt = $pdo->prepare("UPDATE classes SET 
                    class_name = ?, trainer_id = ?, class_description = ?, schedule_day = ?, 
                    start_time = ?, end_time = ?, max_capacity = ?, class_status = ?, updated_at = NOW()
                    WHERE class_id = ?");

                $updateStmt->execute([
                    $className,
                    $trainerId,
                    $classDescription,
                    $scheduleDay,
                    $startTime,
                    $endTime,
                    $maxCapacity,
                    $classStatus,
                    $classId
                ]);

                // Log activity
                try {
                    $logStmt = $pdo->prepare("INSERT INTO activity_log 
                        (user_id, action, module, details, created_at)
                        VALUES (?, 'UPDATE', 'classes', ?, NOW())");
                    $logStmt->execute([
                        $_SESSION['user_id'],
                        "Updated class: $className"
                    ]);
                } catch (Exception $e) {
                    // Silently fail logging
                }

                setMessage('Class updated successfully!', 'success');
                redirect('index.php');

            } catch (Exception $e) {
                setMessage('Error updating class: ' . $e->getMessage(), 'error');
            }
        }
    }
}

$pageTitle = 'Edit Class';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../../dashboard.php'],
    ['label' => 'Classes', 'url' => 'index.php'],
    ['label' => 'Edit', 'url' => null]
];
$csrfToken = generateCSRFToken();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid mt-4">
        <!-- Breadcrumbs -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <?php foreach ($breadcrumbs as $crumb): ?>
                    <li class="breadcrumb-item <?php echo $crumb['url'] ? '' : 'active'; ?>">
                        <?php if ($crumb['url']): ?>
                            <a href="<?php echo $crumb['url']; ?>"><?php echo htmlspecialchars($crumb['label']); ?></a>
                        <?php else: ?>
                            <?php echo htmlspecialchars($crumb['label']); ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1><i class="fas fa-edit"></i> Edit Class</h1>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message['text']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <?php if ($class): ?>
            <div class="card">
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                        <!-- Class Name -->
                        <div class="col-md-6">
                            <label for="class_name" class="form-label">Class Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="class_name" name="class_name" 
                                   value="<?php echo htmlspecialchars($class['class_name']); ?>" required>
                        </div>

                        <!-- Trainer -->
                        <div class="col-md-6">
                            <label for="trainer_id" class="form-label">Trainer <span class="text-danger">*</span></label>
                            <?php if ($_SESSION['user_type'] === 'admin'): ?>
                                <select class="form-select" id="trainer_id" name="trainer_id" required>
                                    <option value="">-- Select Trainer --</option>
                                    <?php foreach ($trainers as $trainer): ?>
                                        <option value="<?php echo htmlspecialchars($trainer['trainer_id']); ?>"
                                            <?php echo $trainer['trainer_id'] === $class['trainer_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($trainer['trainer_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: ?>
                                <input type="text" class="form-control" readonly value="<?php echo htmlspecialchars($class['trainer_name']); ?>">
                                <input type="hidden" name="trainer_id" value="<?php echo htmlspecialchars($class['trainer_id']); ?>">
                            <?php endif; ?>
                        </div>

                        <!-- Schedule Day -->
                        <div class="col-md-6">
                            <label for="schedule_day" class="form-label">Day of Week <span class="text-danger">*</span></label>
                            <select class="form-select" id="schedule_day" name="schedule_day" required>
                                <option value="">-- Select Day --</option>
                                <?php foreach ($daysOfWeek as $day): ?>
                                    <option value="<?php echo htmlspecialchars($day); ?>"
                                        <?php echo $day === $class['schedule_day'] ? 'selected' : ''; ?>>
                                        <?php echo $day; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Start Time -->
                        <div class="col-md-3">
                            <label for="start_time" class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="start_time" name="start_time" 
                                   value="<?php echo htmlspecialchars($class['start_time']); ?>" required>
                        </div>

                        <!-- End Time -->
                        <div class="col-md-3">
                            <label for="end_time" class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="time" class="form-control" id="end_time" name="end_time" 
                                   value="<?php echo htmlspecialchars($class['end_time']); ?>" required>
                        </div>

                        <!-- Max Capacity -->
                        <div class="col-md-6">
                            <label for="max_capacity" class="form-label">Maximum Capacity <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="max_capacity" name="max_capacity" 
                                   value="<?php echo $class['max_capacity']; ?>" min="1" max="100" required>
                        </div>

                        <!-- Class Status -->
                        <div class="col-md-6">
                            <label for="class_status" class="form-label">Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="class_status" name="class_status" required>
                                <option value="Active" <?php echo $class['class_status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $class['class_status'] === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="Cancelled" <?php echo $class['class_status'] === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label for="class_description" class="form-label">Description</label>
                            <textarea class="form-control" id="class_description" name="class_description" rows="4"><?php echo htmlspecialchars($class['class_description'] ?? ''); ?></textarea>
                        </div>

                        <!-- Buttons -->
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Class
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const startTimeInput = document.getElementById('start_time');
        const endTimeInput = document.getElementById('end_time');

        function validateTime() {
            if (startTimeInput.value && endTimeInput.value) {
                if (endTimeInput.value <= startTimeInput.value) {
                    endTimeInput.setCustomValidity('End time must be after start time');
                } else {
                    endTimeInput.setCustomValidity('');
                }
            }
        }

        startTimeInput.addEventListener('change', validateTime);
        endTimeInput.addEventListener('change', validateTime);
    </script>
</body>
</html>
