<?php
/**
 * Payments Management - View Payment Details
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole('admin');

$paymentId = sanitize($_GET['id'] ?? '');
$payment = null;
$member = null;

if (!empty($paymentId)) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE payment_id = ?");
        $stmt->execute([$paymentId]);
        $payment = $stmt->fetch();
        
        if (!$payment) {
            setMessage('Payment not found', 'error');
            redirect(APP_URL . 'modules/payments/');
        }

        // Get member details
        $memberStmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
        $memberStmt->execute([$payment['member_id']]);
        $member = $memberStmt->fetch();

    } catch (Exception $e) {
        setMessage('Error loading payment: ' . $e->getMessage(), 'error');
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <div class="float-end">
                    <a href="<?php echo APP_URL; ?>modules/payments/invoice.php?id=<?php echo $paymentId; ?>" class="btn btn-info btn-sm">
                        <i class="fas fa-file-invoice"></i> View Invoice
                    </a>
                    <a href="<?php echo APP_URL; ?>modules/payments/edit.php?id=<?php echo $paymentId; ?>" class="btn btn-warning btn-sm">
                        <i class="fas fa-edit"></i> Edit
                    </a>
                    <a href="<?php echo APP_URL; ?>modules/payments/delete.php?id=<?php echo $paymentId; ?>" class="btn btn-danger btn-sm btn-delete">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </div>
                <a href="<?php echo APP_URL; ?>modules/payments/" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-receipt"></i> Payment Details</h1>
                <p>View payment information</p>
            </div>

            <?php displayMessage(); ?>

            <?php if ($payment): ?>
            <div class="row">
                <div class="col-md-4">
                    <div class="card mb-3">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Payment Information</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Payment ID:</strong><br>
                                <code><?php echo htmlspecialchars($payment['payment_id']); ?></code>
                            </p>
                            <hr>
                            <p>
                                <strong>Amount:</strong><br>
                                <h4><?php echo formatCurrency($payment['amount']); ?></h4>
                            </p>
                            <hr>
                            <p>
                                <strong>Status:</strong><br>
                                <span class="badge badge-<?php echo strtolower($payment['payment_status']); ?>" style="font-size: 14px;">
                                    <?php echo htmlspecialchars($payment['payment_status']); ?>
                                </span>
                            </p>
                            <hr>
                            <p>
                                <strong>Method:</strong><br>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($payment['payment_method']); ?></span>
                            </p>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Date Information</h5>
                        </div>
                        <div class="card-body">
                            <p>
                                <strong>Payment Date:</strong><br>
                                <?php echo formatDate($payment['payment_date']); ?>
                            </p>
                            <hr>
                            <p>
                                <strong>Recorded:</strong><br>
                                <?php echo formatDate($payment['created_at']); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card mb-3">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Member Information</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($member): ?>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p>
                                            <strong>Member ID:</strong><br>
                                            <code><?php echo htmlspecialchars($member['member_id']); ?></code>
                                        </p>
                                        <p>
                                            <strong>Name:</strong><br>
                                            <?php echo htmlspecialchars($member['member_name']); ?>
                                        </p>
                                        <p>
                                            <strong>Email:</strong><br>
                                            <a href="mailto:<?php echo htmlspecialchars($member['email']); ?>">
                                                <?php echo htmlspecialchars($member['email']); ?>
                                            </a>
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        <p>
                                            <strong>Phone:</strong><br>
                                            <a href="tel:<?php echo htmlspecialchars($member['contact_number']); ?>">
                                                <?php echo htmlspecialchars($member['contact_number']); ?>
                                            </a>
                                        </p>
                                        <p>
                                            <strong>Membership Type:</strong><br>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($member['membership_type']); ?></span>
                                        </p>
                                        <p>
                                            <strong>Status:</strong><br>
                                            <span class="badge badge-<?php echo strtolower($member['status']); ?>">
                                                <?php echo htmlspecialchars($member['status']); ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <a href="<?php echo APP_URL; ?>modules/members/view.php?id=<?php echo $member['member_id']; ?>" 
                                       class="btn btn-sm btn-info">
                                        <i class="fas fa-link"></i> View Member Profile
                                    </a>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Member information not available</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($payment['notes'])): ?>
                    <div class="card">
                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0">Notes</h5>
                        </div>
                        <div class="card-body">
                            <p><?php echo nl2br(htmlspecialchars($payment['notes'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
