<?php
/**
 * Login Page
 * Level Up Fitness - Gym Management System
 */

require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once dirname(dirname(__FILE__)) . '/config/database.php';
require_once dirname(dirname(__FILE__)) . '/includes/functions.php';

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isLoggedIn()) {
    redirect(APP_URL . 'dashboard/');
}

$loginError = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $resendVerificationAction = isset($_POST['resend_verification']);

    // Handle resend verification request
    if ($resendVerificationAction) {
        // This will be handled by AJAX, but include basic validation
        // The actual resend logic is in resend-verification.php
    } else {
        // Normal login flow
        if (empty($email) || empty($password)) {
            $loginError = 'Please enter both email and password.';
        } else {
            try {
                // Query user from database
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && verifyPassword($password, $user['password'])) {
                    // Check if user is verified (for members requiring email verification)
                    if ($user['user_type'] === 'member' && (!isset($user['is_verified']) || $user['is_verified'] == 0)) {
                        // Store email in session for resend verification form
                        $_SESSION['pending_verification_email'] = $user['email'];
                        $loginError = 'Your account is pending email verification. Please check your email for the verification link.';
                    } else {
                        // Update last login
                        $updateStmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                        $updateStmt->execute([$user['user_id']]);

                        // Set session variables
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['user_type'] = $user['user_type'];
                        $_SESSION['last_activity'] = time();

                        // Get additional user info based on role
                        if ($user['user_type'] === 'member') {
                            $memberStmt = $pdo->prepare("SELECT member_name FROM members WHERE user_id = ?");
                            $memberStmt->execute([$user['user_id']]);
                            $member = $memberStmt->fetch();
                            $_SESSION['name'] = $member['member_name'] ?? 'Member';
                        } elseif ($user['user_type'] === 'trainer') {
                            $trainerStmt = $pdo->prepare("SELECT trainer_name FROM trainers WHERE user_id = ?");
                            $trainerStmt->execute([$user['user_id']]);
                            $trainer = $trainerStmt->fetch();
                            $_SESSION['name'] = $trainer['trainer_name'] ?? 'Trainer';
                        } else {
                            $_SESSION['name'] = 'Administrator';
                        }

                        // Log the login action
                        logAction($user['user_id'], 'LOGIN', 'Authentication', 'User logged in successfully');

                        // Redirect to dashboard
                        redirect(APP_URL . 'dashboard/');
                    }
                } else {
                    $loginError = 'Invalid email or password.';
                }
            } catch (Exception $e) {
                $loginError = 'An error occurred. Please try again later.';
                error_log('Login error: ' . $e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?php echo APP_URL; ?>assets/css/style.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
            min-height: 100vh;
        }
        
        .login-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        
        .login-box {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 400px;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 10px;
        }
        
        .login-header p {
            color: var(--neutral-color);
        }
        
        .form-control {
            padding: 12px 15px;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(74, 144, 226, 0.25);
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            font-weight: 600;
            margin-top: 10px;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 0.9rem;
        }
        
        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .login-footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h1><i class="fas fa-dumbbell"></i></h1>
                <h1><?php echo APP_NAME; ?></h1>
                <p>Gym Management System</p>
            </div>

            <?php if ($loginError): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $loginError; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                
                <?php if (isset($_SESSION['pending_verification_email'])): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Didn't receive the email?</strong>
                        <button type="button" class="btn btn-sm btn-link" data-bs-toggle="modal" data-bs-target="#resendVerificationModal">
                            Click here to resend
                        </button>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                </div>

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="remember" name="remember">
                    <label class="form-check-label" for="remember">
                        Remember me
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </button>
            </form>

            <div class="login-footer">
                <p>Demo Credentials:</p>
                <p>Email: admin@levelupfitness.com</p>
                <p>Password: password</p>
            </div>
        </div>
    </div>

    <!-- Resend Verification Email Modal -->
    <div class="modal fade" id="resendVerificationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-envelope"></i> Resend Verification Email</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="resendVerificationForm">
                        <div class="mb-3">
                            <label for="resendEmail" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="resendEmail" name="email" 
                                   value="<?php echo isset($_SESSION['pending_verification_email']) ? htmlspecialchars($_SESSION['pending_verification_email']) : ''; ?>" 
                                   placeholder="Enter your email" required>
                            <small class="text-muted">Enter the email associated with your account</small>
                        </div>
                        <div id="resendMessage" class="alert" style="display: none;"></div>
                        <div id="cooldownWarning" class="alert alert-warning" style="display: none;">
                            <i class="fas fa-clock"></i>
                            <span id="cooldownText"></span>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="resendButton">
                        <i class="fas fa-paper-plane"></i> Resend Email
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script>
        $(document).ready(function() {
            const APP_URL = "<?php echo APP_URL; ?>";
            
            $('#resendButton').click(function() {
                const email = $('#resendEmail').val();
                const $button = $(this);
                const $form = $('#resendVerificationForm');
                const $messageDiv = $('#resendMessage');
                const $cooldownDiv = $('#cooldownWarning');
                
                if (!email) {
                    $messageDiv.removeClass('alert-success alert-danger').addClass('alert-danger').html(
                        '<i class="fas fa-exclamation-circle"></i> Please enter your email address.'
                    ).show();
                    return;
                }
                
                // Disable button and show loading state
                $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sending...');
                $messageDiv.hide();
                $cooldownDiv.hide();
                
                $.ajax({
                    url: APP_URL + 'auth/resend-verification.php',
                    method: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({ email: email }),
                    dataType: 'json',
                    success: function(response) {
                        $messageDiv.removeClass('alert-danger').addClass('alert-success').html(
                            '<i class="fas fa-check-circle"></i> ' + response.message
                        ).show();
                        
                        // Show cooldown timer
                        if (response.next_retry_minutes) {
                            showCooldown($cooldownDiv, response.next_retry_minutes);
                        }
                        
                        // Re-enable button after delay
                        setTimeout(function() {
                            $button.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Resend Email');
                        }, 2000);
                    },
                    error: function(xhr) {
                        let message = 'An error occurred. Please try again later.';
                        
                        try {
                            const response = JSON.parse(xhr.responseText);
                            message = response.message || message;
                            
                            // Show cooldown if available
                            if (response.minutes_remaining && xhr.status === 429) {
                                showCooldown($cooldownDiv, response.minutes_remaining);
                            }
                        } catch(e) {
                            // Response wasn't JSON
                        }
                        
                        $messageDiv.removeClass('alert-success').addClass('alert-danger').html(
                            '<i class="fas fa-exclamation-circle"></i> ' + message
                        ).show();
                        
                        $button.prop('disabled', false).html('<i class="fas fa-paper-plane"></i> Resend Email');
                    }
                });
            });
            
            // Handle Enter key in email field
            $('#resendEmail').keypress(function(e) {
                if (e.which === 13) { // Enter key
                    e.preventDefault();
                    $('#resendButton').click();
                }
            });
            
            function showCooldown($element, minutes) {
                let remainingMinutes = minutes;
                const $text = $('#cooldownText');
                
                $element.show();
                $text.text('Please wait ' + remainingMinutes + ' minute(s) before requesting another verification email.');
                
                const countdownInterval = setInterval(function() {
                    remainingMinutes--;
                    
                    if (remainingMinutes <= 0) {
                        clearInterval(countdownInterval);
                        $element.hide();
                    } else {
                        $text.text('Please wait ' + remainingMinutes + ' minute(s) before requesting another verification email.');
                    }
                }, 60000); // Update every minute
            }
        });
    </script>
</head>
<body>
