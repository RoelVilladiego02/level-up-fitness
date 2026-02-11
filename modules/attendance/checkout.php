<?php
/**
 * Gym Attendance - Member Check-Out
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

// Only members can check out
if ($_SESSION['user_type'] !== 'member') {
    die('Access denied: Only members can check out.');
}

$memberId = null;
$currentCheckIn = null;
$checkOutTime = null;
$duration = null;
$message = '';
$success = false;

// Get member ID
try {
    $memberStmt = $pdo->prepare("SELECT member_id, member_name, status FROM members WHERE user_id = ?");
    $memberStmt->execute([$_SESSION['user_id']]);
    $memberData = $memberStmt->fetch();
    
    if (!$memberData) {
        die('Error: Member profile not found.');
    }
    
    $memberId = $memberData['member_id'];
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

// Check for existing check-in
try {
    $checkStmt = $pdo->prepare("
        SELECT * FROM class_attendance 
        WHERE member_id = ? AND checkout_time IS NULL 
        ORDER BY checkin_time DESC LIMIT 1
    ");
    $checkStmt->execute([$memberId]);
    $currentCheckIn = $checkStmt->fetch();
} catch (Exception $e) {
    // Table might not have these columns
}

// Handle check-out submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    if (!$currentCheckIn) {
        $message = 'You are not currently checked in. Please check in first.';
    } else {
        try {
            $checkOutTime = date('Y-m-d H:i:s');
            
            // Record check-out in activity log
            $stmt = $pdo->prepare("
                INSERT INTO activity_log (user_id, action, module, details, created_at)
                VALUES (?, 'CHECK_OUT', 'Attendance', ?, NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], 'Member checked out at ' . date('H:i:s')]);
            
            // Calculate duration
            $checkInDateTime = new DateTime($currentCheckIn['checkin_time']);
            $checkOutDateTime = new DateTime($checkOutTime);
            $intervalObj = $checkInDateTime->diff($checkOutDateTime);
            $duration = $intervalObj->format('%h hours %i minutes');
            
            $message = '✓ Successfully checked out. Session duration: ' . $duration;
            $success = true;
            
            logAction($_SESSION['user_id'], 'MEMBER_CHECKOUT', 'Attendance', 'Member checked out. Duration: ' . $duration);
            
            // Clear current check-in
            $currentCheckIn = null;
        } catch (Exception $e) {
            $message = 'Error checking out: ' . $e->getMessage();
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="checkin.php" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back to Check In
                </a>
                <h1><i class="fas fa-sign-out-alt"></i> Check Out</h1>
                <p>End your gym session</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $success ? 'alert-success' : 'alert-warning'; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6 offset-md-3">
                    <!-- Check Out Card -->
                    <div class="card mb-4">
                        <div class="card-header <?php echo $currentCheckIn ? 'bg-danger' : 'bg-secondary'; ?> text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-sign-out-alt"></i>
                                Check Out
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <?php if ($currentCheckIn): ?>
                                <p class="text-muted mb-2">Checked in at:</p>
                                <h4 class="mb-3">
                                    <strong><?php echo date('h:i A', strtotime($currentCheckIn['checkin_time'])); ?></strong>
                                </h4>
                                
                                <p class="text-muted mb-4">Current session duration:</p>
                                <h3 class="mb-4 text-primary">
                                    <?php 
                                    $checkInTime = new DateTime($currentCheckIn['checkin_time']);
                                    $now = new DateTime();
                                    $interval = $now->diff($checkInTime);
                                    echo $interval->format('%h hours %i minutes');
                                    ?>
                                </h3>

                                <p class="text-muted small mb-4">You're about to check out. Are you sure?</p>

                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="checkout">
                                    <button type="submit" class="btn btn-danger btn-lg w-100">
                                        <i class="fas fa-sign-out-alt"></i> Complete Check Out
                                    </button>
                                </form>
                            <?php elseif ($success && $checkOutTime): ?>
                                <div class="success-checkmark">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                </div>
                                <h4 class="mb-3">Check Out Successful!</h4>
                                
                                <div class="card bg-light mb-3">
                                    <div class="card-body">
                                        <p class="mb-2">
                                            <strong>Check Out Time:</strong><br>
                                            <?php echo date('h:i A', strtotime($checkOutTime)); ?>
                                        </p>
                                        <p class="mb-0">
                                            <strong>Session Duration:</strong><br>
                                            <?php echo htmlspecialchars($duration); ?>
                                        </p>
                                    </div>
                                </div>

                                <a href="<?php echo APP_URL; ?>dashboard/" class="btn btn-primary btn-lg w-100">
                                    <i class="fas fa-home"></i> Return to Dashboard
                                </a>
                            <?php else: ?>
                                <p class="text-muted mb-4">You are not currently checked in to the gym</p>
                                <a href="checkin.php" class="btn btn-success btn-lg w-100">
                                    <i class="fas fa-sign-in-alt"></i> Go to Check In
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Tips Card -->
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">💡 Session Tips</h5>
                        </div>
                        <div class="card-body">
                            <ul class="small">
                                <li><strong>Warm up:</strong> 5-10 minutes before exercise</li>
                                <li><strong>Main workout:</strong> 30-45 minutes</li>
                                <li><strong>Cool down:</strong> 5-10 minutes after exercise</li>
                                <li><strong>Stay hydrated:</strong> Drink water throughout</li>
                            </ul>
                            <hr>
                            <p class="small mb-0 text-muted">
                                Thank you for using Level Up Fitness! See you next time!
                            </p>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<?php include dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
