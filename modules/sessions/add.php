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
            redirect(APP_URL . 'modules/sessions/index.php');
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
                <!-- Session Details Section -->
                <div style="margin-bottom: 30px;">
                    <h4 style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px; color: #333; font-weight: 600;">
                        <i class="fas fa-info-circle" style="color: #007bff;"></i>
                        Session Details
                    </h4>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="session_name" style="font-weight: 500;">
                                <i class="fas fa-heading" style="color: #666; margin-right: 5px;"></i>
                                Session Name *
                            </label>
                            <input type="text" id="session_name" name="session_name" class="form-control" 
                                   placeholder="e.g., Beginner Yoga, Advanced CrossFit" required>
                            <small class="form-text text-muted">
                                <i class="fas fa-lightbulb" style="color: #ffc107;"></i>
                                Give your session a clear, descriptive name
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="trainer_id" style="font-weight: 500;">
                                <i class="fas fa-user-tie" style="color: #666; margin-right: 5px;"></i>
                                Trainer *
                            </label>
                            <?php if ($_SESSION['user_type'] === 'trainer'): ?>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($trainers[0]['trainer_name'] ?? 'N/A'); ?>" readonly style="background-color: #f5f5f5;">
                                <input type="hidden" name="trainer_id" value="<?php echo htmlspecialchars($trainers[0]['trainer_id'] ?? ''); ?>">
                                <small class="form-text text-muted">
                                    <i class="fas fa-lock" style="color: #28a745;"></i>
                                    Creating session for yourself
                                </small>
                            <?php else: ?>
                                <select id="trainer_id" name="trainer_id" class="form-control" required onchange="trainersChanged()">
                                    <option value="">-- Select a Trainer --</option>
                                    <?php foreach ($trainers as $trainer): ?>
                                        <option value="<?php echo $trainer['trainer_id']; ?>">
                                            <?php echo htmlspecialchars($trainer['trainer_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">
                                    <i class="fas fa-users" style="color: #666;"></i>
                                    Choose the trainer leading this session
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description" style="font-weight: 500;">
                            <i class="fas fa-align-left" style="color: #666; margin-right: 5px;"></i>
                            Description
                        </label>
                        <textarea id="description" name="description" class="form-control" rows="3"
                                  placeholder="e.g., Focus on core strength and flexibility. Suitable for beginners. Bring a mat."></textarea>
                        <small class="form-text text-muted">
                            <i class="fas fa-info-circle" style="color: #17a2b8;"></i>
                            Add details about the session, target audience, or requirements
                        </small>
                    </div>
                </div>

                <!-- Location & Schedule Section -->
                <div style="margin-bottom: 30px; padding-top: 20px; border-top: 2px solid #eee;">
                    <h4 style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px; color: #333; font-weight: 600;">
                        <i class="fas fa-calendar-alt" style="color: #28a745;"></i>
                        Date & Location
                    </h4>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="session_date" style="font-weight: 500;">
                                <i class="fas fa-calendar" style="color: #666; margin-right: 5px;"></i>
                                Session Date *
                            </label>
                            <input type="date" id="session_date" name="session_date" class="form-control" required style="cursor: pointer;">
                            <!-- Date Quick Buttons -->
                            <div style="display: flex; gap: 5px; margin-top: 8px; flex-wrap: wrap;">
                                <button type="button" class="btn btn-outline-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="setDateToday()">Today</button>
                                <button type="button" class="btn btn-outline-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="setDateTomorrow()">Tomorrow</button>
                                <button type="button" class="btn btn-outline-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="setDateNextWeek()">Next Week</button>
                                <button type="button" class="btn btn-outline-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="setDateNextMonth()">Next Month</button>
                            </div>
                            <small class="form-text text-muted" style="display: block; margin-top: 8px;">
                                <i class="fas fa-clock" style="color: #666;"></i>
                                Click the field or use quick buttons. Choose a date up to 90 days in advance.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="gym_id" style="font-weight: 500;">
                                <i class="fas fa-dumbbell" style="color: #666; margin-right: 5px;"></i>
                                Gym Location *
                            </label>
                            <select id="gym_id" name="gym_id" class="form-control" required>
                                <option value="">-- Select a Gym --</option>
                                <?php foreach ($gyms as $gym): ?>
                                    <option value="<?php echo $gym['gym_id']; ?>">
                                        <?php echo htmlspecialchars($gym['gym_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">
                                <i class="fas fa-map-marker-alt" style="color: #dc3545;"></i>
                                Select where the session will take place
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Time & Duration Section -->
                <div style="margin-bottom: 30px; padding-top: 20px; border-top: 2px solid #eee;">
                    <h4 style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px; color: #333; font-weight: 600;">
                        <i class="fas fa-hourglass-half" style="color: #ff6b6b;"></i>
                        Time & Duration
                    </h4>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="session_time" style="font-weight: 500;">
                                <i class="fas fa-clock" style="color: #666; margin-right: 5px;"></i>
                                Start Time *
                            </label>
                            <input type="time" id="session_time" name="session_time" class="form-control" required onchange="updateEndTime()">
                            <small class="form-text text-muted">
                                <i class="fas fa-play-circle" style="color: #666;"></i>
                                When does the session start?
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="duration" style="font-weight: 500;">
                                <i class="fas fa-stopwatch" style="color: #666; margin-right: 5px;"></i>
                                Duration *
                            </label>
                            <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                                <input type="number" id="duration" name="duration" class="form-control" 
                                       min="15" step="15" required onchange="updateEndTime()" style="flex: 1;">
                                <span style="display: flex; align-items: center; padding: 0 10px; background: #f5f5f5; border-radius: 4px; color: #666; font-weight: 500; white-space: nowrap;">
                                    minutes
                                </span>
                            </div>
                            <!-- Duration Presets -->
                            <div style="display: flex; gap: 5px; margin-bottom: 8px; flex-wrap: wrap;">
                                <button type="button" class="btn btn-outline-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="setDuration(30)">30 min</button>
                                <button type="button" class="btn btn-outline-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="setDuration(60)">1 hour</button>
                                <button type="button" class="btn btn-outline-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="setDuration(90)">90 min</button>
                                <button type="button" class="btn btn-outline-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="setDuration(120)">2 hours</button>
                            </div>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle" style="color: #17a2b8;"></i>
                                15-480 minutes (15 min increment). Use quick buttons or enter custom duration.
                            </small>
                        </div>

                        <div class="form-group">
                            <label style="font-weight: 500;">
                                <i class="fas fa-stop-circle" style="color: #666; margin-right: 5px;"></i>
                                End Time
                            </label>
                            <input type="text" id="end_time_display" class="form-control" readonly 
                                   style="background-color: #f5f5f5; cursor: not-allowed;" placeholder="Calculated automatically">
                            <small class="form-text text-muted">
                                <i class="fas fa-check-circle" style="color: #28a745;"></i>
                                Automatically calculated from start time + duration
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Capacity & Status Section -->
                <div style="margin-bottom: 30px; padding-top: 20px; border-top: 2px solid #eee;">
                    <h4 style="display: flex; align-items: center; gap: 8px; margin-bottom: 15px; color: #333; font-weight: 600;">
                        <i class="fas fa-users-cog" style="color: #007bff;"></i>
                        Capacity & Status
                    </h4>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="max_capacity" style="font-weight: 500;">
                                <i class="fas fa-chair" style="color: #666; margin-right: 5px;"></i>
                                Max Capacity *
                            </label>
                            <div style="display: flex; gap: 8px;">
                                <input type="number" id="max_capacity" name="max_capacity" class="form-control" 
                                       min="1" required style="flex: 1;">
                                <span style="display: flex; align-items: center; padding: 0 10px; background: #f5f5f5; border-radius: 4px; color: #666; font-weight: 500; white-space: nowrap;">
                                    members
                                </span>
                            </div>
                            <small class="form-text text-muted">
                                <i class="fas fa-users" style="color: #666;"></i>
                                Maximum number of participants allowed
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="status" style="font-weight: 500;">
                                <i class="fas fa-tag" style="color: #666; margin-right: 5px;"></i>
                                Session Status
                            </label>
                            <select id="status" name="status" class="form-control">
                                <option value="Scheduled">🟢 Scheduled (upcoming session)</option>
                                <option value="Ongoing">🟡 Ongoing (currently happening)</option>
                                <option value="Completed">🔵 Completed (already finished)</option>
                                <option value="Cancelled">⚫ Cancelled (not happening)</option>
                            </select>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle" style="color: #666;"></i>
                                Most sessions start as "Scheduled"
                            </small>
                        </div>
                    </div>
                </div>

                <div class="form-actions" style="padding-top: 20px; border-top: 2px solid #eee;">
                    <button type="submit" class="btn btn-primary" style="min-width: 150px;">
                        <i class="fas fa-plus" style="margin-right: 8px;"></i>
                        Create Session
                    </button>
                    <a href="index.php" class="btn btn-light">
                        <i class="fas fa-times" style="margin-right: 8px;"></i>
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <!-- JavaScript for UX Enhancements -->
        <script>
        function setDuration(minutes) {
            document.getElementById('duration').value = minutes;
            updateEndTime();
        }

        function updateEndTime() {
            const timeInput = document.getElementById('session_time').value;
            const durationInput = document.getElementById('duration').value;
            const endTimeDisplay = document.getElementById('end_time_display');

            if (timeInput && durationInput) {
                const [hours, minutes] = timeInput.split(':').map(Number);
                const totalMinutes = hours * 60 + minutes + parseInt(durationInput);
                const endHours = Math.floor(totalMinutes / 60);
                const endMinutes = totalMinutes % 60;
                
                const endTime = `${String(endHours).padStart(2, '0')}:${String(endMinutes).padStart(2, '0')}`;
                endTimeDisplay.value = endTime;
            } else {
                endTimeDisplay.value = '';
            }
        }

        function trainersChanged() {
            // Optional: Add trainer-specific information loading here
        }

        // Date helper functions
        function getFormattedDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        function setDateToday() {
            const today = new Date();
            document.getElementById('session_date').value = getFormattedDate(today);
        }

        function setDateTomorrow() {
            const tomorrow = new Date();
            tomorrow.setDate(tomorrow.getDate() + 1);
            document.getElementById('session_date').value = getFormattedDate(tomorrow);
        }

        function setDateNextWeek() {
            const nextWeek = new Date();
            nextWeek.setDate(nextWeek.getDate() + 7);
            document.getElementById('session_date').value = getFormattedDate(nextWeek);
        }

        function setDateNextMonth() {
            const nextMonth = new Date();
            nextMonth.setDate(nextMonth.getDate() + 30);
            document.getElementById('session_date').value = getFormattedDate(nextMonth);
        }

        // Calculate end time on page load if fields are pre-filled
        document.addEventListener('DOMContentLoaded', function() {
            updateEndTime();
            // Auto-focus on date input to show calendar
            document.getElementById('session_date').addEventListener('click', function() {
                this.focus();
                this.click();
            });
        });
        </script>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
