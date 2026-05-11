<?php
/**
 * Session Requests Management - List View
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

// Members should use the dedicated my-requests.php page
if ($_SESSION['user_type'] === 'member') {
    header('Location: ' . APP_URL . 'modules/attendance/my-requests.php');
    exit;
}

// Only trainers and admins can access this page
if ($_SESSION['user_type'] !== 'trainer' && $_SESSION['user_type'] !== 'admin') {
    setMessage('Access denied: Only trainers and admins can access this page.', 'error');
    redirect(APP_URL . 'dashboard/');
}

$requests = [];
$message = getMessage();
$searchTerm = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$page = $_GET['page'] ?? 1;
$itemsPerPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $itemsPerPage;
$totalRecords = 0;
$totalPages = 1;

try {
    // Build query - query reservations table (trainer time slot system)
    $query = "SELECT r.*, m.member_name, m.email, t.trainer_name
              FROM reservations r
              LEFT JOIN members m ON r.member_id = m.member_id
              LEFT JOIN trainers t ON r.trainer_id = t.trainer_id
              WHERE 1=1";
    $params = [];

    // Trainers only see requests for them
    if ($_SESSION['user_type'] === 'trainer') {
        $trainerStmt = $pdo->prepare("SELECT trainer_id FROM trainers WHERE user_id = ?");
        $trainerStmt->execute([$_SESSION['user_id']]);
        $trainerData = $trainerStmt->fetch();
        if (!$trainerData) {
            setMessage('Trainer profile not found', 'error');
            redirect(APP_URL . 'dashboard/');
        }
        $query .= " AND r.trainer_id = ?";
        $params[] = $trainerData['trainer_id'];
    }

    // Search filter
    if (!empty($searchTerm)) {
        $query .= " AND (m.member_name LIKE ? OR m.email LIKE ? OR r.purpose LIKE ?)";
        $search = "%$searchTerm%";
        $params = array_merge($params, [$search, $search, $search]);
    }

    // Status filter
    if (!empty($filterStatus)) {
        $query .= " AND r.status = ?";
        $params[] = $filterStatus;
    }

    // Get total records
    $countQuery = str_replace("SELECT r.*, m.member_name, m.email, t.trainer_name", "SELECT COUNT(*) as cnt", $query);
    $countStmt = $pdo->prepare($countQuery);
    $countStmt->execute($params);
    $countResult = $countStmt->fetch();
    $totalRecords = $countResult['cnt'];
    $totalPages = ceil($totalRecords / $itemsPerPage);

    // Ensure page is valid
    if ($page > $totalPages && $totalPages > 0) {
        $page = $totalPages;
    }
    $offset = ($page - 1) * $itemsPerPage;

    // Get records
    $query .= " ORDER BY r.created_at DESC LIMIT " . (int)$itemsPerPage . " OFFSET " . (int)$offset;

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $requests = $stmt->fetchAll();
} catch (Exception $e) {
    setMessage('Error loading trainer time requests: ' . $e->getMessage(), 'error');
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <h1><i class="fas fa-calendar-check"></i> Trainer Time Requests</h1>
                <p>Manage member requests for training time slots</p>
            </div>

            <?php displayMessage(); ?>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-6">
                            <input type="text" name="search" class="form-control" placeholder="Search by member name, email, or purpose..." 
                                   value="<?php echo htmlspecialchars($searchTerm); ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">-- All Statuses --</option>
                                <option value="Pending" <?php echo $filterStatus === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="Approved" <?php echo $filterStatus === 'Approved' ? 'selected' : ''; ?>>Approved</option>
                                <option value="Rejected" <?php echo $filterStatus === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                <option value="Cancelled" <?php echo $filterStatus === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-search"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Requests Table -->
            <div class="card">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Trainer Time Requests</h5>
                    <small class="text-muted">Total: <?php echo $totalRecords; ?></small>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Member</th>
                                <th>Date & Time</th>
                                <th>Duration</th>
                                <th>Purpose</th>
                                <th>Status</th>
                                <th>Requested</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($requests) > 0): ?>
                                <?php foreach ($requests as $request): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($request['member_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($request['email']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo formatDate($request['reservation_date']); ?><br>
                                            <strong><?php echo date('H:i', strtotime($request['start_time'])); ?></strong>
                                        </td>
                                        <td>
                                            <?php
                                            $start = strtotime($request['start_time']);
                                            $end = strtotime($request['end_time']);
                                            $duration = ($end - $start) / 60;
                                            echo $duration . ' min';
                                            ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($request['purpose'] ?? 'N/A'); ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusColor = [
                                                'Pending' => 'warning',
                                                'Approved' => 'success',
                                                'Rejected' => 'danger',
                                                'Cancelled' => 'secondary'
                                            ];
                                            $color = $statusColor[$request['status']] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?php echo $color; ?>">
                                                <?php echo $request['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted"><?php echo formatDate($request['created_at']); ?></small>
                                        </td>
                                        <td>
                                            <a href="<?php echo APP_URL; ?>modules/reservations/view.php?id=<?php echo $request['reservation_id']; ?>" 
                                               class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if ($request['status'] === 'Pending'): ?>
                                                <a href="<?php echo APP_URL; ?>modules/reservations/approve.php?id=<?php echo $request['reservation_id']; ?>" 
                                                   class="btn btn-sm btn-success" title="Approve">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>modules/reservations/reject.php?id=<?php echo $request['reservation_id']; ?>" 
                                                   class="btn btn-sm btn-danger" title="Reject">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox"></i> No pending trainer time requests
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="d-flex justify-content-center p-3">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1&search=<?php echo urlencode($searchTerm); ?>&status=<?php echo urlencode($filterStatus); ?>">First</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchTerm); ?>&status=<?php echo urlencode($filterStatus); ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <li class="page-item active">
                                <span class="page-link"><?php echo $page; ?> / <?php echo $totalPages; ?></span>
                            </li>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchTerm); ?>&status=<?php echo urlencode($filterStatus); ?>">Next</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($searchTerm); ?>&status=<?php echo urlencode($filterStatus); ?>">Last</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>

        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
