<?php
/**
 * Test - Simulate Member Creation
 * Mimics the exact flow of creating a member through the admin form
 */

require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/email-notifications.php';

echo "=== SIMULATING MEMBER CREATION ===\n\n";

// Simulate form data
$formData = [
    'member_name' => 'Test Member ' . time(),
    'email' => 'testmember-' . time() . '@example.com',
    'contact_number' => '09123456789',
    'membership_type' => 'Premium',
    'trainer_id' => '',
    'status' => 'Inactive'
];
$joinDate = date('Y-m-d');

echo "Form Data:\n";
echo "  Name: " . $formData['member_name'] . "\n";
echo "  Email: " . $formData['email'] . "\n";
echo "  Contact: " . $formData['contact_number'] . "\n";
echo "  Join Date: " . $joinDate . "\n";
echo "  Status: " . $formData['status'] . "\n\n";

try {
    echo "Step 1: Creating user account...\n";
    $password = hashPassword('defaultPass123');
    $userStmt = $pdo->prepare("INSERT INTO users (email, password, user_type) VALUES (?, ?, ?)");
    $userStmt->execute([$formData['email'], $password, 'member']);
    $userId = $pdo->lastInsertId();
    echo "  ✓ User created (ID: $userId)\n\n";

    echo "Step 2: Generating member ID...\n";
    $memberId = generateUniqueID(MEMBER_ID_PREFIX, 'members');
    echo "  ✓ Member ID: $memberId\n\n";

    echo "Step 3: Creating member record...\n";
    $stmt = $pdo->prepare("
        INSERT INTO members (
            member_id, user_id, member_name, contact_number, 
            email, membership_type, join_date, trainer_id, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $memberId, $userId, $formData['member_name'], 
        $formData['contact_number'], $formData['email'], 
        $formData['membership_type'], $joinDate,
        !empty($formData['trainer_id']) ? $formData['trainer_id'] : NULL,
        $formData['status']
    ]);
    echo "  ✓ Member record created\n\n";

    echo "Step 4: Generating verification token...\n";
    $verificationToken = generateVerificationToken($userId);
    if ($verificationToken) {
        echo "  ✓ Token generated: " . substr($verificationToken, 0, 8) . "...\n\n";
        
        echo "Step 5: Preparing member data...\n";
        $trainerInfo = ['trainer_name' => '', 'trainer_email' => ''];
        if (!empty($formData['trainer_id'])) {
            $trainerStmt = $pdo->prepare("SELECT full_name, email FROM users WHERE user_id = (SELECT user_id FROM trainers WHERE trainer_id = ?)");
            $trainerStmt->execute([$formData['trainer_id']]);
            $trainer = $trainerStmt->fetch();
            if ($trainer) {
                $trainerInfo = ['trainer_name' => $trainer['full_name'], 'trainer_email' => $trainer['email']];
            }
        }

        $memberData = [
            'member_id' => $memberId,
            'membership_type' => $formData['membership_type'],
            'trainer_name' => $trainerInfo['trainer_name'],
        ];
        echo "  ✓ Member data prepared\n\n";

        echo "Step 6: Sending verification email...\n";
        echo "  Calling sendEmailVerificationEmail()...\n";
        
        $emailResult = sendEmailVerificationEmail(
            $formData['email'], 
            $formData['member_name'], 
            $verificationToken, 
            $memberData,
            24
        );
        
        echo "  Email result: " . var_export($emailResult, true) . "\n";
        
        if (is_array($emailResult) && isset($emailResult['success']) && $emailResult['success']) {
            echo "  ✓ Email sent successfully!\n";
            echo "\n=== MEMBER CREATION SUCCESSFUL ===\n";
            echo "Member ID: $memberId\n";
            echo "Email: " . $formData['email'] . "\n";
            echo "Verification Link: " . APP_URL . "auth/verify-email.php?token=$verificationToken\n";
        } else {
            echo "  ✗ Email sending failed\n";
        }
    } else {
        echo "  ✗ Failed to generate token\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
?>