<?php
/**
 * Test Email Template Rendering
 * Verify that CSS styling is included in rendered email
 */

require_once 'config/database.php';
require_once 'includes/email-notifications.php';

// Test variables for email verification
$testVariables = [
    'member_name' => 'Joshua Fresas',
    'email' => 'bogsmapagmahal@gmail.com',
    'verification_code' => '123456',
    'verification_link' => 'https://levelupfitness.local/auth/verify-email.php?token=abc123def456',
    'dashboard_url' => 'https://levelupfitness.local/dashboard/',
    'support_url' => 'https://levelupfitness.local/support/',
    'website_url' => 'https://levelupfitness.local/'
];

try {
    $rendered = renderEmailTemplate('email-verification', $testVariables);
    
    // Save to file for inspection
    file_put_contents('test-email-output.html', $rendered);
    
    echo "✓ Email template rendered successfully\n";
    echo "✓ Output saved to test-email-output.html\n";
    echo "\nChecking for CSS...\n";
    
    if (strpos($rendered, '<style>') !== false && strpos($rendered, '.email-container') !== false) {
        echo "✓ CSS is present in the rendered email\n";
    } else {
        echo "✗ CSS is missing from the rendered email!\n";
    }
    
    // Check for charset
    if (strpos($rendered, 'charset=UTF-8') !== false || strpos($rendered, 'charset="UTF-8"') !== false) {
        echo "✓ UTF-8 charset is declared\n";
    } else {
        echo "⚠ UTF-8 charset not explicitly found (may still be set by mail service)\n";
    }
    
    // Check for body wrapper
    if (strpos($rendered, 'email-wrapper') !== false) {
        echo "✓ HTML structure is complete (has email-wrapper)\n";
    } else {
        echo "✗ HTML structure incomplete!\n";
    }
    
    echo "\nFirst 500 characters:\n";
    echo substr($rendered, 0, 500) . "\n";
    
} catch (Exception $e) {
    echo "✗ Error rendering template: " . $e->getMessage() . "\n";
}
?>
