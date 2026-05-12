<?php
/**
 * View Invoices - For Members and Admins
 * Level Up Fitness - Gym Management System
 * Admins can view all invoices, members can view their own invoices
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$invoices = [];
$isAdmin = $_SESSION['user_type'] === 'admin';
$currentMemberId = null;

// Pagination setup
$itemsPerPage = 10;
$page = (int)($_GET['page'] ?? 1);
if ($page < 1) $page = 1;
$offset = ($page - 1) * $itemsPerPage;

// Get member ID if user is a member
if (!$isAdmin && $_SESSION['user_type'] === 'member') {
    try {
        $memberStmt = $pdo->prepare("SELECT member_id FROM members WHERE user_id = ?");
        $memberStmt->execute([$_SESSION['user_id']]);
        $member = $memberStmt->fetch();
        $currentMemberId = $member['member_id'] ?? null;
        
        if (!$currentMemberId) {
            setMessage('Member profile not found', 'error');
            redirect(APP_URL . 'dashboard/');
        }
    } catch (Exception $e) {
        setMessage('Error retrieving member information', 'error');
        redirect(APP_URL . 'dashboard/');
    }
}

try {
    if ($isAdmin) {
        // ADMIN: Count total invoices
        $countStmt = $pdo->prepare("SELECT COUNT(*) as count FROM invoices");
        $countStmt->execute();
        $totalInvoices = $countStmt->fetch()['count'];
        
        // ADMIN: Show all invoices
        $stmt = $pdo->prepare("
            SELECT 
                i.*,
                m.member_name,
                COALESCE(SUM(ip.amount), 0) as paid_amount,
                (i.amount - COALESCE(SUM(ip.amount), 0)) as outstanding_amount
            FROM invoices i
            JOIN members m ON i.member_id = m.member_id
            LEFT JOIN invoice_payments ip ON i.invoice_id = ip.invoice_id AND ip.payment_status = 'Paid'
            GROUP BY i.invoice_id
            ORDER BY i.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        // MEMBER: Count total invoices
        $countStmt = $pdo->prepare("SELECT COUNT(*) as count FROM invoices WHERE member_id = ?");
        $countStmt->execute([$currentMemberId]);
        $totalInvoices = $countStmt->fetch()['count'];
        
        // MEMBER: Show only their own invoices
        $stmt = $pdo->prepare("
            SELECT 
                i.*,
                m.member_name,
                COALESCE(SUM(ip.amount), 0) as paid_amount,
                (i.amount - COALESCE(SUM(ip.amount), 0)) as outstanding_amount
            FROM invoices i
            JOIN members m ON i.member_id = m.member_id
            LEFT JOIN invoice_payments ip ON i.invoice_id = ip.invoice_id AND ip.payment_status = 'Paid'
            WHERE i.member_id = ?
            GROUP BY i.invoice_id
            ORDER BY i.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute([$currentMemberId]);
    }
    $invoices = $stmt->fetchAll();
    $totalPages = ceil($totalInvoices / $itemsPerPage);
} catch (Exception $e) {
    setMessage('Error loading invoices: ' . $e->getMessage(), 'error');
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <?php if ($isAdmin): ?>
                <a href="<?php echo APP_URL; ?>modules/invoices/create.php" class="btn btn-primary btn-sm float-end">
                    <i class="fas fa-plus"></i> Create Invoice
                </a>
                <h1><i class="fas fa-file-invoice-dollar"></i> Invoices</h1>
                <p>Manage and create invoices for members</p>
                <?php else: ?>
                <h1><i class="fas fa-file-invoice-dollar"></i> My Invoices</h1>
                <p>View all invoices issued to you</p>
                <?php endif; ?>
            </div>

            <?php displayMessage(); ?>

            <?php if (empty($invoices)): ?>
                <div class="alert alert-info">
                    <?php if ($isAdmin): ?>
                        No invoices yet. <a href="<?php echo APP_URL; ?>modules/invoices/create.php">Create one</a>
                    <?php else: ?>
                        <i class="fas fa-check-circle"></i> No invoices. Your account is all paid up!
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="card">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Invoice ID</th>
                                <?php if ($isAdmin): ?>
                                <th>Member</th>
                                <?php endif; ?>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Due Date</th>
                                <th>Outstanding</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($invoices as $inv): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($inv['invoice_id']); ?></strong></td>
                                <?php if ($isAdmin): ?>
                                <td><?php echo htmlspecialchars($inv['member_name']); ?></td>
                                <?php endif; ?>
                                <td><?php echo htmlspecialchars($inv['description']); ?></td>
                                <td><?php echo formatCurrency($inv['amount']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo ($inv['invoice_status'] === 'Paid') ? 'success' : 
                                             (($inv['invoice_status'] === 'Partially Paid') ? 'warning' : 'danger');
                                    ?>">
                                        <?php echo htmlspecialchars($inv['invoice_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($inv['due_date']); ?></td>
                                <td><strong><?php echo formatCurrency($inv['outstanding_amount']); ?></strong></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-info" onclick="viewInvoice('<?php echo htmlspecialchars($inv['invoice_id']); ?>')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php if ($isAdmin): ?>
                                    <a href="<?php echo APP_URL; ?>modules/invoices/edit.php?id=<?php echo urlencode($inv['invoice_id']); ?>" 
                                       class="btn btn-sm btn-warning"><i class="fas fa-edit"></i></a>
                                    <a href="<?php echo APP_URL; ?>modules/invoices/delete.php?id=<?php echo urlencode($inv['invoice_id']); ?>" 
                                       class="btn btn-sm btn-danger btn-delete"><i class="fas fa-trash"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation" class="mt-3">
                        <ul class="pagination justify-content-center mb-3">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1">First</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                            </li>
                            <?php endif; ?>

                            <?php 
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            if ($startPage > 1): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>

                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <?php if ($endPage < $totalPages): ?>
                                <li class="page-item disabled">
                                    <span class="page-link">...</span>
                                </li>
                            <?php endif; ?>

                            <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $totalPages; ?>">Last</a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                    <div class="text-center text-muted small mb-3">
                        Showing <?php echo (($page - 1) * $itemsPerPage) + 1; ?> - <?php echo min($page * $itemsPerPage, $totalInvoices); ?> of <?php echo $totalInvoices; ?> invoices (Page <?php echo $page; ?> of <?php echo $totalPages; ?>)
                    </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<!-- Invoice Details Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="invoiceTitle">Invoice Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="invoiceDetails">
                <div class="text-center"><span class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></span></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" onclick="printInvoice()">
                    <i class="fas fa-print"></i> Print
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
async function viewInvoice(invoiceId) {
    const modal = new bootstrap.Modal(document.getElementById('invoiceModal'));
    const detailsDiv = document.getElementById('invoiceDetails');
    
    detailsDiv.innerHTML = '<div class="text-center"><span class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></span></div>';
    modal.show();
    
    try {
        const url = window.APP_URL + 'api/invoices/get-invoice-details.php?id=' + encodeURIComponent(invoiceId);
        console.log('Fetching invoice from:', url);
        
        const response = await fetch(url);
        const text = await response.text();
        
        console.log('Response status:', response.status);
        console.log('Response text:', text);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${text}`);
        }
        
        const data = JSON.parse(text);
        
        if (data.success) {
            detailsDiv.innerHTML = data.html;
            window.currentInvoiceId = invoiceId;
        } else {
            detailsDiv.innerHTML = '<div class="alert alert-danger"><strong>Error:</strong> ' + (data.error || 'Unknown error') + '</div>';
        }
    } catch (error) {
        console.error('Invoice fetch error:', error);
        detailsDiv.innerHTML = '<div class="alert alert-danger"><strong>Error loading invoice:</strong> ' + error.message + '</div>';
    }
}

function printInvoice() {
    const detailsDiv = document.getElementById('invoiceDetails');
    const invoiceContent = detailsDiv.innerHTML;
    const printWindow = window.open('', '', 'height=600,width=800');
    
    printWindow.document.write('<html><head><title>Invoice</title>');
    printWindow.document.write('<style>');
    printWindow.document.write('body { font-family: Arial, sans-serif; margin: 20px; }');
    printWindow.document.write('.row { display: flex; margin-bottom: 15px; }');
    printWindow.document.write('.col-md-6 { flex: 1; margin-right: 15px; }');
    printWindow.document.write('.col-md-4 { flex: 0 0 calc(33.333% - 10px); margin-right: 15px; }');
    printWindow.document.write('.col-md-4:last-child { margin-right: 0; }');
    printWindow.document.write('h6 { color: #666; font-size: 12px; text-transform: uppercase; margin: 0 0 5px 0; }');
    printWindow.document.write('p { margin: 0 0 10px 0; }');
    printWindow.document.write('.card { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; background: #f9f9f9; }');
    printWindow.document.write('.card-body { padding: 0; }');
    printWindow.document.write('.text-center { text-align: center; }');
    printWindow.document.write('.table { width: 100%; border-collapse: collapse; margin-top: 15px; }');
    printWindow.document.write('.table th, .table td { padding: 10px; border: 1px solid #ddd; text-align: left; }');
    printWindow.document.write('.table th { background: #f5f5f5; font-weight: bold; }');
    printWindow.document.write('.badge { display: inline-block; padding: 4px 8px; border-radius: 3px; font-size: 12px; font-weight: bold; }');
    printWindow.document.write('.badge.bg-success { background-color: #28a745; color: white; }');
    printWindow.document.write('.badge.bg-warning { background-color: #ffc107; color: black; }');
    printWindow.document.write('.badge.bg-danger { background-color: #dc3545; color: white; }');
    printWindow.document.write('.badge.bg-secondary { background-color: #6c757d; color: white; }');
    printWindow.document.write('code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-size: 12px; }');
    printWindow.document.write('.alert { padding: 15px; margin-bottom: 15px; border: 1px solid; border-radius: 3px; }');
    printWindow.document.write('.alert-danger { border-color: #f8d7da; background: #f8d7da; color: #721c24; }');
    printWindow.document.write('h4 { margin: 0; }');
    printWindow.document.write('</style></head><body>');
    printWindow.document.write(invoiceContent);
    printWindow.document.write('</body></html>');
    printWindow.document.close();
    
    setTimeout(() => {
        printWindow.print();
    }, 250);
}
</script>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
