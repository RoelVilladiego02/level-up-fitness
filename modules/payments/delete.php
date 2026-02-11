<?php
/**
 * Payments Management - Delete Payment
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();
requireRole('admin');

$paymentId = sanitize($_GET['id'] ?? '');

if (empty($paymentId)) {
    setMessage('Invalid payment ID', 'error');
    redirect(APP_URL . 'modules/payments/');
}

try {
    // Get payment info first
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE payment_id = ?");
    $stmt->execute([$paymentId]);
    $payment = $stmt->fetch();

    if (!$payment) {
        setMessage('Payment not found', 'error');
        redirect(APP_URL . 'modules/payments/');
    }

    // If confirmed
    if (isset($_GET['confirm']) && $_GET['confirm'] == 'yes') {
        // Delete payment
        $deleteStmt = $pdo->prepare("DELETE FROM payments WHERE payment_id = ?");
        $deleteStmt->execute([$paymentId]);

        logAction($_SESSION['user_id'], 'DELETE_PAYMENT', 'Payments', 
                 'Deleted payment: ' . $paymentId . ' (' . formatCurrency($payment['amount']) . ')');

        setMessage('Payment deleted successfully', 'success');
        redirect(APP_URL . 'modules/payments/');
    }

    // If cancel
    if (isset($_GET['confirm']) && $_GET['confirm'] == 'no') {
        redirect(APP_URL . 'modules/payments/view.php?id=' . $paymentId);
    }

} catch (Exception $e) {
    setMessage('Error: ' . $e->getMessage(), 'error');
    redirect(APP_URL . 'modules/payments/');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Delete - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card border-danger">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h5>
                    </div>
                    <div class="card-body">
                        <p class="lead">Are you sure you want to delete this payment?</p>
                        
                        <div class="alert alert-danger">
                            <strong>Payment ID:</strong> <?php echo htmlspecialchars($payment['payment_id']); ?><br>
                            <strong>Amount:</strong> <?php echo formatCurrency($payment['amount']); ?><br>
                            <strong>Member:</strong> <?php echo htmlspecialchars($payment['member_id']); ?><br>
                            <strong>Date:</strong> <?php echo formatDate($payment['payment_date']); ?>
                        </div>

                        <p class="text-muted">
                            <i class="fas fa-info-circle"></i> 
                            This action cannot be undone.
                        </p>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="<?php echo APP_URL; ?>modules/payments/view.php?id=<?php echo $paymentId; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                            <a href="?id=<?php echo urlencode($paymentId); ?>&confirm=yes" class="btn btn-danger">
                                <i class="fas fa-trash"></i> Delete Payment
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
