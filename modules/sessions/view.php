<?php
/**
 * Training Sessions Management - View Session Details
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$message = getMessage();
$session = null;
$attendees = [];
$sessionId = $_GET['id'] ?? null;

if (!$sessionId) {
    setMessage('Session ID is required', 'error');
    redirect(APP_URL . 'modules/sessions/index.php');
}

try {
    // Get session details
    $sessionStmt = $pdo->prepare("
        SELECT ts.*, t.trainer_name, t.user_id as trainer_user_id, g.gym_name
        FROM training_sessions ts
        LEFT JOIN trainers t ON ts.trainer_id = t.trainer_id
        LEFT JOIN gyms g ON ts.gym_id = g.gym_id
        WHERE ts.session_id = ?
    ");
    $sessionStmt->execute([$sessionId]);
    $session = $sessionStmt->fetch();

    if (!$session) {
        setMessage('Session not found', 'error');
        redirect(APP_URL . 'modules/sessions/index.php');
    }

    // Authorization check - trainers can only view their own sessions
    if ($_SESSION['user_type'] === 'trainer') {
        $trainerCheckStmt = $pdo->prepare("SELECT user_id FROM trainers WHERE trainer_id = ?");
        $trainerCheckStmt->execute([$session['trainer_id']]);
        $trainer = $trainerCheckStmt->fetch();
        if (!$trainer || $trainer['user_id'] != $_SESSION['user_id']) {
            setMessage('Access denied: You do not have permission to view this session', 'error');
            redirect(APP_URL . 'modules/sessions/index.php');
        }
    }
    
    // Authorization check - members can only view sessions from their assigned trainer
    if ($_SESSION['user_type'] === 'member') {
        $memberCheckStmt = $pdo->prepare("SELECT trainer_id FROM members WHERE user_id = ?");
        $memberCheckStmt->execute([$_SESSION['user_id']]);
        $memberData = $memberCheckStmt->fetch();
        $memberTrainerId = $memberData['trainer_id'] ?? null;
        
        if (!$memberTrainerId || $memberTrainerId !== $session['trainer_id']) {
            setMessage('Access denied: You can only view sessions from your assigned trainer', 'error');
            redirect(APP_URL . 'modules/sessions/index.php');
        }
    }

    // Get attendees
    $attendeeStmt = $pdo->prepare("
        SELECT tsa.*, m.member_name, m.email
        FROM training_session_attendees tsa
        LEFT JOIN members m ON tsa.member_id = m.member_id
        WHERE tsa.session_id = ?
        ORDER BY tsa.check_in_time DESC
    ");
    $attendeeStmt->execute([$sessionId]);
    $attendees = $attendeeStmt->fetchAll();

} catch (Exception $e) {
    setMessage('Error loading session: ' . $e->getMessage(), 'error');
}

// Handle check-in/out
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $memberId = $_POST['member_id'] ?? null;

    if (!$memberId) {
        setMessage('Member ID is required', 'error');
    } else {
        try {
            if ($action === 'checkin') {
                // Check if already checked in
                $checkStmt = $pdo->prepare("
                    SELECT * FROM training_session_attendees 
                    WHERE session_id = ? AND member_id = ?
                ");
                $checkStmt->execute([$sessionId, $memberId]);
                $existing = $checkStmt->fetch();
                
                if ($existing && $existing['check_in_time']) {
                    setMessage('Member is already checked in', 'error');
                } else {
                    // Get check-in time from form or use NOW()
                    $checkInTime = $_POST['check_in_time'] ?? '';
                    if (!empty($checkInTime)) {
                        // Combine session date with provided time
                        $checkInDateTime = $session['session_date'] . ' ' . $checkInTime . ':00';
                    } else {
                        $checkInDateTime = null; // Will use NOW()
                    }
                    
                    if ($existing) {
                        // Update existing attendee record
                        $stmt = $pdo->prepare("
                            UPDATE training_session_attendees 
                            SET check_in_time = " . ($checkInDateTime ? "?" : "NOW()") . ", attendance_status = 'Present'
                            WHERE session_id = ? AND member_id = ?
                        ");
                        if ($checkInDateTime) {
                            $stmt->execute([$checkInDateTime, $sessionId, $memberId]);
                        } else {
                            $stmt->execute([$sessionId, $memberId]);
                        }
                    } else {
                        // Insert new attendee record
                        $stmt = $pdo->prepare("
                            INSERT INTO training_session_attendees (session_id, member_id, check_in_time, attendance_status)
                            VALUES (?, ?, " . ($checkInDateTime ? "?" : "NOW()") . ", 'Present')
                        ");
                        if ($checkInDateTime) {
                            $stmt->execute([$sessionId, $memberId, $checkInDateTime]);
                        } else {
                            $stmt->execute([$sessionId, $memberId]);
                        }
                    }
                    setMessage('Member checked in successfully', 'success');
                    header('Location: view.php?id=' . $sessionId);
                    exit;
                }
            } elseif ($action === 'checkout') {
                // Get check-out time from form or use NOW()
                $checkOutTime = $_POST['check_out_time'] ?? '';
                if (!empty($checkOutTime)) {
                    // Combine session date with provided time
                    $checkOutDateTime = $session['session_date'] . ' ' . $checkOutTime . ':00';
                    $stmt = $pdo->prepare("
                        UPDATE training_session_attendees SET check_out_time = ?
                        WHERE session_id = ? AND member_id = ? AND check_out_time IS NULL
                    ");
                    $stmt->execute([$checkOutDateTime, $sessionId, $memberId]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE training_session_attendees SET check_out_time = NOW()
                        WHERE session_id = ? AND member_id = ? AND check_out_time IS NULL
                    ");
                    $stmt->execute([$sessionId, $memberId]);
                }
                setMessage('Member checked out successfully', 'success');
                header('Location: view.php?id=' . $sessionId);
                exit;
            }
        } catch (Exception $e) {
            setMessage('Error: ' . $e->getMessage(), 'error');
        }
    }
}

?>

<div class="container">
    <div class="card">
        <div class="card-header">
            <h2><?php echo htmlspecialchars($session['session_name'] ?? ''); ?></h2>
            <div class="action-buttons">
                <?php if ($_SESSION['user_type'] === 'admin' || ($_SESSION['user_type'] === 'trainer' && $_SESSION['user_id'] == $session['trainer_user_id'])): ?>
                    <a href="edit.php?id=<?php echo $sessionId; ?>" class="btn btn-warning">Edit</a>
                    <a href="delete.php?id=<?php echo $sessionId; ?>" class="btn btn-danger" onclick="return confirm('Delete this session?');">Delete</a>
                <?php endif; ?>
                <a href="index.php" class="btn btn-light">Back</a>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo htmlspecialchars($message['type']); ?>">
                <?php echo htmlspecialchars($message['text']); ?>
            </div>
        <?php endif; ?>

        <?php if ($session): ?>
            <div class="card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Trainer</label>
                        <p><?php echo htmlspecialchars($session['trainer_name'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Gym</label>
                        <p><?php echo htmlspecialchars($session['gym_name'] ?? 'N/A'); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Date</label>
                        <p><?php echo date('F d, Y', strtotime($session['session_date'])); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Time</label>
                        <p><?php echo date('H:i', strtotime($session['session_time'])); ?></p>
                    </div>
                    <div class="info-item">
                        <label>Duration</label>
                        <p><?php echo htmlspecialchars($session['duration']); ?> minutes</p>
                    </div>
                    <div class="info-item">
                        <label>Status</label>
                        <p>
                            <span class="badge badge-<?php echo strtolower($session['status']); ?>">
                                <?php echo htmlspecialchars($session['status']); ?>
                            </span>
                        </p>
                    </div>
                    <div class="info-item">
                        <label>Capacity</label>
                        <p><?php echo count($attendees); ?>/<?php echo htmlspecialchars($session['max_capacity']); ?></p>
                    </div>
                </div>

                <?php if (!empty($session['description'])): ?>
                    <div class="section">
                        <h3>Description</h3>
                        <p><?php echo nl2br(htmlspecialchars($session['description'])); ?></p>
                    </div>
                <?php endif; ?>

                <!-- Attendees Section -->
                <div class="section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3>Attendees (<?php echo count($attendees); ?>)</h3>
                    </div>

                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Member</th>
                                    <th>Email</th>
                                    <th>Check In Time</th>
                                    <th>Check Out Time</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($attendees) > 0): ?>
                                    <?php foreach ($attendees as $attendee): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($attendee['member_name'] ?? 'Unknown'); ?></td>
                                            <td><?php echo htmlspecialchars($attendee['email'] ?? 'N/A'); ?></td>
                                            <!-- Check In Column -->
                                            <td>
                                                <?php if ($attendee['check_in_time']): ?>
                                                    <span class="badge bg-success"><?php echo date('H:i', strtotime($attendee['check_in_time'])); ?></span>
                                                    <?php if ($_SESSION['user_type'] === 'admin' || ($_SESSION['user_type'] === 'trainer' && $_SESSION['user_id'] == $session['trainer_user_id'])): ?>
                                                        <br><small class="text-muted">Checked in</small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if ($_SESSION['user_type'] === 'admin' || ($_SESSION['user_type'] === 'trainer' && $_SESSION['user_id'] == $session['trainer_user_id'])): ?>
                                                        <form method="POST" style="display: inline-flex; gap: 5px; align-items: center;">
                                                            <input type="hidden" name="action" value="checkin">
                                                            <input type="hidden" name="member_id" value="<?php echo $attendee['member_id']; ?>">
                                                            <input type="time" name="check_in_time" class="form-control form-control-sm" style="width: 120px;" value="<?php echo date('H:i'); ?>" title="Set check-in time">
                                                            <button type="submit" class="btn btn-sm btn-success" title="Check in this member">Check In</button>
                                                        </form>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not checked in</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <!-- Check Out Column -->
                                            <td>
                                                <?php if ($attendee['check_out_time']): ?>
                                                    <span class="badge bg-info"><?php echo date('H:i', strtotime($attendee['check_out_time'])); ?></span>
                                                    <?php if ($_SESSION['user_type'] === 'admin' || ($_SESSION['user_type'] === 'trainer' && $_SESSION['user_id'] == $session['trainer_user_id'])): ?>
                                                        <br><small class="text-muted">Checked out</small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if ($attendee['check_in_time']): ?>
                                                        <?php if ($_SESSION['user_type'] === 'admin' || ($_SESSION['user_type'] === 'trainer' && $_SESSION['user_id'] == $session['trainer_user_id'])): ?>
                                                            <form method="POST" style="display: inline-flex; gap: 5px; align-items: center;">
                                                                <input type="hidden" name="action" value="checkout">
                                                                <input type="hidden" name="member_id" value="<?php echo $attendee['member_id']; ?>">
                                                                <input type="time" name="check_out_time" class="form-control form-control-sm" style="width: 120px;" value="<?php echo date('H:i'); ?>" title="Set check-out time">
                                                                <button type="submit" class="btn btn-sm btn-info" title="Check out this member">Check Out</button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="text-muted">In session</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo strtolower($attendee['attendance_status']); ?>">
                                                    <?php echo htmlspecialchars($attendee['attendance_status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No attendees yet</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
