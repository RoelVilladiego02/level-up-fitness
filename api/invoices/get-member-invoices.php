<?php
/**
 * API: Get Member Invoices
 * Returns JSON list of pending invoices for a member
 * Used by: /modules/payments/add.php (admin manual payment form)
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !hasRole('admin')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$memberId = sanitize($_GET['member_id'] ?? '');

if (empty($memberId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Member ID required']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            i.invoice_id,
            i.description,
            i.amount,
            i.due_date,
            COALESCE(SUM(ip.amount), 0) as paid_amount,
            (i.amount - COALESCE(SUM(ip.amount), 0)) as outstanding_amount
        FROM invoices i
        LEFT JOIN invoice_payments ip ON i.invoice_id = ip.invoice_id AND ip.payment_status = 'Paid'
        WHERE i.member_id = ? AND i.invoice_status != 'Cancelled' AND (i.amount - COALESCE(SUM(ip.amount), 0)) > 0
        GROUP BY i.invoice_id
        ORDER BY i.due_date ASC
    ");
    
    $stmt->execute([$memberId]);
    $invoices = $stmt->fetchAll();
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'invoices' => $invoices
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log('Error loading invoices: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
