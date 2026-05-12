<?php
/**
 * Unified Payments Management - Pay & History
 * Level Up Fitness - Gym Management System
 * 
 * Combines invoice payment interface with payment history
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

$payments = [];
$message = getMessage();
$searchTerm = $_GET['search'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$page = $_GET['page'] ?? 1;
$itemsPerPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $itemsPerPage;
$totalRecords = 0;
$totalPages = 1;
$totalAmount = 0;

// Determine if viewing as member or admin
$isAdmin = $_SESSION['user_type'] === 'admin';
$currentMemberId = null;
$member = null;
$outstandingBalance = null;
$pendingInvoices = [];
$errors = [];

// Get current member/user info
if (!$isAdmin && $_SESSION['user_type'] === 'member') {
    try {
        $memberStmt = $pdo->prepare("SELECT member_id, member_name, email FROM members WHERE user_id = ?");
        $memberStmt->execute([$_SESSION['user_id']]);
        $member = $memberStmt->fetch();
        $currentMemberId = $member['member_id'] ?? null;
        
        if (!$currentMemberId) {
            setMessage('Member profile not found', 'error');
            redirect(APP_URL . 'dashboard/');
        }
        
        // Get outstanding balance and pending invoices
        $outstandingBalance = getMemberOutstandingBalance($currentMemberId);
        $pendingInvoices = getMemberPendingInvoices($currentMemberId);
        
    } catch (Exception $e) {
        setMessage('Error retrieving member information: ' . $e->getMessage(), 'error');
    }
}

// Handle payment form submission (members only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isAdmin) {
    $invoiceId = sanitize($_POST['invoice_id'] ?? '');
    $amountToPay = floatval($_POST['amount_to_pay'] ?? 0);
    
    // Validation
    if (empty($invoiceId)) {
        $errors['invoice_id'] = 'Please select an invoice to pay';
    }
    
    if ($amountToPay <= 0) {
        $errors['amount_to_pay'] = 'Payment amount must be greater than 0';
    }
    
    if (!empty($invoiceId) && $amountToPay > 0) {
        // Get invoice details for validation
        $invoiceData = getInvoiceDetails($invoiceId);
        
        if (!$invoiceData || $invoiceData['member_id'] !== $currentMemberId) {
            $errors['invoice_id'] = 'Invalid invoice selected';
        } elseif ($amountToPay > $invoiceData['outstanding_amount']) {
            $errors['amount_to_pay'] = 'Payment amount exceeds outstanding balance of ' . formatCurrency($invoiceData['outstanding_amount']);
        }
    }
    
    // If no errors, process payment via Maya
    if (empty($errors)) {
        try {
            // Create payment record
            $paymentId = recordInvoicePayment(
                $invoiceId,
                $amountToPay,
                'Maya',
                null,
                'Pending'
            );
            
            if ($paymentId) {
                // Redirect to Maya checkout
                $checkoutUrl = APP_URL . 'payment/checkout.php?' . http_build_query([
                    'payment_id' => $paymentId,
                    'invoice_id' => $invoiceId,
                    'member_id' => $currentMemberId,
                    'gateway' => 'maya',
                    'amount' => $amountToPay,
                    'description' => 'Invoice Payment #' . $invoiceId
                ]);
                
                logAction(
                    $_SESSION['user_id'],
                    'INITIATE_PAYMENT',
                    'Invoices',
                    'Member initiated payment of ' . formatCurrency($amountToPay) . ' for invoice ' . $invoiceId
                );
                
                redirect($checkoutUrl);
            } else {
                $errors['payment'] = 'Failed to create payment record';
            }
        } catch (Exception $e) {
            error_log('Payment processing error: ' . $e->getMessage());
            $errors['payment'] = 'Payment processing error: ' . $e->getMessage();
        }
    }
}

// Load payment history for display
try {
    // Build query - for members and admins, show invoice payments (unified data source)
    $query = "
        SELECT 
            ip.payment_id, ip.invoice_id, ip.amount,
            ip.payment_method, ip.payment_status, ip.payment_date,
            i.description, i.due_date, i.amount as invoice_amount,
            m.member_id, m.member_name,
            'Invoice' as payment_type
        FROM invoice_payments ip
        JOIN invoices i ON ip.invoice_id = i.invoice_id
        JOIN members m ON i.member_id = m.member_id
        WHERE 1=1
    ";
    $params = [];
    
    // For members, filter to only their payments
    if (!$isAdmin && $currentMemberId) {
        $query .= " AND i.member_id = ?";
        $params[] = $currentMemberId;
    }
    
    // Search filter
    if (!empty($searchTerm)) {
        $query .= " AND (LOWER(ip.payment_id) LIKE ? OR LOWER(ip.invoice_id) LIKE ? OR LOWER(m.member_name) LIKE ?)";
        $search = "%".strtolower($searchTerm)."%";
        $params = array_merge($params, [$search, $search, $search]);
    }
    
    // Status filter
    if (!empty($filterStatus)) {
        $query .= " AND ip.payment_status = ?";
        $params[] = $filterStatus;
    }
    
    // Get total count and sum
    $countStmt = $pdo->prepare(str_replace(
        'SELECT ip.payment_id, ip.invoice_id, ip.amount, ip.payment_method, ip.payment_status, ip.payment_date, i.description, i.due_date, i.amount as invoice_amount, m.member_id, m.member_name, \'Invoice\' as payment_type',
        'SELECT COUNT(*) as total, SUM(ip.amount) as total_amount',
        $query
    ));
    $countStmt->execute($params);
    $result = $countStmt->fetch();
    $totalRecords = $result['total'] ?? 0;
    $totalAmount = $result['total_amount'] ?? 0;
    $totalPages = ceil($totalRecords / $itemsPerPage);

    // Get paginated results
    $query .= " ORDER BY ip.payment_date DESC LIMIT " . (int)$itemsPerPage . " OFFSET " . (int)$offset;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $payments = $stmt->fetchAll();

} catch (Exception $e) {
    setMessage('Error loading payments: ' . $e->getMessage(), 'error');
}

// Check for success message from payment gateway
$paymentSuccess = isset($_GET['success']);
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <h1><i class="fas fa-credit-card"></i> <?php echo $isAdmin ? 'Payments Management' : 'Payments'; ?></h1>
                <p><?php echo $isAdmin ? 'Track and manage member payments' : 'Pay invoices and view payment history'; ?></p>
            </div>

            <?php displayMessage(); ?>

            <!-- ADMIN FINANCE MANAGEMENT SECTION -->
            <?php if ($isAdmin): ?>
            
            <!-- Quick Actions -->
            <div class="row mb-4">
                <div class="col-lg-3">
                    <div class="card border-left-info shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-info">
                                <i class="fas fa-receipt"></i> Invoices
                            </h6>
                            <?php 
                            $invoiceCountStmt = $pdo->prepare("SELECT COUNT(*) as count FROM invoices WHERE invoice_status != 'Cancelled'");
                            $invoiceCountStmt->execute();
                            echo '<h3>' . $invoiceCountStmt->fetch()['count'] . '</h3>';
                            ?>
                            <small class="text-muted">Total active invoices</small>
                            <div class="mt-2">
                                <a href="<?php echo APP_URL; ?>modules/invoices/" class="btn btn-sm btn-outline-info">
                                    Manage Invoices
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="card border-left-warning shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-warning">
                                <i class="fas fa-hourglass-half"></i> Pending
                            </h6>
                            <?php 
                            $pendingStmt = $pdo->prepare("
                                SELECT COUNT(*) as count FROM (
                                    SELECT i.invoice_id, i.amount, COALESCE(SUM(ip.amount), 0) as paid_amount
                                    FROM invoices i
                                    LEFT JOIN invoice_payments ip ON i.invoice_id = ip.invoice_id AND ip.payment_status = 'Paid'
                                    WHERE i.invoice_status != 'Cancelled'
                                    GROUP BY i.invoice_id
                                    HAVING i.amount > paid_amount
                                ) as pending_count
                            ");
                            $pendingStmt->execute();
                            echo '<h3>' . $pendingStmt->fetch()['count'] . '</h3>';
                            ?>
                            <small class="text-muted">Unpaid invoices</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="card border-left-success shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-success">
                                <i class="fas fa-check-circle"></i> Paid
                            </h6>
                            <?php 
                            $paidInvoiceStmt = $pdo->prepare("
                                SELECT COUNT(DISTINCT i.invoice_id) as count FROM invoices i
                                LEFT JOIN invoice_payments ip ON i.invoice_id = ip.invoice_id AND ip.payment_status = 'Paid'
                                WHERE i.invoice_status = 'Paid'
                            ");
                            $paidInvoiceStmt->execute();
                            echo '<h3>' . $paidInvoiceStmt->fetch()['count'] . '</h3>';
                            ?>
                            <small class="text-muted">Fully paid invoices</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3">
                    <div class="card border-left-primary shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-primary">
                                <i class="fas fa-plus"></i> Record Payment
                            </h6>
                            <small class="text-muted">Cash, check, or other methods</small>
                            <div class="mt-3">
                                <a href="<?php echo APP_URL; ?>modules/payments/add.php" class="btn btn-sm btn-primary">
                                    <i class="fas fa-hand-holding-usd"></i> Manual Payment
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Invoices Awaiting Payment Collection -->
            <div class="card mb-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-exclamation-circle"></i> Invoices Awaiting Payment</h5>
                </div>
                <div class="card-body">
                    <?php 
                    try {
                        $awaitingStmt = $pdo->prepare("
                            SELECT 
                                i.invoice_id, i.description, i.amount, i.due_date, i.created_at,
                                m.member_id, m.member_name,
                                COALESCE(SUM(ip.amount), 0) as paid_amount,
                                (i.amount - COALESCE(SUM(ip.amount), 0)) as outstanding_amount
                            FROM invoices i
                            JOIN members m ON i.member_id = m.member_id
                            LEFT JOIN invoice_payments ip ON i.invoice_id = ip.invoice_id AND ip.payment_status = 'Paid'
                            WHERE i.invoice_status != 'Cancelled'
                            GROUP BY i.invoice_id
                            HAVING (i.amount - COALESCE(SUM(ip.amount), 0)) > 0
                            ORDER BY i.due_date ASC
                            LIMIT 10
                        ");
                        $awaitingStmt->execute();
                        $awaitingInvoices = $awaitingStmt->fetchAll();
                        
                        if (!empty($awaitingInvoices)):
                    ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Invoice ID</th>
                                    <th>Member</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Outstanding</th>
                                    <th>Due Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($awaitingInvoices as $inv): 
                                    $isOverdue = strtotime($inv['due_date']) < time();
                                ?>
                                <tr <?php echo $isOverdue ? 'class="table-danger"' : ''; ?>>
                                    <td><code><?php echo $inv['invoice_id']; ?></code></td>
                                    <td><?php echo htmlspecialchars($inv['member_name']); ?></td>
                                    <td><?php echo htmlspecialchars($inv['description']); ?></td>
                                    <td><?php echo formatCurrency($inv['amount']); ?></td>
                                    <td><strong><?php echo formatCurrency($inv['outstanding_amount']); ?></strong></td>
                                    <td>
                                        <?php echo formatDate($inv['due_date']); ?>
                                        <?php if ($isOverdue): ?>
                                            <br><small class="badge bg-danger">OVERDUE</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo APP_URL; ?>modules/invoices/edit.php?id=<?php echo $inv['invoice_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary" title="Edit Invoice">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?php echo APP_URL; ?>modules/payments/add.php" 
                                           class="btn btn-sm btn-success" title="Record Payment">
                                            <i class="fas fa-plus"></i> Record Payment
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted mb-0">
                        <i class="fas fa-check-circle"></i> No invoices awaiting payment!
                    </p>
                    <?php endif; 
                    } catch (Exception $e) {
                        echo '<div class="alert alert-danger">Error loading awaiting invoices: ' . htmlspecialchars($e->getMessage()) . '</div>';
                    }
                    ?>
                </div>
            </div>

            <?php endif; ?>
            <!-- END ADMIN FINANCE MANAGEMENT SECTION -->

            <!-- MEMBER PAYMENT SECTION -->
            <?php if (!$isAdmin): ?>
            
            <!-- Outstanding Balance Summary -->
            <?php if ($outstandingBalance && $outstandingBalance['outstanding_amount'] > 0): ?>
            <div class="row mb-4">
                <div class="col-lg-4 mb-3">
                    <div class="card border-left-primary shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-primary">
                                <i class="fas fa-exclamation-circle"></i> Total Due
                            </h6>
                            <h3 class="text-danger"><?php echo formatCurrency($outstandingBalance['outstanding_amount']); ?></h3>
                            <small class="text-muted">Amount you need to pay</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="card border-left-success shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-success">
                                <i class="fas fa-check-circle"></i> Already Paid
                            </h6>
                            <h3><?php echo formatCurrency($outstandingBalance['paid_amount']); ?></h3>
                            <small class="text-muted">Total payments received</small>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mb-3">
                    <div class="card border-left-info shadow-sm">
                        <div class="card-body">
                            <h6 class="card-title text-info">
                                <i class="fas fa-file-invoice"></i> Total Billed
                            </h6>
                            <h3><?php echo formatCurrency($outstandingBalance['total_owed']); ?></h3>
                            <small class="text-muted">All invoices issued</small>
                        </div>
                    </div>
                </div>
            </div>
            <?php elseif ($outstandingBalance): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <strong>All paid up!</strong> You don't have any outstanding invoices.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Payment Form -->
            <?php if (!empty($pendingInvoices) || !empty($errors)): ?>
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-credit-card"></i> Make a Payment</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger" role="alert">
                            <strong>Please fix the following errors:</strong>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="needs-validation">
                        <div class="row">
                            <!-- Select Invoice -->
                            <div class="col-md-6 mb-3">
                                <label for="invoiceSelect" class="form-label">
                                    <strong>Select Invoice</strong>
                                </label>
                                <select class="form-control <?php echo isset($errors['invoice_id']) ? 'is-invalid' : ''; ?>" 
                                        id="invoiceSelect" name="invoice_id" required onchange="updatePaymentAmount()">
                                    <option value="">-- Choose an invoice --</option>
                                    <?php foreach ($pendingInvoices as $invoice): ?>
                                        <option value="<?php echo $invoice['invoice_id']; ?>" 
                                                data-outstanding="<?php echo $invoice['outstanding_amount']; ?>"
                                                data-description="<?php echo htmlspecialchars($invoice['description']); ?>">
                                            <?php echo $invoice['invoice_id']; ?> - <?php echo htmlspecialchars($invoice['description']); ?> 
                                            (Due: <?php echo formatDate($invoice['due_date']); ?>) 
                                            - <?php echo formatCurrency($invoice['outstanding_amount']); ?> outstanding
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors['invoice_id'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['invoice_id']; ?></div>
                                <?php endif; ?>
                            </div>

                            <!-- Amount to Pay -->
                            <div class="col-md-6 mb-3">
                                <label for="amountToPay" class="form-label">
                                    <strong>Amount to Pay</strong>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control <?php echo isset($errors['amount_to_pay']) ? 'is-invalid' : ''; ?>" 
                                           id="amountToPay" name="amount_to_pay" step="0.01" min="0" 
                                           placeholder="0.00" required>
                                    <button class="btn btn-outline-secondary" type="button" id="payFullBtn">
                                        Pay Full Amount
                                    </button>
                                </div>
                                <small class="form-text text-muted">
                                    Outstanding: <span id="outstandingAmount">₱0.00</span>
                                </small>
                                <?php if (isset($errors['amount_to_pay'])): ?>
                                    <div class="invalid-feedback"><?php echo $errors['amount_to_pay']; ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Payment Method Info -->
                        <div class="alert alert-info mb-3">
                            <strong><i class="fas fa-info-circle"></i> Maya Payment</strong><br>
                            Secure online payment via Maya. You'll be redirected to the Maya payment gateway to complete the transaction.
                        </div>

                        <!-- Submit Button -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-lock"></i> Proceed to Payment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Pending Invoices Table (for Reference) -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-list-check"></i> Outstanding Invoices</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($pendingInvoices)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Invoice ID</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Due Date</th>
                                        <th>Outstanding</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingInvoices as $invoice): 
                                        $isOverdue = strtotime($invoice['due_date']) < time();
                                    ?>
                                        <tr <?php echo $isOverdue ? 'class="table-danger"' : ''; ?>>
                                            <td>
                                                <strong><?php echo $invoice['invoice_id']; ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($invoice['description']); ?></td>
                                            <td><?php echo formatCurrency($invoice['amount']); ?></td>
                                            <td>
                                                <?php echo formatDate($invoice['due_date']); ?>
                                                <?php if ($isOverdue): ?>
                                                    <br><small class="badge bg-danger">OVERDUE</small>
                                                <?php endif; ?>
                                            </td>
                                            <td><strong><?php echo formatCurrency($invoice['outstanding_amount']); ?></strong></td>
                                            <td>
                                                <?php
                                                if ($invoice['outstanding_amount'] == 0) {
                                                    echo '<span class="badge bg-success">Paid</span>';
                                                } elseif ($invoice['paid_amount'] > 0) {
                                                    echo '<span class="badge bg-warning">Partially Paid</span>';
                                                } elseif ($isOverdue) {
                                                    echo '<span class="badge bg-danger">Overdue</span>';
                                                } else {
                                                    echo '<span class="badge bg-info">Pending</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <a href="#" class="btn btn-sm btn-outline-primary" 
                                                   onclick="selectInvoice('<?php echo $invoice['invoice_id']; ?>', <?php echo $invoice['outstanding_amount']; ?>); return false;">
                                                    Pay Now
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">
                            <i class="fas fa-check-circle"></i> No outstanding invoices. Your account is all paid up!
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Divider -->
            <hr class="my-5">

            <?php endif; ?>
            <!-- END MEMBER PAYMENT SECTION -->

            <!-- PAYMENT HISTORY SECTION -->
            <div class="card">
                <div class="card-header bg-light">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <h5 class="mb-0"><i class="fas fa-history"></i> Payment History</h5>
                        </div>
                        <div class="col-md-6">
                            <form method="GET" class="d-flex gap-2">
                                <input type="text" name="search" class="form-control form-control-sm" 
                                       placeholder="Search..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">All Statuses</option>
                                    <option value="Paid" <?php echo $filterStatus === 'Paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="Pending" <?php echo $filterStatus === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="Failed" <?php echo $filterStatus === 'Failed' ? 'selected' : ''; ?>>Failed</option>
                                </select>
                                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body">
                                    <h6 class="card-title mb-0"><i class="fas fa-list"></i> Total Payments</h6>
                                    <h3><?php echo $totalRecords; ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body">
                                    <h6 class="card-title mb-0"><i class="fas fa-money-bill"></i> Total Amount</h6>
                                    <h3><?php echo formatCurrency($totalAmount); ?></h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-info text-white">
                                <div class="card-body">
                                    <h6 class="card-title mb-0"><i class="fas fa-check-circle"></i> Paid</h6>
                                    <h3>
                                        <?php 
                                        if (!$isAdmin && $currentMemberId) {
                                            $paidStmt = $pdo->prepare("
                                                SELECT COUNT(*) as count FROM invoice_payments ip
                                                JOIN invoices i ON ip.invoice_id = i.invoice_id
                                                WHERE i.member_id = ? AND ip.payment_status = 'Paid'
                                            ");
                                            $paidStmt->execute([$currentMemberId]);
                                        } else {
                                            $paidStmt = $pdo->prepare("SELECT COUNT(DISTINCT invoice_id) as count FROM invoice_payments WHERE payment_status = 'Paid'");
                                            $paidStmt->execute();
                                        }
                                        echo $paidStmt->fetch()['count'];
                                        ?>
                                    </h3>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body">
                                    <h6 class="card-title mb-0"><i class="fas fa-clock"></i> Pending</h6>
                                    <h3>
                                        <?php 
                                        if (!$isAdmin && $currentMemberId) {
                                            $pendingStmt = $pdo->prepare("
                                                SELECT COUNT(*) as count FROM (
                                                    SELECT i.invoice_id, i.amount, COALESCE(SUM(ip.amount), 0) as paid_amount
                                                    FROM invoices i
                                                    LEFT JOIN invoice_payments ip ON i.invoice_id = ip.invoice_id AND ip.payment_status = 'Paid'
                                                    WHERE i.member_id = ?
                                                    GROUP BY i.invoice_id
                                                    HAVING i.amount > paid_amount
                                                ) as pending_count
                                            ");
                                            $pendingStmt->execute([$currentMemberId]);
                                        } else {
                                            $pendingStmt = $pdo->prepare("
                                                SELECT COUNT(*) as count FROM (
                                                    SELECT i.invoice_id, i.amount, COALESCE(SUM(ip.amount), 0) as paid_amount
                                                    FROM invoices i
                                                    LEFT JOIN invoice_payments ip ON i.invoice_id = ip.invoice_id AND ip.payment_status = 'Paid'
                                                    GROUP BY i.invoice_id
                                                    HAVING i.amount > paid_amount
                                                ) as pending_count
                                            ");
                                            $pendingStmt->execute();
                                        }
                                        echo $pendingStmt->fetch()['count'];
                                        ?>
                                    </h3>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Payments Table -->
                    <?php if (!empty($payments)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Payment ID</th>
                                    <?php if ($isAdmin): ?>
                                        <th>Member</th>
                                    <?php else: ?>
                                        <th>Invoice</th>
                                        <th>Description</th>
                                    <?php endif; ?>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td><code><?php echo htmlspecialchars($payment['payment_id']); ?></code></td>
                                        <?php if ($isAdmin): ?>
                                            <td>
                                                <strong><?php echo htmlspecialchars($payment['member_name']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($payment['member_id']); ?></small>
                                            </td>
                                        <?php else: ?>
                                            <td><strong><?php echo htmlspecialchars($payment['invoice_id']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($payment['description']); ?></td>
                                        <?php endif; ?>
                                        <td><?php echo formatCurrency($payment['amount']); ?></td>
                                        <td>
                                            <span class="badge bg-secondary">
                                                <?php echo htmlspecialchars($payment['payment_method']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($payment['payment_date']); ?></td>
                                        <td>
                                            <?php 
                                                $statusColor = 'secondary';
                                                if ($payment['payment_status'] === 'Paid') $statusColor = 'success';
                                                elseif ($payment['payment_status'] === 'Pending') $statusColor = 'warning';
                                                elseif ($payment['payment_status'] === 'Failed') $statusColor = 'danger';
                                                echo '<span class="badge bg-' . $statusColor . '">' . htmlspecialchars($payment['payment_status']) . '</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($isAdmin): ?>
                                                <a href="<?php echo APP_URL; ?>modules/invoices/edit.php?id=<?php echo urlencode($payment['invoice_id']); ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="Edit Invoice">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-primary" onclick="viewInvoice('<?php echo htmlspecialchars($payment['invoice_id']); ?>')" title="View Invoice">
                                                    <i class="fas fa-eye"></i> View
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Payment history pagination">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i === (int)$page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $searchTerm ? '&search=' . urlencode($searchTerm) : ''; ?><?php echo $filterStatus ? '&status=' . urlencode($filterStatus) : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                    <?php endif; ?>
                    <?php else: ?>
                    <p class="text-muted mb-0">
                        <i class="fas fa-inbox"></i> No payments found.
                    </p>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<!-- Invoice Details Modal -->
<div class="modal fade" id="invoiceModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Invoice Details</h5>
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

<!-- Payment Form JavaScript -->
<?php if (!$isAdmin): ?>
<script>
function updatePaymentAmount() {
    const select = document.getElementById('invoiceSelect');
    if (!select) return;
    
    const selectedOption = select.options[select.selectedIndex];
    const outstanding = parseFloat(selectedOption.getAttribute('data-outstanding')) || 0;
    
    document.getElementById('outstandingAmount').textContent = '₱' + outstanding.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    document.getElementById('amountToPay').value = outstanding.toFixed(2);
    document.getElementById('amountToPay').max = outstanding;
}

function selectInvoice(invoiceId, outstanding) {
    const select = document.getElementById('invoiceSelect');
    if (!select) return;
    
    select.value = invoiceId;
    updatePaymentAmount();
    document.getElementById('amountToPay').focus();
    
    // Scroll to form
    const cardHeader = document.querySelector('.card-header.bg-primary');
    if (cardHeader) {
        cardHeader.scrollIntoView({ behavior: 'smooth' });
    }
}

const payFullBtn = document.getElementById('payFullBtn');
if (payFullBtn) {
    payFullBtn.addEventListener('click', function() {
        const select = document.getElementById('invoiceSelect');
        if (select && select.value) {
            updatePaymentAmount();
        } else {
            alert('Please select an invoice first');
        }
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('invoiceSelect');
    if (select) {
        updatePaymentAmount();
    }
});
</script>
<?php endif; ?>

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
