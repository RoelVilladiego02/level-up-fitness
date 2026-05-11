<?php
/**
 * Session Requests - Request a Training Session
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

// Only members can request sessions
if ($_SESSION['user_type'] !== 'member') {
    setMessage('Access denied: Only members can request sessions.', 'error');
    redirect(APP_URL . 'dashboard/');
}

$errors = [];
$formData = [];
$member = null;
$trainer = null;

// Get member info and assigned trainer
try {
    $memberStmt = $pdo->prepare("SELECT m.*, t.trainer_name FROM members m LEFT JOIN trainers t ON m.trainer_id = t.trainer_id WHERE m.user_id = ? AND m.status = 'Active'");
    $memberStmt->execute([$_SESSION['user_id']]);
    $member = $memberStmt->fetch();
    
    if (!$member) {
        setMessage('Your member account is not active or not found.', 'error');
        redirect(APP_URL . 'dashboard/');
    }
    
    if (!$member['trainer_id']) {
        setMessage('You do not have an assigned trainer. Please contact admin to assign a trainer first.', 'warning');
        redirect(APP_URL . 'dashboard/');
    }
} catch (Exception $e) {
    setMessage('Error loading member information: ' . $e->getMessage(), 'error');
    redirect(APP_URL . 'dashboard/');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['requested_date'] = sanitize($_POST['requested_date'] ?? '');
    $formData['requested_time'] = sanitize($_POST['requested_time'] ?? '');
    $formData['duration'] = intval($_POST['duration'] ?? 30);
    $formData['purpose'] = sanitize($_POST['purpose'] ?? '');

    // Validate
    if (empty($formData['requested_date'])) {
        $errors['requested_date'] = 'Date is required';
    } elseif (strtotime($formData['requested_date']) < strtotime(date('Y-m-d'))) {
        $errors['requested_date'] = 'Cannot request sessions for past dates';
    }
    
    if (empty($formData['requested_time'])) {
        $errors['requested_time'] = 'Time is required';
    }
    
    if ($formData['duration'] < 15 || $formData['duration'] > 480) {
        $errors['duration'] = 'Duration must be between 15 and 480 minutes';
    }
    
    if (empty($formData['purpose'])) {
        $errors['purpose'] = 'Purpose is required';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO session_requests 
                (member_id, trainer_id, requested_date, requested_time, duration, purpose, status)
                VALUES (?, ?, ?, ?, ?, ?, 'Pending')
            ");
            $stmt->execute([
                $member['member_id'],
                $member['trainer_id'],
                $formData['requested_date'],
                $formData['requested_time'],
                $formData['duration'],
                $formData['purpose']
            ]);

            logAction($_SESSION['user_id'], 'REQUEST_SESSION', 'Attendance', 'Requested training session from ' . $member['trainer_name']);

            setMessage('✓ Session request submitted successfully! Your trainer will review it soon.', 'success');
            redirect(APP_URL . 'modules/attendance/my-requests.php');
        } catch (Exception $e) {
            setMessage('Error submitting request: ' . $e->getMessage(), 'error');
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/attendance/my-requests.php" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back to My Requests
                </a>
                <h1><i class="fas fa-calendar-plus"></i> Request Training Session</h1>
                <p>Request a one-on-one training session from your trainer</p>
            </div>

            <?php displayMessage(); ?>

            <?php if ($member && $member['trainer_name']): ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Session Details</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" class="needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="requested_date" class="form-label">Date *</label>
                                        <input type="date" class="form-control <?php echo isset($errors['requested_date']) ? 'is-invalid' : ''; ?>" 
                                               id="requested_date" name="requested_date" 
                                               value="<?php echo htmlspecialchars($formData['requested_date'] ?? ''); ?>" required
                                               min="<?php echo date('Y-m-d'); ?>">
                                        <?php if (isset($errors['requested_date'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['requested_date']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="requested_time" class="form-label">Time *</label>
                                        <input type="time" class="form-control <?php echo isset($errors['requested_time']) ? 'is-invalid' : ''; ?>" 
                                               id="requested_time" name="requested_time" 
                                               value="<?php echo htmlspecialchars($formData['requested_time'] ?? ''); ?>" required>
                                        <?php if (isset($errors['requested_time'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['requested_time']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="duration" class="form-label">Duration (minutes) *</label>
                                        <input type="number" class="form-control <?php echo isset($errors['duration']) ? 'is-invalid' : ''; ?>" 
                                               id="duration" name="duration" 
                                               value="<?php echo htmlspecialchars($formData['duration'] ?? 30); ?>" 
                                               min="15" max="480" step="15" required>
                                        <small class="text-muted">Between 15 and 480 minutes</small>
                                        <?php if (isset($errors['duration'])): ?>
                                            <div class="invalid-feedback d-block"><?php echo $errors['duration']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="purpose" class="form-label">Purpose *</label>
                                        <select class="form-select <?php echo isset($errors['purpose']) ? 'is-invalid' : ''; ?>" 
                                                id="purpose" name="purpose" required>
                                            <option value="">-- Select Purpose --</option>
                                            <option value="Strength Training" <?php echo ($formData['purpose'] ?? '') === 'Strength Training' ? 'selected' : ''; ?>>Strength Training</option>
                                            <option value="Cardio" <?php echo ($formData['purpose'] ?? '') === 'Cardio' ? 'selected' : ''; ?>>Cardio</option>
                                            <option value="Flexibility" <?php echo ($formData['purpose'] ?? '') === 'Flexibility' ? 'selected' : ''; ?>>Flexibility</option>
                                            <option value="General Fitness" <?php echo ($formData['purpose'] ?? '') === 'General Fitness' ? 'selected' : ''; ?>>General Fitness</option>
                                            <option value="Form Correction" <?php echo ($formData['purpose'] ?? '') === 'Form Correction' ? 'selected' : ''; ?>>Form Correction</option>
                                            <option value="Nutrition Consultation" <?php echo ($formData['purpose'] ?? '') === 'Nutrition Consultation' ? 'selected' : ''; ?>>Nutrition Consultation</option>
                                            <option value="Other" <?php echo ($formData['purpose'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                        <?php if (isset($errors['purpose'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['purpose']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="<?php echo APP_URL; ?>modules/attendance/my-requests.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane"></i> Submit Request
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Trainer Info</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Trainer:</strong><br>
                                <?php echo htmlspecialchars($member['trainer_name']); ?>
                            </p>
                            <hr>
                            <p class="small text-muted">
                                <i class="fas fa-info-circle"></i> Your request will be sent to <strong><?php echo htmlspecialchars($member['trainer_name']); ?></strong> for approval.
                            </p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">Tips</h5>
                        </div>
                        <div class="card-body small">
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> Choose a future date</li>
                                <li><i class="fas fa-check text-success"></i> Be specific about your goals</li>
                                <li><i class="fas fa-check text-success"></i> Allow time for trainer review</li>
                                <li><i class="fas fa-check text-success"></i> Check My Requests for status updates</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
