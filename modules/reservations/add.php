<?php
/**
 * Reservations - Create New Reservation
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
// Members and admins can make reservations
if ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'member') {
    die('Access denied: Only members and admins can make reservations.');
}

$errors = [];
$formData = [];
$trainers = [];
$isAdmin = $_SESSION['user_type'] === 'admin';
$currentMemberId = null;
$memberTrainer = null;

// Get current user's member ID if they are a member
if (!$isAdmin) {
    try {
        $memberStmt = $pdo->prepare("SELECT member_id, trainer_id FROM members WHERE user_id = ? AND status = 'Active'");
        $memberStmt->execute([$_SESSION['user_id']]);
        $memberData = $memberStmt->fetch();
        $currentMemberId = $memberData['member_id'] ?? null;
        $memberTrainer = $memberData['trainer_id'] ?? null;
        
        if (!$currentMemberId) {
            die('Access denied: No active member record found for your account.');
        }
    } catch (Exception $e) {
        setMessage('Error loading member data: ' . $e->getMessage(), 'error');
    }
}

// Load trainers for dropdown
try {
    if ($isAdmin) {
        $trainerStmt = $pdo->prepare("SELECT trainer_id, trainer_name FROM trainers WHERE status = 'Active' ORDER BY trainer_name");
        $trainerStmt->execute();
    } else {
        $trainerStmt = $pdo->prepare("SELECT trainer_id, trainer_name FROM trainers WHERE status = 'Active' ORDER BY trainer_name");
        $trainerStmt->execute();
    }
    $trainers = $trainerStmt->fetchAll();
} catch (Exception $e) {
    setMessage('Error loading data: ' . $e->getMessage(), 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // For members, automatically use their own member_id; for admins, use the form value
    if ($isAdmin) {
        $formData['member_id'] = sanitize($_POST['member_id'] ?? '');
    } else {
        $formData['member_id'] = $currentMemberId; // Auto-populate for members
    }
    
    $formData['trainer_id'] = sanitize($_POST['trainer_id'] ?? '');
    $formData['reservation_date'] = sanitize($_POST['reservation_date'] ?? '');
    $formData['start_time'] = sanitize($_POST['start_time'] ?? '');
    $formData['end_time'] = sanitize($_POST['end_time'] ?? '');
    $formData['purpose'] = sanitize($_POST['purpose'] ?? '');
    $formData['notes'] = sanitize($_POST['notes'] ?? '');
    $formData['status'] = sanitize($_POST['status'] ?? 'Pending');

    // Validate Member
    if (empty($formData['member_id'])) {
        $errors['member_id'] = 'Member information is missing';
    } else {
        $memberCheck = $pdo->prepare("SELECT member_id FROM members WHERE member_id = ? AND status = 'Active'");
        $memberCheck->execute([$formData['member_id']]);
        if (!$memberCheck->fetch()) {
            $errors['member_id'] = 'Selected member is not active or does not exist';
        }
    }

    // Check if member has pending reservations (prevent multiple pending requests)
    if (empty($errors['member_id']) && !$isAdmin) {
        $pendingCheck = $pdo->prepare("
            SELECT COUNT(*) as pending_count, GROUP_CONCAT(reservation_id) as pending_ids
            FROM reservations 
            WHERE member_id = ? AND status = 'Pending'
        ");
        $pendingCheck->execute([$formData['member_id']]);
        $pendingResult = $pendingCheck->fetch();
        
        if ($pendingResult['pending_count'] > 0) {
            $errors['pending_requests'] = 'You have pending trainer time requests that need to be reviewed first. Request ID(s): ' . $pendingResult['pending_ids'] . '. Please wait for your trainer to approve or reject them before submitting a new request.';
        }
    }

    // Validate Trainer
    if (empty($formData['trainer_id'])) {
        $errors['trainer_id'] = 'Please select a trainer';
    } else {
        $trainerCheck = $pdo->prepare("SELECT trainer_id FROM trainers WHERE trainer_id = ? AND status = 'Active'");
        $trainerCheck->execute([$formData['trainer_id']]);
        if (!$trainerCheck->fetch()) {
            $errors['trainer_id'] = 'Selected trainer is not active or does not exist';
        }
    }

    // Validate Reservation Date
    if (empty($formData['reservation_date'])) {
        $errors['reservation_date'] = 'Please select a reservation date';
    } else {
        $reservationDateObj = DateTime::createFromFormat('Y-m-d', $formData['reservation_date']);
        if (!$reservationDateObj || $reservationDateObj->format('Y-m-d') !== $formData['reservation_date']) {
            $errors['reservation_date'] = 'Invalid date format';
        } elseif ($reservationDateObj < new DateTime('today')) {
            $errors['reservation_date'] = 'Reservation date cannot be in the past';
        } elseif ($reservationDateObj > new DateTime('+90 days')) {
            $errors['reservation_date'] = 'Reservations can only be made up to 90 days in advance';
        }
    }

    // Validate Start Time
    if (empty($formData['start_time'])) {
        $errors['start_time'] = 'Please enter start time';
    } elseif (!preg_match('/^\d{2}:\d{2}$/', $formData['start_time'])) {
        $errors['start_time'] = 'Invalid time format (use HH:MM)';
    } else {
        // Check gym hours (6:00 AM - 10:00 PM)
        $startParts = explode(':', $formData['start_time']);
        $startHour = (int)$startParts[0];
        if ($startHour < 6 || $startHour >= 22) {
            $errors['start_time'] = 'Gym hours are 6:00 AM - 10:00 PM. Please select a time within these hours.';
        }
    }

    // Validate End Time
    if (empty($formData['end_time'])) {
        $errors['end_time'] = 'Please enter end time';
    } elseif (!preg_match('/^\d{2}:\d{2}$/', $formData['end_time'])) {
        $errors['end_time'] = 'Invalid time format (use HH:MM)';
    } else {
        // Check gym hours (6:00 AM - 10:00 PM)
        $endParts = explode(':', $formData['end_time']);
        $endHour = (int)$endParts[0];
        if ($endHour < 6 || ($endHour >= 22 && $formData['end_time'] !== '22:00')) {
            $errors['end_time'] = 'Gym hours are 6:00 AM - 10:00 PM. Please select a time within these hours.';
        }
    }

    // Validate time relationship
    if (!empty($formData['start_time']) && !empty($formData['end_time']) && !isset($errors['start_time']) && !isset($errors['end_time'])) {
        $startSeconds = strtotime('2000-01-01 ' . $formData['start_time']);
        $endSeconds = strtotime('2000-01-01 ' . $formData['end_time']);
        
        if ($endSeconds <= $startSeconds) {
            $errors['end_time'] = 'End time must be after start time';
        }
        
        // Check minimum duration (at least 30 minutes)
        $durationMinutes = ($endSeconds - $startSeconds) / 60;
        if ($durationMinutes < 30) {
            $errors['duration'] = 'Reservation must be at least 30 minutes long';
        }
        
        // Check maximum duration (no more than 2 hours for one-on-one)
        if ($durationMinutes > 120) {
            $errors['duration'] = 'One-on-one sessions cannot exceed 2 hours';
        }
    }

    // Check for trainer conflicts
    if (empty($errors) || (count($errors) <= 1 && isset($errors['time_conflict']))) {
        try {
            $conflictStmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM reservations 
                WHERE trainer_id = ? 
                AND reservation_date = ? 
                AND status IN ('Confirmed', 'Pending')
                AND (
                    (TIME(start_time) < TIME(?) AND TIME(end_time) > TIME(?))
                    OR (TIME(start_time) = TIME(?) AND TIME(end_time) > TIME(?))
                    OR (TIME(start_time) < TIME(?) AND TIME(end_time) = TIME(?))
                )
            ");
            $conflictStmt->execute([
                $formData['trainer_id'],
                $formData['reservation_date'],
                $formData['end_time'],
                $formData['start_time'],
                $formData['start_time'],
                $formData['start_time'],
                $formData['end_time'],
                $formData['end_time']
            ]);
            $conflict = $conflictStmt->fetch();
            
            if ($conflict['count'] > 0) {
                $errors['time_conflict'] = 'This trainer is already booked during the selected time. Please choose a different time slot.';
            }
        } catch (Exception $e) {
            $errors['database'] = 'Error checking availability: ' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            $reservationId = generateUniqueID(RESERVATION_ID_PREFIX, 'reservations');

            $stmt = $pdo->prepare("
                INSERT INTO reservations (
                    reservation_id, member_id, trainer_id, reservation_date, 
                    start_time, end_time, purpose, notes, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $reservationId, $formData['member_id'], $formData['trainer_id'],
                $formData['reservation_date'], $formData['start_time'], 
                $formData['end_time'], !empty($formData['purpose']) ? $formData['purpose'] : NULL,
                !empty($formData['notes']) ? $formData['notes'] : NULL,
                $formData['status']
            ]);

            logAction($_SESSION['user_id'], 'CREATE_RESERVATION', 'Reservations', 
                     'Created reservation: ' . $reservationId);
            
            // Send notification to trainer about the new request
            try {
                $memberStmt = $pdo->prepare("SELECT member_name FROM members WHERE member_id = ?");
                $memberStmt->execute([$formData['member_id']]);
                $memberData = $memberStmt->fetch();
                
                $trainerStmt = $pdo->prepare("SELECT user_id FROM trainers WHERE trainer_id = ?");
                $trainerStmt->execute([$formData['trainer_id']]);
                $trainerData = $trainerStmt->fetch();
                
                if ($memberData && $trainerData) {
                    notifyTrainerOfReservationRequest(
                        $trainerData['user_id'],
                        $memberData['member_name'],
                        $reservationId,
                        $formData['reservation_date'],
                        $formData['start_time'],
                        $formData['end_time'],
                        $formData['purpose'] ?? ''
                    );
                }
            } catch (Exception $e) {
                error_log('Failed to send trainer notification: ' . $e->getMessage());
            }

            setMessage('Trainer time request submitted successfully! ID: ' . $reservationId . ' (Trainer will review and confirm)', 'success');
            redirect(APP_URL . 'modules/reservations/');
        } catch (Exception $e) {
            setMessage('Error creating reservation: ' . $e->getMessage(), 'error');
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/reservations/" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-plus-circle"></i> Request Trainer Time</h1>
                <p>Reserve one-on-one time with your trainer</p>
            </div>

            <?php displayMessage(); ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Reservation Details</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" class="needs-validation" novalidate>
                                <?php if (!empty($errors['pending_requests'])): ?>
                                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                                        <i class="fas fa-hourglass-half"></i> <strong>Pending Requests!</strong> <?php echo $errors['pending_requests']; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($errors['time_conflict'])): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-calendar-times"></i> <strong>Time Conflict!</strong> <?php echo $errors['time_conflict']; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($errors['database'])): ?>
                                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                        <i class="fas fa-exclamation-circle"></i> <strong>Error!</strong> <?php echo $errors['database']; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($errors['duration'])): ?>
                                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                                        <i class="fas fa-hourglass-half"></i> <strong>Duration Issue!</strong> <?php echo $errors['duration']; ?>
                                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                    </div>
                                <?php endif; ?>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <?php if ($isAdmin): ?>
                                            <!-- Admin: Show dropdown to select any member -->
                                            <label for="member_id" class="form-label">Member *</label>
                                            <select class="form-select <?php echo isset($errors['member_id']) ? 'is-invalid' : ''; ?>" 
                                                    id="member_id" name="member_id" required>
                                                <option value="">-- Select Member --</option>
                                                <?php foreach ($members as $member): ?>
                                                    <option value="<?php echo $member['member_id']; ?>" 
                                                            <?php echo ($formData['member_id'] ?? '') === $member['member_id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($member['member_name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <?php if (isset($errors['member_id'])): ?>
                                                <div class="invalid-feedback"><?php echo $errors['member_id']; ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <!-- Member: Show read-only member info -->
                                            <label for="member_name" class="form-label">Your Member Profile</label>
                                            <div class="form-control-plaintext bg-light border rounded p-2">
                                                <strong>
                                                    <?php 
                                                    $memberNameStmt = $pdo->prepare("SELECT member_name FROM members WHERE member_id = ?");
                                                    $memberNameStmt->execute([$currentMemberId]);
                                                    $memberNameData = $memberNameStmt->fetch();
                                                    echo htmlspecialchars($memberNameData['member_name'] ?? 'N/A');
                                                    ?>
                                                </strong>
                                                <small class="text-muted d-block mt-1">ID: <code><?php echo htmlspecialchars($currentMemberId); ?></code></small>
                                            </div>
                                            <input type="hidden" name="member_id" value="<?php echo htmlspecialchars($currentMemberId); ?>">
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="trainer_id" class="form-label">Trainer *</label>
                                        <select class="form-select <?php echo isset($errors['trainer_id']) ? 'is-invalid' : ''; ?>" 
                                                id="trainer_id" name="trainer_id" required>
                                            <option value="">-- Select Trainer --</option>
                                            <?php foreach ($trainers as $trainer): ?>
                                                <option value="<?php echo $trainer['trainer_id']; ?>" 
                                                        <?php echo ($formData['trainer_id'] ?? $memberTrainer) === $trainer['trainer_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($trainer['trainer_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <?php if (isset($errors['trainer_id'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['trainer_id']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-12">
                                        <h6 class="text-muted mb-3">
                                            <i class="fas fa-calendar"></i> Date & Time Slot Selection
                                        </h6>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="reservation_date" class="form-label">
                                            Reservation Date <span class="text-danger">*</span>
                                        </label>
                                        <input type="date" class="form-control <?php echo isset($errors['reservation_date']) ? 'is-invalid' : ''; ?>" 
                                               id="reservation_date" name="reservation_date" 
                                               value="<?php echo htmlspecialchars($formData['reservation_date'] ?? ''); ?>"
                                               style="cursor: pointer;"
                                               required>
                                        <?php if (isset($errors['reservation_date'])): ?>
                                            <div class="invalid-feedback d-block"><?php echo $errors['reservation_date']; ?></div>
                                        <?php endif; ?>
                                        <!-- Quick Date Buttons -->
                                        <div style="display: flex; gap: 5px; margin-top: 8px; flex-wrap: wrap;">
                                            <button type="button" class="btn btn-outline-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="setReservationDateToday()">Today</button>
                                            <button type="button" class="btn btn-outline-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="setReservationDateTomorrow()">Tomorrow</button>
                                            <button type="button" class="btn btn-outline-secondary" style="padding: 6px 12px; font-size: 12px;" onclick="setReservationDateNextWeek()">Next Week</button>
                                        </div>
                                        <small class="text-muted d-block mt-2">
                                            <i class="fas fa-info-circle"></i> 
                                            Click the field to open calendar or use quick buttons. Select a date up to 90 days in advance.
                                        </small>
                                    </div>
                                </div>

                                <div class="row mt-4">
                                    <div class="col-md-5 mb-3">
                                        <label for="start_time" class="form-label">
                                            Start Time <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-play-circle text-success"></i>
                                            </span>
                                            <input type="time" class="form-control <?php echo isset($errors['start_time']) ? 'is-invalid' : ''; ?>" 
                                                   id="start_time" name="start_time" 
                                                   value="<?php echo htmlspecialchars($formData['start_time'] ?? ''); ?>"
                                                   min="06:00" max="22:00" required>
                                            <?php if (isset($errors['start_time'])): ?>
                                                <div class="invalid-feedback d-block"><?php echo $errors['start_time']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted d-block mt-1">Gym hours: 6:00 AM - 10:00 PM</small>
                                    </div>

                                    <div class="col-md-5 mb-3">
                                        <label for="end_time" class="form-label">
                                            End Time <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text">
                                                <i class="fas fa-stop-circle text-danger"></i>
                                            </span>
                                            <input type="time" class="form-control <?php echo isset($errors['end_time']) ? 'is-invalid' : ''; ?>" 
                                                   id="end_time" name="end_time" 
                                                   value="<?php echo htmlspecialchars($formData['end_time'] ?? ''); ?>"
                                                   min="06:00" max="22:00" required>
                                            <?php if (isset($errors['end_time'])): ?>
                                                <div class="invalid-feedback d-block"><?php echo $errors['end_time']; ?></div>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted d-block mt-1">Must be after start time</small>
                                    </div>

                                    <div class="col-md-2 mb-3">
                                        <label class="form-label">Duration</label>
                                        <div class="alert alert-info mb-0">
                                            <small id="duration-display">-- min</small>
                                        </div>
                                        <small class="text-muted d-block mt-1">Min: 30 min<br>Max: 2 hrs</small>
                                    </div>
                                </div>

                                <?php if (isset($errors['duration'])): ?>
                                    <div class="alert alert-warning alert-sm">
                                        <i class="fas fa-hourglass-half"></i> <?php echo $errors['duration']; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Additional Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                                              placeholder="Any special requests or additional information...">
                                              <?php echo htmlspecialchars($formData['notes'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="Pending" <?php echo ($formData['status'] ?? 'Pending') === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Confirmed" <?php echo ($formData['status'] ?? '') === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="Cancelled" <?php echo ($formData['status'] ?? '') === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="<?php echo APP_URL; ?>modules/reservations/" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary" <?php echo !empty($errors['pending_requests']) ? 'disabled' : ''; ?>>
                                        <i class="fas fa-save"></i> Request Trainer Time
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle"></i> Requirements
                            </h5>
                        </div>
                        <div class="card-body small">
                            <p class="mb-2">
                                <i class="fas fa-check text-success"></i> 
                                <strong>Duration:</strong> 30 min - 2 hours
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-check text-success"></i> 
                                <strong>Hours:</strong> 6:00 AM - 10:00 PM
                            </p>
                            <p class="mb-2">
                                <i class="fas fa-check text-success"></i> 
                                <strong>Booking:</strong> Up to 90 days ahead
                            </p>
                            <p class="mb-0">
                                <i class="fas fa-check text-success"></i> 
                                <strong>Conflicts:</strong> Auto-checked
                            </p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-lightbulb"></i> Tips
                            </h5>
                        </div>
                        <div class="card-body small">
                            <ul class="mb-0">
                                <li class="mb-2">Required fields marked with <span class="text-danger">*</span></li>
                                <li class="mb-2">Your assigned trainer is pre-selected</li>
                                <li class="mb-2">Duration updates automatically</li>
                                <li class="mb-2">All conflicts are validated</li>
                                <li class="mb-2">You can only have one pending request at a time</li>
                                <li class="mb-2">Wait for trainer approval before requesting again</li>
                                <li>Trainer will confirm or reject your request</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const reservationDateInput = document.getElementById('reservation_date');
    const startTimeInput = document.getElementById('start_time');
    const endTimeInput = document.getElementById('end_time');
    const durationDisplay = document.getElementById('duration-display');

    // Helper function to format date for input
    function getFormattedDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // Set minimum date to today
    const today = new Date();
    reservationDateInput.setAttribute('min', getFormattedDate(today));
    
    // Set maximum date to 90 days from now
    const maxDate = new Date();
    maxDate.setDate(maxDate.getDate() + 90);
    reservationDateInput.setAttribute('max', getFormattedDate(maxDate));

    // Quick date buttons - these do NOT interfere with native picker
    window.setReservationDateToday = function() {
        reservationDateInput.value = getFormattedDate(new Date());
    };

    window.setReservationDateTomorrow = function() {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        reservationDateInput.value = getFormattedDate(tomorrow);
    };

    window.setReservationDateNextWeek = function() {
        const nextWeek = new Date();
        nextWeek.setDate(nextWeek.getDate() + 7);
        reservationDateInput.value = getFormattedDate(nextWeek);
    };

    function updateDuration() {
        if (startTimeInput.value && endTimeInput.value) {
            const start = new Date('2000-01-01 ' + startTimeInput.value);
            const end = new Date('2000-01-01 ' + endTimeInput.value);
            
            if (end > start) {
                const minutes = (end - start) / (1000 * 60);
                const hours = Math.floor(minutes / 60);
                const mins = minutes % 60;
                
                let display = '';
                if (hours > 0) {
                    display = hours + 'h ' + mins + 'm';
                } else {
                    display = mins + ' min';
                }
                
                durationDisplay.textContent = display;
                
                // Add visual feedback
                if (minutes < 30) {
                    durationDisplay.parentElement.classList.remove('alert-info', 'alert-success', 'alert-warning');
                    durationDisplay.parentElement.classList.add('alert-danger');
                } else if (minutes > 120) {
                    durationDisplay.parentElement.classList.remove('alert-info', 'alert-success', 'alert-danger');
                    durationDisplay.parentElement.classList.add('alert-warning');
                } else {
                    durationDisplay.parentElement.classList.remove('alert-danger', 'alert-warning');
                    durationDisplay.parentElement.classList.add('alert-success', 'alert-info');
                }
            } else {
                durationDisplay.textContent = '-- min';
                durationDisplay.parentElement.classList.remove('alert-success', 'alert-danger', 'alert-warning');
                durationDisplay.parentElement.classList.add('alert-info');
            }
        } else {
            durationDisplay.textContent = '-- min';
            durationDisplay.parentElement.classList.remove('alert-success', 'alert-danger', 'alert-warning');
            durationDisplay.parentElement.classList.add('alert-info');
        }
    }

    startTimeInput.addEventListener('change', updateDuration);
    endTimeInput.addEventListener('change', updateDuration);
    
    // Initial calculation
    updateDuration();

    // End time validation
    endTimeInput.addEventListener('change', function() {
        if (startTimeInput.value && endTimeInput.value) {
            const start = new Date('2000-01-01 ' + startTimeInput.value);
            const end = new Date('2000-01-01 ' + endTimeInput.value);
            
            if (end <= start) {
                endTimeInput.classList.add('is-invalid');
            } else {
                endTimeInput.classList.remove('is-invalid');
            }
        }
    });
});
</script>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
