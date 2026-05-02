<?php
/**
 * Email Verification System - Complete Test Suite
 * Tests the entire email verification flow for new member registration
 * Level Up Fitness - Gym Management System
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email-notifications.php';

session_start();

// Check if user is admin or allow CLI mode
$isCliMode = php_sapi_name() === 'cli';
if (!$isCliMode) {
    requireLogin();
    if ($_SESSION['user_type'] !== 'admin') {
        die('Only admins can run verification tests');
    }
}

// Test configuration
$testResults = [];
$testEmail = 'test-verification-' . time() . '@example.com';
$testMemberName = 'Test Member ' . date('Ymd-His');
$testContactNumber = '09123456789';

try {
    // Test 1: Verify database tables exist
    echo "Test 1: Checking database schema...\n";
    try {
        $stmt = $pdo->prepare("DESCRIBE verification_tokens");
        $stmt->execute();
        $testResults['database_schema'] = ['status' => 'PASS', 'message' => 'verification_tokens table exists'];
        echo "✓ verification_tokens table exists\n";
    } catch (Exception $e) {
        $testResults['database_schema'] = ['status' => 'FAIL', 'message' => $e->getMessage()];
        echo "✗ verification_tokens table missing\n";
    }

    // Test 2: Create test user and member
    echo "\nTest 2: Creating test member...\n";
    $password = hashPassword('TestPass123!');
    $userStmt = $pdo->prepare("INSERT INTO users (email, password, user_type) VALUES (?, ?, ?)");
    if ($userStmt->execute([$testEmail, $password, 'member'])) {
        $userId = $pdo->lastInsertId();
        $testResults['user_creation'] = ['status' => 'PASS', 'user_id' => $userId];
        echo "✓ Test user created (ID: $userId, Email: $testEmail)\n";

        // Test 3: Generate verification token
        echo "\nTest 3: Generating verification token...\n";
        $token = generateVerificationToken($userId, 24);
        if ($token && strlen($token) === 32) {
            $testResults['token_generation'] = ['status' => 'PASS', 'token' => $token];
            echo "✓ Verification token generated: $token\n";

            // Test 4: Validate token (should be valid)
            echo "\nTest 4: Validating token (before activation)...\n";
            $validatedUserId = validateVerificationToken($token);
            if ($validatedUserId === $userId) {
                $testResults['token_validation_before'] = ['status' => 'PASS'];
                echo "✓ Token validation passed\n";

                // Test 5: Create test member record
                echo "\nTest 5: Creating test member record...\n";
                $memberId = generateUniqueID(MEMBER_ID_PREFIX, 'members');
                $memberStmt = $pdo->prepare("
                    INSERT INTO members (
                        member_id, user_id, member_name, contact_number, 
                        email, membership_type, join_date, status
                    ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
                ");
                if ($memberStmt->execute([$memberId, $userId, $testMemberName, $testContactNumber, $testEmail, 'Premium', 'Inactive'])) {
                    $testResults['member_creation'] = ['status' => 'PASS', 'member_id' => $memberId];
                    echo "✓ Test member created (ID: $memberId, Status: Inactive)\n";

                    // Test 6: Send verification email
                    echo "\nTest 6: Sending verification email...\n";
                    $memberData = [
                        'member_id' => $memberId,
                        'membership_type' => 'Premium',
                        'trainer_name' => 'Sample Trainer',
                    ];
                    $verificationUrl = APP_URL . 'auth/verify-email.php?token=' . urlencode($token);
                    echo "Verification URL: $verificationUrl\n";
                    
                    if (sendEmailVerificationEmail($testEmail, $testMemberName, $token, $memberData, 24)) {
                        $testResults['email_sending'] = ['status' => 'PASS', 'message' => 'Verification email sent'];
                        echo "✓ Verification email sent successfully\n";
                    } else {
                        $testResults['email_sending'] = ['status' => 'WARNING', 'message' => 'Email sending may have failed'];
                        echo "⚠ Warning: Verification email sending status unclear\n";
                    }

                    // Test 7: Check user is_verified flag (should be 0 before activation)
                    echo "\nTest 7: Checking user verification status (before activation)...\n";
                    $userStmt = $pdo->prepare("SELECT is_verified FROM users WHERE id = ?");
                    $userStmt->execute([$userId]);
                    $userCheck = $userStmt->fetch();
                    if ($userCheck['is_verified'] == 0) {
                        $testResults['user_status_before'] = ['status' => 'PASS'];
                        echo "✓ User is_verified = 0 (not verified)\n";
                    } else {
                        $testResults['user_status_before'] = ['status' => 'FAIL'];
                        echo "✗ User is_verified should be 0\n";
                    }

                    // Test 8: Activate account using token
                    echo "\nTest 8: Activating account using token...\n";
                    if (activateUserByToken($token)) {
                        $testResults['account_activation'] = ['status' => 'PASS'];
                        echo "✓ Account activated successfully\n";

                        // Test 9: Verify token is now used
                        echo "\nTest 9: Checking token is marked as used...\n";
                        $tokenStmt = $pdo->prepare("SELECT used_at FROM verification_tokens WHERE token = ?");
                        $tokenStmt->execute([$token]);
                        $tokenCheck = $tokenStmt->fetch();
                        if ($tokenCheck['used_at'] !== null) {
                            $testResults['token_marked_used'] = ['status' => 'PASS'];
                            echo "✓ Token marked as used at: " . $tokenCheck['used_at'] . "\n";
                        } else {
                            $testResults['token_marked_used'] = ['status' => 'FAIL'];
                            echo "✗ Token should be marked as used\n";
                        }

                        // Test 10: Verify user is now marked as verified
                        echo "\nTest 10: Checking user verification status (after activation)...\n";
                        $userStmt = $pdo->prepare("SELECT is_verified, status FROM users WHERE id = ?");
                        $userStmt->execute([$userId]);
                        $userCheck = $userStmt->fetch();
                        if ($userCheck['is_verified'] == 1 && $userCheck['status'] === 'Active') {
                            $testResults['user_status_after'] = ['status' => 'PASS'];
                            echo "✓ User is_verified = 1 and status = Active\n";
                        } else {
                            $testResults['user_status_after'] = ['status' => 'FAIL'];
                            echo "✗ User should be verified and active\n";
                        }

                        // Test 11: Verify token cannot be used again
                        echo "\nTest 11: Testing token reuse prevention...\n";
                        $revalidateUserId = validateVerificationToken($token);
                        if ($revalidateUserId === false) {
                            $testResults['token_reuse_prevention'] = ['status' => 'PASS'];
                            echo "✓ Token cannot be reused (correctly rejected)\n";
                        } else {
                            $testResults['token_reuse_prevention'] = ['status' => 'FAIL'];
                            echo "✗ Token should be rejected after use\n";
                        }

                        // Test 12: Verify member status changed to Active
                        echo "\nTest 12: Checking member status (after activation)...\n";
                        $memberStmt = $pdo->prepare("SELECT status FROM members WHERE member_id = ?");
                        $memberStmt->execute([$memberId]);
                        $memberCheck = $memberStmt->fetch();
                        if ($memberCheck['status'] === 'Active') {
                            $testResults['member_status_after'] = ['status' => 'PASS'];
                            echo "✓ Member status changed to Active\n";
                        } else {
                            $testResults['member_status_after'] = ['status' => 'FAIL'];
                            echo "✗ Member status should be Active\n";
                        }
                    } else {
                        $testResults['account_activation'] = ['status' => 'FAIL', 'message' => 'Account activation failed'];
                        echo "✗ Account activation failed\n";
                    }
                } else {
                    $testResults['member_creation'] = ['status' => 'FAIL', 'message' => 'Failed to create test member'];
                    echo "✗ Failed to create test member\n";
                }
            } else {
                $testResults['token_validation_before'] = ['status' => 'FAIL'];
                echo "✗ Token validation failed\n";
            }
        } else {
            $testResults['token_generation'] = ['status' => 'FAIL', 'message' => 'Failed to generate token'];
            echo "✗ Token generation failed\n";
        }
    } else {
        $testResults['user_creation'] = ['status' => 'FAIL', 'message' => 'Failed to create test user'];
        echo "✗ Failed to create test user\n";
    }

    // Test Summary
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "TEST SUMMARY\n";
    echo str_repeat("=", 60) . "\n";
    
    $passCount = 0;
    $failCount = 0;
    $warningCount = 0;
    
    foreach ($testResults as $test => $result) {
        $status = $result['status'];
        $symbol = '';
        
        if ($status === 'PASS') {
            $passCount++;
            $symbol = '✓';
        } elseif ($status === 'FAIL') {
            $failCount++;
            $symbol = '✗';
        } else {
            $warningCount++;
            $symbol = '⚠';
        }
        
        echo "$symbol $test: $status\n";
    }
    
    echo "\nTotal: " . ($passCount + $failCount + $warningCount) . " | ";
    echo "Pass: $passCount | Fail: $failCount | Warning: $warningCount\n";
    echo str_repeat("=", 60) . "\n";
    
    // Show test data for manual verification
    echo "\nTest Data:\n";
    echo "  Test Email: $testEmail\n";
    echo "  Test Member ID: $memberId\n";
    echo "  Test User ID: $userId\n";
    echo "  Verification Token: $token\n";
    echo "  Verification URL: " . APP_URL . "auth/verify-email.php?token=" . urlencode($token) . "\n";
    
} catch (Exception $e) {
    echo "\nFatal Error: " . $e->getMessage() . "\n";
    error_log("Email verification test error: " . $e->getMessage());
}

?>