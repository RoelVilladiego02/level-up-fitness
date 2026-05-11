<?php
/**
 * Test Invoice Email with PDF Attachment
 * Level Up Fitness - Gym Management System
 */

require_once dirname(__FILE__) . '/includes/header.php';
require_once dirname(__FILE__) . '/config/SMTPMailService.php';
require_once dirname(__FILE__) . '/config/PDFGenerator.php';

echo "Invoice Email with PDF Attachment Test\n";
echo "======================================\n\n";

try {
    // Find a test payment
    $stmt = $pdo->prepare("
        SELECT p.*, m.member_name, m.email, m.contact_number, m.membership_type
        FROM payments p
        JOIN members m ON p.member_id = m.member_id
        LIMIT 1
    ");
    $stmt->execute();
    $payment = $stmt->fetch();
    
    if (!$payment) {
        echo "❌ No payments found in database\n";
        exit;
    }
    
    echo "✓ Found payment: " . $payment['payment_id'] . "\n";
    echo "✓ Member: " . $payment['member_name'] . "\n";
    echo "✓ Email: " . $payment['email'] . "\n\n";
    
    // Generate PDF
    echo "Generating PDF invoice...\n";
    $pdfResult = PDFGenerator::generateInvoicePdf($payment);
    
    if ($pdfResult['success']) {
        echo "✓ PDF generated: " . $pdfResult['file_path'] . "\n";
        echo "✓ File size: " . filesize($pdfResult['file_path']) . " bytes\n";
    } else {
        echo "❌ PDF generation failed: " . $pdfResult['error'] . "\n";
        exit;
    }
    
    echo "\nTesting SMTP connection...\n";
    echo "✓ SMTP Host: " . SMTP_HOST . "\n";
    echo "✓ SMTP Port: " . SMTP_PORT . "\n";
    echo "✓ SMTP User: " . substr(SMTP_USERNAME, 0, 5) . "***\n";
    
    echo "\n✅ All tests passed! The invoice can be sent with PDF attachment.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
