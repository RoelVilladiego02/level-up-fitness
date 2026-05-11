<?php
/**
 * Trainer Classes Management - View Class Details
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/includes/header.php';

requireLogin();
requireRole(['admin', 'trainer']);

$message = getMessage();
$class = null;
$enrolledMembers = [];
$classId = $_GET['class_id'] ?? '';

// Load class data
try {
    $stmt = $pdo->prepare("SELECT c.*, t.trainer_name, 
              (SELECT COUNT(*) FROM class_attendance WHERE class_id = c.class_id) as total_enrolled
              FROM classes c 
              JOIN trainers t ON c.trainer_id = t.trainer_id 
              WHERE c.class_id = ?");
    $stmt->execute([$classId]);
    $class = $stmt->fetch();

    if (!$class) {
        setMessage('Class not found', 'error');
        redirect('index.php');
    }

    // Check authorization
    if ($_SESSION['user_type'] === 'trainer' && $class['trainer_id'] !== $_SESSION['user_id']) {
        setMessage('You do not have permission to view this class', 'error');
        redirect('index.php');
    }

    // Load enrolled members
    $membersStmt = $pdo->prepare("SELECT DISTINCT m.member_id, m.member_name, m.email, m.contact_number, ca.enrollment_date
              FROM class_attendance ca
              JOIN members m ON ca.member_id = m.member_id
              WHERE ca.class_id = ?
              ORDER BY ca.enrollment_date DESC");
    $membersStmt->execute([$classId]);
    $enrolledMembers = $membersStmt->fetchAll();

} catch (Exception $e) {
    setMessage('Error loading class: ' . $e->getMessage(), 'error');
}

$pageTitle = 'View Class Details';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => '../../../dashboard.php'],
    ['label' => 'Classes', 'url' => 'index.php'],
    ['label' => 'View Details', 'url' => null]
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container-fluid mt-4">
        <!-- Breadcrumbs -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <?php foreach ($breadcrumbs as $crumb): ?>
                    <li class="breadcrumb-item <?php echo $crumb['url'] ? '' : 'active'; ?>">
                        <?php if ($crumb['url']): ?>
                            <a href="<?php echo $crumb['url']; ?>"><?php echo htmlspecialchars($crumb['label']); ?></a>
                        <?php else: ?>
                            <?php echo htmlspecialchars($crumb['label']); ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </nav>

        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-md-8">
                <h1><i class="fas fa-eye"></i> Class Details</h1>
            </div>
            <div class="col-md-4 text-end">
                <a href="edit.php?class_id=<?php echo urlencode($classId); ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message['type']; ?> alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($message['text']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Class Details -->
        <?php if ($class): ?>
            <!-- Basic Information -->
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><?php echo htmlspecialchars($class['class_name']); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6 class="text-muted">Trainer</h6>
                                    <p><?php echo htmlspecialchars($class['trainer_name']); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted">Schedule</h6>
                                    <p><?php echo htmlspecialchars($class['schedule_day']); ?>, 
                                       <?php echo date('H:i', strtotime($class['start_time'])); ?> - 
                                       <?php echo date('H:i', strtotime($class['end_time'])); ?></p>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6 class="text-muted">Status</h6>
                                    <p>
                                        <?php 
                                        $statusClass = match($class['class_status']) {
                                            'Active' => 'bg-success',
                                            'Inactive' => 'bg-secondary',
                                            'Cancelled' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo $class['class_status']; ?></span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted">Capacity</h6>
                                    <p><?php echo $class['enrolled_members']; ?> / <?php echo $class['max_capacity']; ?> members</p>
                                </div>
                            </div>
                            <?php if (!empty($class['class_description'])): ?>
                                <div class="row">
                                    <div class="col-12">
                                        <h6 class="text-muted">Description</h6>
                                        <p><?php echo nl2br(htmlspecialchars($class['class_description'])); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Stats -->
                <div class="col-md-4">
                    <div class="card bg-light">
                        <div class="card-body text-center">
                            <h5 class="card-title">Enrollment</h5>
                            <div class="display-4 text-primary mb-3">
                                <?php echo $class['total_enrolled']; ?>
                            </div>
                            <p class="card-text text-muted">Members Enrolled</p>
                            <hr>
                            <h6 class="text-muted">Available Slots</h6>
                            <p class="display-5 text-success">
                                <?php echo $class['max_capacity'] - $class['total_enrolled']; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Enrolled Members -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Enrolled Members (<?php echo count($enrolledMembers); ?>)</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Member Name</th>
                                <th>Email</th>
                                <th>Contact</th>
                                <th>Enrollment Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($enrolledMembers): ?>
                                <?php foreach ($enrolledMembers as $member): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($member['member_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($member['email']); ?></td>
                                        <td><?php echo htmlspecialchars($member['contact_number']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($member['enrollment_date'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox"></i> No members enrolled yet
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
