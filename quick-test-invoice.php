<?php
require_once dirname(__FILE__) . '/config/config.php';
require_once dirname(__FILE__) . '/config/database.php';
require_once dirname(__FILE__) . '/config/SMTPMailService.php';
require_once dirname(__FILE__) . '/config/PDFGenerator.php';

echo "Quick Invoice Test\n";
echo "==================\n\n";

try {
    $payment = $pdo->query("SELECT p.*, m.member_name, m.email, m.contact_number, m.membership_type FROM payments p JOIN members m ON p.member_id = m.member_id LIMIT 1")->fetch();
    
    if ($payment) {
        echo "Member: " . $payment['member_name'] . "\n";
        echo "Email: " . $payment['email'] . "\n\n";
        
        $result = PDFGenerator::generateInvoicePdf($payment);
        
        if ($result['success']) {
            echo "✅ Invoice file created successfully\n";
            echo "File: " . basename($result['file_path']) . "\n";
            echo "Type: " . $result['file_type'] . "\n";
            echo "Size: " . filesize($result['file_path']) . " bytes\n";
            
            // Show first 500 chars to verify content
            echo "\nFirst 500 characters of content:\n";
            echo substr(file_get_contents($result['file_path']), 0, 500) . "\n";
        } else {
            echo "❌ Failed: " . $result['error'] . "\n";
        }
    } else {
        echo "No payments found\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
