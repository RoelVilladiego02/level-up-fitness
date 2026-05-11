<?php
/**
 * Mock Webhook Handler
 * Level Up Fitness - Gym Management System
 * 
 * Simulates Maya webhook callbacks for testing
 * Only works in sandbox mode
 */

require_once dirname(dirname(dirname(__FILE__))) . '/config/database.php';
require_once dirname(dirname(dirname(__FILE__))) . '/includes/functions.php';
require_once dirname(dirname(dirname(__FILE__))) . '/includes/email-notifications.php';

// Get parameters
$transactionId = sanitize($_GET['transaction_id'] ?? '');
$status = sanitize($_GET['status'] ?? 'completed');

if (empty($transactionId)) {
    http_response_code(400);
    die('Invalid transaction');
}

try {
    // Get transaction details from database
    $txnStmt = $pdo->prepare("
        SELECT * FROM payment_gateway_transactions
        WHERE transaction_id = ?
    ");
    $txnStmt->execute([$transactionId]);
    $transaction = $txnStmt->fetch();
    
    if (!$transaction) {
        http_response_code(404);
        die('Transaction not found');
    }
    
    // Map mock status to system status
    $systemStatus = 'failed'; // default
    if ($status === 'completed' || $status === 'success') {
        $systemStatus = 'completed';
    } elseif ($status === 'cancelled') {
        $systemStatus = 'failed';
    }
    
    // Update transaction status
    $updateTxnStmt = $pdo->prepare("
        UPDATE payment_gateway_transactions SET
            status = ?,
            response_data = ?,
            webhook_data = ?,
            completed_at = NOW(),
            updated_at = NOW()
        WHERE transaction_id = ?
    ");
    
    $mockResponse = [
        'transaction_id' => $transactionId,
        'status' => $status,
        'amount' => $transaction['amount'],
        'timestamp' => date('Y-m-d H:i:s'),
        'is_mock' => true
    ];
    
    $updateTxnStmt->execute([
        $systemStatus,
        json_encode($mockResponse),
        json_encode($mockResponse),
        $transactionId
    ]);
    
    // Update payment record if exists
    $paymentId = $transaction['payment_id'];
    if (!empty($paymentId)) {
        // Map status to payment status
        $paymentStatus = 'Paid';
        if ($systemStatus === 'failed') {
            $paymentStatus = 'Overdue';
        } elseif ($systemStatus === 'pending') {
            $paymentStatus = 'Pending';
        }
        
        $updatePaymentStmt = $pdo->prepare("
            UPDATE payments SET
                payment_status = ?,
                gateway_transaction_id = ?,
                updated_at = NOW()
            WHERE payment_id = ?
        ");
        
        $updatePaymentStmt->execute([
            $paymentStatus,
            $transactionId,
            $paymentId
        ]);
        
        // Get payment details
        $paymentStmt = $pdo->prepare("
            SELECT p.*, m.email, m.member_name, m.user_id, m.member_id
            FROM payments p
            JOIN members m ON p.member_id = m.member_id
            WHERE p.payment_id = ?
        ");
        $paymentStmt->execute([$paymentId]);
        $payment = $paymentStmt->fetch();
        
        if ($payment) {
            // Send notification if payment successful
            if ($paymentStatus === 'Paid') {
                // In-app notification
                $notifStmt = $pdo->prepare("
                    INSERT INTO notifications (
                        user_id, notification_type, notification_title,
                        notification_message, is_read, created_at
                    ) VALUES (?, ?, ?, ?, ?, NOW())
                ");
                
                $notifStmt->execute([
                    $payment['user_id'],
                    'payment',
                    'Payment Successful',
                    "Your test payment of ₱{$transaction['amount']} has been processed (Mock/Sandbox)",
                    0
                ]);
            }
        }
    }
    
    // Redirect based on status
    $redirectUrl = 'http://localhost/level-up-fitness/modules/payments/';
    
    if ($systemStatus === 'completed') {
        // Success - redirect to payments page
        setcookie('payment_success', '1', time() + 5);
        header('Location: ' . $redirectUrl . '?success=Payment processed successfully (SANDBOX)');
    } else {
        // Failed/Cancelled
        header('Location: ' . $redirectUrl . '?error=Payment ' . ucfirst($status) . ' (SANDBOX)');
    }
    
} catch (Exception $e) {
    error_log('Mock Webhook Error: ' . $e->getMessage());
    http_response_code(500);
    die('Error processing payment: ' . $e->getMessage());
}
