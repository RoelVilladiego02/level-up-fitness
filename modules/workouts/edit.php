<?php
/**
 * Workout Plans - Edit Plan
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$planId = sanitize($_GET['id'] ?? '');
$plan = null;
$errors = [];
$formData = [];
$members = [];
$trainers = [];

// Load plan
if (!empty($planId)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM workout_plans WHERE workout_plan_id = ?");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch();
        
        if (!$plan) {
            setMessage('Plan not found', 'error');
            redirect(APP_URL . 'modules/workouts/');
        }
        
        // Parse JSON fields for display
        $schedule = json_decode($plan['weekly_schedule'], true);
        $formData = $plan;
        $formData['goal'] = $schedule['goal'] ?? '';
        $formData['duration_weeks'] = $schedule['duration_weeks'] ?? 0;
    } catch (Exception $e) {
        setMessage('Error loading plan: ' . $e->getMessage(), 'error');
    }
}

// Get members and trainers
try {
    $memberStmt = $pdo->prepare("SELECT member_id, member_name FROM members WHERE status = 'Active' ORDER BY member_name");
    $memberStmt->execute();
    $members = $memberStmt->fetchAll();

    $trainerStmt = $pdo->prepare("SELECT trainer_id, trainer_name FROM trainers ORDER BY trainer_name");
    $trainerStmt->execute();
    $trainers = $trainerStmt->fetchAll();
} catch (Exception $e) {
    setMessage('Error loading data: ' . $e->getMessage(), 'error');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($planId)) {
    $formData['plan_name'] = sanitize($_POST['plan_name'] ?? '');
    $formData['goal'] = sanitize($_POST['goal'] ?? '');
    $formData['duration_weeks'] = intval($_POST['duration_weeks'] ?? 0);
    $formData['trainer_id'] = sanitize($_POST['trainer_id'] ?? '');
    $formData['details'] = sanitize($_POST['details'] ?? '');

    // Validate
    if (empty($formData['plan_name'])) {
        $errors['plan_name'] = 'Plan name is required';
    }
    if (empty($formData['goal'])) {
        $errors['goal'] = 'Goal is required';
    }
    if ($formData['duration_weeks'] <= 0) {
        $errors['duration_weeks'] = 'Duration must be greater than 0';
    }
    if (empty($formData['details'])) {
        $errors['details'] = 'Plan details are required';
    }

    if (empty($errors)) {
        try {
            // Build weekly schedule and plan details
            $weeklySchedule = json_encode([
                'goal' => $formData['goal'],
                'duration_weeks' => $formData['duration_weeks']
            ]);

            $stmt = $pdo->prepare("
                UPDATE workout_plans SET 
                    plan_name = ?, weekly_schedule = ?, 
                    trainer_id = ?, plan_details = ?
                WHERE workout_plan_id = ?
            ");
            $stmt->execute([
                $formData['plan_name'], $weeklySchedule,
                !empty($formData['trainer_id']) ? $formData['trainer_id'] : NULL,
                $formData['details'], $planId
            ]);

            logAction($_SESSION['user_id'], 'EDIT_PLAN', 'Workouts', 'Updated plan: ' . $formData['plan_name']);

            setMessage('Plan updated successfully', 'success');
            redirect(APP_URL . 'modules/workouts/view.php?id=' . $planId);
        } catch (Exception $e) {
            setMessage('Error updating plan: ' . $e->getMessage(), 'error');
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/workouts/" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-edit"></i> Edit Workout Plan</h1>
                <p>Update plan details</p>
            </div>

            <?php displayMessage(); ?>

            <?php if ($plan): ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Plan Details - <?php echo htmlspecialchars($plan['workout_plan_id']); ?></h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" class="needs-validation" novalidate>
                                <div class="mb-3">
                                    <label for="member_id" class="form-label">Member (Read-only)</label>
                                    <input type="text" class="form-control" id="member_id" disabled
                                           value="<?php echo htmlspecialchars($plan['member_id']); ?>">
                                </div>

                                <div class="mb-3">
                                    <label for="plan_name" class="form-label">Plan Name *</label>
                                    <input type="text" class="form-control <?php echo isset($errors['plan_name']) ? 'is-invalid' : ''; ?>" 
                                           id="plan_name" name="plan_name" 
                                           value="<?php echo htmlspecialchars($formData['plan_name'] ?? ''); ?>" required>
                                    <?php if (isset($errors['plan_name'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['plan_name']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="goal" class="form-label">Goal *</label>
                                        <input type="text" class="form-control <?php echo isset($errors['goal']) ? 'is-invalid' : ''; ?>" 
                                               id="goal" name="goal" 
                                               value="<?php echo htmlspecialchars($formData['goal'] ?? ''); ?>" required>
                                        <?php if (isset($errors['goal'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['goal']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="duration_weeks" class="form-label">Duration (Weeks) *</label>
                                        <input type="number" class="form-control <?php echo isset($errors['duration_weeks']) ? 'is-invalid' : ''; ?>" 
                                               id="duration_weeks" name="duration_weeks" min="1" max="52"
                                               value="<?php echo htmlspecialchars($formData['duration_weeks'] ?? ''); ?>" required>
                                        <?php if (isset($errors['duration_weeks'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['duration_weeks']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="trainer_id" class="form-label">Trainer (Optional)</label>
                                    <select class="form-select" id="trainer_id" name="trainer_id">
                                        <option value="">-- Unassigned --</option>
                                        <?php foreach ($trainers as $trainer): ?>
                                            <option value="<?php echo htmlspecialchars($trainer['trainer_id']); ?>" 
                                                    <?php echo ($formData['trainer_id'] ?? '') === $trainer['trainer_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($trainer['trainer_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="details" class="form-label">Plan Details *</label>
                                    <textarea class="form-control <?php echo isset($errors['details']) ? 'is-invalid' : ''; ?>" 
                                              id="details" name="details" rows="5" required><?php echo htmlspecialchars($formData['details'] ?? ''); ?></textarea>
                                    <?php if (isset($errors['details'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['details']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                    <a href="<?php echo APP_URL; ?>modules/workouts/view.php?id=<?php echo $planId; ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Update Plan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Plan Info</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Plan ID:</strong><br>
                                <code><?php echo htmlspecialchars($plan['workout_plan_id']); ?></code>
                            </p>
                            <hr>
                            <p>
                                <strong>Created:</strong><br>
                                <?php echo formatDate($plan['created_at']); ?>
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
