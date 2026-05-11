<?php
/**
 * Session Requests - Approve Request
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

// Only trainers and admins can approve
if ($_SESSION['user_type'] !== 'trainer' && $_SESSION['user_type'] !== 'admin') {
    setMessage('Access denied', 'error');
    redirect(APP_URL . 'dashboard/');
}

$requestId = sanitize($_GET['id'] ?? '');
$request = null;
$errors = [];
$formData = [];

// Load request
if (!empty($requestId)) {
    try {
        $stmt = $pdo->prepare("SELECT sr.*, m.member_name, t.trainer_name FROM session_requests sr
                              JOIN members m ON sr.member_id = m.member_id
                              JOIN trainers t ON sr.trainer_id = t.trainer_id
                              WHERE sr.request_id = ?");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch();
        
        if (!$request) {
            setMessage('Request not found', 'error');
            redirect(APP_URL . 'modules/attendance/');
        }

        if ($request['status'] !== 'Pending') {
            setMessage('Only pending requests can be approved', 'error');
            redirect(APP_URL . 'modules/attendance/');
        }

        // Access control - trainers can only approve their own requests
        if ($_SESSION['user_type'] === 'trainer') {
            $trainerStmt = $pdo->prepare("SELECT trainer_id FROM trainers WHERE user_id = ?");
            $trainerStmt->execute([$_SESSION['user_id']]);
            $trainerData = $trainerStmt->fetch();
            if (!$trainerData || $trainerData['trainer_id'] !== $request['trainer_id']) {
                setMessage('Access denied', 'error');
                redirect(APP_URL . 'modules/attendance/');
            }
        }
    } catch (Exception $e) {
        setMessage('Error loading request: ' . $e->getMessage(), 'error');
        redirect(APP_URL . 'modules/attendance/');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($requestId)) {
    $formData['trainer_notes'] = sanitize($_POST['trainer_notes'] ?? '');

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE session_requests SET status = 'Approved', trainer_notes = ?, updated_at = NOW() WHERE request_id = ?");
            $stmt->execute([$formData['trainer_notes'], $requestId]);

            logAction($_SESSION['user_id'], 'APPROVE_SESSION_REQUEST', 'Attendance', 'Approved session request for ' . $request['member_name']);

            setMessage('✓ Session request approved! Member will be notified.', 'success');
            redirect(APP_URL . 'modules/attendance/');
        } catch (Exception $e) {
            setMessage('Error approving request: ' . $e->getMessage(), 'error');
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/attendance/" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-check-circle"></i> Approve Session Request</h1>
            </div>

            <?php displayMessage(); ?>

            <?php if ($request): ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Request Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <strong>Member</strong><br>
                                    <p class="text-muted"><?php echo htmlspecialchars($request['member_name']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <strong>Purpose</strong><br>
                                    <p class="text-muted"><?php echo htmlspecialchars($request['purpose']); ?></p>
                                </div>
                            </div>
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
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Duration</strong><br>
                                    <p class="text-muted"><?php echo $request['duration']; ?> minutes</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Approval Form</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="trainer_notes" class="form-label">Notes (Optional)</label>
                                    <textarea class="form-control" id="trainer_notes" name="trainer_notes" rows="4" 
                                              placeholder="Add any notes for the member...">
<?php echo htmlspecialchars($formData['trainer_notes'] ?? ''); ?></textarea>
                                    <small class="text-muted">These notes will be visible to the member</small>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="<?php echo APP_URL; ?>modules/attendance/" class="btn btn-outline-secondary">
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
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Info</h5>
                        </div>
                        <div class="card-body small">
                            <p>
                                <i class="fas fa-info-circle"></i> 
                                Approving this request will notify the member and confirm the session.
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
