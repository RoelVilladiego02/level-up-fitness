<?php
/**
 * Email Verification Handler
 * 
 * Processes email verification tokens and activates user accounts
 * Accessible to unauthenticated users
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/email-notifications.php';

// Get token from URL
$token = sanitize($_GET['token'] ?? '');

// Initialize response
$isValid = false;
$message = '';
$messageType = 'danger';
$userName = '';

if (empty($token)) {
    $message = 'No verification token provided. Please check your email for the verification link.';
} else {
    // Validate token and get user ID
    $userId = validateVerificationToken($token);
    
    if ($userId) {
        // Token is valid, activate the user
        if (activateUserByToken($token)) {
            $isValid = true;
            $messageType = 'success';
            
            // Get user details for personalized message
            try {
                $stmt = $pdo->prepare("SELECT email FROM users WHERE user_id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Get member name
                $memberStmt = $pdo->prepare("SELECT member_name FROM members WHERE user_id = ?");
                $memberStmt->execute([$userId]);
                $member = $memberStmt->fetch(PDO::FETCH_ASSOC);
                $userName = $member['member_name'] ?? 'Member';
            } catch (Exception $e) {
                error_log("Error fetching user details: " . $e->getMessage());
                $userName = 'Member';
            }
            
            $message = 'Email verified successfully! Your account is now active and you can log in.';
        } else {
            $message = 'An error occurred while activating your account. Please contact support.';
        }
    } else {
        // Token is invalid or expired
        $message = 'The verification link is invalid or has expired. Please request a new verification email.';
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Level Up Fitness</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .verification-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        .verification-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        .success-icon {
            color: #28a745;
        }
        .error-icon {
            color: #dc3545;
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
            font-size: 28px;
        }
        .message {
            margin-bottom: 30px;
            font-size: 16px;
            line-height: 1.6;
        }
        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .alert-success {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        .alert-danger {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        .btn {
            flex: 1;
            padding: 12px 20px;
            font-size: 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background-color: #667eea;
            color: white;
        }
        .btn-primary:hover {
            background-color: #5568d3;
            text-decoration: none;
            color: white;
        }
        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            text-decoration: none;
            color: white;
        }
        .info-box {
            background-color: #f8f9fa;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-top: 30px;
            border-radius: 5px;
            text-align: left;
        }
        .info-box h5 {
            color: #667eea;
            margin-bottom: 10px;
        }
        .info-box p {
            margin: 5px 0;
            color: #666;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <?php if ($isValid): ?>
            <div class="verification-icon success-icon">✓</div>
            <h1>Email Verified!</h1>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <p class="message">
                Welcome, <strong><?php echo htmlspecialchars($userName); ?></strong>!<br>
                Your account is now active and ready to use.
            </p>
            <div class="info-box">
                <h5>Next Steps:</h5>
                <p>✓ Your account has been activated</p>
                <p>✓ You can now log in with your credentials</p>
                <p>✓ Check your email for additional account information</p>
            </div>
            <div class="btn-group">
                <a href="<?php echo APP_URL; ?>auth/login.php" class="btn btn-primary">Go to Login</a>
                <a href="<?php echo APP_URL; ?>" class="btn btn-secondary">Home</a>
            </div>
        <?php else: ?>
            <div class="verification-icon error-icon">✕</div>
            <h1>Verification Failed</h1>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($message); ?>
            </div>
            <div class="info-box">
                <h5>What Can You Do?</h5>
                <p>• Check your email for a new verification link</p>
                <p>• Ensure the link wasn't expired (links expire after 24 hours)</p>
                <p>• Contact support if you continue to have issues</p>
                <p><strong>Support Email:</strong> <?php echo htmlspecialchars(SUPPORT_EMAIL ?? 'support@levelupfitness.com'); ?></p>
            </div>
            <div class="btn-group">
                <a href="<?php echo APP_URL; ?>auth/login.php" class="btn btn-primary">Back to Login</a>
                <a href="<?php echo APP_URL; ?>" class="btn btn-secondary">Home</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
