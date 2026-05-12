<?php
require 'config/database.php';

echo "=== Testing INSERT into payment_gateway_transactions ===\n";

try {
    $transactionId = 'TEST-' . time();
    $stmt = $pdo->prepare("
        INSERT INTO payment_gateway_transactions (
            transaction_id, payment_id, member_id, gateway_name,
            gateway_transaction_id, gateway_reference_number,
            amount, currency, status, request_data, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $transactionId,
        'PAYMENT123',  // payment_id
        'MEM001',      // member_id - use real member ID
        'maya',        // gateway_name
        'GATE123',     // gateway_transaction_id
        'REF-123',     // gateway_reference_number
        1000.00,       // amount
        'PHP',         // currency
        'pending',     // status
        json_encode(['test' => 'data'])  // request_data
    ]);
    
    echo "✓ INSERT successful\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}
?>
