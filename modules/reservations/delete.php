<?php
/**
 * Reservations - Delete Reservation
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
// Members and admins can make reservations
if ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'member') {
    die('Access denied: Only members and admins can make reservations.');
}

$reservationId = sanitize($_GET['id'] ?? '');
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

if (empty($reservationId)) {
    setMessage('Invalid reservation ID', 'error');
    redirect(APP_URL . 'modules/reservations/');
}

try {
    // Get reservation info first
    $stmt = $pdo->prepare("SELECT * FROM reservations WHERE reservation_id = ?");
    $stmt->execute([$reservationId]);
    $reservation = $stmt->fetch();

    if (!$reservation) {
        setMessage('Reservation not found', 'error');
        redirect(APP_URL . 'modules/reservations/');
    }
    
    // Members can only delete their own reservations
    if (!$isAdmin && $reservation['member_id'] !== $currentMemberId) {
        die('Access denied: You can only delete your own reservations.');
    }

    // If confirmed
    if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
        // Delete reservation
        $deleteStmt = $pdo->prepare("DELETE FROM reservations WHERE reservation_id = ?");
        $deleteStmt->execute([$reservationId]);

        logAction($_SESSION['user_id'], 'DELETE_RESERVATION', 'Reservations', 'Deleted reservation: ' . $reservationId);

        setMessage('Reservation deleted successfully', 'success');
        redirect(APP_URL . 'modules/reservations/');
    }

    // If cancel
    if (isset($_GET['confirm']) && $_GET['confirm'] == 'no') {
        redirect(APP_URL . 'modules/reservations/view.php?id=' . $reservationId);
    }

} catch (Exception $e) {
    setMessage('Error: ' . $e->getMessage(), 'error');
    redirect(APP_URL . 'modules/reservations/');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Delete - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h5>
                    </div>
                    <div class="card-body">
                        <p class="lead">Are you sure you want to delete this reservation?</p>
                        
                        <div class="alert alert-danger">
                            <strong>Reservation ID:</strong> <?php echo htmlspecialchars($reservation['reservation_id']); ?><br>
                            <strong>Date:</strong> <?php echo formatDate($reservation['reservation_date']); ?><br>
                            <strong>Time:</strong> <?php echo substr($reservation['start_time'], 0, 5); ?> - <?php echo substr($reservation['end_time'], 0, 5); ?><br>
                            <strong>Status:</strong> <?php echo htmlspecialchars($reservation['status']); ?>
                        </div>

                        <p class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            This action cannot be undone.
                        </p>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?php echo APP_URL; ?>modules/reservations/view.php?id=<?php echo $reservationId; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <a href="?id=<?php echo urlencode($reservationId); ?>&confirm=yes" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Delete Reservation
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
