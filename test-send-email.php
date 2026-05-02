<?php
/**
 * Quick test - Send verification email
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/email-notifications.php';

echo "Testing email verification system...\n\n";

// Create test user
$testEmail = 'test-member-' . time() . '@example.com';
$testName = 'Test Member ' . time();

echo "1. Creating test user...\n";
$password = hashPassword('TestPassword123!');
$userStmt = $pdo->prepare("INSERT INTO users (email, password, user_type) VALUES (?, ?, ?)");
if ($userStmt->execute([$testEmail, $password, 'member'])) {
    $userId = $pdo->lastInsertId();
    echo "   ✓ User created (ID: $userId)\n\n";
    
    // Generate token
    echo "2. Generating verification token...\n";
    $token = generateVerificationToken($userId, 24);
    if ($token) {
        echo "   ✓ Token generated: $token\n\n";
        
        // Send email
        echo "3. Sending verification email...\n";
        $memberData = [
            'member_id' => 'TEMP001',
            'membership_type' => 'Premium',
            'trainer_name' => 'John Trainer'
        ];
        
        try {
            $result = sendEmailVerificationEmail($testEmail, $testName, $token, $memberData, 24);
            echo "   Email sending result: " . var_export($result, true) . "\n";
            echo "   ✓ Verification email call completed\n\n";
            
            echo "Verification link: " . APP_URL . "auth/verify-email.php?token=$token\n";
            echo "Test email address: $testEmail\n";
        } catch (Exception $e) {
            echo "   ✗ Error sending email: " . $e->getMessage() . "\n";
        }
    } else {
        echo "   ✗ Failed to generate token\n";
    }
} else {
    echo "   ✗ Failed to create user\n";
}
?>