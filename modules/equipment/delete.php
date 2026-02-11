<?php
/**
 * Equipment Management - Delete Equipment
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole('admin');

$equipmentId = sanitize($_GET['id'] ?? '');

if (empty($equipmentId)) {
    setMessage('Invalid equipment ID', 'error');
    redirect(APP_URL . 'modules/equipment/');
}

try {
    // Get equipment info first
    $stmt = $pdo->prepare("SELECT * FROM equipment WHERE equipment_id = ?");
    $stmt->execute([$equipmentId]);
    $equipment = $stmt->fetch();

    if (!$equipment) {
        setMessage('Equipment not found', 'error');
        redirect(APP_URL . 'modules/equipment/');
    }

    // If confirmed
    if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
        // Delete equipment
        $deleteStmt = $pdo->prepare("DELETE FROM equipment WHERE equipment_id = ?");
        $deleteStmt->execute([$equipmentId]);

        logAction($_SESSION['user_id'], 'DELETE_EQUIPMENT', 'Equipment', 'Deleted equipment: ' . $equipmentId);

        setMessage('Equipment deleted successfully', 'success');
        redirect(APP_URL . 'modules/equipment/');
    }

    // If cancel
    if (isset($_GET['confirm']) && $_GET['confirm'] == 'no') {
        redirect(APP_URL . 'modules/equipment/view.php?id=' . urlencode($equipmentId));
    }

} catch (Exception $e) {
    setMessage('Error: ' . $e->getMessage(), 'error');
    redirect(APP_URL . 'modules/equipment/');
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <h1><i class="fas fa-trash"></i> Delete Equipment</h1>
            </div>

            <div class="alert alert-danger" role="alert">
                <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h4>
                <p>Are you sure you want to delete the following equipment?</p>
                <hr>
                <div class="card">
                    <div class="card-body">
                        <h5><?php echo htmlspecialchars($equipment['equipment_name']); ?></h5>
                        <p class="mb-0 text-muted">ID: <?php echo htmlspecialchars($equipment['equipment_id']); ?></p>
                    </div>
                </div>
                <hr>
                <p class="mb-0">
                    <strong><i class="fas fa-info-circle"></i> Note:</strong> This action cannot be undone. All data associated with this equipment will be permanently deleted.
                </p>
            </div>

            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                <a href="?id=<?php echo urlencode($equipmentId); ?>&confirm=yes" class="btn btn-danger btn-lg">
                    <i class="fas fa-check"></i> Yes, Delete
                </a>
                <a href="?id=<?php echo urlencode($equipmentId); ?>&confirm=no" class="btn btn-secondary btn-lg">
                    <i class="fas fa-times"></i> Cancel
                </a>
            </div>

        </main>
    </div>
</div>

<?php include dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
