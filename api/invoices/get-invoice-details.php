<?php
/**
 * Get Invoice Details API
 * Returns invoice details as JSON with HTML
 */

header('Content-Type: application/json');

// Don't use header.php to avoid session redirects
require_once dirname(dirname(dirname(__FILE__))) . '/config/config.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/database.php';
require_once dirname(dirname(dirname(__FILE__))) . '/includes/functions.php';

session_start();

// Check if user is logged in
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$invoiceId = sanitize($_GET['id'] ?? '');

if (empty($invoiceId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invoice ID required']);
    exit;
}

try {
    $isAdmin = $_SESSION['user_type'] === 'admin';
    $currentMemberId = null;
    
    // Get member ID if user is a member
    if (!$isAdmin && $_SESSION['user_type'] === 'member') {
        $memberStmt = $pdo->prepare("SELECT member_id FROM members WHERE user_id = ?");
        $memberStmt->execute([$_SESSION['user_id']]);
        $member = $memberStmt->fetch();
        $currentMemberId = $member['member_id'] ?? null;
    }
    
    // Get invoice details
    $stmt = $pdo->prepare("
        SELECT 
            i.*,
            m.member_name, m.email, m.contact_number,
            COALESCE(SUM(ip.amount), 0) as paid_amount,
            (i.amount - COALESCE(SUM(ip.amount), 0)) as outstanding_amount
        FROM invoices i
        JOIN members m ON i.member_id = m.member_id
        LEFT JOIN invoice_payments ip ON i.invoice_id = ip.invoice_id AND ip.payment_status = 'Paid'
        WHERE i.invoice_id = ?
        GROUP BY i.invoice_id
    ");
    $stmt->execute([$invoiceId]);
    $invoice = $stmt->fetch();
    
    if (!$invoice) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Invoice not found']);
        exit;
    }
    
    // Check permission
    if (!$isAdmin && $invoice['member_id'] !== $currentMemberId) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Access denied']);
        exit;
    }
    
    // Get all payments for this invoice
    $paymentsStmt = $pdo->prepare("
        SELECT * FROM invoice_payments 
        WHERE invoice_id = ? 
        ORDER BY payment_date DESC
    ");
    $paymentsStmt->execute([$invoiceId]);
    $payments = $paymentsStmt->fetchAll();
    
    // Generate HTML
    $html = '
    <div class="row mb-3">
        <div class="col-md-6">
            <h6 class="text-muted">Invoice ID</h6>
            <p>' . htmlspecialchars($invoice['invoice_id']) . '</p>
        </div>
        <div class="col-md-6">
            <h6 class="text-muted">Status</h6>
            <p><span class="badge bg-' . ($invoice['invoice_status'] === 'Paid' ? 'success' : ($invoice['invoice_status'] === 'Partially Paid' ? 'warning' : 'danger')) . '">' . htmlspecialchars($invoice['invoice_status']) . '</span></p>
        </div>
    </div>
    
    <div class="row mb-3">
        <div class="col-md-6">
            <h6 class="text-muted">Member Name</h6>
            <p>' . htmlspecialchars($invoice['member_name']) . '</p>
        </div>
        <div class="col-md-6">
            <h6 class="text-muted">Email</h6>
            <p>' . htmlspecialchars($invoice['email']) . '</p>
        </div>
    </div>
    
    <div class="row mb-3">
        <div class="col-md-6">
            <h6 class="text-muted">Description</h6>
            <p>' . htmlspecialchars($invoice['description']) . '</p>
        </div>
        <div class="col-md-6">
            <h6 class="text-muted">Due Date</h6>
            <p>' . formatDate($invoice['due_date']) . '</p>
        </div>
    </div>
    
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body text-center">
                    <h6 class="text-muted">Total Amount</h6>
                    <h4>' . formatCurrency($invoice['amount']) . '</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body text-center">
                    <h6 class="text-muted">Paid Amount</h6>
                    <h4>' . formatCurrency($invoice['paid_amount']) . '</h4>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-light">
                <div class="card-body text-center">
                    <h6 class="text-muted">Outstanding</h6>
                    <h4>' . formatCurrency($invoice['outstanding_amount']) . '</h4>
                </div>
            </div>
        </div>
    </div>
    ';
    
    if (!empty($payments)) {
        $html .= '
        <h6 class="mt-3 mb-2">Payment History</h6>
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead class="table-light">
                    <tr>
                        <th>Payment ID</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
        ';
        
        foreach ($payments as $payment) {
            $statusColor = $payment['payment_status'] === 'Paid' ? 'success' : ($payment['payment_status'] === 'Pending' ? 'warning' : 'danger');
            $html .= '
                    <tr>
                        <td><code>' . htmlspecialchars($payment['payment_id']) . '</code></td>
                        <td>' . formatCurrency($payment['amount']) . '</td>
                        <td><span class="badge bg-secondary">' . htmlspecialchars($payment['payment_method']) . '</span></td>
                        <td>' . formatDate($payment['payment_date']) . '</td>
                        <td><span class="badge bg-' . $statusColor . '">' . htmlspecialchars($payment['payment_status']) . '</span></td>
                    </tr>
            ';
        }
        
        $html .= '
                </tbody>
            </table>
        </div>
        ';
    }
    
    echo json_encode(['success' => true, 'html' => $html]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Error loading invoice: ' . $e->getMessage()]);
}
?>
