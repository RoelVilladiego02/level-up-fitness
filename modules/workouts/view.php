<?php
/**
 * Workout Plans - View Plan Details
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$planId = sanitize($_GET['id'] ?? '');
$plan = null;
$member = null;
$trainer = null;

if (!empty($planId)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM workout_plans WHERE workout_plan_id = ?");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch();
        
        if (!$plan) {
            setMessage('Plan not found', 'error');
            redirect(APP_URL . 'modules/workouts/');
        }

        // Get member info
        $memberStmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
        $memberStmt->execute([$plan['member_id']]);
        $member = $memberStmt->fetch();

        // Get trainer info if assigned
        if (!empty($plan['trainer_id'])) {
            $trainerStmt = $pdo->prepare("SELECT * FROM trainers WHERE trainer_id = ?");
            $trainerStmt->execute([$plan['trainer_id']]);
            $trainer = $trainerStmt->fetch();
        }

    } catch (Exception $e) {
        setMessage('Error loading plan: ' . $e->getMessage(), 'error');
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <div class="float-end">
                    <a href="<?php echo APP_URL; ?>modules/workouts/edit.php?id=<?php echo $planId; ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="<?php echo APP_URL; ?>modules/workouts/delete.php?id=<?php echo $planId; ?>" class="btn btn-danger btn-sm btn-delete">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </div>
                <a href="<?php echo APP_URL; ?>modules/workouts/" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-dumbbell"></i> Workout Plan</h1>
                <p>View plan details and schedule</p>
            </div>

            <?php displayMessage(); ?>

            <?php if ($plan): ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><?php echo htmlspecialchars($plan['plan_name']); ?></h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Plan ID:</strong> <code><?php echo htmlspecialchars($plan['workout_plan_id']); ?></code>
                            </p>
                            <hr>
                            <p>
                                <strong>Goal:</strong><br>
                                <h5><?php 
                                    $schedule = json_decode($plan['weekly_schedule'], true);
                                    echo htmlspecialchars($schedule['goal'] ?? 'N/A'); 
                                ?></h5>
                            </p>
                            <hr>
                            <p>
                                <strong>Duration:</strong> <?php 
                                    $schedule = json_decode($plan['weekly_schedule'], true);
                                    echo htmlspecialchars($schedule['duration_weeks'] ?? 'N/A'); 
                                ?> weeks
                            </p>
                            <hr>
                            <p>
                                <strong>Plan Details:</strong><br>
                                <?php echo nl2br(htmlspecialchars($plan['plan_details'])); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Member Info</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($member): ?>
                                <p>
                                    <strong>Name:</strong><br>
                                    <?php echo htmlspecialchars($member['member_name']); ?>
                                </p>
                                <p>
                                    <strong>ID:</strong><br>
                                    <code><?php echo htmlspecialchars($member['member_id']); ?></code>
                                </p>
                                <p>
                                    <strong>Email:</strong><br>
                                    <a href="mailto:<?php echo htmlspecialchars($member['email']); ?>">
                                        <?php echo htmlspecialchars($member['email']); ?>
                                    </a>
                                </p>
                                <div class="mt-2">
                                    <a href="<?php echo APP_URL; ?>modules/members/view.php?id=<?php echo $member['member_id']; ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-link"></i> View Profile
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($trainer): ?>
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Assigned Trainer</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Name:</strong><br>
                                <?php echo htmlspecialchars($trainer['trainer_name']); ?>
                            </p>
                            <p>
                                <strong>ID:</strong><br>
                                <code><?php echo htmlspecialchars($trainer['trainer_id']); ?></code>
                            </p>
                            <p>
                                <strong>Specialization:</strong><br>
                                <span class="badge bg-warning"><?php echo htmlspecialchars($trainer['specialization']); ?></span>
                            </p>
                            <div class="mt-2">
                                <a href="<?php echo APP_URL; ?>modules/trainers/view.php?id=<?php echo $trainer['trainer_id']; ?>" 
                                   class="btn btn-sm btn-info">
                                    <i class="fas fa-link"></i> View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card mb-3">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">Trainer Assignment</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">No trainer assigned</p>
                            <a href="<?php echo APP_URL; ?>modules/workouts/edit.php?id=<?php echo $planId; ?>" 
                               class="btn btn-sm btn-primary">
                                Assign Trainer
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">Timeline</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Created:</strong><br>
                                <?php echo formatDate($plan['created_at']); ?>
                            </p>
                            <hr>
                            <p>
                                <strong>Last Updated:</strong><br>
                                <?php echo formatDate($plan['updated_at']); ?>
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
