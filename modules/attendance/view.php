<?php
/**
 * Session Requests - View Details
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$requestId = sanitize($_GET['id'] ?? '');
$request = null;

// Load request
if (!empty($requestId)) {
    try {
        $stmt = $pdo->prepare("SELECT sr.*, m.member_name, m.email, t.trainer_name FROM session_requests sr
                              JOIN members m ON sr.member_id = m.member_id
                              JOIN trainers t ON sr.trainer_id = t.trainer_id
                              WHERE sr.request_id = ?");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();
        
        if (!$request) {
            setMessage('Request not found', 'error');
            if ($_SESSION['user_type'] === 'member') {
                redirect(APP_URL . 'modules/attendance/my-requests.php');
            } else {
                redirect(APP_URL . 'modules/attendance/');
            }
        }

        // Access control
        if ($_SESSION['user_type'] === 'member') {
            $memberStmt = $pdo->prepare("SELECT member_id FROM members WHERE user_id = ?");
            $memberStmt->execute([$_SESSION['user_id']]);
            $memberData = $memberStmt->fetch();
            if (!$memberData || $memberData['member_id'] !== $request['member_id']) {
                setMessage('Access denied: You can only view your own requests', 'error');
                redirect(APP_URL . 'modules/attendance/my-requests.php');
            }
        } elseif ($_SESSION['user_type'] === 'trainer') {
            $trainerStmt = $pdo->prepare("SELECT trainer_id FROM trainers WHERE user_id = ?");
            $trainerStmt->execute([$_SESSION['user_id']]);
            $trainerData = $trainerStmt->fetch();
            if (!$trainerData || $trainerData['trainer_id'] !== $request['trainer_id']) {
                setMessage('Access denied: You can only view requests for your members', 'error');
                redirect(APP_URL . 'modules/attendance/');
            }
        }
    } catch (Exception $e) {
        setMessage('Error loading request: ' . $e->getMessage(), 'error');
    }
}

// Handle cancel for members
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    if ($_SESSION['user_type'] !== 'member') {
        setMessage('Access denied', 'error');
    } elseif ($request['status'] !== 'Pending') {
        setMessage('Can only cancel pending requests', 'error');
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE session_requests SET status = 'Cancelled', updated_at = NOW() WHERE request_id = ?");
            $stmt->execute([$requestId]);
            
            logAction($_SESSION['user_id'], 'CANCEL_SESSION_REQUEST', 'Attendance', 'Cancelled session request');
            
            setMessage('Request cancelled successfully', 'success');
            redirect(APP_URL . 'modules/attendance/my-requests.php');
        } catch (Exception $e) {
            setMessage('Error cancelling request: ' . $e->getMessage(), 'error');
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/attendance/<?php echo $_SESSION['user_type'] === 'member' ? 'my-requests.php' : 'index.php'; ?>" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-calendar-check"></i> Session Request Details</h1>
            </div>

            <?php displayMessage(); ?>

            <?php if ($request): ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Request Information</h5>
                            <?php
                            $statusColor = [
                                'Pending' => 'warning',
                                'Approved' => 'success',
                                'Rejected' => 'danger',
                                'Cancelled' => 'secondary'
                            ];
                            $color = $statusColor[$request['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?php echo $color; ?> fs-6">
                                <?php echo $request['status']; ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Date</strong><br>
                                    <p class="text-muted"><?php echo formatDate($request['requested_date']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Time</strong><br>
                                    <p class="text-muted"><?php echo date('H:i', strtotime($request['requested_time'])); ?></p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Duration</strong><br>
                                    <p class="text-muted"><?php echo $request['duration']; ?> minutes</p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Purpose</strong><br>
                                    <p class="text-muted"><?php echo htmlspecialchars($request['purpose']); ?></p>
                                </div>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <strong>Requested</strong><br>
                                <small class="text-muted"><?php echo formatDate($request['created_at']); ?></small>
                            </div>
                        </div>
                    </div>

                    <?php if ($request['trainer_notes']): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Trainer Notes</h5>
                        </div>
                        <div class="card-body">
                            <p><?php echo nl2br(htmlspecialchars($request['trainer_notes'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Member</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">
                                <strong><?php echo htmlspecialchars($request['member_name']); ?></strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($request['email']); ?></small>
                            </p>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Trainer</h6>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">
                                <strong><?php echo htmlspecialchars($request['trainer_name']); ?></strong>
                            </p>
                        </div>
                    </div>

                    <?php if ($_SESSION['user_type'] === 'member' && $request['status'] === 'Pending'): ?>
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0">Actions</h6>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="action" value="cancel">
                                <button type="submit" class="btn btn-outline-danger w-100" onclick="return confirm('Cancel this request?');">
                                    <i class="fas fa-times"></i> Cancel Request
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php elseif ($_SESSION['user_type'] === 'trainer' && $request['status'] === 'Pending'): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Use the approve/reject buttons in the requests list to manage this request.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
