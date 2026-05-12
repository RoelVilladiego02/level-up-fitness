<?php
/**
 * Edit Invoice - Admin Only
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole('admin');

$invoice = null;
$invoiceId = $_GET['id'] ?? null;
$errors = [];

if (!$invoiceId) {
    setMessage('Invoice ID is required', 'error');
    redirect(APP_URL . 'modules/invoices/');
}

// Get invoice details
try {
    $stmt = $pdo->prepare("
        SELECT i.*, m.member_name, m.member_id 
        FROM invoices i
        JOIN members m ON i.member_id = m.member_id
        WHERE i.invoice_id = ?
    ");
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch();
    
    if (!$invoice) {
        setMessage('Invoice not found', 'error');
        redirect(APP_URL . 'modules/invoices/');
    }
} catch (Exception $e) {
    setMessage('Error loading invoice: ' . $e->getMessage(), 'error');
    redirect(APP_URL . 'modules/invoices/');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $description = sanitize($_POST['description'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $dueDate = sanitize($_POST['due_date'] ?? '');
    $status = sanitize($_POST['status'] ?? 'Pending');
    
    // Validation
    if (empty($description)) {
        $errors['description'] = 'Description is required';
    }
    if ($amount <= 0) {
        $errors['amount'] = 'Amount must be greater than 0';
    }
    if (empty($dueDate)) {
        $errors['due_date'] = 'Due date is required';
    }
    if (strtotime($dueDate) === false) {
        $errors['due_date'] = 'Invalid date format';
    }
    if (!in_array($status, ['Pending', 'Paid', 'Partially Paid', 'Overdue', 'Cancelled'])) {
        $errors['status'] = 'Invalid status';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                UPDATE invoices 
                SET description = ?, amount = ?, due_date = ?, invoice_status = ?, updated_at = NOW()
                WHERE invoice_id = ?
            ");
            $stmt->execute([$description, $amount, $dueDate, $status, $invoiceId]);
            
            logAction($_SESSION['user_id'], 'UPDATE_INVOICE', 'Invoices', 
                     'Updated invoice ' . $invoiceId . ' for member ' . $invoice['member_id']);
            
            setMessage('Invoice updated successfully', 'success');
            redirect(APP_URL . 'modules/invoices/view.php?id=' . $invoiceId);
        } catch (Exception $e) {
            $errors['general'] = 'Error updating invoice: ' . $e->getMessage();
        }
    }
}

// Get invoice payments
$payments = [];
try {
    $paymentsStmt = $pdo->prepare("
        SELECT * FROM invoice_payments 
        WHERE invoice_id = ? 
        ORDER BY payment_date DESC
    ");
    $paymentsStmt->execute([$invoiceId]);
    $payments = $paymentsStmt->fetchAll();
} catch (Exception $e) {
    error_log('Error loading payments: ' . $e->getMessage());
}

// Calculate paid amount
$paidAmount = 0;
foreach ($payments as $payment) {
    if ($payment['payment_status'] === 'Paid') {
        $paidAmount += $payment['amount'];
    }
}
$outstanding = $invoice['amount'] - $paidAmount;
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/invoices/" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back to Invoices
                </a>
                <h1><i class="fas fa-edit"></i> Edit Invoice</h1>
                <p>Update invoice details for <?php echo htmlspecialchars($invoice['member_name']); ?></p>
            </div>

            <?php displayMessage(); ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Invoice Details</h5>
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

                            <form method="POST">
                                <div class="mb-3">
                                    <label for="invoiceId" class="form-label">Invoice ID</label>
                                    <input type="text" class="form-control" id="invoiceId" value="<?php echo htmlspecialchars($invoiceId); ?>" disabled>
                                </div>

                                <div class="mb-3">
                                    <label for="memberId" class="form-label">Member</label>
                                    <input type="text" class="form-control" id="memberId" value="<?php echo htmlspecialchars($invoice['member_name']); ?>" disabled>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <input type="text" class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" 
                                           id="description" name="description" 
                                           value="<?php echo htmlspecialchars($_POST['description'] ?? $invoice['description']); ?>" required>
                                    <?php if (isset($errors['description'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['description']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="amount" class="form-label">Amount (₱)</label>
                                        <input type="number" class="form-control <?php echo isset($errors['amount']) ? 'is-invalid' : ''; ?>" 
                                               id="amount" name="amount" step="0.01" min="0"
                                               value="<?php echo htmlspecialchars($_POST['amount'] ?? $invoice['amount']); ?>" required>
                                        <?php if (isset($errors['amount'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['amount']; ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="dueDate" class="form-label">Due Date</label>
                                        <input type="date" class="form-control <?php echo isset($errors['due_date']) ? 'is-invalid' : ''; ?>" 
                                               id="dueDate" name="due_date"
                                               value="<?php echo htmlspecialchars($_POST['due_date'] ?? date('Y-m-d', strtotime($invoice['due_date']))); ?>" required>
                                        <?php if (isset($errors['due_date'])): ?>
                                            <div class="invalid-feedback"><?php echo $errors['due_date']; ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="status" class="form-label">Status</label>
                                    <select class="form-control <?php echo isset($errors['status']) ? 'is-invalid' : ''; ?>" 
                                            id="status" name="status" required>
                                        <option value="Pending" <?php echo ($invoice['invoice_status'] === 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Partially Paid" <?php echo ($invoice['invoice_status'] === 'Partially Paid') ? 'selected' : ''; ?>>Partially Paid</option>
                                        <option value="Paid" <?php echo ($invoice['invoice_status'] === 'Paid') ? 'selected' : ''; ?>>Paid</option>
                                        <option value="Overdue" <?php echo ($invoice['invoice_status'] === 'Overdue') ? 'selected' : ''; ?>>Overdue</option>
                                        <option value="Cancelled" <?php echo ($invoice['invoice_status'] === 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <?php if (isset($errors['status'])): ?>
                                        <div class="invalid-feedback"><?php echo $errors['status']; ?></div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                    <a href="<?php echo APP_URL; ?>modules/invoices/" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <!-- Payment Summary -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Payment Summary</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label text-muted">Total Amount</label>
                                <h5><?php echo formatCurrency($invoice['amount']); ?></h5>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Paid Amount</label>
                                <h5 class="text-success"><?php echo formatCurrency($paidAmount); ?></h5>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-muted">Outstanding</label>
                                <h5 class="<?php echo $outstanding > 0 ? 'text-danger' : 'text-success'; ?>">
                                    <?php echo formatCurrency($outstanding); ?>
                                </h5>
                            </div>
                            <hr>
                            <a href="<?php echo APP_URL; ?>modules/payments/add.php" class="btn btn-sm btn-primary w-100">
                                <i class="fas fa-plus"></i> Record Payment
                            </a>
                        </div>
                    </div>

                    <!-- Payment History -->
                    <?php if (!empty($payments)): ?>
                    <div class="card">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Payment History</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($payments as $payment): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo formatCurrency($payment['amount']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo $payment['payment_method']; ?></small>
                                        </div>
                                        <div class="text-end">
                                            <small><?php echo formatDate($payment['payment_date']); ?></small>
                                            <br>
                                            <span class="badge bg-<?php echo $payment['payment_status'] === 'Paid' ? 'success' : 'warning'; ?>">
                                                <?php echo $payment['payment_status']; ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
