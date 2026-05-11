<?php
/**
 * Reservations - View Reservation Details
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$reservationId = sanitize($_GET['id'] ?? '');
$reservation = null;
$member = null;
$trainer = null;
$isAdmin = $_SESSION['user_type'] === 'admin';
$isTrainer = $_SESSION['user_type'] === 'trainer';
$currentMemberId = null;
$currentTrainerId = null;

// Get current user's member ID if they are a member
if (!$isAdmin && $_SESSION['user_type'] === 'member') {
    try {
        $memberStmt = $pdo->prepare("SELECT member_id FROM members WHERE user_id = ? AND status = 'Active'");
        $memberStmt->execute([$_SESSION['user_id']]);
        $memberData = $memberStmt->fetch();
        $currentMemberId = $memberData['member_id'] ?? null;
    } catch (Exception $e) {
        setMessage('Error loading member data: ' . $e->getMessage(), 'error');
    }
}

// Get current user's trainer ID if they are a trainer
if ($isTrainer) {
    try {
        $trainerStmt = $pdo->prepare("SELECT trainer_id FROM trainers WHERE user_id = ?");
        $trainerStmt->execute([$_SESSION['user_id']]);
        $trainerData = $trainerStmt->fetch();
        $currentTrainerId = $trainerData['trainer_id'] ?? null;
    } catch (Exception $e) {
        setMessage('Error loading trainer data: ' . $e->getMessage(), 'error');
    }
}

if (!empty($reservationId)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM reservations WHERE reservation_id = ?");
        $stmt->execute([$reservationId]);
        $reservation = $stmt->fetch();
        
        if (!$reservation) {
            setMessage('Reservation not found', 'error');
            redirect(APP_URL . 'modules/reservations/');
        }
        
        // Members can only view their own reservations
        if (!$isAdmin && !$isTrainer && $reservation['member_id'] !== $currentMemberId) {
            die('Access denied: You can only view your own reservations.');
        }

        // Trainers can only view reservations assigned to them
        if ($isTrainer && $reservation['trainer_id'] !== $currentTrainerId) {
            die('Access denied: You can only view reservations assigned to you.');
        }

        // Get member info
        if (!empty($reservation['member_id'])) {
            $memberStmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
            $memberStmt->execute([$reservation['member_id']]);
            $member = $memberStmt->fetch();
        }

        // Get trainer info
        if (!empty($reservation['trainer_id'])) {
            $trainerStmt = $pdo->prepare("SELECT * FROM trainers WHERE trainer_id = ?");
            $trainerStmt->execute([$reservation['trainer_id']]);
            $trainer = $trainerStmt->fetch();
        }

    } catch (Exception $e) {
        setMessage('Error loading reservation: ' . $e->getMessage(), 'error');
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <div class="float-end">
                    <?php if ($isTrainer && $reservation['status'] === 'Pending'): ?>
                        <a href="<?php echo APP_URL; ?>modules/reservations/approve.php?id=<?php echo $reservationId; ?>" class="btn btn-success btn-sm">
                            <i class="fas fa-check"></i> Approve
                        </a>
                    <?php endif; ?>
                    <a href="<?php echo APP_URL; ?>modules/reservations/edit.php?id=<?php echo $reservationId; ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="<?php echo APP_URL; ?>modules/reservations/delete.php?id=<?php echo $reservationId; ?>" class="btn btn-danger btn-sm btn-delete">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </div>
                <a href="<?php echo APP_URL; ?>modules/reservations/" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-calendar-check"></i> Trainer Time Request</h1>
                <p>View reservation details and status</p>
            </div>

            <?php displayMessage(); ?>

            <?php if ($reservation): ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Reservation Information</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Purpose:</strong> <span class="badge bg-info"><?php echo htmlspecialchars($reservation['purpose'] ?? 'Not specified'); ?></span><br>
                            </p>
                            <hr>
                            <p>
                                <strong>Reservation Date:</strong> <?php echo formatDate($reservation['reservation_date']); ?>
                            </p>
                            <hr>
                            <p>
                                <strong>Time Slot:</strong><br>
                                From: <?php echo substr($reservation['start_time'], 0, 5); ?><br>
                                To: <?php echo substr($reservation['end_time'], 0, 5); ?>
                            </p>
                            <hr>
                            <p>
                                <strong>Status:</strong><br>
                                <span class="badge badge-<?php 
                                    echo $reservation['status'] === 'Confirmed' ? 'success' : 
                                         ($reservation['status'] === 'Pending' ? 'warning' : 'danger');
                                ?>" style="font-size: 14px;">
                                    <?php echo htmlspecialchars($reservation['status']); ?>
                                </span>
                            </p>
                            <?php if (!empty($reservation['notes'])): ?>
                                <hr>
                                <p>
                                    <strong>Notes:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($reservation['notes'])); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($member): ?>
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Member Details</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Name:</strong> <?php echo htmlspecialchars($member['member_name']); ?><br>
                                <strong>ID:</strong> <code><?php echo htmlspecialchars($member['member_id']); ?></code><br>
                                <strong>Email:</strong> <?php echo htmlspecialchars($member['email']); ?>
                            </p>
                            <hr>
                            <a href="<?php echo APP_URL; ?>modules/members/view.php?id=<?php echo $member['member_id']; ?>" 
                               class="btn btn-sm btn-info">
                                <i class="fas fa-link"></i> View Member Profile
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($trainer): ?>
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Trainer Details</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Trainer:</strong> <?php echo htmlspecialchars($trainer['trainer_name']); ?><br>
                                <strong>ID:</strong> <code><?php echo htmlspecialchars($trainer['trainer_id']); ?></code><br>
                                <strong>Specialization:</strong> <?php echo htmlspecialchars($trainer['specialization'] ?? 'N/A'); ?>
                            </p>
                            <hr>
                            <a href="<?php echo APP_URL; ?>modules/trainers/view.php?id=<?php echo $trainer['trainer_id']; ?>" 
                               class="btn btn-sm btn-success">
                                <i class="fas fa-link"></i> View Trainer Profile
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Reservation ID</h5>
                        </div>
                        <div class="card-body">
                            <code class="d-block text-center" style="font-size: 16px; word-break: break-all;">
                                <?php echo htmlspecialchars($reservation['reservation_id']); ?>
                            </code>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Duration</h5>
                        </div>
                        <div class="card-body text-center">
                            <h4><?php 
                                $startTime = strtotime($reservation['start_time']);
                                $endTime = strtotime($reservation['end_time']);
                                $durationMinutes = ($endTime - $startTime) / 60;
                                $hours = floor($durationMinutes / 60);
                                $minutes = $durationMinutes % 60;
                                echo ($hours > 0 ? $hours . 'h ' : '') . $minutes . 'm';
                            ?></h4>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">Status</h5>
                        </div>
                        <div class="card-body text-center">
                            <span class="badge badge-<?php 
                                echo $reservation['status'] === 'Confirmed' ? 'success' : 
                                     ($reservation['status'] === 'Pending' ? 'warning' : 'danger');
                            ?>" style="font-size: 18px;">
                                <?php echo htmlspecialchars($reservation['status']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">Details</h5>
                        </div>
                        <div class="card-body small">
                            <p>
                                <strong>Created:</strong><br>
                                <?php echo formatDate($reservation['created_at']); ?>
                            </p>
                            <p>
                                <strong>Updated:</strong><br>
                                <?php echo formatDate($reservation['updated_at']); ?>
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
