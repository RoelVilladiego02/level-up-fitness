<?php
/**
 * Reports - Revenue Report
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

// Get report parameters
$startDate = sanitize($_GET['start_date'] ?? date('Y-m-01'));
$endDate = sanitize($_GET['end_date'] ?? date('Y-m-d'));

try {
    // Revenue by Payment Method
    $revenueByMethodStmt = $pdo->prepare("
        SELECT payment_method, COUNT(*) as transaction_count, SUM(amount) as total_amount, AVG(amount) as avg_amount
        FROM payments
        WHERE payment_status = 'Completed' AND DATE(payment_date) BETWEEN ? AND ?
        GROUP BY payment_method
        ORDER BY total_amount DESC
    ");
    $revenueByMethodStmt->execute([$startDate, $endDate]);
    $revenueByMethod = $revenueByMethodStmt->fetchAll();

    // Revenue by Day
    $revenueByDayStmt = $pdo->prepare("
        SELECT DATE(payment_date) as payment_day, SUM(amount) as daily_total, COUNT(*) as transaction_count
        FROM payments
        WHERE payment_status = 'Completed' AND DATE(payment_date) BETWEEN ? AND ?
        GROUP BY DATE(payment_date)
        ORDER BY payment_day DESC
    ");
    $revenueByDayStmt->execute([$startDate, $endDate]);
    $revenueByDay = $revenueByDayStmt->fetchAll();

    // Top Paying Members
    $topPayersStmt = $pdo->prepare("
        SELECT m.member_id, m.member_name, m.membership_type, SUM(p.amount) as total_paid, COUNT(p.payment_id) as payment_count
        FROM payments p
        JOIN members m ON p.member_id = m.member_id
        WHERE p.payment_status = 'Completed' AND DATE(p.payment_date) BETWEEN ? AND ?
        GROUP BY p.member_id
        ORDER BY total_paid DESC
        LIMIT 10
    ");
    $topPayersStmt->execute([$startDate, $endDate]);
    $topPayers = $topPayersStmt->fetchAll();

    // Payment Status Summary
    $paymentStatusStmt = $pdo->prepare("
        SELECT payment_status, COUNT(*) as count, SUM(amount) as total
        FROM payments
        WHERE DATE(payment_date) BETWEEN ? AND ?
        GROUP BY payment_status
    ");
    $paymentStatusStmt->execute([$startDate, $endDate]);
    $paymentStatus = $paymentStatusStmt->fetchAll();

    // Summary Statistics
    $summaryStmt = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN payment_status = 'Completed' THEN amount ELSE 0 END) as completed_revenue,
            SUM(CASE WHEN payment_status = 'Pending' THEN amount ELSE 0 END) as pending_revenue,
            SUM(CASE WHEN payment_status = 'Failed' THEN amount ELSE 0 END) as failed_amount,
            COUNT(*) as total_transactions,
            AVG(CASE WHEN payment_status = 'Completed' THEN amount END) as avg_transaction
        FROM payments
        WHERE DATE(payment_date) BETWEEN ? AND ?
    ");
    $summaryStmt->execute([$startDate, $endDate]);
    $summary = $summaryStmt->fetch();

} catch (Exception $e) {
    setMessage('Error loading revenue report: ' . $e->getMessage(), 'error');
}
?>

<div class="container-fluid">
    <div class="row">
        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
            
            <div class="page-header">
                <a href="<?php echo APP_URL; ?>modules/reports/" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
                <h1><i class="fas fa-chart-line"></i> Revenue Report</h1>
                <p>Financial performance and payment analysis</p>
            </div>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" 
                                   value="<?php echo htmlspecialchars($startDate); ?>">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" 
                                   value="<?php echo htmlspecialchars($endDate); ?>">
                        </div>
                        <div class="col-md-4 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-money-bill"></i> Completed Revenue</h6>
                            <h3>$<?php echo number_format($summary['completed_revenue'] ?? 0, 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-clock"></i> Pending Revenue</h6>
                            <h3>$<?php echo number_format($summary['pending_revenue'] ?? 0, 2); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-list"></i> Transactions</h6>
                            <h3><?php echo $summary['total_transactions'] ?? 0; ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h6 class="card-title mb-0"><i class="fas fa-dollar-sign"></i> Avg Transaction</h6>
                            <h3>$<?php echo number_format($summary['avg_transaction'] ?? 0, 2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-credit-card"></i> Revenue by Payment Method</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($revenueByMethod)): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>Method</th>
                                                <th class="text-end">Count</th>
                                                <th class="text-end">Total</th>
                                                <th class="text-end">Average</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($revenueByMethod as $method): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($method['payment_method']); ?></td>
                                                    <td class="text-end"><?php echo $method['transaction_count']; ?></td>
                                                    <td class="text-end font-weight-bold">$<?php echo number_format($method['total_amount'], 2); ?></td>
                                                    <td class="text-end">$<?php echo number_format($method['avg_amount'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mb-0">No data available</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-check-circle"></i> Payment Status Summary</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($paymentStatus)): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-sm">
                                        <thead>
                                            <tr>
                                                <th>Status</th>
                                                <th class="text-end">Count</th>
                                                <th class="text-end">Total Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($paymentStatus as $status): ?>
                                                <tr>
                                                    <td>
                                                        <span class="badge badge-<?php echo strtolower(str_replace('Completed', 'success', str_replace('Pending', 'warning', str_replace('Failed', 'danger', $status['payment_status'])))); ?>">
                                                            <?php echo htmlspecialchars($status['payment_status']); ?>
                                                        </span>
                                                    </td>
                                                    <td class="text-end"><?php echo $status['count']; ?></td>
                                                    <td class="text-end">$<?php echo number_format($status['total'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mb-0">No data available</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-users"></i> Top 10 Paying Members</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($topPayers)): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Member ID</th>
                                                <th>Member Name</th>
                                                <th>Membership Type</th>
                                                <th class="text-end">Payments</th>
                                                <th class="text-end">Total Paid</th>
                                                <th class="text-end">Avg Payment</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($topPayers as $payer): ?>
                                                <tr>
                                                    <td><code><?php echo htmlspecialchars($payer['member_id']); ?></code></td>
                                                    <td><?php echo htmlspecialchars($payer['member_name']); ?></td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo htmlspecialchars($payer['membership_type']); ?></span>
                                                    </td>
                                                    <td class="text-end"><?php echo $payer['payment_count']; ?></td>
                                                    <td class="text-end font-weight-bold">$<?php echo number_format($payer['total_paid'], 2); ?></td>
                                                    <td class="text-end">$<?php echo number_format($payer['total_paid'] / $payer['payment_count'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mb-0">No payment data available</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
