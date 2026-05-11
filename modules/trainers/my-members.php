<?php
/**
 * My Members - Trainers View Their Assigned Members
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole(['trainer', 'admin']);

$trainerId = null;
$members = [];
$searchQuery = $_GET['search'] ?? '';
$currentPage = (int)($_GET['page'] ?? 1);
$itemsPerPage = ITEMS_PER_PAGE ?? 10;

try {
    // Get trainer ID
    if ($_SESSION['user_type'] === 'trainer') {
        $trainerStmt = $pdo->prepare("SELECT trainer_id FROM trainers WHERE user_id = ? LIMIT 1");
        $trainerStmt->execute([$_SESSION['user_id']]);
        $trainerData = $trainerStmt->fetch();
        $trainerId = $trainerData['trainer_id'] ?? null;
        
        if (!$trainerId) {
            setMessage('Trainer profile not found', 'error');
        }
    } else if ($_SESSION['user_type'] === 'admin') {
        // Admin can specify trainer ID
        $trainerId = sanitize($_GET['trainer_id'] ?? '');
        if (empty($trainerId)) {
            setMessage('Please select a trainer', 'error');
        }
    }

    if ($trainerId) {
        // Build search query
        $baseQuery = "
            SELECT DISTINCT m.member_id, m.member_name, m.email, m.contact_number, m.status, m.join_date
            FROM members m
            WHERE m.trainer_id = ?
        ";
        
        $params = [$trainerId];
        
        // Add search filters
        if (!empty($searchQuery)) {
            $baseQuery .= " AND (m.member_name LIKE ? OR m.email LIKE ? OR m.contact_number LIKE ?)";
            $searchTerm = "%{$searchQuery}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        // Get total count
        $countQuery = "SELECT COUNT(DISTINCT m.member_id) as cnt FROM members m WHERE m.trainer_id = ?";
        $countParams = [$trainerId];
        
        if (!empty($searchQuery)) {
            $countQuery .= " AND (m.member_name LIKE ? OR m.email LIKE ? OR m.contact_number LIKE ?)";
            $countParams[] = "%{$searchQuery}%";
            $countParams[] = "%{$searchQuery}%";
            $countParams[] = "%{$searchQuery}%";
        }
        
        $countStmt = $pdo->prepare($countQuery);
        $countStmt->execute($countParams);
        $countResult = $countStmt->fetch();
        $totalRecords = $countResult['cnt'];
        $totalPages = ceil($totalRecords / $itemsPerPage);
        
        // Ensure valid page
        if ($currentPage > $totalPages && $totalPages > 0) {
            $currentPage = $totalPages;
        }
        $offset = ($currentPage - 1) * $itemsPerPage;

        // Get members
        $baseQuery .= " ORDER BY m.member_name ASC LIMIT " . (int)$itemsPerPage . " OFFSET " . (int)$offset;
        $stmt = $pdo->prepare($baseQuery);
        $stmt->execute($params);
        $members = $stmt->fetchAll();
    }

} catch (Exception $e) {
    setMessage('Error loading members: ' . $e->getMessage(), 'error');
    error_log('Error: ' . $e->getMessage());
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <h1><i class="fas fa-users"></i> My Assigned Members</h1>
                <p>View and manage all your members</p>
            </div>

            <?php displayMessage(); ?>

            <!-- Search Box -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="form-inline d-flex gap-2">
                        <div class="flex-grow-1">
                            <input type="text" name="search" class="form-control w-100" 
                                   placeholder="Search by name, email, or phone..." 
                                   value="<?php echo htmlspecialchars($searchQuery); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Search
                        </button>
                        <?php if (!empty($searchQuery)): ?>
                            <a href="<?php echo APP_URL; ?>modules/trainers/my-members.php" class="btn btn-light">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Members List -->
            <?php if (count($members) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Member Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($members as $member): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($member['member_name']); ?></strong>
                                    </td>
                                    <td>
                                        <a href="mailto:<?php echo htmlspecialchars($member['email']); ?>">
                                            <?php echo htmlspecialchars($member['email']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php echo !empty($member['contact_number']) ? htmlspecialchars($member['contact_number']) : '<span class="text-muted">—</span>'; ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $statusColor = $member['status'] === 'Active' ? 'success' : 'warning';
                                            $statusIcon = $member['status'] === 'Active' ? 'check-circle' : 'clock';
                                        ?>
                                        <span class="badge bg-<?php echo $statusColor; ?>">
                                            <i class="fas fa-<?php echo $statusIcon; ?>"></i>
                                            <?php echo htmlspecialchars($member['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y', strtotime($member['join_date'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?php echo APP_URL; ?>modules/members/view.php?id=<?php echo htmlspecialchars($member['member_id']); ?>" 
                                               class="btn btn-outline-primary" title="View Profile">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>modules/workouts/add.php?member_id=<?php echo htmlspecialchars($member['member_id']); ?>" 
                                               class="btn btn-outline-success" title="Create Workout Plan">
                                                <i class="fas fa-plus"></i>
                                            </a>
                                            <a href="<?php echo APP_URL; ?>modules/workouts/?member_id=<?php echo htmlspecialchars($member['member_id']); ?>" 
                                               class="btn btn-outline-info" title="View Workout Plans">
                                                <i class="fas fa-list"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <nav class="mt-4" aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($currentPage > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=1<?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>">
                                        <i class="fas fa-chevron-left"></i> First
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>">
                                        Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <?php 
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($totalPages, $currentPage + 2);
                            
                            for ($i = $startPage; $i <= $endPage; $i++): 
                            ?>
                                <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($currentPage < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>">
                                        Next
                                    </a>
                                </li>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo !empty($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>">
                                        Last <i class="fas fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i>
                    <?php echo !empty($searchQuery) ? 'No members found matching your search.' : 'You have no assigned members yet.'; ?>
                </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
