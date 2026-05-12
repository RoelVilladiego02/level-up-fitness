<?php
// Delete orphaned/test pending payments
include 'config/config.php';
include 'includes/functions.php';

try {
    $pdo->beginTransaction();
    
    // Delete all pending payments for test invoices
    $stmt = $pdo->prepare("
        DELETE FROM invoice_payments 
        WHERE payment_status = 'Pending' 
        AND invoice_id IN ('INV1778600827465', 'INV1778600827658')
    ");
    $stmt->execute();
    
    $pdo->commit();
    echo "✅ Cleaned up " . $stmt->rowCount() . " pending test payments\n";
} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
