<?php
/**
 * Reports - Revenue Report
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

requireLogin();

// =========================
// FILTERS
// =========================
$startDate = sanitize($_GET['start_date'] ?? date('Y-m-01'));
$endDate = sanitize($_GET['end_date'] ?? date('Y-m-d'));
$paymentMethod = sanitize($_GET['payment_method'] ?? '');
$paymentStatusFilter = sanitize($_GET['payment_status'] ?? '');
$membershipType = sanitize($_GET['membership_type'] ?? '');

// Quick Date Filters
$quickFilter = sanitize($_GET['quick_filter'] ?? '');

switch ($quickFilter) {
    case 'today':
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d');
        break;

    case 'this_week':
        $startDate = date('Y-m-d', strtotime('monday this week'));
        $endDate = date('Y-m-d');
        break;

    case 'this_month':
        $startDate = date('Y-m-01');
        $endDate = date('Y-m-d');
        break;

    case 'last_month':
        $startDate = date('Y-m-01', strtotime('first day of last month'));
        $endDate = date('Y-m-t', strtotime('last day of last month'));
        break;

    case 'this_year':
        $startDate = date('Y-01-01');
        $endDate = date('Y-m-d');
        break;
}

// =========================
// BUILD FILTER CONDITIONS
// =========================
$whereConditions = [
    "DATE(p.payment_date) BETWEEN ? AND ?"
];

$params = [$startDate, $endDate];

if (!empty($paymentMethod)) {
    $whereConditions[] = "p.payment_method = ?";
    $params[] = $paymentMethod;
}

if (!empty($paymentStatusFilter)) {
    $whereConditions[] = "p.payment_status = ?";
    $params[] = $paymentStatusFilter;
}

if (!empty($membershipType)) {
    $whereConditions[] = "m.membership_type = ?";
    $params[] = $membershipType;
}

$whereClause = implode(' AND ', $whereConditions);

try {

    // =========================
    // FILTER OPTIONS
    // =========================
    $paymentMethodsStmt = $pdo->query("SELECT DISTINCT payment_method FROM invoice_payments ORDER BY payment_method ASC");
    $paymentMethods = $paymentMethodsStmt->fetchAll();

    $membershipTypesStmt = $pdo->query("SELECT DISTINCT membership_type FROM members ORDER BY membership_type ASC");
    $membershipTypes = $membershipTypesStmt->fetchAll();

    // =========================
    // SUMMARY STATISTICS
    // =========================
    $summaryStmt = $pdo->prepare("
        SELECT
            COALESCE(SUM(CASE WHEN p.payment_status = 'Paid' THEN p.amount ELSE 0 END), 0) as completed_revenue,
            COALESCE(SUM(CASE WHEN p.payment_status = 'Pending' THEN p.amount ELSE 0 END), 0) as pending_revenue,
            COALESCE(SUM(CASE WHEN p.payment_status = 'Overdue' THEN p.amount ELSE 0 END), 0) as overdue_revenue,
            COUNT(*) as total_transactions,
            COUNT(DISTINCT i.member_id) as total_members,
            COALESCE(AVG(CASE WHEN p.payment_status = 'Paid' THEN p.amount END), 0) as avg_transaction,
            COALESCE(MAX(CASE WHEN p.payment_status = 'Paid' THEN p.amount END), 0) as highest_payment
        FROM invoice_payments p
        LEFT JOIN invoices i ON p.invoice_id = i.invoice_id
        LEFT JOIN members m ON i.member_id = m.member_id
        WHERE $whereClause
    ");

    $summaryStmt->execute($params);
    $summary = $summaryStmt->fetch();

    // =========================
    // REVENUE BY PAYMENT METHOD
    // =========================
    $revenueByMethodStmt = $pdo->prepare("
        SELECT
            p.payment_method,
            COUNT(*) as transaction_count,
            SUM(p.amount) as total_amount,
            AVG(p.amount) as avg_amount
        FROM invoice_payments p
        LEFT JOIN invoices i ON p.invoice_id = i.invoice_id
        LEFT JOIN members m ON i.member_id = m.member_id
        WHERE $whereClause
        AND p.payment_status = 'Paid'
        GROUP BY p.payment_method
        ORDER BY total_amount DESC
    ");

    $revenueByMethodStmt->execute($params);
    $revenueByMethod = $revenueByMethodStmt->fetchAll();

    // =========================
    // DAILY REVENUE
    // =========================
    $revenueByDayStmt = $pdo->prepare("
        SELECT
            DATE(p.payment_date) as payment_day,
            SUM(CASE WHEN p.payment_status = 'Paid' THEN p.amount ELSE 0 END) as daily_total,
            COUNT(*) as transaction_count
        FROM invoice_payments p
        LEFT JOIN invoices i ON p.invoice_id = i.invoice_id
        LEFT JOIN members m ON i.member_id = m.member_id
        WHERE $whereClause
        GROUP BY DATE(p.payment_date)
        ORDER BY payment_day DESC
    ");

    $revenueByDayStmt->execute($params);
    $revenueByDay = $revenueByDayStmt->fetchAll();

    // =========================
    // TOP PAYING MEMBERS
    // =========================
    $topPayersStmt = $pdo->prepare("
        SELECT
            m.member_id,
            m.member_name,
            m.membership_type,
            SUM(p.amount) as total_paid,
            COUNT(p.payment_id) as payment_count,
            AVG(p.amount) as avg_payment
        FROM invoice_payments p
        JOIN invoices i ON p.invoice_id = i.invoice_id
        JOIN members m ON i.member_id = m.member_id
        WHERE $whereClause
        AND p.payment_status = 'Paid'
        GROUP BY m.member_id
        ORDER BY total_paid DESC
        LIMIT 10
    ");

    $topPayersStmt->execute($params);
    $topPayers = $topPayersStmt->fetchAll();

    // =========================
    // PAYMENT STATUS SUMMARY
    // =========================
    $paymentStatusStmt = $pdo->prepare("
        SELECT
            p.payment_status,
            COUNT(*) as count,
            SUM(p.amount) as total
        FROM invoice_payments p
        LEFT JOIN invoices i ON p.invoice_id = i.invoice_id
        LEFT JOIN members m ON i.member_id = m.member_id
        WHERE $whereClause
        GROUP BY p.payment_status
        ORDER BY total DESC
    ");

    $paymentStatusStmt->execute($params);
    $paymentStatus = $paymentStatusStmt->fetchAll();

    // =========================
    // RECENT TRANSACTIONS
    // =========================
    $recentTransactionsStmt = $pdo->prepare("
        SELECT
            p.payment_id,
            p.payment_date,
            p.payment_method,
            p.payment_status,
            p.amount,
            m.member_name,
            m.membership_type
        FROM invoice_payments p
        LEFT JOIN invoices i ON p.invoice_id = i.invoice_id
        LEFT JOIN members m ON i.member_id = m.member_id
        WHERE $whereClause
        ORDER BY p.payment_date DESC
        LIMIT 15
    ");

    $recentTransactionsStmt->execute($params);
    $recentTransactions = $recentTransactionsStmt->fetchAll();

} catch (Exception $e) {
    setMessage('Error loading revenue report: ' . $e->getMessage(), 'error');
}
?>

<div class="container-fluid">
    <div class="row">

        <?php include dirname(dirname(dirname(__FILE__))) . '/includes/sidebar.php'; ?>

        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">

            <div class="page-header mb-4">
                <a href="<?php echo APP_URL; ?>modules/reports/" class="btn btn-secondary btn-sm float-end">
                    <i class="fas fa-arrow-left"></i> Back
                </a>

                <h1>
                    <i class="fas fa-chart-line text-success"></i>
                    Revenue Report
                </h1>

                <p class="text-muted mb-0">
                    Financial analytics, payment tracking, and revenue monitoring.
                </p>
            </div>

            <!-- FILTERS -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-filter text-primary"></i>
                        Advanced Filters
                    </h5>
                </div>

                <div class="card-body">
                    <form method="GET" class="row g-3">

                        <div class="col-md-2">
                            <label class="form-label">Start Date</label>
                            <input
                                type="date"
                                class="form-control"
                                name="start_date"
                                value="<?php echo htmlspecialchars($startDate); ?>"
                            >
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">End Date</label>
                            <input
                                type="date"
                                class="form-control"
                                name="end_date"
                                value="<?php echo htmlspecialchars($endDate); ?>"
                            >
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" class="form-select">
                                <option value="">All Methods</option>

                                <?php foreach ($paymentMethods as $method): ?>
                                    <option
                                        value="<?php echo htmlspecialchars($method['payment_method']); ?>"
                                        <?php echo ($paymentMethod == $method['payment_method']) ? 'selected' : ''; ?>
                                    >
                                        <?php echo htmlspecialchars($method['payment_method']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Payment Status</label>
                            <select name="payment_status" class="form-select">
                                <option value="">All Status</option>
                                <option value="Paid" <?php echo ($paymentStatusFilter == 'Paid') ? 'selected' : ''; ?>>Paid</option>
                                <option value="Pending" <?php echo ($paymentStatusFilter == 'Pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="Overdue" <?php echo ($paymentStatusFilter == 'Overdue') ? 'selected' : ''; ?>>Overdue</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Membership Type</label>
                            <select name="membership_type" class="form-select">
                                <option value="">All Memberships</option>

                                <?php foreach ($membershipTypes as $type): ?>
                                    <option
                                        value="<?php echo htmlspecialchars($type['membership_type']); ?>"
                                        <?php echo ($membershipType == $type['membership_type']) ? 'selected' : ''; ?>
                                    >
                                        <?php echo htmlspecialchars($type['membership_type']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Quick Filter</label>
                            <select name="quick_filter" class="form-select">
                                <option value="">Custom Range</option>
                                <option value="today">Today</option>
                                <option value="this_week">This Week</option>
                                <option value="this_month">This Month</option>
                                <option value="last_month">Last Month</option>
                                <option value="this_year">This Year</option>
                            </select>
                        </div>

                        <div class="col-12 d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                                Apply Filters
                            </button>

                            <a href="revenue.php" class="btn btn-outline-secondary">
                                <i class="fas fa-undo"></i>
                                Reset
                            </a>

                            <button
                                type="button"
                                onclick="window.print()"
                                class="btn btn-success"
                            >
                                <i class="fas fa-print"></i>
                                Print Report
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- SUMMARY CARDS -->
            <div class="row mb-4">

                <div class="col-md-3 mb-3">
                    <div class="card border-0 shadow-sm bg-success text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Completed Revenue</h6>
                                    <h3 class="mb-0">
                                        $<?php echo number_format($summary['completed_revenue'] ?? 0, 2); ?>
                                    </h3>
                                </div>

                                <i class="fas fa-money-bill-wave fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card border-0 shadow-sm bg-warning text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Pending Revenue</h6>
                                    <h3 class="mb-0">
                                        $<?php echo number_format($summary['pending_revenue'] ?? 0, 2); ?>
                                    </h3>
                                </div>

                                <i class="fas fa-clock fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card border-0 shadow-sm bg-primary text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Transactions</h6>
                                    <h3 class="mb-0">
                                        <?php echo number_format($summary['total_transactions'] ?? 0); ?>
                                    </h3>
                                </div>

                                <i class="fas fa-receipt fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3 mb-3">
                    <div class="card border-0 shadow-sm bg-dark text-white h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Average Transaction</h6>
                                    <h3 class="mb-0">
                                        $<?php echo number_format($summary['avg_transaction'] ?? 0, 2); ?>
                                    </h3>
                                </div>

                                <i class="fas fa-chart-bar fa-2x opacity-75"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECONDARY SUMMARY -->
            <div class="row mb-4">

                <div class="col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="mb-3">
                                <i class="fas fa-users text-primary"></i>
                                Member Statistics
                            </h5>

                            <div class="row text-center">
                                <div class="col-6">
                                    <h3 class="text-primary">
                                        <?php echo number_format($summary['total_members'] ?? 0); ?>
                                    </h3>
                                    <small class="text-muted">Active Paying Members</small>
                                </div>

                                <div class="col-6">
                                    <h3 class="text-success">
                                        $<?php echo number_format($summary['highest_payment'] ?? 0, 2); ?>
                                    </h3>
                                    <small class="text-muted">Highest Payment</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-3">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h5 class="mb-3">
                                <i class="fas fa-calendar-alt text-success"></i>
                                Report Coverage
                            </h5>

                            <table class="table table-borderless mb-0">
                                <tr>
                                    <td><strong>Start Date:</strong></td>
                                    <td><?php echo date('F d, Y', strtotime($startDate)); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>End Date:</strong></td>
                                    <td><?php echo date('F d, Y', strtotime($endDate)); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Generated:</strong></td>
                                    <td><?php echo date('F d, Y h:i A'); ?></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TABLES -->
            <div class="row">

                <div class="col-lg-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0">
                                <i class="fas fa-credit-card text-primary"></i>
                                Revenue by Payment Method
                            </h5>
                        </div>

                        <div class="card-body">

                            <?php if (!empty($revenueByMethod)): ?>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Method</th>
                                                <th class="text-end">Transactions</th>
                                                <th class="text-end">Revenue</th>
                                                <th class="text-end">Average</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php foreach ($revenueByMethod as $method): ?>
                                                <tr>
                                                    <td>
                                                        <strong>
                                                            <?php echo htmlspecialchars($method['payment_method']); ?>
                                                        </strong>
                                                    </td>

                                                    <td class="text-end">
                                                        <?php echo number_format($method['transaction_count']); ?>
                                                    </td>

                                                    <td class="text-end text-success fw-bold">
                                                        $<?php echo number_format($method['total_amount'], 2); ?>
                                                    </td>

                                                    <td class="text-end">
                                                        $<?php echo number_format($method['avg_amount'], 2); ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                            <?php else: ?>

                                <div class="alert alert-info mb-0">
                                    No revenue data available.
                                </div>

                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card border-0 shadow-sm h-100">

                        <div class="card-header bg-white border-bottom">
                            <h5 class="mb-0">
                                <i class="fas fa-check-circle text-success"></i>
                                Payment Status Summary
                            </h5>
                        </div>

                        <div class="card-body">

                            <?php if (!empty($paymentStatus)): ?>

                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Status</th>
                                                <th class="text-end">Count</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            <?php foreach ($paymentStatus as $status): ?>

                                                <?php
                                                    $badgeClass = 'secondary';

                                                    if ($status['payment_status'] == 'Paid') {
                                                        $badgeClass = 'success';
                                                    } elseif ($status['payment_status'] == 'Pending') {
                                                        $badgeClass = 'warning';
                                                    } elseif ($status['payment_status'] == 'Overdue') {
                                                        $badgeClass = 'danger';
                                                    }
                                                ?>

                                                <tr>
                                                    <td>
                                                        <span class="badge bg-<?php echo $badgeClass; ?> px-3 py-2">
                                                            <?php echo htmlspecialchars($status['payment_status']); ?>
                                                        </span>
                                                    </td>

                                                    <td class="text-end">
                                                        <?php echo number_format($status['count']); ?>
                                                    </td>

                                                    <td class="text-end fw-bold">
                                                        $<?php echo number_format($status['total'], 2); ?>
                                                    </td>
                                                </tr>

                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                            <?php else: ?>

                                <div class="alert alert-info mb-0">
                                    No payment status data available.
                                </div>

                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TOP PAYERS -->
            <div class="card border-0 shadow-sm mb-4">

                <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-crown text-warning"></i>
                        Top Paying Members
                    </h5>

                    <span class="badge bg-primary">
                        Top 10
                    </span>
                </div>

                <div class="card-body">

                    <?php if (!empty($topPayers)): ?>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Member ID</th>
                                        <th>Member Name</th>
                                        <th>Membership</th>
                                        <th class="text-end">Payments</th>
                                        <th class="text-end">Average</th>
                                        <th class="text-end">Total Paid</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php foreach ($topPayers as $index => $payer): ?>

                                        <tr>
                                            <td>
                                                <span class="badge bg-dark">
                                                    <?php echo $index + 1; ?>
                                                </span>
                                            </td>

                                            <td>
                                                <code>
                                                    <?php echo htmlspecialchars($payer['member_id']); ?>
                                                </code>
                                            </td>

                                            <td>
                                                <strong>
                                                    <?php echo htmlspecialchars($payer['member_name']); ?>
                                                </strong>
                                            </td>

                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($payer['membership_type']); ?>
                                                </span>
                                            </td>

                                            <td class="text-end">
                                                <?php echo number_format($payer['payment_count']); ?>
                                            </td>

                                            <td class="text-end">
                                                $<?php echo number_format($payer['avg_payment'], 2); ?>
                                            </td>

                                            <td class="text-end text-success fw-bold">
                                                $<?php echo number_format($payer['total_paid'], 2); ?>
                                            </td>
                                        </tr>

                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php else: ?>

                        <div class="alert alert-info mb-0">
                            No top payer data available.
                        </div>

                    <?php endif; ?>
                </div>
            </div>

            <!-- RECENT TRANSACTIONS -->
            <div class="card border-0 shadow-sm mb-5">

                <div class="card-header bg-white border-bottom">
                    <h5 class="mb-0">
                        <i class="fas fa-history text-dark"></i>
                        Recent Transactions
                    </h5>
                </div>

                <div class="card-body">

                    <?php if (!empty($recentTransactions)): ?>

                        <div class="table-responsive">
                            <table class="table table-striped align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Member</th>
                                        <th>Membership</th>
                                        <th>Method</th>
                                        <th>Status</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>

                                <tbody>

                                    <?php foreach ($recentTransactions as $transaction): ?>

                                        <?php
                                            $statusClass = 'secondary';

                                            if ($transaction['payment_status'] == 'Paid') {
                                                $statusClass = 'success';
                                            } elseif ($transaction['payment_status'] == 'Pending') {
                                                $statusClass = 'warning';
                                            } elseif ($transaction['payment_status'] == 'Overdue') {
                                                $statusClass = 'danger';
                                            }
                                        ?>

                                        <tr>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($transaction['payment_date'])); ?>
                                            </td>

                                            <td>
                                                <?php echo htmlspecialchars($transaction['member_name']); ?>
                                            </td>

                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo htmlspecialchars($transaction['membership_type']); ?>
                                                </span>
                                            </td>

                                            <td>
                                                <?php echo htmlspecialchars($transaction['payment_method']); ?>
                                            </td>

                                            <td>
                                                <span class="badge bg-<?php echo $statusClass; ?>">
                                                    <?php echo htmlspecialchars($transaction['payment_status']); ?>
                                                </span>
                                            </td>

                                            <td class="text-end fw-bold">
                                                $<?php echo number_format($transaction['amount'], 2); ?>
                                            </td>
                                        </tr>

                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                    <?php else: ?>

                        <div class="alert alert-info mb-0">
                            No recent transactions available.
                        </div>

                    <?php endif; ?>
                </div>
            </div>

        </main>
    </div>
</div>

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
