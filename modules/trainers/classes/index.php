<?php
/**
 * Trainer Classes Management - List View
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/includes/header.php';

requireLogin();
requireRole(['admin', 'trainer']);

$classes = [];
$message = getMessage();
$searchTerm = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$page = $_GET['page'] ?? 1;
$itemsPerPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $itemsPerPage;
$totalRecords = 0;
$totalPages = 1;

try {
    // Build query
    $query = "SELECT c.*, t.trainer_name, 
              (SELECT COUNT(*) FROM class_attendance WHERE class_id = c.class_id) as enrolled_members
              FROM classes c
              JOIN trainers t ON c.trainer_id = t.trainer_id
              WHERE 1=1";
    $params = [];

    // Role-based access control
    if ($_SESSION['user_type'] === 'trainer') {
        $query .= " AND c.trainer_id = ?";
        $params[] = $_SESSION['user_id'];
    }

    // Search filter
    if (!empty($searchTerm)) {
        $query .= " AND (LOWER(c.class_name) LIKE ? OR LOWER(c.class_description) LIKE ? OR LOWER(t.trainer_name) LIKE ?)";
        $search = "%".strtolower($searchTerm)."%";
        $params = array_merge($params, [$search, $search, $search]);
    }

    // Status filter
    if (!empty($filterStatus)) {
        $query .= " AND c.class_status = ?";
        $params[] = $filterStatus;
    }

    // Get total count
    $countStmt = $pdo->prepare(str_replace('SELECT c.*, t.trainer_name, (SELECT COUNT(*) FROM class_attendance WHERE class_id = c.class_id) as enrolled_members', 'SELECT COUNT(*) as total', $query));
    $countStmt->execute($params);
    $countResult = $countStmt->fetch();
    $totalRecords = ($countResult && isset($countResult['total'])) ? $countResult['total'] : 0;
    $totalPages = ceil($totalRecords / $itemsPerPage);

    // Get paginated results
    $query .= " ORDER BY c.schedule_day, c.start_time LIMIT " . (int)$itemsPerPage . " OFFSET " . (int)$offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $classes = $stmt->fetchAll();

} catch (Exception $e) {
    setMessage('Error loading classes: ' . $e->getMessage(), 'error');
}

$pageTitle = 'Classes Management';
$breadcrumbs = [
    ['label' => 'Dashboard', 'url' => 'dashboard.php'],
    ['label' => 'Classes', 'url' => null]
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
                <h1><i class="fas fa-dumbbell"></i> Classes Management</h1>
            </div>
            <div class="col-md-4 text-end">
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Create New Class
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

        <!-- Filters -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-6">
                        <input type="text" name="search" class="form-control" placeholder="Search by class name, description..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                    <div class="col-md-4">
                        <select name="status" class="form-select">
                            <option value="">All Statuses</option>
                            <option value="Active" <?php echo $filterStatus === 'Active' ? 'selected' : ''; ?>>Active</option>
                            <option value="Inactive" <?php echo $filterStatus === 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="Cancelled" <?php echo $filterStatus === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Classes Table -->
        <div class="card">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Class Name</th>
                            <th>Trainer</th>
                            <th>Day</th>
                            <th>Time</th>
                            <th>Capacity</th>
                            <th>Enrolled</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($classes): ?>
                            <?php foreach ($classes as $class): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($class['class_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($class['trainer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($class['schedule_day']); ?></td>
                                    <td><?php echo date('H:i', strtotime($class['start_time'])); ?> - <?php echo date('H:i', strtotime($class['end_time'])); ?></td>
                                    <td><?php echo $class['max_capacity']; ?></td>
                                    <td>
                                        <span class="badge bg-info"><?php echo $class['enrolled_members']; ?></span>
                                    </td>
                                    <td>
                                        <?php 
                                        $statusClass = match($class['class_status']) {
                                            'Active' => 'bg-success',
                                            'Inactive' => 'bg-secondary',
                                            'Cancelled' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>"><?php echo $class['class_status']; ?></span>
                                    </td>
                                    <td>
                                        <a href="view.php?class_id=<?php echo urlencode($class['class_id']); ?>" class="btn btn-sm btn-info" title="View">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit.php?class_id=<?php echo urlencode($class['class_id']); ?>" class="btn btn-sm btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="delete.php?class_id=<?php echo urlencode($class['class_id']); ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox"></i> No classes found
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === (int)$page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($searchTerm); ?>&status=<?php echo urlencode($filterStatus); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
