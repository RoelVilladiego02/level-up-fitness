<?php
/**
 * Training Sessions Management - Add New Session
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole(['admin', 'trainer']);

$message = getMessage();
$trainers = [];
$gyms = [];
$currentTrainer = null;

// Get trainers and gyms for dropdown
try {
    // If user is trainer, only get their own info
    if ($_SESSION['user_type'] === 'trainer') {
        $trainerStmt = $pdo->prepare("SELECT trainer_id, trainer_name FROM trainers WHERE trainer_id = ? OR user_id = ?");
        $trainerStmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
        $currentTrainer = $trainerStmt->fetch();
        if ($currentTrainer) {
            $trainers = [$currentTrainer];
        }
    } else {
        // Admin can see all trainers
        $trainerStmt = $pdo->prepare("SELECT trainer_id, trainer_name FROM trainers WHERE status = 'Active' ORDER BY trainer_name");
        $trainerStmt->execute();
        $trainers = $trainerStmt->fetchAll();
    }

    $gymStmt = $pdo->prepare("SELECT gym_id, gym_name FROM gyms ORDER BY gym_name");
    $gymStmt->execute();
    $gyms = $gymStmt->fetchAll();
} catch (Exception $e) {
    setMessage('Error loading data: ' . $e->getMessage(), 'error');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sessionName = $_POST['session_name'] ?? '';
    $trainerId = $_POST['trainer_id'] ?? '';
    $gymId = $_POST['gym_id'] ?? '';
    $sessionDate = $_POST['session_date'] ?? '';
    $sessionTime = $_POST['session_time'] ?? '';
    $duration = $_POST['duration'] ?? '';
    $maxCapacity = $_POST['max_capacity'] ?? '';
    $description = $_POST['description'] ?? '';
    $status = $_POST['status'] ?? 'Scheduled';

    // Validation
    $errors = [];
    if (empty($sessionName)) $errors[] = 'Session name is required';
    if (empty($trainerId)) {
        $errors[] = 'Trainer is required';
    } else {
        // Check if trainer exists and is active
        $trainerCheckStmt = $pdo->prepare("SELECT trainer_id, status FROM trainers WHERE trainer_id = ?");
        $trainerCheckStmt->execute([$trainerId]);
        $trainerData = $trainerCheckStmt->fetch();
        if (!$trainerData) {
            $errors[] = 'Selected trainer does not exist';
        } elseif (isset($trainerData['status']) && $trainerData['status'] !== 'Active') {
            $errors[] = 'Selected trainer is not currently active';
        }
    }
    if (empty($gymId)) $errors[] = 'Gym is required';
    if (empty($sessionDate)) {
        $errors[] = 'Session date is required';
    } else {
        // Validate date format and prevent past dates
        $sessionDateObj = DateTime::createFromFormat('Y-m-d', $sessionDate);
        if (!$sessionDateObj || $sessionDateObj->format('Y-m-d') !== $sessionDate) {
            $errors[] = 'Invalid date format';
        } elseif ($sessionDateObj < new DateTime('today')) {
            $errors[] = 'Session date cannot be in the past';
        } elseif ($sessionDateObj > new DateTime('+90 days')) {
            $errors[] = 'Sessions can only be scheduled up to 90 days in advance';
        }
    }
    if (empty($sessionTime)) $errors[] = 'Session time is required';
    if (empty($duration)) {
        $errors[] = 'Duration is required';
    } elseif (!is_numeric($duration) || $duration < 15) {
        $errors[] = 'Duration must be at least 15 minutes';
    } elseif ($duration > 480) {
        $errors[] = 'Duration cannot exceed 8 hours';
    }
    if (empty($maxCapacity)) {
        $errors[] = 'Max capacity is required';
    } elseif (!is_numeric($maxCapacity) || $maxCapacity < 1) {
        $errors[] = 'Max capacity must be at least 1';
    }
    
    // Check for trainer double-booking conflicts (only if other validations pass)
    if (empty($errors) && !empty($trainerId) && !empty($sessionDate) && !empty($sessionTime)) {
        try {
            // Calculate end time from session_time and duration
            $startTime = new DateTime('2000-01-01 ' . $sessionTime);
            $endTime = clone $startTime;
            $endTime->add(new DateInterval('PT' . intval($duration) . 'M'));
            
            // Check if trainer has any sessions at overlapping time on same date
            $conflictStmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM training_sessions 
                WHERE trainer_id = ? 
                AND session_date = ? 
                AND status IN ('Scheduled', 'Ongoing')
                AND (
                    (TIME(session_time) < TIME(?) AND TIME(DATE_ADD(session_time, INTERVAL duration MINUTE)) > TIME(?))
                    OR (TIME(session_time) = TIME(?) AND TIME(DATE_ADD(session_time, INTERVAL duration MINUTE)) > TIME(?))
                    OR (TIME(session_time) < TIME(?) AND TIME(DATE_ADD(session_time, INTERVAL duration MINUTE)) = TIME(?))
                )
            ");
            $endTimeStr = $endTime->format('H:i:s');
            $conflictStmt->execute([
                $trainerId,
                $sessionDate,
                $endTimeStr,
                $sessionTime,
                $sessionTime,
                $sessionTime,
                $endTimeStr,
                $endTimeStr
            ]);
            $conflict = $conflictStmt->fetch();
            if ($conflict['count'] > 0) {
                $errors['trainer_conflict'] = 'This trainer is already scheduled for another session at this time';
            }
        } catch (Exception $e) {
            $errors['database'] = 'Error checking trainer availability: ' . $e->getMessage();
        }
    }

    if (count($errors) > 0) {
        $_SESSION['form_errors'] = $errors;
        setMessage('Please fix the following errors: ' . implode(', ', $errors), 'error');
    } else {
        try {
            // If trainer is not admin, they can only create their own sessions
            if ($_SESSION['user_type'] === 'trainer') {
                // Get the user_id for the selected trainer
                $trainerUserIdStmt = $pdo->prepare("SELECT user_id FROM trainers WHERE trainer_id = ?");
                $trainerUserIdStmt->execute([$trainerId]);
                $trainerUser = $trainerUserIdStmt->fetch();
                if (!$trainerUser || $trainerUser['user_id'] != $_SESSION['user_id']) {
                    throw new Exception('You can only create sessions for yourself');
                }
            }

            $stmt = $pdo->prepare("
                INSERT INTO training_sessions 
                (session_name, trainer_id, gym_id, session_date, session_time, duration, max_capacity, description, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $sessionName, $trainerId, $gymId, $sessionDate, $sessionTime,
                $duration, $maxCapacity, $description, $status
            ]);

            setMessage('Session created successfully!', 'success');
            redirect('modules/sessions/index.php');
        } catch (Exception $e) {
            setMessage('Error creating session: ' . $e->getMessage(), 'error');
        }
    }
}

?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2>Create New Training Session</h2>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message['type']); ?>">
                <?php echo htmlspecialchars($message['text']); ?>
            </div>
        <?php endif; ?>

        <div class="card-body">
            <form method="POST" class="form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="session_name">Session Name *</label>
                        <input type="text" id="session_name" name="session_name" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="trainer_id">Trainer *</label>
                        <?php if ($_SESSION['user_type'] === 'trainer'): ?>
                            <!-- For trainers, show read-only field -->
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($trainers[0]['trainer_name'] ?? 'N/A'); ?>" readonly>
                            <input type="hidden" name="trainer_id" value="<?php echo htmlspecialchars($trainers[0]['trainer_id'] ?? ''); ?>">
                            <small class="form-text text-muted">You can only create sessions for yourself</small>
                        <?php else: ?>
                            <!-- For admin, show dropdown -->
                            <select id="trainer_id" name="trainer_id" class="form-control" required>
                                <option value="">Select Trainer</option>
                                <?php foreach ($trainers as $trainer): ?>
                                    <option value="<?php echo $trainer['trainer_id']; ?>">
                                        <?php echo htmlspecialchars($trainer['trainer_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="gym_id">Gym *</label>
                        <select id="gym_id" name="gym_id" class="form-control" required>
                            <option value="">Select Gym</option>
                            <?php foreach ($gyms as $gym): ?>
                                <option value="<?php echo $gym['gym_id']; ?>">
                                    <?php echo htmlspecialchars($gym['gym_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="session_date">Session Date *</label>
                        <input type="date" id="session_date" name="session_date" class="form-control" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="session_time">Session Time *</label>
                        <input type="time" id="session_time" name="session_time" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="duration">Duration (minutes) *</label>
                        <input type="number" id="duration" name="duration" class="form-control" min="15" step="15" required>
                    </div>

                    <div class="form-group">
                        <label for="max_capacity">Max Capacity *</label>
                        <input type="number" id="max_capacity" name="max_capacity" class="form-control" min="1" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control">
                            <option value="Scheduled">Scheduled</option>
                            <option value="Ongoing">Ongoing</option>
                            <option value="Completed">Completed</option>
                            <option value="Cancelled">Cancelled</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description" class="form-control" rows="4"></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Create Session</button>
                    <a href="index.php" class="btn btn-light">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
