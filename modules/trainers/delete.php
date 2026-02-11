<?php
/**
 * Trainers Management - Delete Trainer
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$trainerId = sanitize($_GET['id'] ?? '');

if (empty($trainerId)) {
    setMessage('Invalid trainer ID', 'error');
    redirect(APP_URL . 'modules/trainers/');
}

try {
    // Get trainer info first
    $stmt = $pdo->prepare("SELECT trainer_name, user_id FROM trainers WHERE trainer_id = ?");
    $stmt->execute([$trainerId]);
    $trainer = $stmt->fetch();

    if (!$trainer) {
        setMessage('Trainer not found', 'error');
        redirect(APP_URL . 'modules/trainers/');
    }

    // If confirmed
    if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
        // Delete trainer
        $deleteStmt = $pdo->prepare("DELETE FROM trainers WHERE trainer_id = ?");
        $deleteStmt->execute([$trainerId]);

        // Optional: Delete associated user account
        if (!empty($trainer['user_id'])) {
            $userDeleteStmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $userDeleteStmt->execute([$trainer['user_id']]);
        }

        logAction($_SESSION['user_id'], 'DELETE_TRAINER', 'Trainers', 'Deleted trainer: ' . $trainer['trainer_name']);

        setMessage('Trainer deleted successfully', 'success');
        redirect(APP_URL . 'modules/trainers/');
    }

    // If cancel
    if (isset($_GET['confirm']) && $_GET['confirm'] == 'no') {
        redirect(APP_URL . 'modules/trainers/view.php?id=' . $trainerId);
    }

} catch (Exception $e) {
    setMessage('Error: ' . $e->getMessage(), 'error');
    redirect(APP_URL . 'modules/trainers/');
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
                        <p class="lead">Are you sure you want to delete this trainer?</p>
                        
                        <div class="alert alert-danger">
                            <strong>Trainer:</strong> <?php echo htmlspecialchars($trainer['trainer_name']); ?><br>
                            <strong>Trainer ID:</strong> <?php echo htmlspecialchars($trainerId); ?>
                        </div>

                        <p class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            This action cannot be undone. All related records will be deleted.
                        </p>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?php echo APP_URL; ?>modules/trainers/view.php?id=<?php echo $trainerId; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <a href="?id=<?php echo urlencode($trainerId); ?>&confirm=yes" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Delete Trainer
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
