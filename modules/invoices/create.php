<?php
/**
 * Admin: Create Invoice for Member
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole('admin');

$errors = [];
$members = [];

try {
    $stmt = $pdo->prepare("SELECT member_id, member_name, email FROM members WHERE status = 'Active' ORDER BY member_name");
    $stmt->execute();
    $members = $stmt->fetchAll();
} catch (Exception $e) {
    setMessage('Error loading members: ' . $e->getMessage(), 'error');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $memberId = sanitize($_POST['member_id'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $dueDate = sanitize($_POST['due_date'] ?? '');
    $notes = sanitize($_POST['notes'] ?? '');

    if (empty($memberId)) $errors['member_id'] = 'Member required';
    if (empty($description)) $errors['description'] = 'Description required';
    if ($amount <= 0) $errors['amount'] = 'Amount must be > 0';
    if (empty($dueDate)) $errors['due_date'] = 'Due date required';

    if (empty($errors)) {
        try {
            $invoiceId = createInvoice($memberId, $amount, $description, $dueDate, 'Manual', $_SESSION['user_id']);
            
            if ($invoiceId) {
                // Add notes if provided
                if (!empty($notes)) {
                    $stmt = $pdo->prepare("UPDATE invoices SET notes = ? WHERE invoice_id = ?");
                    $stmt->execute([$notes, $invoiceId]);
                }
                
                logAction($_SESSION['user_id'], 'INVOICE_CREATED', 'Invoices', 
                         'Created invoice ' . $invoiceId . ' for member ' . $memberId . ' amount: ' . formatCurrency($amount));
                
                // Send email to member
                try {
                    $memberStmt = $pdo->prepare("SELECT email, member_name FROM members WHERE member_id = ?");
                    $memberStmt->execute([$memberId]);
                    $memberData = $memberStmt->fetch();
                    
                    if ($memberData && !empty($memberData['email'])) {
                        $subject = 'New Invoice - Level Up Fitness';
                        $message = "Hello " . htmlspecialchars($memberData['member_name']) . ",\n\n"
                                 . "You have a new invoice:\n\n"
                                 . "Invoice ID: " . $invoiceId . "\n"
                                 . "Description: " . htmlspecialchars($description) . "\n"
                                 . "Amount: " . formatCurrency($amount) . "\n"
                                 . "Due Date: " . formatDate($dueDate) . "\n\n"
                                 . "Please log in to your account to view and pay this invoice.\n\n"
                                 . "Best regards,\nLevel Up Fitness";
                        
                        sendEmailNotification($memberData['email'], $subject, $message, 'text');
                    }
                } catch (Exception $e) {
                    error_log('Failed to send invoice email: ' . $e->getMessage());
                }
                
                setMessage('Invoice created successfully! ID: ' . $invoiceId, 'success');
                redirect(APP_URL . 'modules/invoices/');
            } else {
                $errors['payment'] = 'Failed to create invoice';
            }
        } catch (Exception $e) {
            setMessage('Error creating invoice: ' . $e->getMessage(), 'error');
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/invoices/" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-file-invoice-dollar"></i> Create Invoice</h1>
            </div>

            <?php displayMessage(); ?>

            <div class="row">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">New Invoice</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <strong>Fix errors:</strong>
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <form method="POST">
                                <div class="mb-3">
                                    <label for="member_id" class="form-label"><strong>Member</strong> *</label>
                                    <select class="form-select <?php echo isset($errors['member_id']) ? 'is-invalid' : ''; ?>" 
                                            id="member_id" name="member_id" required>
                                        <option value="">-- Select Member --</option>
                                        <?php foreach ($members as $m): ?>
                                            <option value="<?php echo htmlspecialchars($m['member_id']); ?>">
                                                <?php echo htmlspecialchars($m['member_name']); ?> (<?php echo htmlspecialchars($m['member_id']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label"><strong>Description</strong> *</label>
                                    <input type="text" class="form-control <?php echo isset($errors['description']) ? 'is-invalid' : ''; ?>" 
                                           id="description" name="description" placeholder="e.g., Monthly Membership, Personal Training" required>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="amount" class="form-label"><strong>Amount</strong> *</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control <?php echo isset($errors['amount']) ? 'is-invalid' : ''; ?>" 
                                                   id="amount" name="amount" step="0.01" min="0" placeholder="0.00" required>
                                        </div>
                                    </div>

                                    <div class="col-md-6 mb-3">
                                        <label for="due_date" class="form-label"><strong>Due Date</strong> *</label>
                                        <input type="date" class="form-control <?php echo isset($errors['due_date']) ? 'is-invalid' : ''; ?>" 
                                               id="due_date" name="due_date" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes (Optional)</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Additional details..."></textarea>
                                </div>

                                <div class="d-flex gap-2 justify-content-end">
                                    <a href="<?php echo APP_URL; ?>modules/invoices/" class="btn btn-secondary">Cancel</a>
                                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Invoice</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
