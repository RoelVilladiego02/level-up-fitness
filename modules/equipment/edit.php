<?php
/**
 * Equipment Management - Edit Equipment
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole('admin');

$equipmentId = sanitize($_GET['id'] ?? '');
$equipment = null;
$errors = [];
$formData = [];

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
        
        $formData = $equipment;
    } catch (Exception $e) {
        setMessage('Error loading equipment: ' . $e->getMessage(), 'error');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($equipmentId)) {
    $formData['equipment_name'] = sanitize($_POST['equipment_name'] ?? '');
    $formData['equipment_category'] = sanitize($_POST['equipment_category'] ?? '');
    $formData['description'] = sanitize($_POST['description'] ?? '');
    $formData['quantity'] = sanitize($_POST['quantity'] ?? '');
    $formData['location'] = sanitize($_POST['location'] ?? '');
    $formData['availability'] = sanitize($_POST['availability'] ?? 'Available');

    // Validate
    if (empty($formData['equipment_name'])) {
        $errors['equipment_name'] = 'Equipment name is required';
    }
    if (empty($formData['equipment_category'])) {
        $errors['equipment_category'] = 'Equipment category is required';
    }
    if (empty($formData['quantity']) || !is_numeric($formData['quantity']) || (int)$formData['quantity'] < 1) {
        $errors['quantity'] = 'Quantity must be a number greater than 0';
    }
    if (empty($formData['location'])) {
        $errors['location'] = 'Location is required';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE equipment SET 
                    equipment_name = ?, equipment_category = ?, description = ?, 
                    quantity = ?, location = ?, availability = ?
                WHERE equipment_id = ?
            ");
            $stmt->execute([
                $formData['equipment_name'], $formData['equipment_category'],
                !empty($formData['description']) ? $formData['description'] : NULL,
                (int)$formData['quantity'], $formData['location'], 
                $formData['availability'], $equipmentId
            ]);

            logAction($_SESSION['user_id'], 'EDIT_EQUIPMENT', 'Equipment', 'Updated equipment: ' . $equipmentId);

            setMessage('Equipment updated successfully', 'success');
            redirect(APP_URL . 'modules/equipment/view.php?id=' . urlencode($equipmentId));
        } catch (Exception $e) {
            setMessage('Error updating equipment: ' . $e->getMessage(), 'error');
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/equipment/" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-edit"></i> Edit Equipment</h1>
                <p>Update equipment information</p>
            </div>

            <?php displayMessage(); ?>

            <?php if ($equipment): ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Equipment Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="equipment_name" class="form-label">Equipment Name *</label>
                                        <input type="text" class="form-control <?php echo isset($errors['equipment_name']) ? 'is-invalid' : ''; ?>" 
                                               id="equipment_name" name="equipment_name" 
                                               value="<?php echo htmlspecialchars($formData['equipment_name'] ?? ''); ?>" required>
                                        <?php if (isset($errors['equipment_name'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['equipment_name']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="equipment_category" class="form-label">Category *</label>
                                        <input type="text" class="form-control <?php echo isset($errors['equipment_category']) ? 'is-invalid' : ''; ?>" 
                                               id="equipment_category" name="equipment_category" 
                                               value="<?php echo htmlspecialchars($formData['equipment_category'] ?? ''); ?>" required>
                                        <?php if (isset($errors['equipment_category'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['equipment_category']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($formData['description'] ?? ''); ?></textarea>
                                    <small class="text-muted">Optional - Notes about the equipment</small>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="quantity" class="form-label">Quantity *</label>
                                        <input type="number" class="form-control <?php echo isset($errors['quantity']) ? 'is-invalid' : ''; ?>" 
                                               id="quantity" name="quantity" min="1"
                                               value="<?php echo htmlspecialchars($formData['quantity'] ?? '1'); ?>" required>
                                        <?php if (isset($errors['quantity'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['quantity']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="location" class="form-label">Location *</label>
                                        <input type="text" class="form-control <?php echo isset($errors['location']) ? 'is-invalid' : ''; ?>" 
                                               id="location" name="location" 
                                               value="<?php echo htmlspecialchars($formData['location'] ?? ''); ?>" required>
                                        <?php if (isset($errors['location'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['location']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="availability" class="form-label">Status</label>
                                    <select class="form-select" id="availability" name="availability">
                                        <option value="Available" <?php echo ($formData['availability'] ?? 'Available') === 'Available' ? 'selected' : ''; ?>>Available</option>
                                        <option value="Maintenance" <?php echo ($formData['availability'] ?? '') === 'Maintenance' ? 'selected' : ''; ?>>Under Maintenance</option>
                                        <option value="Out of Service" <?php echo ($formData['availability'] ?? '') === 'Out of Service' ? 'selected' : ''; ?>>Out of Service</option>
                                    </select>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save"></i> Update Equipment
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">ℹ️ Info</h5>
                        </div>
                        <div class="card-body">
                            <h6>Equipment ID</h6>
                            <p class="small"><code><?php echo htmlspecialchars($equipmentId); ?></code></p>
                            <hr>
                            <h6>Last Updated</h6>
                            <p class="small"><?php echo formatDate($equipment['updated_at']); ?></p>
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
