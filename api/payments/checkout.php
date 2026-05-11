<?php
/**
 * Payment API - Checkout Endpoint
 * Level Up Fitness - Gym Management System
 * 
 * Handles payment initiation and checkout flow for online payments
 * Supports Maya and other payment gateways
 */

require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';
require_once dirname(dirname(dirname(__FILE__))) . '/config/MayaPaymentService.php';

// Only allow authenticated requests
requireLogin();

// Set response headers
header('Content-Type: application/json');

// Allow only POST requests for checkout
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed. Use POST.',
        'code' => 'METHOD_NOT_ALLOWED'
    ]);
    exit;
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (!isset($input['gateway']) || !isset($input['amount'])) {
        throw new Exception('Gateway and amount are required');
    }
    
    $gateway = sanitize($input['gateway']);
    $amount = floatval($input['amount']);
    $paymentId = sanitize($input['payment_id'] ?? '');
    $description = sanitize($input['description'] ?? 'Gym Membership Payment');
    
    // Validate amount
    if ($amount <= 0) {
        throw new Exception('Amount must be greater than 0');
    }
    
    // Get current user information
    $userStmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $userStmt->execute([$_SESSION['user_id']]);
    $user = $userStmt->fetch();
    
    if (!$user) {
        throw new Exception('User not found');
    }
    
    // Get member information
    $memberStmt = $pdo->prepare("SELECT * FROM members WHERE user_id = ?");
    $memberStmt->execute([$_SESSION['user_id']]);
    $member = $memberStmt->fetch();
    
    if (!$member) {
        throw new Exception('Member profile not found');
    }
    
    // ====================================================================
    // MAYA GATEWAY - ONLINE PAYMENT
    // ====================================================================
    if (strtolower($gateway) === 'maya') {
        
        // Initialize Maya Payment Service
        $mayaService = new MayaPaymentService('sandbox'); // Use sandbox for testing
        
        // Prepare payment data
        $paymentData = [
            'member_id' => $member['member_id'],
            'payment_id' => $paymentId,
            'amount' => $amount,
            'description' => $description,
            'email' => $user['email'],
            'phone' => $member['contact_number'] ?? '',
            'first_name' => $user['first_name'] ?? '',
            'last_name' => $user['last_name'] ?? '',
            'address' => $member['address'] ?? '',
            'city' => $member['city'] ?? '',
            'state' => $member['state'] ?? '',
            'postal_code' => $member['postal_code'] ?? '',
        ];
        
        // Create payment request with Maya
        $mayaResponse = $mayaService->createPaymentRequest($paymentData);
        
        if (!$mayaResponse['success']) {
            throw new Exception('Failed to create Maya payment: ' . $mayaResponse['error']);
        }
        
        // Store transaction in database
        $transactionId = $mayaResponse['transaction_id'];
        $gatewayTransactionId = $mayaResponse['reference_number'];
        
        // Insert into payment_gateway_transactions
        $txnStmt = $pdo->prepare("
            INSERT INTO payment_gateway_transactions (
                transaction_id, payment_id, member_id, gateway_name,
                gateway_transaction_id, gateway_reference_number,
                amount, currency, status, request_data, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $txnStmt->execute([
            $transactionId,
            $paymentId,
            $member['member_id'],
            'maya',
            $gatewayTransactionId,
            $mayaResponse['reference_number'],
            $amount,
            'PHP',
            'pending',
            json_encode($paymentData)
        ]);
        
        // Update payments table if payment_id exists
        if (!empty($paymentId)) {
            $updateStmt = $pdo->prepare("
                UPDATE payments SET
                    payment_gateway = 'maya',
                    gateway_transaction_id = ?,
                    gateway_reference_number = ?,
                    payment_attempt_count = payment_attempt_count + 1
                WHERE payment_id = ?
            ");
            $updateStmt->execute([$transactionId, $gatewayTransactionId, $paymentId]);
        }
        
        // Log transaction
        logAction(
            $_SESSION['user_id'],
            'PAYMENT_CHECKOUT',
            'Payments',
            "Initiated Maya payment: {$transactionId} - Amount: ₱{$amount}"
        );
        
        // Return checkout link to frontend
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Payment checkout initiated',
            'code' => 'CHECKOUT_SUCCESS',
            'data' => [
                'transaction_id' => $transactionId,
                'checkout_url' => $mayaResponse['checkout_url'],
                'reference_number' => $mayaResponse['reference_number'],
                'amount' => $amount,
                'gateway' => 'maya',
                'status' => 'pending'
            ]
        ]);
        exit;
    }
    
    // ====================================================================
    // MANUAL PAYMENT METHOD
    // ====================================================================
    else if (strtolower($gateway) === 'manual') {
        
        // Create record for manual payment processing
        $transactionId = 'MANUAL-' . time() . '-' . substr(uniqid(), 0, 8);
        
        // Insert into payment_gateway_transactions
        $txnStmt = $pdo->prepare("
            INSERT INTO payment_gateway_transactions (
                transaction_id, payment_id, member_id, gateway_name,
                amount, currency, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $txnStmt->execute([
            $transactionId,
            $paymentId,
            $member['member_id'],
            'manual',
            $amount,
            'PHP',
            'pending'
        ]);
        
        // Log transaction
        logAction(
            $_SESSION['user_id'],
            'PAYMENT_MANUAL',
            'Payments',
            "Submitted manual payment: {$transactionId} - Amount: ₱{$amount}"
        );
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => 'Manual payment request received',
            'code' => 'MANUAL_PAYMENT_RECEIVED',
            'data' => [
                'transaction_id' => $transactionId,
                'amount' => $amount,
                'gateway' => 'manual',
                'status' => 'pending',
                'next_steps' => 'An admin will verify and process your payment within 24-48 hours'
            ]
        ]);
        exit;
    }
    
    // Invalid gateway
    else {
        throw new Exception("Payment gateway '{$gateway}' is not supported");
    }
    
} catch (Exception $e) {
    error_log('Payment Checkout Error: ' . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'code' => 'CHECKOUT_FAILED'
    ]);
    exit;
}
?>
