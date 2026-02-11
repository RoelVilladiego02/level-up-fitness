<?php
/**
 * Classes - View Class Details
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$classId = sanitize($_GET['id'] ?? '');
$class = null;
$trainer = null;
$members = [];

if (!empty($classId)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM classes WHERE class_id = ?");
        $stmt->execute([$classId]);
        $class = $stmt->fetch();
        
        if (!$class) {
            setMessage('Class not found', 'error');
            redirect(APP_URL . 'modules/classes/');
        }

        // Get trainer info if assigned
        if (!empty($class['trainer_id'])) {
            $trainerStmt = $pdo->prepare("SELECT * FROM trainers WHERE trainer_id = ?");
            $trainerStmt->execute([$class['trainer_id']]);
            $trainer = $trainerStmt->fetch();
        }

        // Get class members
        $memberStmt = $pdo->prepare("
            SELECT m.member_id, m.member_name, m.email, ca.enrollment_date
            FROM class_attendance ca
            JOIN members m ON ca.member_id = m.member_id
            WHERE ca.class_id = ?
            ORDER BY ca.enrollment_date DESC
        ");
        $memberStmt->execute([$classId]);
        $members = $memberStmt->fetchAll();

    } catch (Exception $e) {
        setMessage('Error loading class: ' . $e->getMessage(), 'error');
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <div class="float-end">
                    <a href="<?php echo APP_URL; ?>modules/classes/edit.php?id=<?php echo $classId; ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="<?php echo APP_URL; ?>modules/classes/delete.php?id=<?php echo $classId; ?>" class="btn btn-danger btn-sm btn-delete">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </div>
                <a href="<?php echo APP_URL; ?>modules/classes/" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-dumbbell"></i> Class Details</h1>
                <p>View class information</p>
            </div>

            <?php displayMessage(); ?>

            <?php if ($class): ?>
            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><?php echo htmlspecialchars($class['class_name']); ?></h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Class ID:</strong> <code><?php echo htmlspecialchars($class['class_id']); ?></code>
                            </p>
                            <hr>
                            <?php if (!empty($class['class_description'])): ?>
                                <p>
                                    <strong>Description:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($class['class_description'])); ?>
                                </p>
                                <hr>
                            <?php endif; ?>
                            <p>
                                <strong>Schedule:</strong> <?php echo htmlspecialchars($class['class_schedule']); ?>
                            </p>
                            <hr>
                            <p>
                                <strong>Status:</strong><br>
                                <span class="badge badge-<?php echo strtolower(str_replace('Active', 'success', str_replace('Inactive', 'secondary', $class['class_status']))); ?>" style="font-size: 14px;">
                                    <?php echo htmlspecialchars($class['class_status']); ?>
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Class Members (<?php echo count($members); ?>/<?php echo $class['max_capacity']; ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($members)): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No members enrolled yet.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Member Name</th>
                                                <th>Email</th>
                                                <th>Enrolled</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($members as $member): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($member['member_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($member['email']); ?></td>
                                                    <td><?php echo formatDate($member['enrollment_date']); ?></td>
                                                    <td>
                                                        <a href="<?php echo APP_URL; ?>modules/members/view.php?id=<?php echo $member['member_id']; ?>" 
                                                           class="btn btn-sm btn-info">
                                                            <i class="fas fa-link"></i>
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Capacity</h5>
                        </div>
                        <div class="card-body text-center">
                            <h3><?php echo count($members); ?> / <?php echo $class['max_capacity']; ?></h3>
                            <p class="text-muted">Members Enrolled</p>
                            <div class="progress" style="height: 25px;">
                                <div class="progress-bar <?php 
                                    $capacityPercent = (count($members) / $class['max_capacity'] * 100);
                                    echo $capacityPercent >= 80 ? 'bg-danger' : ($capacityPercent >= 60 ? 'bg-warning' : 'bg-success');
                                ?>" 
                                     role="progressbar" 
                                     style="width: <?php echo $capacityPercent; ?>%">
                                    <?php echo round($capacityPercent); ?>%
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($trainer): ?>
                    <div class="card mb-3">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Instructor</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong><?php echo htmlspecialchars($trainer['trainer_name']); ?></strong><br>
                                <code><?php echo htmlspecialchars($trainer['trainer_id']); ?></code>
                            </p>
                            <p>
                                <span class="badge bg-warning"><?php echo htmlspecialchars($trainer['specialization']); ?></span>
                            </p>
                            <div class="mt-2">
                                <a href="<?php echo APP_URL; ?>modules/trainers/view.php?id=<?php echo $trainer['trainer_id']; ?>" 
                                   class="btn btn-sm btn-info">
                                    <i class="fas fa-link"></i> View Profile
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card mb-3">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">Instructor</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">No instructor assigned</p>
                            <a href="<?php echo APP_URL; ?>modules/classes/edit.php?id=<?php echo $classId; ?>" 
                               class="btn btn-sm btn-primary">
                                Assign Instructor
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">Details</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Created:</strong><br>
                                <?php echo formatDate($class['created_at']); ?>
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
