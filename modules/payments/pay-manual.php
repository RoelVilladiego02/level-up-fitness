<?php
/**
 * Manual Payment Confirmation
 * Level Up Fitness - Gym Management System
 * 
 * Shows bank transfer details and allows proof of payment upload
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole('member');

$userInfo = getUserInfo();
$paymentId = sanitize($_GET['payment_id'] ?? '');
$invoiceId = sanitize($_GET['invoice_id'] ?? '');
$payment = null;
$invoice = null;
$errors = [];

// Get payment and invoice details
try {
    if (!empty($paymentId)) {
        $stmt = $pdo->prepare("
            SELECT ip.*, i.amount, i.description, i.due_date 
            FROM invoice_payments ip
            JOIN invoices i ON ip.invoice_id = i.invoice_id
            WHERE ip.payment_id = ? AND ip.member_id = (SELECT member_id FROM members WHERE user_id = ?)
        ");
        $stmt->execute([$paymentId, $userInfo['user_id']]);
        $payment = $stmt->fetch();
        
        if (!$payment) {
            setMessage('Payment record not found', 'error');
            redirect(APP_URL . 'modules/payments/pay.php');
        }
    } else {
        redirect(APP_URL . 'modules/payments/pay.php');
    }
} catch (Exception $e) {
    setMessage('Error loading payment details: ' . $e->getMessage(), 'error');
    redirect(APP_URL . 'modules/payments/pay.php');
}

// Handle proof of payment upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_proof'])) {
    $notes = sanitize($_POST['notes'] ?? '');
    
    // Validate file upload
    if (empty($_FILES['proof_file']) || $_FILES['proof_file']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors['proof_file'] = 'Please upload a proof of payment file';
    } elseif ($_FILES['proof_file']['error'] !== UPLOAD_ERR_OK) {
        $errors['proof_file'] = 'File upload error. Please try again.';
    } else {
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $fileType = mime_content_type($_FILES['proof_file']['tmp_name']);
        
        if (!in_array($fileType, $allowedTypes)) {
            $errors['proof_file'] = 'Please upload a valid file (JPEG, PNG, or PDF only)';
        } elseif ($_FILES['proof_file']['size'] > 5 * 1024 * 1024) { // 5MB limit
            $errors['proof_file'] = 'File size must be less than 5MB';
        }
        
        if (empty($errors)) {
            try {
                // Create upload directory
                $uploadDir = dirname(dirname(dirname(__FILE__))) . '/backend/storage/payment-proofs/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Generate unique filename
                $filename = $paymentId . '_' . time() . '.' . pathinfo($_FILES['proof_file']['name'], PATHINFO_EXTENSION);
                $filePath = $uploadDir . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['proof_file']['tmp_name'], $filePath)) {
                    // Update payment record with proof
                    $updateStmt = $pdo->prepare("
                        UPDATE invoice_payments 
                        SET payment_proof_url = ?, notes = ? 
                        WHERE payment_id = ?
                    ");
                    $updateStmt->execute([
                        'backend/storage/payment-proofs/' . $filename,
                        $notes,
                        $paymentId
                    ]);
                    
                    // Send notification to admin
                    logAction(
                        $userInfo['user_id'],
                        'UPLOAD_PAYMENT_PROOF',
                        'Invoices',
                        'Member uploaded proof of payment for ' . $paymentId
                    );
                    
                    // Create admin notification
                    createNotification(
                        null, // Will be set to all admins
                        'payment_proof',
                        'Payment Proof Uploaded',
                        'Member ' . htmlspecialchars($payment['member_id']) . ' uploaded proof of payment for ' . formatCurrency($payment['amount']) . '.',
                        [
                            'icon' => 'file-upload',
                            'color' => 'info',
                            'entity_type' => 'payment',
                            'entity_id' => $paymentId,
                            'priority' => 'high'
                        ]
                    );
                    
                    setMessage('Payment proof uploaded successfully! Admin will verify your payment within 24 hours.', 'success');
                    redirect(APP_URL . 'modules/payments/');
                } else {
                    $errors['proof_file'] = 'Failed to upload file. Please try again.';
                }
            } catch (Exception $e) {
                error_log('Error uploading payment proof: ' . $e->getMessage());
                $errors['proof_file'] = 'Error uploading file: ' . $e->getMessage();
            }
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header mb-4">
                <h1><i class="fas fa-university"></i> Manual Bank Transfer Payment</h1>
                <p>Follow the instructions below to complete your payment</p>
            </div>

            <?php displayMessage(); ?>

            <div class="row">
                <!-- Payment Details -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-file-invoice-dollar"></i> Payment Details</h5>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless">
                                <tr>
                                    <td class="fw-bold" style="width: 40%;">Payment ID:</td>
                                    <td>
                                        <code><?php echo $paymentId; ?></code>
                                        <br><small class="text-muted">Reference this ID in your transfer</small>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Invoice ID:</td>
                                    <td><?php echo $payment['invoice_id']; ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Amount to Pay:</td>
                                    <td>
                                        <h5 class="text-primary mb-0"><?php echo formatCurrency($payment['amount']); ?></h5>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Description:</td>
                                    <td><?php echo htmlspecialchars($payment['description']); ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Payment Method:</td>
                                    <td><?php echo $payment['payment_method']; ?></td>
                                </tr>
                                <tr>
                                    <td class="fw-bold">Status:</td>
                                    <td>
                                        <span class="badge bg-warning">Awaiting Verification</span>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Bank Transfer Instructions -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow-sm border-left-primary">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-bank"></i> Bank Transfer Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-info mb-3">
                                <strong><i class="fas fa-exclamation-circle"></i> Important</strong><br>
                                Make sure to include the Payment ID in your transfer reference to ensure your payment is automatically matched to your account.
                            </div>

                            <h6 class="mb-3">Bank Account Details:</h6>
                            <div class="card bg-light p-3 mb-3">
                                <dl class="row mb-0">
                                    <dt class="col-sm-5">Bank Name:</dt>
                                    <dd class="col-sm-7"><strong>BDO Unibank</strong></dd>

                                    <dt class="col-sm-5">Account Name:</dt>
                                    <dd class="col-sm-7"><strong>Level Up Fitness Gym</strong></dd>

                                    <dt class="col-sm-5">Account Number:</dt>
                                    <dd class="col-sm-7">
                                        <strong style="font-family: monospace; font-size: 1.1rem;">
                                            00-123-456789-1
                                        </strong>
                                    </dd>

                                    <dt class="col-sm-5">Branch Code:</dt>
                                    <dd class="col-sm-7"><strong>0011</strong> (Head Office)</dd>
                                </dl>
                            </div>

                            <h6 class="mb-2">Other Payment Options:</h6>
                            <ul class="list-unstyled small">
                                <li><i class="fas fa-qrcode"></i> <strong>GCash:</strong> 09XX-XXX-XXXX (QR Code available at gym)</li>
                                <li><i class="fas fa-mobile-alt"></i> <strong>PayMaya:</strong> Same account number</li>
                                <li><i class="fas fa-university"></i> <strong>Over-the-Counter:</strong> Available at any BDO branch</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upload Proof of Payment -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-upload"></i> Upload Proof of Payment</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        After completing your bank transfer, please upload a screenshot or scan of your receipt. 
                        Admin will verify your payment within 24 hours.
                    </p>

                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger" role="alert">
                            <strong>Upload Error:</strong>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="needs-validation">
                        <div class="mb-3">
                            <label for="proofFile" class="form-label">
                                <strong>Select File</strong>
                                <span class="text-danger">*</span>
                            </label>
                            <input type="file" class="form-control <?php echo isset($errors['proof_file']) ? 'is-invalid' : ''; ?>" 
                                   id="proofFile" name="proof_file" accept="image/jpeg,image/png,application/pdf" required>
                            <small class="form-text text-muted d-block mt-2">
                                <strong>Accepted formats:</strong> JPEG, PNG, PDF (Max 5MB)<br>
                                <strong>Recommended:</strong> Screenshot of receipt with transaction details visible
                            </small>
                            <?php if (isset($errors['proof_file'])): ?>
                                <div class="invalid-feedback d-block"><?php echo $errors['proof_file']; ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">Additional Notes (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" 
                                      placeholder="Add any additional information about this payment..."></textarea>
                            <small class="form-text text-muted">
                                e.g., Transaction date, reference number, or any other details
                            </small>
                        </div>

                        <div class="d-grid gap-2 d-sm-flex justify-content-sm-end">
                            <a href="<?php echo APP_URL; ?>modules/payments/" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                            <button type="submit" name="upload_proof" class="btn btn-primary">
                                <i class="fas fa-check-circle"></i> Upload & Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- FAQ Section -->
            <div class="card mt-4 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-question-circle"></i> Frequently Asked Questions</h5>
                </div>
                <div class="card-body">
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                    How long does it take to verify my payment?
                                </button>
                            </h2>
                            <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Payment verification usually takes 24 hours. If you don't see your payment reflected in your account after this time, please contact our admin team.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                    What if the transfer amount is different?
                                </button>
                            </h2>
                            <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Please make sure to transfer the exact amount shown above. If there are any discrepancies, admin will contact you for clarification.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                    Is there a deadline for payment?
                                </button>
                            </h2>
                            <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes, please complete your payment by the due date shown in your invoice to avoid late fees. The due date is: <strong><?php echo formatDate($payment['due_date']); ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                    Can I pay for multiple invoices at once?
                                </button>
                            </h2>
                            <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes, but please list all payment IDs in your receipt notes so we can correctly match your transfer to all invoices.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
