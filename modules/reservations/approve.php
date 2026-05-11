<?php
/**
 * Reservations - Trainer Approval
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

// Only trainers and admins can approve
if ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'trainer') {
    die('Access denied: Only trainers and admins can approve reservations.');
}

$errors = [];
$reservation = null;
$trainerNotes = '';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($reservationId)) {
    try {
        $trainerNotes = sanitize($_POST['trainer_notes'] ?? '');
        
        // Update reservation status to Confirmed
        $updateStmt = $pdo->prepare("
            UPDATE reservations 
            SET status = 'Confirmed', 
                notes = CONCAT(IFNULL(notes, ''), '\n[Trainer Approval] ', ?)
            WHERE reservation_id = ?
        ");
        $updateStmt->execute([$trainerNotes, $reservationId]);
        
        logAction($_SESSION['user_id'], 'APPROVE_RESERVATION', 'Reservations', 
                 'Approved reservation: ' . $reservationId);
        
        // Send notification to member
        try {
            $reservationRefresh = $pdo->prepare("SELECT * FROM reservations WHERE reservation_id = ?");
            $reservationRefresh->execute([$reservationId]);
            $reservationRefresh = $reservationRefresh->fetch();
            
            $memberStmt = $pdo->prepare("SELECT user_id FROM members WHERE member_id = ?");
            $memberStmt->execute([$reservationRefresh['member_id']]);
            $memberData = $memberStmt->fetch();
            
            $trainerStmt = $pdo->prepare("SELECT trainer_name FROM trainers WHERE trainer_id = ?");
            $trainerStmt->execute([$reservationRefresh['trainer_id']]);
            $trainerData = $trainerStmt->fetch();
            
            if ($memberData && $trainerData) {
                notifyMemberOfReservationApproval(
                    $memberData['user_id'],
                    $trainerData['trainer_name'],
                    $reservationId,
                    $reservationRefresh['reservation_date'],
                    $reservationRefresh['start_time'],
                    $reservationRefresh['end_time']
                );
            }
        } catch (Exception $e) {
            error_log('Failed to send member notification: ' . $e->getMessage());
        }
        
        setMessage('Reservation approved successfully! Member has been notified.', 'success');
        redirect(APP_URL . 'modules/reservations/view.php?id=' . $reservationId);
        
    } catch (Exception $e) {
        setMessage('Error approving reservation: ' . $e->getMessage(), 'error');
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
                                <li class="mb-2">Click "Approve Request"</li>
                                <li>Member will be notified automatically</li>
                            </ol>
                        </div>
                    </div>

                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <p class="mb-0">
                                <i class="fas fa-bell"></i> 
                                Member will receive an in-app notification when you approve this request.
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
