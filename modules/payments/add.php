<?php
/**
 * Payments Management - Manual Adjustments
 * Level Up Fitness - Gym Management System
 * 
 * Admin only: Record manual cash payments, discounts, refunds, and adjustments
 * Members now use /modules/payments/pay.php to pay their own invoices
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole('admin');

$errors = [];
$formData = [];
$members = [];

// Get all members with outstanding balance
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT 
            m.member_id, 
            m.member_name,
            COALESCE(SUM(i.amount), 0) - COALESCE(SUM(ip.amount), 0) as outstanding
        FROM members m
        LEFT JOIN invoices i ON m.member_id = i.member_id AND i.invoice_status != 'Cancelled'
        LEFT JOIN invoice_payments ip ON i.invoice_id = ip.invoice_id AND ip.payment_status = 'Paid'
        WHERE m.status = 'Active'
        GROUP BY m.member_id
        ORDER BY m.member_name
    ");
    $stmt->execute();
    $members = $stmt->fetchAll();
} catch (Exception $e) {
    setMessage('Error loading members: ' . $e->getMessage(), 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData['member_id'] = sanitize($_POST['member_id'] ?? '');
    $formData['invoice_id'] = sanitize($_POST['invoice_id'] ?? '');
    $formData['amount'] = floatval($_POST['amount'] ?? 0);
    $formData['adjustment_type'] = sanitize($_POST['adjustment_type'] ?? 'cash_payment');
    $paymentDate = sanitize($_POST['payment_date'] ?? date('Y-m-d'));
    $formData['notes'] = sanitize($_POST['notes'] ?? '');

    // Validate
    if (empty($formData['member_id'])) {
        $errors['member_id'] = 'Member is required';
    }
    if (empty($formData['invoice_id'])) {
        $errors['invoice_id'] = 'Invoice is required';
    }
    if ($formData['amount'] <= 0) {
        $errors['amount'] = 'Amount must be greater than 0';
    }
    if (empty($paymentDate)) {
        $errors['payment_date'] = 'Payment date is required';
    }

    if (empty($errors)) {
        try {
            // Get invoice details
            $invoiceStmt = $pdo->prepare("SELECT * FROM invoices WHERE invoice_id = ? AND member_id = ?");
            $invoiceStmt->execute([$formData['invoice_id'], $formData['member_id']]);
            $invoice = $invoiceStmt->fetch();
            
            if (!$invoice) {
                $errors['invoice_id'] = 'Invoice not found or does not belong to this member';
            } else {
                // Check if payment exceeds outstanding
                $outstandingStmt = $pdo->prepare("
                    SELECT (i.amount - COALESCE(SUM(ip.amount), 0)) as outstanding
                    FROM invoices i
                    LEFT JOIN invoice_payments ip ON i.invoice_id = ip.invoice_id AND ip.payment_status = 'Paid'
                    WHERE i.invoice_id = ?
                    GROUP BY i.invoice_id
                ");
                $outstandingStmt->execute([$formData['invoice_id']]);
                $outstanding = $outstandingStmt->fetch();
                
                if ($formData['amount'] > $outstanding['outstanding']) {
                    $errors['amount'] = 'Payment amount exceeds outstanding balance of ' . formatCurrency($outstanding['outstanding']);
                }
            }
        } catch (Exception $e) {
            $errors['invoice_id'] = 'Error validating invoice: ' . $e->getMessage();
        }
    }

    if (empty($errors)) {
        try {
            // Determine payment method based on adjustment type
            $paymentMethodMap = [
                'cash_payment' => 'Cash',
                'check_payment' => 'Check',
                'bank_transfer' => 'Bank Transfer',
                'discount' => 'Discount',
                'refund' => 'Refund'
            ];
            
            $paymentMethod = $paymentMethodMap[$formData['adjustment_type']] ?? 'Manual';
            
            // Record payment
            $paymentId = recordInvoicePayment(
                $formData['invoice_id'],
                $formData['amount'],
                $paymentMethod,
                null,
                'Paid'
            );

            if ($paymentId) {
                logAction($_SESSION['user_id'], 'MANUAL_PAYMENT', 'Payments', 
                         'Recorded ' . strtolower($paymentMethod) . ' of ' . formatCurrency($formData['amount']) . 
                         ' for invoice ' . $formData['invoice_id'] . ' (' . $formData['member_id'] . '). Notes: ' . $formData['notes']);
                
                // Send notification to member
                try {
                    $memberStmt = $pdo->prepare("SELECT user_id, email, member_name FROM members WHERE member_id = ?");
                    $memberStmt->execute([$formData['member_id']]);
                    $memberData = $memberStmt->fetch();
                    
                    if ($memberData && !empty($memberData['email'])) {
                        $subject = 'Payment Received - Level Up Fitness';
                        $message = "Hello " . htmlspecialchars($memberData['member_name']) . ",\n\n"
                                 . "We have received your payment of " . formatCurrency($formData['amount']) . " for invoice " . $formData['invoice_id'] . ".\n\n"
                                 . "Payment Method: " . $paymentMethod . "\n"
                                 . "Payment Date: " . formatDate($paymentDate) . "\n"
                                 . "Notes: " . htmlspecialchars($formData['notes']) . "\n\n"
                                 . "Your account has been updated. Please log in to view your payment history.\n\n"
                                 . "Thank you!\n\nBest regards,\nLevel Up Fitness Management";
                        
                        sendEmailNotification($memberData['email'], $subject, $message, 'text');
                    }
                } catch (Exception $e) {
                    error_log('Failed to send payment notification: ' . $e->getMessage());
                }

                setMessage('Payment recorded successfully! ID: ' . $paymentId, 'success');
                redirect(APP_URL . 'modules/payments/');
            } else {
                $errors['payment'] = 'Failed to record payment';
            }
        } catch (Exception $e) {
            setMessage('Error recording payment: ' . $e->getMessage(), 'error');
        }
    }
}

// Get pending invoices for selected member
$selectedMemberInvoices = [];
if (!empty($formData['member_id'])) {
    try {
        $invoicesStmt = $pdo->prepare("
            SELECT 
                i.*,
                COALESCE(SUM(ip.amount), 0) as paid_amount,
                (i.amount - COALESCE(SUM(ip.amount), 0)) as outstanding_amount
            FROM invoices i
            LEFT JOIN invoice_payments ip ON i.invoice_id = ip.invoice_id AND ip.payment_status = 'Paid'
            WHERE i.member_id = ? AND i.invoice_status != 'Cancelled' AND (i.amount - COALESCE(SUM(ip.amount), 0)) > 0
            GROUP BY i.invoice_id
            ORDER BY i.due_date ASC
        ");
        $invoicesStmt->execute([$formData['member_id']]);
        $selectedMemberInvoices = $invoicesStmt->fetchAll();
    } catch (Exception $e) {
        error_log('Error loading member invoices: ' . $e->getMessage());
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/payments/" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back to Payments
                </a>
                <h1><i class="fas fa-hand-holding-usd"></i> Record Manual Payment</h1>
                <p>Admin: Record cash, check, bank transfers, discounts, or refunds</p>
            </div>

            <?php displayMessage(); ?>

            <div class="alert alert-info" role="alert">
                <strong><i class="fas fa-info-circle"></i> Updated Flow:</strong> Members can now pay online directly via the member portal. 
                Use this form ONLY for manual payments (cash, checks, transfers) or account adjustments (discounts, refunds).
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Manual Payment Record</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger" role="alert">
                                    <strong>Please fix the following errors:</strong>
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $field => $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="" class="needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="member_id" class="form-label"><strong>Member</strong> *</label>
                                        <select class="form-select <?php echo isset($errors['member_id']) ? 'is-invalid' : ''; ?>" 
                                                id="member_id" name="member_id" required onchange="loadMemberInvoices()">
                                            <option value="">-- Select Member --</option>
                                            <?php foreach ($members as $member): ?>
                                                <option value="<?php echo htmlspecialchars($member['member_id']); ?>" 
                                                        <?php echo ($formData['member_id'] ?? '') === $member['member_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($member['member_name']); ?> 
                                                    (Due: <?php echo formatCurrency($member['outstanding']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="adjustment_type" class="form-label"><strong>Payment Type</strong> *</label>
                                        <select class="form-select" id="adjustment_type" name="adjustment_type" required>
                                            <option value="cash_payment" <?php echo ($formData['adjustment_type'] ?? '') === 'cash_payment' ? 'selected' : ''; ?>>💵 Cash Payment</option>
                                            <option value="check_payment" <?php echo ($formData['adjustment_type'] ?? '') === 'check_payment' ? 'selected' : ''; ?>>📝 Check Payment</option>
                                            <option value="bank_transfer" <?php echo ($formData['adjustment_type'] ?? '') === 'bank_transfer' ? 'selected' : ''; ?>>🏦 Bank Transfer</option>
                                            <option value="discount" <?php echo ($formData['adjustment_type'] ?? '') === 'discount' ? 'selected' : ''; ?>>🏷️ Discount/Adjustment</option>
                                            <option value="refund" <?php echo ($formData['adjustment_type'] ?? '') === 'refund' ? 'selected' : ''; ?>>💸 Refund</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="invoice_id" class="form-label"><strong>Invoice</strong> *</label>
                                        <select class="form-select <?php echo isset($errors['invoice_id']) ? 'is-invalid' : ''; ?>" 
                                                id="invoice_id" name="invoice_id" required onchange="updateInvoiceDetails()">
                                            <option value="">-- Select Invoice --</option>
                                            <?php foreach ($selectedMemberInvoices as $inv): ?>
                                                <option value="<?php echo htmlspecialchars($inv['invoice_id']); ?>"
                                                        data-outstanding="<?php echo $inv['outstanding_amount']; ?>"
                                                        <?php echo ($formData['invoice_id'] ?? '') === $inv['invoice_id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($inv['invoice_id']); ?> - <?php echo htmlspecialchars($inv['description']); ?>
                                                    (Outstanding: <?php echo formatCurrency($inv['outstanding_amount']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="amount" class="form-label"><strong>Payment Amount</strong> *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control <?php echo isset($errors['amount']) ? 'is-invalid' : ''; ?>" 
                                                   id="amount" name="amount" step="0.01" min="0" 
                                                   value="<?php echo htmlspecialchars($formData['amount'] ?? ''); ?>" 
                                                   placeholder="0.00" required>
                                        </div>
                                        <small class="form-text text-muted">Outstanding: <strong id="outstandingAmount">₱0.00</strong></small>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="payment_date" class="form-label"><strong>Payment Date</strong> *</label>
                                        <input type="date" class="form-control <?php echo isset($errors['payment_date']) ? 'is-invalid' : ''; ?>" 
                                               id="payment_date" name="payment_date" 
                                               value="<?php echo htmlspecialchars($formData['payment_date'] ?? date('Y-m-d')); ?>" required>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="notes" class="form-label">Notes (Optional)</label>
                                        <input type="text" class="form-control" id="notes" name="notes" 
                                               placeholder="Check #, Reference, etc." 
                                               value="<?php echo htmlspecialchars($formData['notes'] ?? ''); ?>">
                                    </div>
                                </div>

                                <div class="d-grid gap-2 d-sm-flex justify-content-sm-end mt-4">
                                    <a href="<?php echo APP_URL; ?>modules/payments/" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Record Payment</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header bg-light"><h6 class="mb-0"><i class="fas fa-info-circle"></i> Use This Form For</h6></div>
                        <div class="card-body small">
                            <ul class="mb-0">
                                <li>💵 Cash payments at gym</li>
                                <li>📝 Check payments</li>
                                <li>🏦 Manual bank transfers</li>
                                <li>🏷️ Discounts/adjustments</li>
                                <li>💸 Refunds</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
function loadMemberInvoices() {
    const memberId = document.getElementById('member_id').value;
    if (!memberId) {
        document.getElementById('invoice_id').innerHTML = '<option value="">-- Select Invoice --</option>';
        return;
    }
    document.getElementById('invoice_id').innerHTML = '<option value="">Loading invoices...</option>';
}

function updateInvoiceDetails() {
    const select = document.getElementById('invoice_id');
    const option = select.options[select.selectedIndex];
    const outstanding = parseFloat(option.getAttribute('data-outstanding')) || 0;
    document.getElementById('outstandingAmount').textContent = '₱' + outstanding.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    document.getElementById('amount').value = outstanding.toFixed(2);
    document.getElementById('amount').max = outstanding;
}

document.addEventListener('DOMContentLoaded', function() {
    updateInvoiceDetails();
});
</script>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
