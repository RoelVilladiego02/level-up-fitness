<?php
/**
 * Resend Email Verification
 * Level Up Fitness - Gym Management System
 * 
 * Allows users to resend their verification email with cooldown protection
 */

require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once dirname(dirname(__FILE__)) . '/config/database.php';
require_once dirname(dirname(__FILE__)) . '/includes/functions.php';
require_once dirname(dirname(__FILE__)) . '/includes/email-notifications.php';

header('Content-Type: application/json');

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$email = sanitize($input['email'] ?? $_POST['email'] ?? '');

if (empty($email)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Email address is required'
    ]);
    exit;
}

try {
    // Find user by email
    $stmt = $pdo->prepare("SELECT user_id, user_type, is_verified, email FROM users WHERE email = ? AND user_type = 'member'");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'User account not found'
        ]);
        exit;
    }

    // Check if already verified
    if ($user['is_verified']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Your account is already verified. Please log in.'
        ]);
        exit;
    }

    // Check cooldown - prevent spam (5 minute cooldown)
    $COOLDOWN_MINUTES = 5;
    $stmt = $pdo->prepare("
        SELECT verification_email_sent_at FROM users 
        WHERE user_id = ?
    ");
    $stmt->execute([$user['user_id']]);
    $userData = $stmt->fetch();

    if (!empty($userData['verification_email_sent_at'])) {
        $lastSentTime = strtotime($userData['verification_email_sent_at']);
        $minutesSinceLastEmail = (time() - $lastSentTime) / 60;

        if ($minutesSinceLastEmail < $COOLDOWN_MINUTES) {
            $minutesRemaining = ceil($COOLDOWN_MINUTES - $minutesSinceLastEmail);
            http_response_code(429); // Too Many Requests
            echo json_encode([
                'success' => false,
                'message' => "Please wait {$minutesRemaining} minute(s) before requesting another verification email.",
                'retry_after' => $minutesRemaining * 60,
                'minutes_remaining' => $minutesRemaining
            ]);
            exit;
        }
    }

    // Generate new verification token
    $verificationToken = generateVerificationToken($user['user_id']);

    if (!$verificationToken) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to generate verification token. Please try again.'
        ]);
        exit;
    }

    // Get member details for email
    $memberStmt = $pdo->prepare("
        SELECT member_id, member_name, membership_type, trainer_id 
        FROM members 
        WHERE user_id = ?
    ");
    $memberStmt->execute([$user['user_id']]);
    $member = $memberStmt->fetch();

    // Get trainer info if assigned
    $trainerInfo = ['trainer_name' => '', 'trainer_email' => ''];
    if ($member && !empty($member['trainer_id'])) {
        $trainerStmt = $pdo->prepare("SELECT trainer_name FROM trainers WHERE trainer_id = ?");
        $trainerStmt->execute([$member['trainer_id']]);
        $trainer = $trainerStmt->fetch();
        if ($trainer) {
            $trainerInfo = ['trainer_name' => $trainer['trainer_name']];
        }
    }

    $memberData = [
        'member_id' => $member['member_id'] ?? '',
        'membership_type' => $member['membership_type'] ?? '',
        'trainer_name' => $trainerInfo['trainer_name'],
    ];

    // Send verification email
    if (defined('ENABLE_EMAIL_NOTIFICATIONS') && ENABLE_EMAIL_NOTIFICATIONS) {
        $emailResult = sendEmailVerificationEmail(
            $user['email'],
            $member['member_name'] ?? 'Member',
            $verificationToken,
            $memberData,
            24
        );

        if (!$emailResult['success']) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to send verification email. Please try again later.',
                'details' => $emailResult['message']
            ]);
            exit;
        }

        // Update verification email sent timestamp
        $updateStmt = $pdo->prepare("
            UPDATE users 
            SET verification_email_sent_at = NOW() 
            WHERE user_id = ?
        ");
        $updateStmt->execute([$user['user_id']]);

        echo json_encode([
            'success' => true,
            'message' => 'Verification email sent successfully! Please check your email.',
            'next_retry_minutes' => $COOLDOWN_MINUTES
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Email service is currently disabled. Please contact support.'
        ]);
    }

} catch (Exception $e) {
    error_log('Resend verification email error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred. Please try again later.',
        'error' => $e->getMessage()
    ]);
}
?>
