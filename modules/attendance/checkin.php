<?php
/**
 * Gym Attendance - Member Check-In
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

// Only members can check in
if ($_SESSION['user_type'] !== 'member') {
    die('Access denied: Only members can check in.');
}

$memberId = null;
$currentCheckIn = null;
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
    
    // Check if member is active
    if ($memberData['status'] !== 'Active') {
        die('Access denied: Your membership is inactive. Please contact the gym administrator.');
    }
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
    // Table might not have these columns, that's ok
}

// Handle check-in submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkin') {
    // Prevent double check-in
    if ($currentCheckIn) {
        $message = 'You are already checked in. Please check out first.';
    } else {
        try {
            // Use activity_log table for gym visits since it already tracks events
            $visitId = generateID('VISIT');
            
            $stmt = $pdo->prepare("
                INSERT INTO activity_log (user_id, action, module, details, created_at)
                VALUES (?, 'CHECK_IN', 'Attendance', ?, NOW())
            ");
            $stmt->execute([$_SESSION['user_id'], 'Member checked in at ' . date('H:i:s')]);
            
            $message = '✓ Successfully checked in at ' . date('h:i A');
            $success = true;
            
            // Refresh current check-in status
            $currentCheckIn = [
                'checkin_time' => date('Y-m-d H:i:s'),
                'member_id' => $memberId
            ];
            
            logAction($_SESSION['user_id'], 'MEMBER_CHECKIN', 'Attendance', 'Member checked in to gym');
        } catch (Exception $e) {
            $message = 'Error checking in: ' . $e->getMessage();
        }
    }
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
                <h1><i class="fas fa-sign-in-alt"></i> Check In</h1>
                <p>Check in to start your gym session</p>
            </div>

            <?php if (!empty($message)): ?>
                <div class="alert <?php echo $success ? 'alert-success' : 'alert-warning'; ?> alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-6 offset-md-3">
                    <!-- Current Status Card -->
                    <div class="card mb-4">
                        <div class="card-header <?php echo $currentCheckIn ? 'bg-success' : 'bg-secondary'; ?> text-white">
                            <h5 class="mb-0">
                                <i class="fas <?php echo $currentCheckIn ? 'fa-sign-in-alt' : 'fa-door-open'; ?>"></i>
                                <?php echo $currentCheckIn ? 'Currently Checked In' : 'Not Checked In'; ?>
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <?php if ($currentCheckIn): ?>
                                <p class="text-muted mb-2">Checked in at:</p>
                                <h3 class="mb-4">
                                    <strong><?php echo date('h:i A', strtotime($currentCheckIn['checkin_time'])); ?></strong>
                                </h3>
                                <p class="text-muted small mb-4">
                                    Duration: <?php 
                                    $checkInTime = new DateTime($currentCheckIn['checkin_time']);
                                    $now = new DateTime();
                                    $interval = $now->diff($checkInTime);
                                    echo $interval->format('%h hours %i minutes');
                                    ?>
                                </p>
                                <a href="checkout.php" class="btn btn-danger btn-lg w-100">
                                    <i class="fas fa-sign-out-alt"></i> Check Out
                                </a>
                            <?php else: ?>
                                <p class="text-muted mb-4">You are not currently checked in to the gym</p>
                                <form method="POST" action="">
                                    <input type="hidden" name="action" value="checkin">
                                    <button type="submit" class="btn btn-success btn-lg w-100">
                                        <i class="fas fa-sign-in-alt"></i> Check In Now
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Info Card -->
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">ℹ️ About Check-In</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-2"><strong>Why check in?</strong></p>
                            <ul class="small">
                                <li>Track your gym attendance</li>
                                <li>Monitor your workout duration</li>
                                <li>View attendance history</li>
                                <li>Get visit statistics</li>
                            </ul>
                            <hr>
                            <p class="small mb-0">
                                <i class="fas fa-lightbulb"></i>
                                <strong>Tip:</strong> Don't forget to check out when you leave!
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Visit History -->
                <div class="col-md-12 mt-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">📊 Recent Check-In History</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    try {
                                        // Get recent check-in/check-out records
                                        $historyStmt = $pdo->prepare("
                                            SELECT * FROM activity_log 
                                            WHERE user_id = ? AND (action = 'CHECK_IN' OR action = 'CHECK_OUT')
                                            ORDER BY created_at DESC LIMIT 20
                                        ");
                                        $historyStmt->execute([$_SESSION['user_id']]);
                                        $history = $historyStmt->fetchAll();
                                        
                                        if (empty($history)) {
                                            echo '<tr><td colspan="4" class="text-center text-muted py-4">No check-in history yet</td></tr>';
                                        } else {
                                            $checkins = [];
                                            foreach ($history as $record) {
                                                $date = date('Y-m-d', strtotime($record['created_at']));
                                                $time = date('H:i:s', strtotime($record['created_at']));
                                                
                                                if ($record['action'] === 'CHECK_IN') {
                                                    if (!isset($checkins[$date])) $checkins[$date] = [];
                                                    $checkins[$date]['in'] = $time;
                                                } else {
                                                    if (!isset($checkins[$date])) $checkins[$date] = [];
                                                    $checkins[$date]['out'] = $time;
                                                }
                                            }
                                            
                                            foreach ($checkins as $date => $times) {
                                                $checkInTime = isset($times['in']) ? $times['in'] : '-';
                                                $checkOutTime = isset($times['out']) ? $times['out'] : 'Still inside';
                                                $duration = '-';
                                                
                                                if (isset($times['in']) && isset($times['out'])) {
                                                    $in = DateTime::createFromFormat('H:i:s', $times['in']);
                                                    $out = DateTime::createFromFormat('H:i:s', $times['out']);
                                                    if ($in && $out) {
                                                        $interval = $in->diff($out);
                                                        $duration = $interval->format('%h:%02d (h:m)');
                                                    }
                                                }
                                                
                                                echo '<tr>';
                                                echo '<td><strong>' . htmlspecialchars($date) . '</strong></td>';
                                                echo '<td>' . htmlspecialchars($checkInTime) . '</td>';
                                                echo '<td>' . htmlspecialchars($checkOutTime) . '</td>';
                                                echo '<td>' . htmlspecialchars($duration) . '</td>';
                                                echo '</tr>';
                                            }
                                        }
                                    } catch (Exception $e) {
                                        echo '<tr><td colspan="4" class="text-center text-warning">Unable to load history</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</div>

<?php include dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
