<?php
/**
 * Gym Information - Delete Branch
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$gymId = sanitize($_GET['id'] ?? '');

if (empty($gymId)) {
    setMessage('Invalid gym ID', 'error');
    redirect(APP_URL . 'modules/gyms/');
}

try {
    // Get gym info first
    $stmt = $pdo->prepare("SELECT * FROM gyms WHERE gym_id = ?");
    $stmt->execute([$gymId]);
    $gym = $stmt->fetch();

    if (!$gym) {
        setMessage('Gym branch not found', 'error');
        redirect(APP_URL . 'modules/gyms/');
    }

    // If confirmed
    if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
        // Delete gym
        $deleteStmt = $pdo->prepare("DELETE FROM gyms WHERE gym_id = ?");
        $deleteStmt->execute([$gymId]);

        logAction($_SESSION['user_id'], 'DELETE_GYM', 'Gyms', 'Deleted gym: ' . $gym['gym_name']);

        setMessage('Gym branch deleted successfully', 'success');
        redirect(APP_URL . 'modules/gyms/');
    }

    // If cancel
    if (isset($_GET['confirm']) && $_GET['confirm'] == 'no') {
        redirect(APP_URL . 'modules/gyms/view.php?id=' . $gymId);
    }

} catch (Exception $e) {
    setMessage('Error: ' . $e->getMessage(), 'error');
    redirect(APP_URL . 'modules/gyms/');
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
                        <p class="lead">Are you sure you want to delete this gym branch?</p>
                        
                        <div class="alert alert-danger">
                            <strong>Gym:</strong> <?php echo htmlspecialchars($gym['gym_name']); ?><br>
                            <strong>Gym ID:</strong> <?php echo htmlspecialchars($gymId); ?><br>
                            <strong>Location:</strong> <?php echo htmlspecialchars($gym['location']); ?>
                        </div>

                        <p class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            This action cannot be undone.
                        </p>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?php echo APP_URL; ?>modules/gyms/view.php?id=<?php echo $gymId; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <a href="?id=<?php echo urlencode($gymId); ?>&confirm=yes" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Delete Gym
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
