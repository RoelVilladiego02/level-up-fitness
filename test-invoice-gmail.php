<?php
/**
 * Test Invoice Email with Gmail
 * Level Up Fitness - Gym Management System
 */

require_once dirname(__FILE__) . '/includes/header.php';
require_once dirname(__FILE__) . '/config/SMTPMailService.php';
require_once dirname(__FILE__) . '/config/PDFGenerator.php';

echo "Testing Invoice Email with Gmail SMTP\n";
echo "=====================================\n\n";

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
    
    echo "Payment Details:\n";
    echo "- Payment ID: " . $payment['payment_id'] . "\n";
    echo "- Member: " . $payment['member_name'] . "\n";
    echo "- Email: " . $payment['email'] . "\n";
    echo "- Amount: ₱" . $payment['amount'] . "\n\n";
    
    // Generate PDF
    echo "Step 1: Generating PDF invoice...\n";
    $pdfResult = PDFGenerator::generateInvoicePdf($payment);
    
    if ($pdfResult['success']) {
        echo "✓ PDF generated (" . filesize($pdfResult['file_path']) . " bytes)\n\n";
    } else {
        echo "❌ PDF generation failed: " . $pdfResult['error'] . "\n";
        exit;
    }
    
    // Create HTML body
    echo "Step 2: Preparing email body...\n";
    $emailBody = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; }
            .invoice { max-width: 600px; margin: 0 auto; }
            .header { background: #4A90E2; color: white; padding: 20px; text-align: center; }
            .details { margin: 20px 0; }
            .footer { text-align: center; color: #666; font-size: 12px; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='invoice'>
            <div class='header'>
                <h2>Level Up Fitness - Invoice</h2>
            </div>
            <div class='details'>
                <p><strong>Invoice #:</strong> " . htmlspecialchars($payment['payment_id']) . "</p>
                <p><strong>Member:</strong> " . htmlspecialchars($payment['member_name']) . "</p>
                <p><strong>Amount:</strong> ₱" . number_format($payment['amount'], 2) . "</p>
                <p><strong>Method:</strong> " . htmlspecialchars($payment['payment_method']) . "</p>
                <p><strong>Status:</strong> " . htmlspecialchars($payment['payment_status']) . "</p>
            </div>
            <div class='footer'>
                <p>Thank you for your payment!</p>
                <p>Level Up Fitness</p>
            </div>
        </div>
    </body>
    </html>
    ";
    echo "✓ Email body prepared\n\n";
    
    // Send email
    echo "Step 3: Sending email with PDF attachment via Gmail SMTP...\n";
    $emailOptions = [
        'attachments' => [
            [
                'path' => $pdfResult['file_path'],
                'name' => 'invoice_' . $payment['payment_id'] . '.pdf'
            ]
        ]
    ];
    
    $result = SMTPMailService::send(
        $payment['email'],
        'Invoice for Payment - Level Up Fitness',
        $emailBody,
        '',
        $emailOptions
    );
    
    if ($result['success']) {
        echo "✅ SUCCESS!\n";
        echo "- Email sent to: " . $payment['email'] . "\n";
        echo "- With PDF: invoice_" . $payment['payment_id'] . ".pdf\n";
        echo "- Message ID: " . ($result['message_id'] ?? 'N/A') . "\n";
        echo "\n✓ Check your Gmail 'Sent' folder and the recipient's inbox (including spam folder)\n";
    } else {
        echo "❌ FAILED: " . $result['message'] . "\n";
    }
    
    // Clean up old files
    PDFGenerator::cleanupOldFiles();
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

?>
