<?php
/**
 * Reservations - Trainer Approval
 * Level Up Fitness - Gym Management System
 */

// Process form BEFORE including header to avoid header already sent error
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/database.php';
require_once dirname(dirname(dirname(__FILE__))) . '/includes/functions.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

requireLogin();

// Only trainers and admins can approve
if ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'trainer') {
    die('Access denied: Only trainers and admins can approve reservations.');
}

$errors = [];
$reservation = null;
$trainerNotes = '';
$currentTrainerId = null;
$gyms = [];

// Get current user's trainer ID if they are a trainer
if ($_SESSION['user_type'] === 'trainer') {
    try {
        $trainerStmt = $pdo->prepare("SELECT trainer_id FROM trainers WHERE user_id = ?");
        $trainerStmt->execute([$_SESSION['user_id']]);
        $trainerData = $trainerStmt->fetch();
        $currentTrainerId = $trainerData['trainer_id'] ?? null;
    } catch (Exception $e) {
        setMessage('Error loading trainer data: ' . $e->getMessage(), 'error');
    }
}

// Load gyms for session creation option
try {
    $gymStmt = $pdo->prepare("SELECT gym_id, gym_name FROM gyms ORDER BY gym_name");
    $gymStmt->execute();
    $gyms = $gymStmt->fetchAll();
} catch (Exception $e) {
    error_log('Error loading gyms: ' . $e->getMessage());
}

$reservationId = sanitize($_GET['id'] ?? '');

if (!empty($reservationId)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM reservations WHERE reservation_id = ?");
        $stmt->execute([$reservationId]);
        $reservation = $stmt->fetch();
        
        if (!$reservation) {
            setMessage('Reservation not found', 'error');
            redirect(APP_URL . 'modules/reservations/');
        }
        
        // Trainers can only approve reservations for themselves
        if ($_SESSION['user_type'] === 'trainer' && $reservation['trainer_id'] !== $currentTrainerId) {
            die('Access denied: You can only approve reservations assigned to you.');
        }
        
        // Only pending reservations can be approved
        if ($reservation['status'] !== 'Pending') {
            setMessage('Only pending reservations can be approved', 'error');
            redirect(APP_URL . 'modules/reservations/view.php?id=' . $reservationId);
        }
        
    } catch (Exception $e) {
        setMessage('Error loading reservation: ' . $e->getMessage(), 'error');
    }
}

// HANDLE FORM SUBMISSION BEFORE HEADER OUTPUT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($reservationId)) {
    try {
        $trainerNotes = sanitize($_POST['trainer_notes'] ?? '');
        $gymId = sanitize($_POST['gym_id'] ?? '');
        $duration = sanitize($_POST['duration'] ?? '');
        
        // Validate session creation fields (now mandatory)
        if (empty($gymId)) {
            $errors[] = 'Gym is required';
        }
        if (empty($duration)) {
            $errors[] = 'Duration is required';
        } elseif (!is_numeric($duration) || $duration < 15 || $duration > 480) {
            $errors[] = 'Duration must be between 15 and 480 minutes';
        }
        
        if (!empty($errors)) {
            setMessage(implode('<br>', $errors), 'error');
        } else {
            // Create training session FIRST (now mandatory)
            $sessionId = null;
            try {
                $sessionName = "Private Session - " . date('M d, Y', strtotime($reservation['reservation_date']));
                
                $sessionStmt = $pdo->prepare("
                    INSERT INTO training_sessions 
                    (session_name, trainer_id, gym_id, session_date, session_time, duration, max_capacity, description, status)
                    VALUES (?, ?, ?, ?, ?, ?, 1, ?, 'Scheduled')
                ");
                $sessionStmt->execute([
                    $sessionName,
                    $reservation['trainer_id'],
                    $gymId,
                    $reservation['reservation_date'],
                    $reservation['start_time'],
                    $duration,
                    "Private session created from reservation " . $reservationId
                ]);
                
                $sessionId = $pdo->lastInsertId();
                
                // Enroll member in training session
                $enrollStmt = $pdo->prepare("
                    INSERT INTO training_session_attendees 
                    (session_id, member_id, attendance_status)
                    VALUES (?, ?, 'Present')
                ");
                $enrollStmt->execute([$sessionId, $reservation['member_id']]);
                
                logAction($_SESSION['user_id'], 'CREATE_SESSION_FROM_RESERVATION', 'Sessions',
                         'Created session ' . $sessionId . ' from reservation ' . $reservationId);
                
            } catch (Exception $e) {
                error_log('Error creating training session: ' . $e->getMessage());
                setMessage('Error creating training session: ' . $e->getMessage(), 'error');
                throw $e;
            }
            
            // NOW update reservation status to Confirmed with session_id
            $updateStmt = $pdo->prepare("
                UPDATE reservations 
                SET status = 'Confirmed',
                    session_id = ?,
                    notes = CONCAT(IFNULL(notes, ''), '\n[Trainer Approval] ', ?)
                WHERE reservation_id = ?
            ");
            $updateStmt->execute([$sessionId, $trainerNotes, $reservationId]);
            
            logAction($_SESSION['user_id'], 'APPROVE_RESERVATION', 'Reservations', 
                     'Approved reservation: ' . $reservationId);
            
            // Send notification to member
            try {
                $reservationRefresh = $pdo->prepare("SELECT * FROM reservations WHERE reservation_id = ?");
                $reservationRefresh->execute([$reservationId]);
                $reservationRefresh = $reservationRefresh->fetch();
                
                if (!$reservationRefresh) {
                    throw new Exception('Reservation not found after update');
                }
                
                $memberStmt = $pdo->prepare("SELECT user_id FROM members WHERE member_id = ?");
                $memberStmt->execute([$reservationRefresh['member_id']]);
                $memberData = $memberStmt->fetch();
                
                if (!$memberData) {
                    error_log('Member not found for member_id: ' . $reservationRefresh['member_id']);
                    throw new Exception('Member record not found');
                }
                
                $trainerStmt = $pdo->prepare("SELECT trainer_name FROM trainers WHERE trainer_id = ?");
                $trainerStmt->execute([$reservationRefresh['trainer_id']]);
                $trainerData = $trainerStmt->fetch();
                
                if (!$trainerData) {
                    error_log('Trainer not found for trainer_id: ' . $reservationRefresh['trainer_id']);
                    throw new Exception('Trainer record not found');
                }
                
                $result = notifyMemberOfReservationApproval(
                    $memberData['user_id'],
                    $trainerData['trainer_name'],
                    $reservationId,
                    $reservationRefresh['reservation_date'],
                    $reservationRefresh['start_time'],
                    $reservationRefresh['end_time']
                );
                
                if (!$result) {
                    error_log('Failed to create notification for user: ' . $memberData['user_id']);
                }
            } catch (Exception $e) {
                error_log('Failed to send member notification: ' . $e->getMessage());
                error_log('Stack trace: ' . $e->getTraceAsString());
            }
            
            $successMsg = 'Reservation approved successfully! Training session #' . $sessionId . ' has been created and member enrolled. Member has been notified.';
            setMessage($successMsg, 'success');
            redirect(APP_URL . 'modules/reservations/view.php?id=' . $reservationId);
        }
        
    } catch (Exception $e) {
        setMessage('Error approving reservation: ' . $e->getMessage(), 'error');
    }
}

// NOW INCLUDE HEADER AFTER FORM PROCESSING
require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/reservations/" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-check-circle"></i> Approve Trainer Time Request</h1>
                <p>Review and approve the member's request</p>
            </div>

            <?php displayMessage(); ?>

            <?php if ($reservation): ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Request Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p>
                                        <strong>Request ID:</strong> <code><?php echo htmlspecialchars($reservation['reservation_id']); ?></code>
                                    </p>
                                    <p>
                                        <strong>Status:</strong> 
                                        <span class="badge bg-warning">
                                            <i class="fas fa-hourglass-half"></i> <?php echo htmlspecialchars($reservation['status']); ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p>
                                        <strong>Requested Date:</strong> 
                                        <?php echo formatDate($reservation['reservation_date']); ?>
                                    </p>
                                    <p>
                                        <strong>Time:</strong> 
                                        <?php echo substr($reservation['start_time'], 0, 5); ?> - 
                                        <?php echo substr($reservation['end_time'], 0, 5); ?>
                                    </p>
                                </div>
                            </div>

                            <?php 
                            $memberStmt = $pdo->prepare("SELECT member_name, contact_number, email FROM members WHERE member_id = ?");
                            $memberStmt->execute([$reservation['member_id']]);
                            $member = $memberStmt->fetch();
                            if ($member):
                            ?>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p>
                                        <strong>Member Name:</strong> 
                                        <?php echo htmlspecialchars($member['member_name']); ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p>
                                        <strong>Contact:</strong> 
                                        <?php echo htmlspecialchars($member['contact_number'] ?? 'N/A'); ?>
                                    </p>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($reservation['purpose'])): ?>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <p>
                                        <strong>Purpose:</strong> 
                                        <span class="badge bg-info">
                                            <?php echo htmlspecialchars($reservation['purpose']); ?>
                                        </span>
                                    </p>
                                </div>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($reservation['notes'])): ?>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <p>
                                        <strong>Member Notes:</strong>
                                    </p>
                                    <div class="alert alert-light border">
                                        <?php echo htmlspecialchars($reservation['notes']); ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Approval Form</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="trainer_notes" class="form-label">
                                        <i class="fas fa-sticky-note"></i> Trainer Notes (Optional)
                                    </label>
                                    <textarea class="form-control" id="trainer_notes" name="trainer_notes" rows="4" 
                                              placeholder="Add any notes or special instructions for the member...">
                                              <?php echo htmlspecialchars($trainerNotes); ?></textarea>
                                    <small class="text-muted d-block mt-1">
                                        These notes will be added to the reservation record.
                                    </small>
                                </div>

                                <hr>

                                <div class="card bg-light mb-3">
                                    <div class="card-header bg-primary text-white">
                                        <h6 class="mb-0"><i class="fas fa-calendar-plus"></i> Create Training Session</h6>
                                    </div>
                                    <div class="card-body" style="background-color: #f8f9fa; padding: 15px;">
                                    <div class="mb-3">
                                        <label for="gym_id" class="form-label">
                                            <i class="fas fa-map-marker-alt"></i> Gym Location
                                        </label>
                                        <select class="form-select" id="gym_id" name="gym_id">
                                            <option value="">-- Select Gym --</option>
                                            <?php foreach ($gyms as $gym): ?>
                                                <option value="<?php echo htmlspecialchars($gym['gym_id']); ?>">
                                                    <?php echo htmlspecialchars($gym['gym_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="duration" class="form-label">
                                            <i class="fas fa-clock"></i> Session Duration (minutes)
                                        </label>
                                        <input type="number" class="form-control" id="duration" name="duration" 
                                               min="15" max="480" step="15" placeholder="e.g., 60"
                                               value="">
                                        <small class="text-muted d-block mt-1">
                                            Duration between 15 and 480 minutes (15 min increments)
                                        </small>
                                    </div>

                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle"></i> 
                                        <strong>Required:</strong> A training session must be created with every approval. This ensures every approved reservation has an associated session, 
                                        and the member will be automatically enrolled and marked as present.
                                    </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="<?php echo APP_URL; ?>modules/reservations/" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check"></i> Approve Request
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card bg-light mb-3">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle"></i> Approval Process
                            </h5>
                        </div>
                        <div class="card-body small">
                            <ol class="mb-0">
                                <li class="mb-2">Review the request details</li>
                                <li class="mb-2">Add any trainer notes if needed</li>
                                <li class="mb-2"><strong>Select gym location and duration</strong> for the training session</li>
                                <li class="mb-2">Click "Approve Request" to create the session</li>
                                <li>Member will be automatically enrolled and notified</li>
                            </ol>
                        </div>
                    </div>

                    <div class="card bg-success text-white mb-3">
                        <div class="card-body">
                            <p class="mb-0">
                                <i class="fas fa-bell"></i> 
                                <strong>Notifications:</strong> Member will receive an in-app notification when you approve this request.
                            </p>
                        </div>
                    </div>

                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <p class="mb-0">
                                <i class="fas fa-lightbulb"></i> 
                                <strong>Tip:</strong> Use "Create Training Session" for immediate scheduling and automatic member enrollment.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> 
                    Reservation not found or not available for approval.
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>
