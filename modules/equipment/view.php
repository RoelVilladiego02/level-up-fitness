<?php
/**
 * Equipment Management - View Equipment
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole('admin');

$equipmentId = sanitize($_GET['id'] ?? '');
$equipment = null;

// Load equipment
if (!empty($equipmentId)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM equipment WHERE equipment_id = ?");
        $stmt->execute([$equipmentId]);
        $equipment = $stmt->fetch();
        
        if (!$equipment) {
            setMessage('Equipment not found', 'error');
            redirect(APP_URL . 'modules/equipment/');
        }
    } catch (Exception $e) {
        setMessage('Error loading equipment: ' . $e->getMessage(), 'error');
    }
}

// Get reservations count
$reservationCount = 0;
if ($equipment) {
    try {
        $resStmt = $pdo->prepare("SELECT COUNT(*) as count FROM reservations WHERE equipment_id = ? AND status = 'Confirmed'");
        $resStmt->execute([$equipmentId]);
        $reservationCount = $resStmt->fetch()['count'];
    } catch (Exception $e) {
        // Ignore
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <div class="btn-group float-end" role="group">
                    <a href="edit.php?id=<?php echo urlencode($equipmentId); ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="delete.php?id=<?php echo urlencode($equipmentId); ?>" class="btn btn-danger btn-sm">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                    <a href="<?php echo APP_URL; ?>modules/equipment/" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
                <h1><i class="fas fa-tools"></i> Equipment Details</h1>
            </div>

            <?php displayMessage(); ?>

            <?php if ($equipment): ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><?php echo htmlspecialchars($equipment['equipment_name']); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Equipment ID</h6>
                                    <p class="mb-3"><strong><?php echo htmlspecialchars($equipment['equipment_id']); ?></strong></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Category</h6>
                                    <p class="mb-3"><?php echo htmlspecialchars($equipment['equipment_category'] ?? 'N/A'); ?></p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Quantity</h6>
                                    <p class="mb-3"><span class="badge bg-info"><?php echo (int)$equipment['quantity']; ?> units</span></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Location</h6>
                                    <p class="mb-3"><?php echo htmlspecialchars($equipment['location'] ?? 'N/A'); ?></p>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Status</h6>
                                    <p>
                                        <?php
                                        $statusClass = match($equipment['availability']) {
                                            'Available' => 'success',
                                            'Maintenance' => 'warning',
                                            'Out of Service' => 'danger',
                                            default => 'secondary'
                                        };
                                        ?>
                                        <span class="badge bg-<?php echo $statusClass; ?>">
                                            <?php echo htmlspecialchars($equipment['availability']); ?>
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-1">Active Reservations</h6>
                                    <p><strong><?php echo $reservationCount; ?></strong> confirmed reservations</p>
                                </div>
                            </div>

                            <?php if (!empty($equipment['description'])): ?>
                            <hr>
                            <h6 class="text-muted mb-2">Description</h6>
                            <p><?php echo nl2br(htmlspecialchars($equipment['description'])); ?></p>
                            <?php endif; ?>

                            <hr>
                            <div class="row text-muted small">
                                <div class="col-md-6">
                                    <strong>Created:</strong> <?php echo formatDate($equipment['created_at']); ?>
                                </div>
                                <div class="col-md-6 text-md-end">
                                    <strong>Updated:</strong> <?php echo formatDate($equipment['updated_at']); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">📊 Stats</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <h6 class="text-muted">Total Reservations</h6>
                                <p class="h4"><?php echo $reservationCount; ?></p>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <h6 class="text-muted">Equipment Status</h6>
                                <p><?php echo htmlspecialchars($equipment['availability']); ?></p>
                            </div>
                            <?php if ($equipment['availability'] !== 'Available'): ?>
                            <div class="alert alert-warning small">
                                <i class="fas fa-exclamation-triangle"></i> This equipment is not available for reservations
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">⚙️ Actions</h5>
                        </div>
                        <div class="card-body">
                            <a href="edit.php?id=<?php echo urlencode($equipmentId); ?>" class="btn btn-warning btn-sm w-100 mb-2">
                                <i class="fas fa-edit"></i> Edit Equipment
                            </a>
                            <a href="delete.php?id=<?php echo urlencode($equipmentId); ?>" class="btn btn-danger btn-sm w-100">
                                <i class="fas fa-trash"></i> Delete Equipment
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> Equipment not found
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php include dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
