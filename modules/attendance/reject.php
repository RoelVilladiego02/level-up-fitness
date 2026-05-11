<?php
/**
 * Session Requests - Reject Request
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

// Only trainers and admins can reject
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
            setMessage('Only pending requests can be rejected', 'error');
            redirect(APP_URL . 'modules/attendance/');
        }

        // Access control - trainers can only reject their own requests
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

    if (empty($formData['trainer_notes'])) {
        $errors['trainer_notes'] = 'Please provide a reason for rejection';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE session_requests SET status = 'Rejected', trainer_notes = ?, updated_at = NOW() WHERE request_id = ?");
            $stmt->execute([$formData['trainer_notes'], $requestId]);

            logAction($_SESSION['user_id'], 'REJECT_SESSION_REQUEST', 'Attendance', 'Rejected session request for ' . $request['member_name']);

            setMessage('✓ Session request rejected. Member will be notified.', 'success');
            redirect(APP_URL . 'modules/attendance/');
        } catch (Exception $e) {
            setMessage('Error rejecting request: ' . $e->getMessage(), 'error');
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
                <h1><i class="fas fa-times-circle"></i> Reject Session Request</h1>
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
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">Rejection Form</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="trainer_notes" class="form-label">Reason for Rejection *</label>
                                    <textarea class="form-control <?php echo isset($errors['trainer_notes']) ? 'is-invalid' : ''; ?>" 
                                              id="trainer_notes" name="trainer_notes" rows="4" required
                                              placeholder="Explain why you're rejecting this request...">
<?php echo htmlspecialchars($formData['trainer_notes'] ?? ''); ?></textarea>
                                    <small class="text-muted">The member will see this reason</small>
                                    <?php if (isset($errors['trainer_notes'])): ?>
                                        <div class="invalid-feedback d-block"><?php echo $errors['trainer_notes']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="<?php echo APP_URL; ?>modules/attendance/" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-times"></i> Reject Request
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">Important</h5>
                        </div>
                        <div class="card-body small">
                            <p>
                                <i class="fas fa-exclamation-triangle"></i> 
                                Rejecting this request will notify the member with your reason. Provide clear feedback so they can make future requests accordingly.
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
