<?php
/**
 * Gym Information - View Branch Details
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$gymId = sanitize($_GET['id'] ?? '');
$gym = null;
$trainerCount = 0;
$memberCount = 0;

if (!empty($gymId)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM gyms WHERE gym_id = ?");
        $stmt->execute([$gymId]);
        $gym = $stmt->fetch();
        
        if (!$gym) {
            setMessage('Gym branch not found', 'error');
            redirect(APP_URL . 'modules/gyms/');
        }

        // Get trainer count (assuming trainers have gym_id reference)
        // Note: if trainers table doesn't have gym_id, adjust this query
        $trainerStmt = $pdo->prepare("SELECT COUNT(*) as count FROM trainers");
        $trainerStmt->execute();
        $trainerCount = $trainerStmt->fetch()['count'];

        // Get member count
        $memberStmt = $pdo->prepare("SELECT COUNT(*) as count FROM members");
        $memberStmt->execute();
        $memberCount = $memberStmt->fetch()['count'];

    } catch (Exception $e) {
        setMessage('Error loading gym: ' . $e->getMessage(), 'error');
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <div class="float-end">
                    <a href="<?php echo APP_URL; ?>modules/gyms/edit.php?id=<?php echo $gymId; ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="<?php echo APP_URL; ?>modules/gyms/delete.php?id=<?php echo $gymId; ?>" class="btn btn-danger btn-sm btn-delete">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </div>
                <a href="<?php echo APP_URL; ?>modules/gyms/" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-building"></i> Gym Branch Details</h1>
                <p>View branch information</p>
            </div>

            <?php displayMessage(); ?>

            <?php if ($gym): ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Branch Information</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Gym ID:</strong><br>
                                <code><?php echo htmlspecialchars($gym['gym_id']); ?></code>
                            </p>
                            <hr>
                            <p>
                                <strong>Branch Name:</strong><br>
                                <?php echo htmlspecialchars($gym['gym_branch']); ?>
                            </p>
                            <hr>
                            <p>
                                <strong>Gym Name:</strong><br>
                                <?php echo htmlspecialchars($gym['gym_name'] ?? 'N/A'); ?>
                            </p>
                            <hr>
                            <p>
                                <strong>Location:</strong><br>
                                <?php echo nl2br(htmlspecialchars($gym['location'] ?? 'N/A')); ?>
                            </p>
                            <hr>
                            <p>
                                <strong>Contact Number:</strong><br>
                                <a href="tel:<?php echo htmlspecialchars($gym['contact_number']); ?>">
                                    <?php echo htmlspecialchars($gym['contact_number']); ?>
                                </a>
                            </p>
                            <hr>
                            <p>
                                <strong>Description:</strong><br>
                                <?php echo nl2br(htmlspecialchars($gym['description'] ?? 'N/A')); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="row text-center mb-2">
                                <div class="col">
                                    <h6>Members</h6>
                                    <h3><?php echo $memberCount; ?></h3>
                                </div>
                            </div>
                            <hr>
                            <div class="row text-center">
                                <div class="col">
                                    <h6>Trainers</h6>
                                    <h3><?php echo $trainerCount; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Timeline</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Created:</strong><br>
                                <?php echo formatDate($gym['created_at']); ?>
                            </p>
                            <hr>
                            <p>
                                <strong>Last Updated:</strong><br>
                                <?php echo formatDate($gym['updated_at']); ?>
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
