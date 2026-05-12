<?php
/**
 * Payment Webhook Handler
 * Level Up Fitness - Gym Management System
 * 
 * Receives and processes webhook callbacks from payment gateways (Maya, etc)
 * Updates payment status and triggers notifications
 */

require_once dirname(dirname(dirname(__FILE__))) . '/config/database.php';
require_once dirname(dirname(dirname(__FILE__))) . '/includes/email-notifications.php';
require_once dirname(dirname(dirname(__FILE__))) . '/includes/functions.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/MayaPaymentService.php';

// Get raw input
$rawInput = file_get_contents('php://input');

// Set response headers
header('Content-Type: application/json');

try {
    // Parse JSON
    $webhookData = json_decode($rawInput, true);
    
    if (!$webhookData) {
        throw new Exception('Invalid JSON payload');
    }
    
    // Extract gateway name from headers or payload
    $gateway = strtolower($webhookData['gateway'] ?? $_SERVER['HTTP_X_GATEWAY'] ?? 'maya');
    $transactionId = $webhookData['reference_number'] ?? $webhookData['transaction_id'] ?? null;
    
    if (!$transactionId) {
        throw new Exception('Missing transaction reference');
    }
    
    // ====================================================================
    // MAYA GATEWAY WEBHOOK
    // ====================================================================
    if ($gateway === 'maya') {
        
        // Initialize Maya service for signature verification
        $mayaService = new MayaPaymentService('sandbox');
        
        // Verify webhook signature
        if (!$mayaService->verifyWebhookSignature($webhookData, $webhookData['signature'] ?? '')) {
            throw new Exception('Invalid webhook signature - potential security issue');
        }
        
        // Process webhook
        $webhookResponse = $mayaService->processWebhookCallback($webhookData);
        
        if (!$webhookResponse['success']) {
            throw new Exception($webhookResponse['error']);
        }
        
        $paymentStatus = $webhookResponse['status'];
        $webhookTransactionId = $webhookResponse['transaction_id'];
        
    } else {
        throw new Exception("Unsupported gateway: {$gateway}");
    }
    
    // ====================================================================
    // UPDATE PAYMENT STATUS IN DATABASE
    // ====================================================================
    
    // Store webhook record
    $webhookId = 'WH-' . time() . '-' . substr(uniqid(), 0, 8);
    
    $webhookStmt = $pdo->prepare("
        INSERT INTO gateway_webhooks (
            webhook_id, transaction_id, gateway_name, event_type,
            payload, signature_verified, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $webhookStmt->execute([
        $webhookId,
        $transactionId,
        $gateway,
        $webhookData['event_type'] ?? 'payment_completed',
        $rawInput,
        true,
        'processing'
    ]);
    
    // Get transaction details
    $txnStmt = $pdo->prepare("
        SELECT * FROM payment_gateway_transactions
        WHERE transaction_id = ?
    ");
    $txnStmt->execute([$transactionId]);
    $transaction = $txnStmt->fetch();
    
    if (!$transaction) {
        throw new Exception("Transaction not found: {$transactionId}");
    }
    
    $paymentId = $transaction['payment_id'];
    $memberId = $transaction['member_id'];
    $amount = $transaction['amount'];
    
    // Update transaction status
    $updateTxnStmt = $pdo->prepare("
        UPDATE payment_gateway_transactions SET
            status = ?,
            response_data = ?,
            webhook_data = ?,
            completed_at = NOW()
        WHERE transaction_id = ?
    ");
    
    $updateTxnStmt->execute([
        $webhookResponse['status'],
        json_encode($webhookResponse),
        $rawInput,
        $transactionId
    ]);
    
    // Update webhook status
    $updateWebhookStmt = $pdo->prepare("
        UPDATE gateway_webhooks SET
            status = 'processed',
            processed_at = NOW()
        WHERE webhook_id = ?
    ");
    
    $updateWebhookStmt->execute([$webhookId]);
    
    // ====================================================================
    // UPDATE PAYMENT RECORD (if exists)
    // ====================================================================
    
    if (!empty($paymentId)) {
        // Map gateway status to system payment status
        $systemPaymentStatus = mapGatewayStatusToPaymentStatus($paymentStatus);
        
        // Check if this is an invoice payment (new system)
        $invoicePaymentStmt = $pdo->prepare("
            SELECT ip.*
            FROM invoice_payments ip
            WHERE ip.payment_id = ?
        ");
        $invoicePaymentStmt->execute([$paymentId]);
        $invoicePayment = $invoicePaymentStmt->fetch();
        
        if ($invoicePayment) {
            // NEW SYSTEM: Update invoice_payments table
            $invoicePaymentStatus = ($systemPaymentStatus === 'Paid') ? 'Paid' : 'Failed';
            
            $updateInvoicePaymentStmt = $pdo->prepare("
                UPDATE invoice_payments SET
                    payment_status = ?,
                    transaction_id = ?,
                    payment_date = NOW(),
                    updated_at = NOW()
                WHERE payment_id = ?
            ");
            
            $updateInvoicePaymentStmt->execute([
                $invoicePaymentStatus,
                $transactionId,
                $paymentId
            ]);
            
            // Auto-update invoice status if payment successful
            if ($systemPaymentStatus === 'Paid') {
                updateInvoiceStatus($invoicePayment['invoice_id']);
            }
            
        } else {
            // OLD SYSTEM: Update legacy payments table (backward compatibility)
            $updatePaymentStmt = $pdo->prepare("
                UPDATE payments SET
                    payment_status = ?,
                    gateway_transaction_id = ?
                WHERE payment_id = ?
            ");
            
            $updatePaymentStmt->execute([
                $systemPaymentStatus,
                $transactionId,
                $paymentId
            ]);
        }
        
        // Get payment details for notification
        if ($invoicePayment) {
            // Get from new system
            $paymentDetailStmt = $pdo->prepare("
                SELECT m.email, m.member_name, m.user_id, i.invoice_id, i.description
                FROM invoice_payments ip
                JOIN invoices i ON ip.invoice_id = i.invoice_id
                JOIN members m ON i.member_id = m.member_id
                WHERE ip.payment_id = ?
            ");
            $paymentDetailStmt->execute([$paymentId]);
            $payment = $paymentDetailStmt->fetch();
            $invoiceId = $payment['invoice_id'] ?? null;
        } else {
            // Get from old system
            $paymentStmt = $pdo->prepare("
                SELECT p.*, m.email, m.member_name, m.user_id
                FROM payments p
                JOIN members m ON p.member_id = m.member_id
                WHERE p.payment_id = ?
            ");
            $paymentStmt->execute([$paymentId]);
            $payment = $paymentStmt->fetch();
            $invoiceId = null;
        }
        
        // ====================================================================
        // SEND NOTIFICATIONS
        // ====================================================================
        
        if ($systemPaymentStatus === 'Paid') {
            // Payment successful
            
            // Send in-app notification
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
                "Your payment of ₱{$amount} has been successfully processed via {$gateway}.",
                false
            ]);
            
            // Send email notification
            try {
                $subject = 'Payment Confirmation - Level Up Fitness';
                $message = "
                    <h2>Payment Confirmation</h2>
                    <p>Hello {$payment['member_name']},</p>
                    <p>Your payment has been successfully processed.</p>
                    <table style='width: 100%; border-collapse: collapse;'>
                        <tr style='background-color: #f0f0f0;'>
                            <td style='padding: 10px; border: 1px solid #ddd;'><strong>Payment ID</strong></td>
                            <td style='padding: 10px; border: 1px solid #ddd;'>{$paymentId}</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; border: 1px solid #ddd;'><strong>Amount</strong></td>
                            <td style='padding: 10px; border: 1px solid #ddd;'>₱{$amount}</td>
                        </tr>
                        <tr style='background-color: #f0f0f0;'>
                            <td style='padding: 10px; border: 1px solid #ddd;'><strong>Method</strong></td>
                            <td style='padding: 10px; border: 1px solid #ddd;'>Online - {$gateway}</td>
                        </tr>
                        <tr>
                            <td style='padding: 10px; border: 1px solid #ddd;'><strong>Date</strong></td>
                            <td style='padding: 10px; border: 1px solid #ddd;'>" . date('F d, Y') . "</td>
                        </tr>
                    </table>
                    <p>Thank you for your payment!</p>
                ";
                
                sendEmailNotification($payment['email'], $subject, $message);
            } catch (Exception $e) {
                error_log("Payment notification email failed: " . $e->getMessage());
            }
            
            // Log action
            logAction(0, 'PAYMENT_COMPLETED', 'Payments', "Payment {$paymentId} completed via {$gateway}");
            
        } else if ($systemPaymentStatus === 'Pending') {
            // Payment pending or failed
            
            if ($paymentStatus === 'FAILED' || $paymentStatus === 'DECLINED') {
                // Payment failed
                
                $notifStmt->execute([
                    $payment['user_id'],
                    'payment',
                    'Payment Failed',
                    "Your payment of ₱{$amount} could not be processed. Please try again.",
                    false
                ]);
                
                try {
                    $subject = 'Payment Failed - Level Up Fitness';
                    $message = "
                        <h2>Payment Failed</h2>
                        <p>Hello {$payment['member_name']},</p>
                        <p>Unfortunately, your payment could not be processed.</p>
                        <p><strong>Amount:</strong> ₱{$amount}</p>
                        <p>Please try again or contact our support team for assistance.</p>
                    ";
                    
                    sendEmailNotification($payment['email'], $subject, $message);
                } catch (Exception $e) {
                    error_log("Payment failure email failed: " . $e->getMessage());
                }
                
                logAction(0, 'PAYMENT_FAILED', 'Payments', "Payment {$paymentId} failed via {$gateway}");
            }
        }
    }
    
    // ====================================================================
    // SEND SUCCESS RESPONSE TO GATEWAY
    // ====================================================================
    
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Webhook processed successfully',
        'webhook_id' => $webhookId,
        'transaction_id' => $transactionId,
        'code' => 'WEBHOOK_PROCESSED'
    ]);
    exit;
    
} catch (Exception $e) {
    error_log('Webhook Processing Error: ' . $e->getMessage());
    error_log('Raw Input: ' . $rawInput);
    
    // Log failed webhook
    try {
        $failedWebhookId = 'WH-FAILED-' . time() . '-' . substr(uniqid(), 0, 8);
        $webhookStmt = $pdo->prepare("
            INSERT INTO gateway_webhooks (
                webhook_id, transaction_id, gateway_name,
                payload, status, error_message, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $webhookStmt->execute([
            $failedWebhookId,
            $transactionId ?? 'UNKNOWN',
            $gateway ?? 'UNKNOWN',
            $rawInput,
            'failed',
            $e->getMessage()
        ]);
    } catch (Exception $logError) {
        error_log('Failed to log webhook error: ' . $logError->getMessage());
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => 'WEBHOOK_ERROR'
    ]);
    exit;
}

/**
 * Map Gateway Status to Payment Status
 */
function mapGatewayStatusToPaymentStatus($gatewayStatus) {
    $statusMap = [
        'COMPLETED' => 'Paid',
        'SUCCESS' => 'Paid',
        'PAID' => 'Paid',
        'PENDING' => 'Pending',
        'AUTHORIZED' => 'Pending',
        'PROCESSING' => 'Pending',
        'FAILED' => 'Pending',
        'DECLINED' => 'Pending',
        'CANCELLED' => 'Pending',
        'EXPIRED' => 'Overdue',
        'REVERSED' => 'Pending'
    ];
    
    return $statusMap[strtoupper($gatewayStatus)] ?? 'Pending';
}
?>
