<?php
/**
 * Admin: Create Invoice for Member
 * Level Up Fitness - Gym Management System
 */

// Check if this is an AJAX request BEFORE loading HTML header
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    // Use clean API header for AJAX requests
    require_once dirname(dirname(dirname(__FILE__))) . '/includes/api_header.php';
} else {
    // Use full HTML header for page loads
    require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';
}

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
                
                // Send email asynchronously - don't wait for it
                if ($isAjax) {
                    // For AJAX, send email in background and return immediately
                    ob_clean();
                    echo json_encode([
                        'success' => true,
                        'invoice_id' => $invoiceId,
                        'message' => 'Invoice created successfully!'
                    ]);
                    
                    // Send email after response (won't block the user)
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
                    exit;
                } else {
                    // For traditional form submission, set message and redirect
                    setMessage('Invoice created successfully! ID: ' . $invoiceId, 'success');
                    redirect(APP_URL . 'modules/invoices/');
                }
            } else {
                $errors['payment'] = 'Failed to create invoice';
            }
        } catch (Exception $e) {
            if ($isAjax) {
                ob_clean();
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => $e->getMessage()
                ]);
                exit;
            } else {
                setMessage('Error creating invoice: ' . $e->getMessage(), 'error');
            }
        }
    } else if ($isAjax) {
        ob_clean();
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'errors' => $errors
        ]);
        exit;
    }
}
?>

<?php if (!$isAjax): ?>
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
                                    <button type="submit" class="btn btn-primary" id="submitBtn">
                                        <span id="submitBtnText"><i class="fas fa-save"></i> Create Invoice</span>
                                        <span id="submitBtnLoading" style="display: none;">
                                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                            Creating...
                                        </span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="position-fixed top-0 start-0 w-100 h-100 bg-dark bg-opacity-50 d-none flex-center z-index-9999" 
     style="display: none; justify-content: center; align-items: center; z-index: 9999;">
    <div class="text-white text-center">
        <div class="spinner-border mb-3" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h5>Creating Invoice...</h5>
        <p class="small">Please wait, this may take a moment</p>
    </div>
</div>

<script>
document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault(); // Prevent traditional form submission
    
    const submitBtn = document.getElementById('submitBtn');
    const submitBtnText = document.getElementById('submitBtnText');
    const submitBtnLoading = document.getElementById('submitBtnLoading');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const form = this;
    
    // Validate form
    if (!form.checkValidity()) {
        form.classList.add('was-validated');
        return;
    }
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtnText.style.display = 'none';
    submitBtnLoading.style.display = 'inline';
    loadingOverlay.style.display = 'flex';
    
    // Prepare form data
    const formData = new FormData(form);
    
    // Submit via AJAX
    fetch(form.action || window.location.href, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.error || data.errors?.join(', ') || 'Failed to create invoice');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Show success message
            const successAlert = document.createElement('div');
            successAlert.className = 'alert alert-success alert-dismissible fade show';
            successAlert.innerHTML = `
                <strong>✓ Success!</strong> Invoice ${data.invoice_id} created successfully.
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Insert before form
            form.parentElement.insertBefore(successAlert, form);
            
            // Reset form
            form.reset();
            
            // Redirect after 1.5 seconds
            setTimeout(() => {
                window.location.href = '<?php echo APP_URL; ?>modules/invoices/';
            }, 1500);
        } else {
            throw new Error(data.error || 'Unknown error');
        }
    })
    .catch(error => {
        // Show error
        const errorAlert = document.createElement('div');
        errorAlert.className = 'alert alert-danger alert-dismissible fade show';
        errorAlert.innerHTML = `
            <strong>Error:</strong> ${error.message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        form.parentElement.insertBefore(errorAlert, form);
        
        // Reset button
        submitBtn.disabled = false;
        submitBtnText.style.display = 'inline';
        submitBtnLoading.style.display = 'none';
        loadingOverlay.style.display = 'none';
    });
});
</script>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
<?php endif; ?>
