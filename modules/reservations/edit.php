<?php
/**
 * Reservations - Edit Reservation
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
// Members and admins can make reservations
if ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'member') {
    die('Access denied: Only members and admins can make reservations.');
}

$reservationId = sanitize($_GET['id'] ?? '');
$reservation = null;
$errors = [];
$formData = [];
$members = [];
$equipment = [];
$isAdmin = $_SESSION['user_type'] === 'admin';
$currentMemberId = null;

// Get current user's member ID if they are a member
if (!$isAdmin) {
    try {
        $memberStmt = $pdo->prepare("SELECT member_id FROM members WHERE user_id = ? AND status = 'Active'");
        $memberStmt->execute([$_SESSION['user_id']]);
        $memberData = $memberStmt->fetch();
        $currentMemberId = $memberData['member_id'] ?? null;
        
        // If user is a member but doesn't have a member record, deny access
        if (!$currentMemberId) {
            die('Access denied: No active member record found for your account.');
        }
    } catch (Exception $e) {
        setMessage('Error loading member data: ' . $e->getMessage(), 'error');
    }
}

// Load members and equipment for dropdowns
try {
    // If member, only load their own member info; if admin, load all active members
    if ($isAdmin) {
        $memberStmt = $pdo->prepare("SELECT member_id, member_name FROM members WHERE status = 'Active' ORDER BY member_name");
        $memberStmt->execute();
    } else {
        $memberStmt = $pdo->prepare("SELECT member_id, member_name FROM members WHERE member_id = ? AND status = 'Active'");
        $memberStmt->execute([$currentMemberId]);
    }
    $members = $memberStmt->fetchAll();

    $equipmentStmt = $pdo->prepare("SELECT equipment_id, equipment_name FROM equipment ORDER BY equipment_name");
    $equipmentStmt->execute();
    $equipment = $equipmentStmt->fetchAll();
} catch (Exception $e) {
    setMessage('Error loading data: ' . $e->getMessage(), 'error');
}

// Load reservation
if (!empty($reservationId)) {
    try {
        $stmt = $pdo->prepare("SELECT r.* FROM reservations r JOIN members m ON r.member_id = m.member_id WHERE r.reservation_id = ?");
        $stmt->execute([$reservationId]);
        $reservation = $stmt->fetch();
        
        if (!$reservation) {
            setMessage('Reservation not found', 'error');
            redirect(APP_URL . 'modules/reservations/');
        }
        
        // Members can only edit their own reservations
        if (!$isAdmin && $reservation['member_id'] !== $currentMemberId) {
            die('Access denied: You can only edit your own reservations.');
        }
        
        // Check member status
        $memberCheck = $pdo->prepare("SELECT status FROM members WHERE member_id = ?");
        $memberCheck->execute([$reservation['member_id']]);
        $memberData = $memberCheck->fetch();
        if (!$memberData || $memberData['status'] !== 'Active') {
            setMessage('This reservation belongs to an inactive member and cannot be edited', 'error');
            redirect(APP_URL . 'modules/reservations/');
        }
        
        $formData = $reservation;
    } catch (Exception $e) {
        setMessage('Error loading reservation: ' . $e->getMessage(), 'error');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($reservationId)) {
    $formData['reservation_date'] = sanitize($_POST['reservation_date'] ?? '');
    $formData['start_time'] = sanitize($_POST['start_time'] ?? '');
    $formData['end_time'] = sanitize($_POST['end_time'] ?? '');
    $formData['notes'] = sanitize($_POST['notes'] ?? '');
    $formData['status'] = sanitize($_POST['status'] ?? 'Pending');

    // Validate Reservation Date
    if (empty($formData['reservation_date'])) {
        $errors['reservation_date'] = 'Please select a reservation date';
    } else {
        $reservationDateObj = DateTime::createFromFormat('Y-m-d', $formData['reservation_date']);
        if (!$reservationDateObj || $reservationDateObj->format('Y-m-d') !== $formData['reservation_date']) {
            $errors['reservation_date'] = 'Invalid date format';
        }
    }

    // Validate Start Time
    if (empty($formData['start_time'])) {
        $errors['start_time'] = 'Please enter start time';
    } elseif (!preg_match('/^\d{2}:\d{2}$/', $formData['start_time'])) {
        $errors['start_time'] = 'Invalid time format (use HH:MM)';
    }

    // Validate End Time
    if (empty($formData['end_time'])) {
        $errors['end_time'] = 'Please enter end time';
    } elseif (!preg_match('/^\d{2}:\d{2}$/', $formData['end_time'])) {
        $errors['end_time'] = 'Invalid time format (use HH:MM)';
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
        
        // Check maximum duration (no more than 8 hours)
        if ($durationMinutes > 480) {
            $errors['duration'] = 'Reservation cannot exceed 8 hours';
        }
    }

    // Check for conflicts (excluding current reservation)
    if (empty($errors) || (count($errors) <= 1 && isset($errors['time_conflict']))) {
        try {
            // Check for time conflicts with same equipment
            $conflictStmt = $pdo->prepare("
                SELECT COUNT(*) as count FROM reservations 
                WHERE equipment_id = ? 
                AND reservation_date = ? 
                AND reservation_id != ?
                AND status IN ('Confirmed')
                AND (
                    (TIME(start_time) < TIME(?) AND TIME(end_time) > TIME(?))
                    OR (TIME(start_time) = TIME(?) AND TIME(end_time) > TIME(?))
                    OR (TIME(start_time) < TIME(?) AND TIME(end_time) = TIME(?))
                )
            ");
            $conflictStmt->execute([
                $reservation['equipment_id'],
                $formData['reservation_date'],
                $reservationId,
                $formData['end_time'],
                $formData['start_time'],
                $formData['start_time'],
                $formData['start_time'],
                $formData['end_time'],
                $formData['end_time']
            ]);
            $conflict = $conflictStmt->fetch();
            
            if ($conflict['count'] > 0) {
                $errors['time_conflict'] = 'This equipment is already reserved during the selected time. Please choose a different time slot.';
            }
        } catch (Exception $e) {
            $errors['database'] = 'Error checking availability: ' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE reservations SET 
                    reservation_date = ?, start_time = ?, end_time = ?, 
                    notes = ?, status = ?
                WHERE reservation_id = ?
            ");
            $stmt->execute([
                $formData['reservation_date'], $formData['start_time'], 
                $formData['end_time'], !empty($formData['notes']) ? $formData['notes'] : NULL,
                $formData['status'], $reservationId
            ]);

            logAction($_SESSION['user_id'], 'EDIT_RESERVATION', 'Reservations', 'Updated reservation: ' . $reservationId);

            setMessage('Reservation updated successfully', 'success');
            redirect(APP_URL . 'modules/reservations/view.php?id=' . $reservationId);
        } catch (Exception $e) {
            setMessage('Error updating reservation: ' . $e->getMessage(), 'error');
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
                <h1><i class="fas fa-edit"></i> Edit Reservation</h1>
                <p>Update reservation details</p>
            </div>

            <?php displayMessage(); ?>

            <?php if ($reservation): ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Reservation Details - <?php echo htmlspecialchars($reservation['reservation_id']); ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" class="needs-validation" novalidate>
                                <?php if (!empty($errors['time_conflict'])): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-circle"></i> <?php echo $errors['time_conflict']; ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($errors['database'])): ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-circle"></i> <?php echo $errors['database']; ?>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label class="form-label">Member</label>
                                    <div class="form-control-plaintext bg-light border rounded p-2">
                                        <strong>
                                            <?php 
                                            // Find and display member name
                                            foreach ($members as $member) {
                                                if ($member['member_id'] === $reservation['member_id']) {
                                                    echo htmlspecialchars($member['member_name']);
                                                    break;
                                                }
                                            }
                                            ?>
                                        </strong>
                                        <small class="text-muted d-block mt-1">ID: <code><?php echo htmlspecialchars($reservation['member_id']); ?></code></small>
                                    </div>
                                    <small class="text-muted">Member cannot be changed</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Equipment</label>
                                    <input type="text" class="form-control" disabled value="<?php echo htmlspecialchars($reservation['equipment_id']); ?>">
                                    <small class="text-muted">Equipment cannot be changed</small>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="reservation_date" class="form-label">Reservation Date *</label>
                                        <input type="date" class="form-control <?php echo isset($errors['reservation_date']) ? 'is-invalid' : ''; ?>" 
                                               id="reservation_date" name="reservation_date" 
                                               value="<?php echo htmlspecialchars($formData['reservation_date'] ?? ''); ?>" required>
                                        <?php if (isset($errors['reservation_date'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['reservation_date']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label for="start_time" class="form-label">Start Time *</label>
                                        <input type="time" class="form-control <?php echo isset($errors['start_time']) ? 'is-invalid' : ''; ?>" 
                                               id="start_time" name="start_time" 
                                               value="<?php echo htmlspecialchars($formData['start_time'] ?? ''); ?>" required>
                                        <?php if (isset($errors['start_time'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['start_time']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-3 mb-3">
                                        <label for="end_time" class="form-label">End Time *</label>
                                        <input type="time" class="form-control <?php echo isset($errors['end_time']) ? 'is-invalid' : ''; ?>" 
                                               id="end_time" name="end_time" 
                                               value="<?php echo htmlspecialchars($formData['end_time'] ?? ''); ?>" required>
                                        <?php if (isset($errors['end_time'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['end_time']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3">
                                              <?php echo htmlspecialchars($formData['notes'] ?? ''); ?></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-select" id="status" name="status">
                                        <option value="Pending" <?php echo ($formData['status'] ?? '') === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Confirmed" <?php echo ($formData['status'] ?? '') === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                        <option value="Cancelled" <?php echo ($formData['status'] ?? '') === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="<?php echo APP_URL; ?>modules/reservations/view.php?id=<?php echo $reservationId; ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Reservation
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Reservation Info</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Reservation ID:</strong><br>
                                <code><?php echo htmlspecialchars($reservation['reservation_id']); ?></code>
                            </p>
                            <hr>
                            <p>
                                <strong>Status:</strong><br>
                                <span class="badge badge-<?php 
                                    echo $reservation['status'] === 'Confirmed' ? 'success' : 
                                         ($reservation['status'] === 'Pending' ? 'warning' : 'danger');
                                ?>">
                                    <?php echo htmlspecialchars($reservation['status']); ?>
                                </span>
                            </p>
                            <hr>
                            <p>
                                <strong>Created:</strong><br>
                                <?php echo formatDate($reservation['created_at']); ?>
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
