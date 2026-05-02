<?php
require 'config/SMTPMailService.php';

echo "=== SMTP Connection Test ===\n";
echo "Host: " . SMTP_HOST . "\n";
echo "Port: " . SMTP_PORT . "\n";
echo "Username: " . substr(SMTP_USERNAME, 0, 5) . "...\n";
echo "\nAttempting connection...\n";

$result = SMTPMailService::testConnection();

echo "\n=== Result ===\n";
echo json_encode($result, JSON_PRETTY_PRINT);
echo "\n";

if ($result['success']) {
    echo "\n✓ SMTP connection successful!\n";
    
    echo "\n=== Sending test email ===\n";
    $testEmail = 'test@levelupfitness.local';
    echo "Sending test email to: $testEmail\n";
    
    $sendResult = SMTPMailService::sendTest($testEmail);
    echo json_encode($sendResult, JSON_PRETTY_PRINT);
    
    if ($sendResult['success']) {
        echo "\n✓ Test email sent successfully!\n";
        echo "Message ID: " . $sendResult['message_id'] . "\n";
    } else {
        echo "\n✗ Failed to send test email\n";
    }
} else {
    echo "\n✗ SMTP connection failed\n";
}
?>
