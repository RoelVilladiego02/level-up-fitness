<?php
/**
 * Reservations - Trainer Rejection
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

// Only trainers and admins can reject
if ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'trainer') {
    die('Access denied: Only trainers and admins can reject reservations.');
}

$errors = [];
$reservation = null;
$rejectionReason = '';
$currentTrainerId = null;

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
        
        // Trainers can only reject reservations for themselves
        if ($_SESSION['user_type'] === 'trainer' && $reservation['trainer_id'] !== $currentTrainerId) {
            die('Access denied: You can only reject reservations assigned to you.');
        }
        
        // Only pending reservations can be rejected
        if ($reservation['status'] !== 'Pending') {
            setMessage('Only pending reservations can be rejected', 'error');
            redirect(APP_URL . 'modules/reservations/view.php?id=' . $reservationId);
        }
        
    } catch (Exception $e) {
        setMessage('Error loading reservation: ' . $e->getMessage(), 'error');
    }
}

// HANDLE FORM SUBMISSION BEFORE HEADER OUTPUT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($reservationId)) {
    try {
        $rejectionReason = sanitize($_POST['rejection_reason'] ?? '');
        
        // Validate rejection reason
        if (empty($rejectionReason)) {
            $errors['rejection_reason'] = 'Please provide a reason for rejection';
        }
        
        if (empty($errors)) {
            // Update reservation status to Rejected
            $updateStmt = $pdo->prepare("
                UPDATE reservations 
                SET status = 'Rejected', 
                    notes = CONCAT(IFNULL(notes, ''), '\n[Rejection Reason] ', ?)
                WHERE reservation_id = ?
            ");
            $updateStmt->execute([$rejectionReason, $reservationId]);
            
            logAction($_SESSION['user_id'], 'REJECT_RESERVATION', 'Reservations', 
                     'Rejected reservation: ' . $reservationId);
            
            // Send notification to member
            try {
                $reservationRefresh = $pdo->prepare("SELECT * FROM reservations WHERE reservation_id = ?");
                $reservationRefresh->execute([$reservationId]);
                $reservationRefresh = $reservationRefresh->fetch();
                
                $memberStmt = $pdo->prepare("SELECT user_id, member_name FROM members WHERE member_id = ?");
                $memberStmt->execute([$reservationRefresh['member_id']]);
                $memberData = $memberStmt->fetch();
                
                $trainerStmt = $pdo->prepare("SELECT trainer_name FROM trainers WHERE trainer_id = ?");
                $trainerStmt->execute([$reservationRefresh['trainer_id']]);
                $trainerData = $trainerStmt->fetch();
                
                if ($memberData && $trainerData) {
                    notifyMemberOfReservationRejection(
                        $memberData['user_id'],
                        $trainerData['trainer_name'],
                        $reservationId,
                        $reservationRefresh['reservation_date'],
                        $reservationRefresh['start_time'],
                        $rejectionReason
                    );
                }
            } catch (Exception $e) {
                error_log('Failed to send member notification: ' . $e->getMessage());
            }
            
            setMessage('✓ Trainer time request rejected. Member will be notified.', 'success');
            redirect(APP_URL . 'modules/reservations/');
        }
    } catch (Exception $e) {
        setMessage('Error rejecting reservation: ' . $e->getMessage(), 'error');
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
                <h1><i class="fas fa-times-circle"></i> Reject Trainer Time Request</h1>
                <p>Decline the member's request</p>
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
                                        <strong>Reservation Date:</strong><br>
                                        <?php echo formatDate($reservation['reservation_date']); ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p>
                                        <strong>Time Slot:</strong><br>
                                        <?php echo substr($reservation['start_time'], 0, 5); ?> - 
                                        <?php echo substr($reservation['end_time'], 0, 5); ?>
                                    </p>
                                </div>
                            </div>
                            <hr>
                            <p>
                                <strong>Purpose:</strong><br>
                                <span class="badge bg-info"><?php echo htmlspecialchars($reservation['purpose'] ?? 'Not specified'); ?></span>
                            </p>
                            <?php if (!empty($reservation['notes'])): ?>
                                <hr>
                                <p>
                                    <strong>Member Notes:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($reservation['notes'])); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">Rejection Reason</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="rejection_reason" class="form-label">
                                        <i class="fas fa-pen"></i> Reason for Rejection <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control <?php echo isset($errors['rejection_reason']) ? 'is-invalid' : ''; ?>" 
                                              id="rejection_reason" 
                                              name="rejection_reason" 
                                              rows="5" 
                                              placeholder="Explain why you cannot accommodate this request..." 
                                              required><?php echo htmlspecialchars($rejectionReason); ?></textarea>
                                    <?php if (isset($errors['rejection_reason'])): ?>
                                        <div class="invalid-feedback d-block">
                                            <?php echo htmlspecialchars($errors['rejection_reason']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <small class="form-text text-muted d-block mt-2">
                                        <i class="fas fa-info-circle"></i> The member will see this reason in their notification
                                    </small>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="<?php echo APP_URL; ?>modules/reservations/" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-check"></i> Reject Request
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
                                <i class="fas fa-info-circle"></i> Rejection Process
                            </h5>
                        </div>
                        <div class="card-body small">
                            <ol class="mb-0">
                                <li class="mb-2">Review the request details</li>
                                <li class="mb-2">Provide a clear reason for rejection</li>
                                <li class="mb-2">Click "Reject Request"</li>
                                <li class="mb-2">Member will be notified automatically</li>
                                <li>Member can request another time slot</li>
                            </ol>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Request Information</h5>
                        </div>
                        <div class="card-body small">
                            <p class="mb-2">
                                <strong>ID:</strong><br>
                                <code><?php echo htmlspecialchars($reservation['reservation_id']); ?></code>
                            </p>
                            <p class="mb-2">
                                <strong>Status:</strong><br>
                                <span class="badge bg-warning">Pending</span>
                            </p>
                            <p class="mb-0">
                                <strong>Requested:</strong><br>
                                <?php echo formatDate($reservation['created_at']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> Reservation not found or cannot be rejected.
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
