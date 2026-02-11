<?php
/**
 * Payments - Invoice View & Export
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole('admin');

$paymentId = sanitize($_GET['id'] ?? '');
$payment = null;
$member = null;

// Load payment details
if (!empty($paymentId)) {
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, m.member_name, m.email, m.contact_number, m.membership_type
            FROM payments p
            JOIN members m ON p.member_id = m.member_id
            WHERE p.payment_id = ?
        ");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();
        
        if (!$payment) {
            setMessage('Payment not found', 'error');
            redirect(APP_URL . 'modules/payments/');
        }
        
        $member = [
            'name' => $payment['member_name'],
            'email' => $payment['email'],
            'phone' => $payment['contact_number'],
            'membership' => $payment['membership_type']
        ];
    } catch (Exception $e) {
        setMessage('Error loading payment: ' . $e->getMessage(), 'error');
    }
}

// Handle email sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_email']) && $payment) {
    $to = $payment['email'];
    $subject = 'Invoice for Payment - Level Up Fitness';
    
    // Create email body
    $emailBody = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .invoice { max-width: 600px; margin: 0 auto; }
            .header { background: #4A90E2; color: white; padding: 20px; text-align: center; }
            .invoice-details { margin: 20px 0; }
            .table { width: 100%; border-collapse: collapse; }
            .table th, .table td { padding: 10px; border: 1px solid #ddd; text-align: left; }
            .table th { background: #f0f0f0; }
            .footer { margin-top: 20px; text-align: center; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='invoice'>
            <div class='header'>
                <h2>Level Up Fitness - Invoice</h2>
            </div>
            <div class='invoice-details'>
                <p><strong>Invoice Date:</strong> " . formatDate($payment['payment_date']) . "</p>
                <p><strong>Invoice Number:</strong> " . htmlspecialchars($payment['payment_id']) . "</p>
                <p><strong>Member:</strong> " . htmlspecialchars($payment['member_name']) . "</p>
            </div>
            <table class='table'>
                <tbody>
                    <tr>
                        <th>Description</th>
                        <th>Amount</th>
                    </tr>
                    <tr>
                        <td>" . htmlspecialchars($payment['payment_method']) . " Payment</td>
                        <td>" . formatCurrency($payment['amount']) . "</td>
                    </tr>
                    <tr>
                        <th style='text-align: right;'>Total:</th>
                        <th style='text-align: right;'>" . formatCurrency($payment['amount']) . "</th>
                    </tr>
                </tbody>
            </table>
            <div class='invoice-details'>
                <p><strong>Status:</strong> " . htmlspecialchars($payment['payment_status']) . "</p>
                <p><strong>Payment Method:</strong> " . htmlspecialchars($payment['payment_method']) . "</p>
                <p><strong>Reference Number:</strong> " . htmlspecialchars($payment['payment_reference'] ?? 'N/A') . "</p>
            </div>
            <div class='footer'>
                <p>Thank you for your payment!</p>
                <p>Level Up Fitness - Gym Management System</p>
            </div>
        </div>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    $headers .= 'From: ' . 'noreply@levelupfitness.com' . "\r\n";
    
    if (mail($to, $subject, $emailBody, $headers)) {
        setMessage('Invoice sent to ' . htmlspecialchars($payment['email']) . ' successfully!', 'success');
        logAction($_SESSION['user_id'], 'SEND_INVOICE', 'Payments', 'Sent invoice for payment: ' . $paymentId);
    } else {
        setMessage('Error sending email. Please try again.', 'error');
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <div class="btn-group float-end" role="group">
                    <button class="btn btn-info btn-sm" onclick="window.print()">
                        <i class="fas fa-print"></i> Print / Save as PDF
                    </button>
                    <?php if ($payment): ?>
                    <form method="POST" style="display: inline;">
                        <button type="submit" name="send_email" class="btn btn-success btn-sm">
                            <i class="fas fa-envelope"></i> Send via Email
                        </button>
                    </form>
                    <?php endif; ?>
                    <a href="<?php echo APP_URL; ?>modules/payments/" class="btn btn-secondary btn-sm">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </div>
                <h1><i class="fas fa-file-invoice"></i> Invoice</h1>
            </div>

            <?php displayMessage(); ?>

            <?php if ($payment): ?>

            <div class="invoice-container" style="background: white; padding: 40px; margin: 20px 0; border: 1px solid #ddd;">
                <!-- Invoice Header -->
                <div class="row mb-5 pb-3" style="border-bottom: 2px solid #4A90E2;">
                    <div class="col-md-6">
                        <h2 class="mb-0" style="color: #4A90E2; font-size: 28px;">
                            <i class="fas fa-file-invoice-dollar"></i> INVOICE
                        </h2>
                        <p class="text-muted mb-0">Level Up Fitness</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <p class="mb-1"><strong>Invoice #:</strong> <?php echo htmlspecialchars($payment['payment_id']); ?></p>
                        <p class="mb-1"><strong>Invoice Date:</strong> <?php echo formatDate($payment['payment_date']); ?></p>
                        <p class="mb-0"><strong>Status:</strong> <span class="badge bg-success"><?php echo htmlspecialchars($payment['payment_status']); ?></span></p>
                    </div>
                </div>

                <!-- Bill To -->
                <div class="row mb-5">
                    <div class="col-md-6">
                        <h6 style="color: #666; font-weight: bold; margin-bottom: 10px;">BILL TO:</h6>
                        <p class="mb-1"><strong><?php echo htmlspecialchars($payment['member_name']); ?></strong></p>
                        <p class="mb-1">Email: <?php echo htmlspecialchars($payment['email']); ?></p>
                        <p class="mb-1">Phone: <?php echo htmlspecialchars($payment['contact_number'] ?? 'N/A'); ?></p>
                        <p>Membership: <?php echo htmlspecialchars($payment['membership_type']); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6 style="color: #666; font-weight: bold; margin-bottom: 10px;">PAYMENT DETAILS:</h6>
                        <p class="mb-1"><strong>Method:</strong> <?php echo htmlspecialchars($payment['payment_method']); ?></p>
                        <p class="mb-1"><strong>Reference:</strong> <?php echo htmlspecialchars($payment['payment_reference'] ?? 'N/A'); ?></p>
                        <p class="mb-1"><strong>Due Date:</strong> <?php echo formatDate($payment['created_at']); ?></p>
                    </div>
                </div>

                <!-- Invoice Items -->
                <table class="table mb-5" style="border: 1px solid #ddd;">
                    <thead style="background-color: #f8f9fa;">
                        <tr>
                            <th style="padding: 15px; border-bottom: 2px solid #ddd;">Description</th>
                            <th class="text-end" style="padding: 15px; border-bottom: 2px solid #ddd;">Unit Price</th>
                            <th class="text-end" style="padding: 15px; border-bottom: 2px solid #ddd;">Quantity</th>
                            <th class="text-end" style="padding: 15px; border-bottom: 2px solid #ddd;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="padding: 15px;">
                                <strong><?php echo htmlspecialchars($payment['payment_method']); ?> Payment</strong><br>
                                <small class="text-muted"><?php echo htmlspecialchars($payment['membership_type']); ?> Membership Fee</small>
                            </td>
                            <td class="text-end" style="padding: 15px;"><?php echo formatCurrency($payment['amount']); ?></td>
                            <td class="text-end" style="padding: 15px;">1</td>
                            <td class="text-end" style="padding: 15px;"><strong><?php echo formatCurrency($payment['amount']); ?></strong></td>
                        </tr>
                    </tbody>
                </table>

                <!-- Totals -->
                <div class="row mb-4">
                    <div class="col-md-6 offset-md-6">
                        <table class="w-100" style="border-collapse: collapse;">
                            <tr style="border-bottom: 1px solid #ddd;">
                                <td style="padding: 10px; text-align: right;"><strong>Subtotal:</strong></td>
                                <td style="padding: 10px; text-align: right; text-align: right;"><?php echo formatCurrency($payment['amount']); ?></td>
                            </tr>
                            <tr style="border-bottom: 1px solid #ddd;">
                                <td style="padding: 10px; text-align: right;"><strong>Tax:</strong></td>
                                <td style="padding: 10px; text-align: right;">₱0.00</td>
                            </tr>
                            <tr style="background: #f8f9fa; border: 2px solid #4A90E2;">
                                <td style="padding: 15px; text-align: right; font-size: 16px;"><strong>TOTAL:</strong></td>
                                <td style="padding: 15px; text-align: right; font-size: 16px; color: #4A90E2;"><strong><?php echo formatCurrency($payment['amount']); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>

                <!-- Footer -->
                <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; text-align: center; color: #666; font-size: 12px;">
                    <p class="mb-1">Thank you for your business!</p>
                    <p class="mb-1">Level Up Fitness - Gym Management System</p>
                    <p class="mb-0">For inquiries, please contact: admin@levelupfitness.com</p>
                </div>
            </div>

            <!-- Print Styles -->
            <style media="print">
                body { background: white; }
                .page-header { display: none; }
                .sidebar { display: none; }
                .navbar { display: none; }
                main { margin: 0; width: 100%; }
                .btn-group { display: none; }
                .invoice-container { border: none; padding: 0; margin: 0; }
            </style>

            <?php else: ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> Payment not found
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>

<?php include dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
