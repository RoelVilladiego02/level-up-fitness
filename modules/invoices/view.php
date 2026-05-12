<?php
/**
 * View Invoice Details
 * Level Up Fitness - Gym Management System
 * Admins can view any invoice, members can only view their own invoices
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$invoiceId = sanitize($_GET['id'] ?? '');
$invoice = null;
$payments = [];
$isAdmin = $_SESSION['user_type'] === 'admin';
$currentMemberId = null;

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

if (empty($invoiceId)) {
    setMessage('Invoice ID required', 'error');
    redirect(APP_URL . 'modules/invoices/');
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            i.*,
            m.member_name, m.email, m.contact_number,
            COALESCE(SUM(ip.amount), 0) as paid_amount,
            (i.amount - COALESCE(SUM(ip.amount), 0)) as outstanding_amount
        FROM invoices i
        JOIN members m ON i.member_id = m.member_id
        LEFT JOIN invoice_payments ip ON i.invoice_id = ip.invoice_id AND ip.payment_status = 'Paid'
        WHERE i.invoice_id = ?
        GROUP BY i.invoice_id
    ");
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch();
    
    if (!$invoice) {
        setMessage('Invoice not found', 'error');
        redirect(APP_URL . 'modules/invoices/');
    }
    
    // Check permission: admins can view any invoice, members can only view their own
    if (!$isAdmin && $invoice['member_id'] !== $currentMemberId) {
        setMessage('Access denied: You do not have permission to access this page.', 'error');
        redirect(APP_URL . 'modules/payments/');
    }
    
    // Get all payments for this invoice
    $paymentsStmt = $pdo->prepare("
        SELECT * FROM invoice_payments 
        WHERE invoice_id = ? 
        ORDER BY created_at DESC
    ");
    $paymentsStmt->execute([$invoiceId]);
    $payments = $paymentsStmt->fetchAll();
    
} catch (Exception $e) {
    setMessage('Error loading invoice: ' . $e->getMessage(), 'error');
    redirect(APP_URL . 'modules/invoices/');
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo $isAdmin ? APP_URL . 'modules/invoices/' : APP_URL . 'modules/payments/'; ?>" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-file-invoice-dollar"></i> Invoice Details</h1>
            </div>

            <?php displayMessage(); ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><?php echo htmlspecialchars($invoice['invoice_id']); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6>Member Information</h6>
                                    <p class="mb-1">
                                        <strong>Name:</strong> <?php echo htmlspecialchars($invoice['member_name']); ?>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Member ID:</strong> <?php echo htmlspecialchars($invoice['member_id']); ?>
                                    </p>
                                    <p class="mb-0">
                                        <strong>Email:</strong> <?php echo htmlspecialchars($invoice['email']); ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Invoice Information</h6>
                                    <p class="mb-1">
                                        <strong>Status:</strong> 
                                        <span class="badge bg-<?php 
                                            echo ($invoice['invoice_status'] === 'Paid') ? 'success' : 
                                                 (($invoice['invoice_status'] === 'Partially Paid') ? 'warning' : 'danger');
                                        ?>">
                                            <?php echo htmlspecialchars($invoice['invoice_status']); ?>
                                        </span>
                                    </p>
                                    <p class="mb-1">
                                        <strong>Created:</strong> <?php echo formatDate($invoice['created_at']); ?>
                                    </p>
                                    <p class="mb-0">
                                        <strong>Due Date:</strong> <?php echo formatDate($invoice['due_date']); ?>
                                    </p>
                                </div>
                            </div>

                            <hr>

                            <h6>Invoice Amount</h6>
                            <div class="row text-center">
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded">
                                        <p class="text-muted mb-1">Total Amount</p>
                                        <p class="h4 mb-0"><?php echo formatCurrency($invoice['amount']); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded">
                                        <p class="text-muted mb-1">Paid Amount</p>
                                        <p class="h4 mb-0 text-success"><?php echo formatCurrency($invoice['paid_amount']); ?></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="p-3 bg-light rounded">
                                        <p class="text-muted mb-1">Outstanding</p>
                                        <p class="h4 mb-0 text-danger"><?php echo formatCurrency($invoice['outstanding_amount']); ?></p>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <p><strong>Description:</strong> <?php echo htmlspecialchars($invoice['description']); ?></p>
                            <?php if (!empty($invoice['notes'])): ?>
                                <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($invoice['notes'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($payments)): ?>
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Payment History</h6>
                        </div>
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($p['payment_id']); ?></td>
                                    <td><?php echo formatCurrency($p['amount']); ?></td>
                                    <td><?php echo htmlspecialchars($p['payment_method']); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo ($p['payment_status'] === 'Paid') ? 'success' : 'warning'; ?>">
                                            <?php echo htmlspecialchars($p['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo formatDate($p['payment_date'] ?? $p['created_at']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">No payments recorded yet.</div>
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Actions</h6>
                        </div>
                        <div class="card-body">
                            <?php if ($invoice['outstanding_amount'] > 0): ?>
                            <a href="<?php echo APP_URL; ?>modules/payments/add.php" class="btn btn-sm btn-primary w-100 mb-2">
                                <i class="fas fa-plus"></i> Record Payment
                            </a>
                            <?php endif; ?>
                            <a href="<?php echo APP_URL; ?>modules/invoices/" class="btn btn-sm btn-secondary w-100">
                                <i class="fas fa-list"></i> View All
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
