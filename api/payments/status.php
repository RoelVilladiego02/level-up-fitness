<?php
/**
 * Payment API - Status Check Endpoint
 * Level Up Fitness - Gym Management System
 * 
 * Check payment status via transaction ID
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/api-init.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/MayaPaymentService.php';

// Set response headers
header('Content-Type: application/json');

// Allow GET or POST
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]);
    exit;
}

try {
    // Get transaction ID
    $transactionId = sanitize($_GET['transaction_id'] ?? $_POST['transaction_id'] ?? '');
    
    if (empty($transactionId)) {
        throw new Exception('Transaction ID is required');
    }
    
    // Get transaction from database
    $txnStmt = $pdo->prepare("
        SELECT * FROM payment_gateway_transactions
        WHERE transaction_id = ? AND member_id IN (
            SELECT member_id FROM members WHERE user_id = ?
        )
    ");
    $txnStmt->execute([$transactionId, $_SESSION['user_id']]);
    $transaction = $txnStmt->fetch();
    
    if (!$transaction) {
        throw new Exception('Transaction not found');
    }
    
    // If gateway is Maya, check with external service
    if ($transaction['gateway_name'] === 'maya' && empty($transaction['response_data'])) {
        
        $mayaService = new MayaPaymentService('sandbox');
        $statusResponse = $mayaService->checkTransactionStatus($transactionId);
        
        if ($statusResponse['success']) {
            // Update local record
            $updateStmt = $pdo->prepare("
                UPDATE payment_gateway_transactions SET
                    status = ?,
                    response_data = ?,
                    updated_at = NOW()
                WHERE transaction_id = ?
            ");
            
            $updateStmt->execute([
                $statusResponse['status'],
                json_encode($statusResponse),
                $transactionId
            ]);
            
            $transaction['status'] = $statusResponse['status'];
            $transaction['response_data'] = json_encode($statusResponse);
        }
    }
    
    // Return transaction status
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'data' => [
            'transaction_id' => $transaction['transaction_id'],
            'payment_id' => $transaction['payment_id'],
            'amount' => $transaction['amount'],
            'currency' => $transaction['currency'],
            'status' => $transaction['status'],
            'gateway' => $transaction['gateway_name'],
            'created_at' => $transaction['created_at'],
            'completed_at' => $transaction['completed_at'],
            'payment_method' => $transaction['payment_method'] ?? null
        ]
    ]);
    exit;
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    exit;
}
?>
