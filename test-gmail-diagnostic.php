<?php
/**
 * Gmail SMTP Diagnostic Test
 * Helps troubleshoot Gmail email configuration issues
 */

require_once dirname(__FILE__) . '/config/config.php';
require_once dirname(__FILE__) . '/config/database.php';
require_once dirname(__FILE__) . '/config/SMTPMailService.php';

echo "Gmail SMTP Configuration Diagnostic\n";
echo "====================================\n\n";

// Check configuration
echo "Current Configuration:\n";
echo "- SMTP Host: " . SMTP_HOST . "\n";
echo "- SMTP Port: " . SMTP_PORT . "\n";
echo "- SMTP Username: " . substr(SMTP_USERNAME, 0, 10) . "***\n";
echo "- From Email: " . MAIL_FROM_EMAIL . "\n";
echo "- From Name: " . MAIL_FROM_NAME . "\n\n";

// Check for Gmail-specific issues
echo "Validation Checks:\n";

// Issue 1: From address must match Gmail account
if (strpos(SMTP_USERNAME, '@gmail.com') !== false && strpos(MAIL_FROM_EMAIL, SMTP_USERNAME) === false) {
    echo "❌ ISSUE FOUND: From address doesn't match Gmail account\n";
    echo "   - Gmail Account: " . SMTP_USERNAME . "\n";
    echo "   - From Address: " . MAIL_FROM_EMAIL . "\n";
    echo "   - SOLUTION: Update MAIL_FROM_ADDRESS in .env to match Gmail account\n\n";
} else if (strpos(SMTP_USERNAME, '@gmail.com') !== false) {
    echo "✓ From address matches Gmail account\n";
}

// Issue 2: Check app password format
if (strlen(SMTP_PASSWORD) < 15) {
    echo "❌ ISSUE: App password might be incomplete\n";
    echo "   - Length: " . strlen(SMTP_PASSWORD) . " characters\n";
    echo "   - Expected: 16 characters (with spaces)\n\n";
} else {
    echo "✓ App password length OK\n";
}

// Try sending a test email
echo "\nAttempting test email...\n";
$testResult = SMTPMailService::send(
    'test@mailinator.com',
    'Level Up Fitness - Gmail Test',
    '<h1>Test Email</h1><p>If you see this, Gmail SMTP is working!</p>',
    '',
    []
);

if ($testResult['success']) {
    echo "✅ SUCCESS: Email sent to test@mailinator.com\n";
    echo "Message ID: " . ($testResult['message_id'] ?? 'N/A') . "\n";
} else {
    echo "❌ FAILED: " . $testResult['message'] . "\n";
}

?>
