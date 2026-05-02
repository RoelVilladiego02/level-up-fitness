<?php
/**
 * Detailed Debug - Email Verification System
 * Tracks every step of the email sending process
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/email-notifications.php';

echo "=== EMAIL VERIFICATION DEBUG ===\n\n";

// Step 1: Verify includes
echo "Step 1: Checking includes...\n";
echo "  SMTPMailService defined: " . (class_exists('SMTPMailService') ? 'YES' : 'NO') . "\n";
echo "  renderEmailTemplate function exists: " . (function_exists('renderEmailTemplate') ? 'YES' : 'NO') . "\n";
echo "  sendEmailVerificationEmail function exists: " . (function_exists('sendEmailVerificationEmail') ? 'YES' : 'NO') . "\n";
echo "  generateVerificationToken function exists: " . (function_exists('generateVerificationToken') ? 'YES' : 'NO') . "\n\n";

// Step 2: Test database
echo "Step 2: Testing database...\n";
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "  Users table accessible: YES (Total users: " . $result['count'] . ")\n";
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM verification_tokens");
    $stmt->execute();
    $result = $stmt->fetch();
    echo "  Verification tokens table accessible: YES (Total tokens: " . $result['count'] . ")\n\n";
} catch (Exception $e) {
    echo "  Database error: " . $e->getMessage() . "\n\n";
}

// Step 3: Test token generation
echo "Step 3: Testing token generation...\n";
$testEmail = 'debug-' . time() . '@example.com';
$testName = 'Debug Test ' . time();

try {
    $password = hashPassword('TestPass123!');
    $userStmt = $pdo->prepare("INSERT INTO users (email, password, user_type) VALUES (?, ?, ?)");
    $userStmt->execute([$testEmail, $password, 'member']);
    $userId = $pdo->lastInsertId();
    echo "  Test user created (ID: $userId, Email: $testEmail)\n";
    
    $token = generateVerificationToken($userId, 24);
    if ($token) {
        echo "  ✓ Token generated: " . substr($token, 0, 8) . "...\n";
        
        // Verify token in DB
        $checkStmt = $pdo->prepare("SELECT * FROM verification_tokens WHERE token = ?");
        $checkStmt->execute([$token]);
        $tokenRow = $checkStmt->fetch();
        echo "  ✓ Token stored in DB: " . ($tokenRow ? 'YES' : 'NO') . "\n\n";
        
        // Step 4: Test email template
        echo "Step 4: Testing email template...\n";
        $templateFile = EMAIL_TEMPLATE_DIR . 'email-verification.html';
        echo "  Template file path: $templateFile\n";
        echo "  Template exists: " . (file_exists($templateFile) ? 'YES' : 'NO') . "\n";
        
        if (file_exists($templateFile)) {
            $templateSize = filesize($templateFile);
            echo "  Template file size: " . $templateSize . " bytes\n";
            
            // Test rendering
            $variables = [
                'member_name' => $testName,
                'email' => $testEmail,
                'member_id' => 'TEST001',
                'membership_type' => 'Premium',
                'trainer_assigned' => true,
                'trainer_name' => 'Test Trainer',
                'verification_url' => APP_URL . 'auth/verify-email.php?token=' . $token,
                'expiration_hours' => 24,
                'website_url' => APP_URL,
                'dashboard_url' => APP_URL . 'dashboard/',
                'support_url' => APP_URL . 'support/',
            ];
            
            $htmlBody = renderEmailTemplate('email-verification', $variables);
            echo "  Rendered HTML size: " . strlen($htmlBody) . " bytes\n";
            echo "  Contains verification link: " . (strpos($htmlBody, 'verify-email.php') !== false ? 'YES' : 'NO') . "\n\n";
        }
        
        // Step 5: Test email sending
        echo "Step 5: Testing email sending...\n";
        echo "  SMTP Host: " . SMTP_HOST . "\n";
        echo "  SMTP Port: " . SMTP_PORT . "\n";
        echo "  Mail From: " . MAIL_FROM_EMAIL . "\n";
        echo "  Target Email: $testEmail\n";
        
        try {
            echo "  Attempting to send verification email...\n";
            $memberData = [
                'member_id' => 'TEST001',
                'membership_type' => 'Premium',
                'trainer_name' => 'Test Trainer',
            ];
            
            $result = sendEmailVerificationEmail($testEmail, $testName, $token, $memberData, 24);
            
            echo "  Send result: " . var_export($result, true) . "\n";
            echo "  Result is array: " . (is_array($result) ? 'YES' : 'NO') . "\n";
            
            if (is_array($result)) {
                echo "  Result keys: " . implode(', ', array_keys($result)) . "\n";
                if (isset($result['success'])) {
                    echo "  Success flag: " . ($result['success'] ? 'YES' : 'NO') . "\n";
                }
                if (isset($result['error'])) {
                    echo "  Error: " . $result['error'] . "\n";
                }
            }
        } catch (Exception $e) {
            echo "  Exception caught: " . $e->getMessage() . "\n";
            echo "  Exception trace: " . $e->getTraceAsString() . "\n";
        }
        
    } else {
        echo "  ✗ Failed to generate token\n\n";
    }
    
} catch (Exception $e) {
    echo "  Error: " . $e->getMessage() . "\n\n";
}

echo "\n=== END DEBUG ===\n";
?>