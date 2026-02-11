<?php
/**
 * Members Management - Delete Member
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$memberId = sanitize($_GET['id'] ?? '');

if (empty($memberId)) {
    setMessage('Invalid member ID', 'error');
    redirect(APP_URL . 'modules/members/');
}

try {
    // Get member info first
    $stmt = $pdo->prepare("SELECT member_name, user_id FROM members WHERE member_id = ?");
    $stmt->execute([$memberId]);
    $member = $stmt->fetch();

    if (!$member) {
        setMessage('Member not found', 'error');
        redirect(APP_URL . 'modules/members/');
    }

    // If confirmed
    if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
        // Delete member (cascades to related records)
        $deleteStmt = $pdo->prepare("DELETE FROM members WHERE member_id = ?");
        $deleteStmt->execute([$memberId]);

        // Optional: Delete associated user account
        if (!empty($member['user_id'])) {
            $userDeleteStmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $userDeleteStmt->execute([$member['user_id']]);
        }

        logAction($_SESSION['user_id'], 'DELETE_MEMBER', 'Members', 'Deleted member: ' . $member['member_name']);

        setMessage('Member deleted successfully', 'success');
        redirect(APP_URL . 'modules/members/');
    }

    // If cancel
    if (isset($_GET['confirm']) && $_GET['confirm'] == 'no') {
        redirect(APP_URL . 'modules/members/view.php?id=' . $memberId);
    }

} catch (Exception $e) {
    setMessage('Error: ' . $e->getMessage(), 'error');
    redirect(APP_URL . 'modules/members/');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Delete - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h5>
                    </div>
                    <div class="card-body">
                        <p class="lead">Are you sure you want to delete this member?</p>
                        
                        <div class="alert alert-danger">
                            <strong>Member:</strong> <?php echo htmlspecialchars($member['member_name']); ?><br>
                            <strong>Member ID:</strong> <?php echo htmlspecialchars($memberId); ?>
                        </div>

                        <p class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            This action cannot be undone. All related records will be deleted.
                        </p>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?php echo APP_URL; ?>modules/members/view.php?id=<?php echo $memberId; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <a href="?id=<?php echo urlencode($memberId); ?>&confirm=yes" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Delete Member
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
