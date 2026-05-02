<?php
/**
 * Complete Email Verification Flow Test
 * Creates member, sends verification email, then verifies the token
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/email-notifications.php';

echo "=== COMPLETE EMAIL VERIFICATION FLOW TEST ===\n\n";

// Step 1: Create test user and member
echo "STEP 1: Creating test user and member...\n";
$testEmail = 'endtoend-' . time() . '@example.com';
$testName = 'End to End Test ' . time();

try {
    $password = hashPassword('defaultPass123');
    $userStmt = $pdo->prepare("INSERT INTO users (email, password, user_type) VALUES (?, ?, ?)");
    $userStmt->execute([$testEmail, $password, 'member']);
    $userId = (int)$pdo->lastInsertId();
    echo "  ✓ User created (ID: $userId, Email: $testEmail)\n";

    $memberId = generateUniqueID(MEMBER_ID_PREFIX, 'members');
    $stmt = $pdo->prepare("
        INSERT INTO members (
            member_id, user_id, member_name, contact_number, 
            email, membership_type, join_date, status
        ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)
    ");
    $stmt->execute([$memberId, $userId, $testName, '09123456789', $testEmail, 'Premium', 'Inactive']);
    echo "  ✓ Member created (ID: $memberId, Status: Inactive)\n";

    // Step 2: Generate and store token
    echo "\nSTEP 2: Generating verification token...\n";
    $verificationToken = generateVerificationToken($userId, 24);
    echo "  ✓ Token generated: " . substr($verificationToken, 0, 8) . "...\n";
    
    // Verify token is in database
    $checkStmt = $pdo->prepare("SELECT * FROM verification_tokens WHERE token = ?");
    $checkStmt->execute([$verificationToken]);
    $tokenRow = $checkStmt->fetch();
    echo "  ✓ Token stored in DB\n";
    echo "    - User ID: " . $tokenRow['user_id'] . "\n";
    echo "    - Token Type: " . $tokenRow['token_type'] . "\n";
    echo "    - Expires: " . $tokenRow['expires_at'] . "\n";
    echo "    - Used: " . ($tokenRow['used_at'] ?? 'Not yet') . "\n";

    // Step 3: Send verification email
    echo "\nSTEP 3: Sending verification email...\n";
    $memberData = [
        'member_id' => $memberId,
        'membership_type' => 'Premium',
        'trainer_name' => 'Test Trainer',
    ];
    
    $emailResult = sendEmailVerificationEmail($testEmail, $testName, $verificationToken, $memberData, 24);
    if (is_array($emailResult) && $emailResult['success']) {
        echo "  ✓ Email sent successfully\n";
        echo "    - Message ID: " . $emailResult['message_id'] . "\n";
    } else {
        echo "  ✗ Email sending failed\n";
    }

    // Step 4: Check user status before verification
    echo "\nSTEP 4: Checking user status before verification...\n";
    $userStmt = $pdo->prepare("SELECT is_verified FROM users WHERE user_id = ?");
    $userStmt->execute([$userId]);
    $userCheck = $userStmt->fetch();
    echo "  - is_verified: " . ($userCheck['is_verified'] ? 'Yes' : 'No') . "\n";
    
    $memberStmt = $pdo->prepare("SELECT status FROM members WHERE member_id = ?");
    $memberStmt->execute([$memberId]);
    $memberCheck = $memberStmt->fetch();
    echo "  - Member status: " . $memberCheck['status'] . "\n";

    // Step 5: Validate and activate token
    echo "\nSTEP 5: Validating token...\n";
    echo "  Token to validate: $verificationToken\n";
    
    // Check if token exists in DB
    $checkStmt = $pdo->prepare("SELECT * FROM verification_tokens WHERE token = ?");
    $checkStmt->execute([$verificationToken]);
    $tokenData = $checkStmt->fetch();
    if ($tokenData) {
        echo "  Token found in DB:\n";
        echo "    - token_id: " . $tokenData['token_id'] . "\n";
        echo "    - user_id: " . $tokenData['user_id'] . "\n";
        echo "    - token_type: " . $tokenData['token_type'] . "\n";
        echo "    - expires_at: " . $tokenData['expires_at'] . "\n";
        echo "    - used_at: " . ($tokenData['used_at'] ?? 'NULL') . "\n";
        echo "    - created_at: " . $tokenData['created_at'] . "\n";
    } else {
        echo "  ✗ Token NOT found in DB!\n";
    }
    
    $validatedUserId = validateVerificationToken($verificationToken);
    echo "  validateVerificationToken() returned: " . var_export($validatedUserId, true) . " (type: " . gettype($validatedUserId) . ")\n";
    echo "  Expected userId: " . $userId . " (type: " . gettype($userId) . ")\n";
    echo "  Comparison result (===): " . ($validatedUserId === $userId ? 'MATCH' : 'NO MATCH') . "\n";
    echo "  Comparison result (==): " . ($validatedUserId == $userId ? 'MATCH' : 'NO MATCH') . "\n";
    
    if ($validatedUserId === $userId) {
        echo "  ✓ Token is valid\n";
        
        echo "\nSTEP 6: Activating account using token...\n";
        if (activateUserByToken($verificationToken)) {
            echo "  ✓ Account activated successfully\n";
            
            // Step 7: Check user status after activation
            echo "\nSTEP 7: Checking user status after verification...\n";
            $userStmt = $pdo->prepare("SELECT is_verified FROM users WHERE user_id = ?");
            $userStmt->execute([$userId]);
            $userCheck = $userStmt->fetch();
            echo "  - is_verified: " . ($userCheck['is_verified'] ? 'Yes' : 'No') . "\n";
            
            $memberStmt = $pdo->prepare("SELECT status FROM members WHERE member_id = ?");
            $memberStmt->execute([$memberId]);
            $memberCheck = $memberStmt->fetch();
            echo "  - Member status: " . $memberCheck['status'] . "\n";
            
            // Step 8: Verify token is marked as used
            echo "\nSTEP 8: Checking token usage...\n";
            $checkStmt = $pdo->prepare("SELECT used_at FROM verification_tokens WHERE token = ?");
            $checkStmt->execute([$verificationToken]);
            $tokenCheck = $checkStmt->fetch();
            echo "  - Token used at: " . ($tokenCheck['used_at'] ?? 'Not marked') . "\n";
            
            // Step 9: Verify token cannot be reused
            echo "\nSTEP 9: Testing token reuse prevention...\n";
            $revalidate = validateVerificationToken($verificationToken);
            if ($revalidate === false) {
                echo "  ✓ Token correctly rejected (cannot be reused)\n";
            } else {
                echo "  ✗ Token should not be reusable!\n";
            }
            
            echo "\n=== ✓ VERIFICATION FLOW SUCCESSFUL ===\n";
            echo "\nTest Details:\n";
            echo "  Email: $testEmail\n";
            echo "  Member ID: $memberId\n";
            echo "  Verification Token: $verificationToken\n";
            echo "  Verification URL: " . APP_URL . "auth/verify-email.php?token=$verificationToken\n";
        } else {
            echo "  ✗ Failed to activate account\n";
        }
    } else {
        echo "  ✗ Token validation failed\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>