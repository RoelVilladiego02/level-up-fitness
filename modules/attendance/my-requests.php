<?php
/**
 * Session Requests - My Requests
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

// Only members can access this page
if ($_SESSION['user_type'] !== 'member') {
    setMessage('Access denied: Only members can access this page.', 'error');
    redirect(APP_URL . 'dashboard/');
}

$requests = [];
$message = getMessage();
$filterStatus = $_GET['status'] ?? '';
$page = $_GET['page'] ?? 1;
$itemsPerPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $itemsPerPage;
$totalRecords = 0;
$totalPages = 1;

try {
    // Get member ID
    $memberStmt = $pdo->prepare("SELECT member_id FROM members WHERE user_id = ? AND status = 'Active'");
    $memberStmt->execute([$_SESSION['user_id']]);
    $memberData = $memberStmt->fetch();
    
    if (!$memberData) {
        setMessage('Your member account is not active or not found.', 'error');
        redirect(APP_URL . 'dashboard/');
    }

    $memberId = $memberData['member_id'];

    // Build query
    $query = "SELECT sr.*, t.trainer_name FROM session_requests sr
              JOIN trainers t ON sr.trainer_id = t.trainer_id
              WHERE sr.member_id = ?";
    $params = [$memberId];

    // Status filter
    if (!empty($filterStatus)) {
        $query .= " AND sr.status = ?";
        $params[] = $filterStatus;
    }

    // Get total records
    $countQuery = str_replace("SELECT sr.*,", "SELECT COUNT(*) as cnt", $query);
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
    $query .= " ORDER BY sr.created_at DESC LIMIT " . (int)$itemsPerPage . " OFFSET " . (int)$offset;

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $requests = $stmt->fetchAll();
} catch (Exception $e) {
    setMessage('Error loading session requests: ' . $e->getMessage(), 'error');
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/attendance/request.php" class="btn btn-primary btn-sm float-end">
                    <i class="fas fa-plus"></i> Request New Session
                </a>
                <h1><i class="fas fa-history"></i> My Session Requests</h1>
                <p>View and manage your training session requests</p>
            </div>

            <?php displayMessage(); ?>

            <!-- Status Filter -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="btn-group" role="group">
                                <a href="<?php echo APP_URL; ?>modules/attendance/my-requests.php" 
                                   class="btn btn-outline-primary <?php echo empty($filterStatus) ? 'active' : ''; ?>">
                                    All (<?php echo $totalRecords; ?>)
                                </a>
                                <a href="?status=Pending" 
                                   class="btn btn-outline-warning <?php echo $filterStatus === 'Pending' ? 'active' : ''; ?>">
                                    <i class="fas fa-clock"></i> Pending
                                </a>
                                <a href="?status=Approved" 
                                   class="btn btn-outline-success <?php echo $filterStatus === 'Approved' ? 'active' : ''; ?>">
                                    <i class="fas fa-check"></i> Approved
                                </a>
                                <a href="?status=Rejected" 
                                   class="btn btn-outline-danger <?php echo $filterStatus === 'Rejected' ? 'active' : ''; ?>">
                                    <i class="fas fa-times"></i> Rejected
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Requests Cards -->
            <?php if (count($requests) > 0): ?>
                <div class="row">
                    <?php foreach ($requests as $request): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 border-left">
                                <div class="card-header bg-light d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="card-title mb-0"><?php echo htmlspecialchars($request['purpose']); ?></h6>
                                        <small class="text-muted">With <?php echo htmlspecialchars($request['trainer_name']); ?></small>
                                    </div>
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
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <strong><i class="fas fa-calendar"></i> Date & Time</strong><br>
                                        <?php echo formatDate($request['requested_date']); ?> at <?php echo date('H:i', strtotime($request['requested_time'])); ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong><i class="fas fa-hourglass-half"></i> Duration</strong><br>
                                        <?php echo $request['duration']; ?> minutes
                                    </div>
                                    <?php if ($request['trainer_notes']): ?>
                                        <div class="mb-3">
                                            <strong><i class="fas fa-sticky-note"></i> Trainer Notes</strong><br>
                                            <p class="text-muted"><?php echo htmlspecialchars($request['trainer_notes']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                    <small class="text-muted">Requested on <?php echo formatDate($request['created_at']); ?></small>
                                </div>
                                <div class="card-footer bg-light">
                                    <a href="<?php echo APP_URL; ?>modules/attendance/view.php?id=<?php echo $request['request_id']; ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> View Details
                                    </a>
                                    <?php if ($request['status'] === 'Pending'): ?>
                                        <a href="<?php echo APP_URL; ?>modules/attendance/view.php?id=<?php echo $request['request_id']; ?>&action=cancel" 
                                           class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this request?');">
                                            <i class="fas fa-times"></i> Cancel
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="d-flex justify-content-center mt-4">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1<?php echo !empty($filterStatus) ? '&status=' . urlencode($filterStatus) : ''; ?>">First</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($filterStatus) ? '&status=' . urlencode($filterStatus) : ''; ?>">Previous</a>
                                </li>
                            <?php endif; ?>
                            
                            <li class="page-item active">
                                <span class="page-link"><?php echo $page; ?> / <?php echo $totalPages; ?></span>
                            </li>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($filterStatus) ? '&status=' . urlencode($filterStatus) : ''; ?>">Next</a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo !empty($filterStatus) ? '&status=' . urlencode($filterStatus) : ''; ?>">Last</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <?php if (!empty($filterStatus)): ?>
                        No <?php echo strtolower($filterStatus); ?> session requests.
                    <?php else: ?>
                        You haven't made any session requests yet.
                    <?php endif; ?>
                    <a href="<?php echo APP_URL; ?>modules/attendance/request.php">Request a session</a>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
