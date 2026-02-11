<?php
/**
 * My Trainer - View Assigned Trainer
 * Level Up Fitness - Gym Management System
 * Members can view and contact their assigned trainer
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole('member');

$userInfo = getUserInfo();
$trainer = null;
$member = null;
$workoutPlans = [];
$sessions = [];

try {
    // Get member info
    $memberStmt = $pdo->prepare("SELECT * FROM members WHERE user_id = ?");
    $memberStmt->execute([$userInfo['user_id']]);
    $member = $memberStmt->fetch();
    
    if (!$member) {
        setMessage('Member profile not found', 'error');
    } else if (empty($member['trainer_id'])) {
        // No trainer assigned
        $trainer = null;
    } else {
        // Get trainer details
        $trainerStmt = $pdo->prepare("
            SELECT t.*, u.email FROM trainers t
            LEFT JOIN users u ON t.user_id = u.user_id
            WHERE t.trainer_id = ? AND t.status = 'Active'
        ");
        $trainerStmt->execute([$member['trainer_id']]);
        $trainer = $trainerStmt->fetch();
        
        if (!$trainer) {
            setMessage('Trainer information not available', 'error');
        }
        
        // Get workout plans created by this trainer for this member
        $plansStmt = $pdo->prepare("
            SELECT * FROM workout_plans
            WHERE member_id = ? AND trainer_id = ?
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $plansStmt->execute([$member['member_id'], $member['trainer_id']]);
        $workoutPlans = $plansStmt->fetchAll();
        
        // Get sessions with this trainer
        $sessionsStmt = $pdo->prepare("
            SELECT * FROM sessions
            WHERE member_id = ? AND trainer_id = ?
            AND session_date >= CURDATE()
            ORDER BY session_date ASC, session_time ASC
            LIMIT 5
        ");
        $sessionsStmt->execute([$member['member_id'], $member['trainer_id']]);
        $sessions = $sessionsStmt->fetchAll();
    }
} catch (Exception $e) {
    error_log('Error loading trainer: ' . $e->getMessage());
    setMessage('Error loading trainer information', 'error');
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>dashboard/" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
                <h1><i class="fas fa-user-tie"></i> My Trainer</h1>
                <p>View your assigned trainer information and sessions</p>
            </div>

            <?php displayMessage(); ?>

            <?php if ($trainer): ?>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Trainer Profile</h5>
                            </div>
                            <div class="card-body text-center">
                                <div class="mb-3">
                                    <div class="rounded-circle bg-light d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                        <i class="fas fa-user-tie fa-3x text-primary"></i>
                                    </div>
                                </div>
                                <h4><?php echo htmlspecialchars($trainer['trainer_name']); ?></h4>
                                <p class="text-muted"><?php echo htmlspecialchars($trainer['specialization']); ?></p>
                            </div>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">Contact Information</h5>
                            </div>
                            <div class="card-body small">
                                <p>
                                    <strong>Email:</strong><br>
                                    <a href="mailto:<?php echo htmlspecialchars($trainer['email']); ?>">
                                        <?php echo htmlspecialchars($trainer['email']); ?>
                                    </a>
                                </p>
                                <p>
                                    <strong>Phone:</strong><br>
                                    <a href="tel:<?php echo htmlspecialchars($trainer['contact_number']); ?>">
                                        <?php echo htmlspecialchars($trainer['contact_number']); ?>
                                    </a>
                                </p>
                                <div class="d-grid gap-2 mt-3">
                                    <a href="mailto:<?php echo htmlspecialchars($trainer['email']); ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-envelope"></i> Send Email
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Experience</h5>
                            </div>
                            <div class="card-body small">
                                <p class="mb-0">
                                    <strong><?php echo htmlspecialchars($trainer['years_of_experience']); ?> years</strong> of experience
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="fas fa-dumbbell"></i> My Workout Plans
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($workoutPlans)): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> No workout plans created yet. 
                                        Contact your trainer to get a personalized plan!
                                    </div>
                                <?php else: ?>
                                    <div class="list-group">
                                        <?php foreach ($workoutPlans as $plan): ?>
                                            <a href="<?php echo APP_URL; ?>modules/workouts/view.php?id=<?php echo urlencode($plan['plan_id']); ?>" 
                                               class="list-group-item list-group-item-action">
                                                <div class="d-flex w-100 justify-content-between">
                                                    <h6 class="mb-1"><?php echo htmlspecialchars($plan['plan_name']); ?></h6>
                                                    <small class="text-muted">
                                                        <?php echo formatDate($plan['created_at']); ?>
                                                    </small>
                                                </div>
                                                <p class="mb-1 small text-muted">
                                                    <strong>Goal:</strong> <?php echo htmlspecialchars($plan['goal']); ?>
                                                </p>
                                                <small>
                                                    <span class="badge bg-primary"><?php echo $plan['duration_weeks']; ?> weeks</span>
                                                </small>
                                            </a>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">
                                    <i class="fas fa-calendar-alt"></i> Upcoming Sessions
                                </h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($sessions)): ?>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i> No upcoming sessions scheduled.
                                        Check back later for scheduled training sessions!
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Date</th>
                                                    <th>Time</th>
                                                    <th>Type</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($sessions as $session): ?>
                                                    <tr>
                                                        <td><?php echo formatDate($session['session_date']); ?></td>
                                                        <td><?php echo htmlspecialchars($session['session_time']); ?></td>
                                                        <td><?php echo htmlspecialchars($session['session_type'] ?? 'Training'); ?></td>
                                                        <td>
                                                            <span class="badge bg-success">
                                                                <?php echo htmlspecialchars($session['session_status'] ?? 'Scheduled'); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <div class="col-md-8 mx-auto">
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-user-slash fa-4x text-muted mb-3"></i>
                                <h4>No Trainer Assigned</h4>
                                <p class="text-muted">
                                    You don't have a trainer assigned yet. Contact the gym administration to get a personal trainer assigned to your account.
                                </p>
                                <p class="text-muted small">
                                    Once assigned, you'll be able to view your trainer's profile, contact information, and all your training sessions and workout plans here.
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
