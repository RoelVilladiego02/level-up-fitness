<?php
/**
 * SMTP Email Service using PHPMailer
 * Level Up Fitness - Gym Management System
 * 
 * Handles all email sending through direct SMTP connection to Mailtrap
 */

require_once dirname(__FILE__) . '/smtp.php';
require_once dirname(__FILE__) . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class SMTPMailService {
    
    /**
     * Send email via SMTP
     * 
     * @param mixed $to Recipient(s) - email string or ['email' => 'name']
     * @param string $subject Email subject
     * @param string $htmlBody HTML body content
     * @param string $textBody Plain text body (optional)
     * @param array $options Additional options (cc, bcc, attachments, reply_to, etc.)
     * @return array Result ['success' => bool, 'message' => string, 'message_id' => string]
     */
    public static function send($to, $subject, $htmlBody, $textBody = '', $options = []) {
        
        // Validate configuration
        if (!MAIL_ENABLED) {
            error_log('SMTP mail service is disabled - email not sent');
            return ['success' => false, 'message' => 'Email service disabled'];
        }
        
        if (!validateSmtpConfig()) {
            error_log('SMTP configuration incomplete: check SMTP_HOST, SMTP_PORT, SMTP_USERNAME, SMTP_PASSWORD');
            return ['success' => false, 'message' => 'Email service not properly configured'];
        }
        
        // Create PHPMailer instance
        $mail = new PHPMailer(true);
        
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->Port = SMTP_PORT;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                    'allow_self_signed' => false
                ]
            ];
            
            // Enable debug logging in development
            if (MAIL_DEBUG) {
                $mail->SMTPDebug = SMTP::DEBUG_SERVER;
                $mail->Debugoutput = function($str, $level) {
                    error_log('SMTP Debug [' . $level . ']: ' . $str);
                };
            }
            
            // Set timeouts
            $mail->Timeout = 30;
            
            // Sender
            $mail->setFrom(MAIL_FROM_EMAIL, MAIL_FROM_NAME);
            
            // Reply-to
            if (!empty(MAIL_REPLY_TO_EMAIL)) {
                $mail->addReplyTo(MAIL_REPLY_TO_EMAIL, MAIL_REPLY_TO_NAME);
            }
            
            // Recipients
            if (is_string($to)) {
                $mail->addAddress($to);
            } elseif (is_array($to)) {
                foreach ($to as $email => $name) {
                    if (is_numeric($email)) {
                        // Numeric key - just email address
                        if (is_array($name)) {
                            $mail->addAddress($name['email'] ?? $name, $name['name'] ?? '');
                        } else {
                            $mail->addAddress($name);
                        }
                    } else {
                        // Associative array [email => name]
                        $mail->addAddress($email, $name);
                    }
                }
            }
            
            // CC recipients
            if (!empty($options['cc'])) {
                if (is_string($options['cc'])) {
                    $mail->addCC($options['cc']);
                } elseif (is_array($options['cc'])) {
                    foreach ($options['cc'] as $email => $name) {
                        if (is_numeric($email)) {
                            $mail->addCC($name);
                        } else {
                            $mail->addCC($email, $name);
                        }
                    }
                }
            }
            
            // BCC recipients
            if (!empty($options['bcc'])) {
                if (is_string($options['bcc'])) {
                    $mail->addBCC($options['bcc']);
                } elseif (is_array($options['bcc'])) {
                    foreach ($options['bcc'] as $email => $name) {
                        if (is_numeric($email)) {
                            $mail->addBCC($name);
                        } else {
                            $mail->addBCC($email, $name);
                        }
                    }
                }
            }
            
            // Content
            $mail->isHTML(true);
            $mail->CharSet = PHPMailer::CHARSET_UTF8;  // Ensure UTF-8 charset
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            
            // Plain text alternative
            if (!empty($textBody)) {
                $mail->AltBody = $textBody;
            } else {
                // Strip HTML tags for plain text version
                $mail->AltBody = strip_tags($htmlBody);
            }
            
            // Add custom headers if provided
            if (!empty($options['headers']) && is_array($options['headers'])) {
                foreach ($options['headers'] as $name => $value) {
                    $mail->addCustomHeader($name, $value);
                }
            }
            
            // Add attachments if provided
            if (!empty($options['attachments']) && is_array($options['attachments'])) {
                foreach ($options['attachments'] as $attachment) {
                    if (is_array($attachment) && !empty($attachment['path'])) {
                        $name = $attachment['name'] ?? basename($attachment['path']);
                        $mail->addAttachment($attachment['path'], $name);
                    } elseif (is_string($attachment)) {
                        $mail->addAttachment($attachment);
                    }
                }
            }
            
            // Send with retry logic
            $result = self::sendWithRetry($mail, MAIL_RETRY_COUNT);
            
            // Log result
            if ($result['success']) {
                error_log("Email sent successfully to: " . json_encode($to) . " | Subject: " . $subject);
            } else {
                error_log("Email send failed to: " . json_encode($to) . " | Subject: " . $subject . " | Error: " . $result['message']);
            }
            
            return $result;
            
        } catch (Exception $e) {
            $error = "Exception: " . $e->getMessage();
            error_log("Email exception: " . $error);
            return [
                'success' => false,
                'message' => $error,
                'message_id' => null
            ];
        }
    }
    
    /**
     * Send email with retry logic
     * 
     * @param PHPMailer $mail PHPMailer instance
     * @param int $retries Number of retries
     * @return array Result
     */
    private static function sendWithRetry($mail, $retries = 3) {
        
        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            try {
                if ($mail->send()) {
                    return [
                        'success' => true,
                        'message' => 'Email sent successfully',
                        'message_id' => $mail->getLastMessageID() ?? uniqid('mail_')
                    ];
                }
            } catch (Exception $e) {
                error_log("Send attempt $attempt failed: " . $e->getMessage());
            }
            
            // If not last attempt, wait before retrying
            if ($attempt < $retries) {
                error_log("Email send failed (attempt $attempt/$retries), retrying in " . MAIL_RETRY_DELAY . " seconds...");
                sleep(MAIL_RETRY_DELAY);
            }
        }
        
        return [
            'success' => false,
            'message' => 'Failed to send email after ' . $retries . ' attempts',
            'message_id' => null
        ];
    }
    
    /**
     * Send bulk emails
     * 
     * @param array $emails Array of recipient configurations
     * @return array Results for each email
     */
    public static function sendBulk($emails) {
        $results = [];
        
        foreach ($emails as $index => $emailConfig) {
            $result = self::send(
                $emailConfig['to'],
                $emailConfig['subject'],
                $emailConfig['html'],
                $emailConfig['text'] ?? '',
                $emailConfig['options'] ?? []
            );
            $results[$index] = $result;
        }
        
        return $results;
    }
    
    /**
     * Test SMTP connection
     * 
     * @return array Connection test result
     */
    public static function testConnection() {
        
        if (!validateSmtpConfig()) {
            return [
                'success' => false,
                'message' => 'SMTP configuration is incomplete'
            ];
        }
        
        $mail = new PHPMailer(true);
        
        try {
            $mail->isSMTP();
            $mail->Host = SMTP_HOST;
            $mail->Port = SMTP_PORT;
            $mail->SMTPAuth = true;
            $mail->Username = SMTP_USERNAME;
            $mail->Password = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            
            // Just test connection without sending
            if ($mail->smtpConnect()) {
                $mail->smtpClose();
                return [
                    'success' => true,
                    'message' => 'SMTP connection successful'
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'SMTP connection failed'
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection test error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Send test email
     * 
     * @param string $testEmail Email address to send test to
     * @return array Result
     */
    public static function sendTest($testEmail) {
        
        $htmlBody = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background: #f9f9f9; border-radius: 8px; }
        .header { background: #4A90E2; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { background: white; padding: 20px; border-radius: 0 0 8px 8px; }
        .footer { font-size: 12px; color: #666; margin-top: 20px; text-align: center; }
        .badge { display: inline-block; background: #27ae60; color: white; padding: 4px 8px; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>SMTP Connection Test</h1>
        </div>
        <div class="content">
            <p>Hello,</p>
            <p>This is a test email from <strong>Level Up Fitness</strong> system.</p>
            <p>If you received this email, your SMTP configuration is working correctly!</p>
            <p><span class="badge">✓ SUCCESS</span></p>
            <p><strong>Configuration Details:</strong></p>
            <ul>
                <li>Host: <code>' . SMTP_HOST . '</code></li>
                <li>Port: <code>' . SMTP_PORT . '</code></li>
                <li>From: <code>' . MAIL_FROM_EMAIL . '</code></li>
                <li>Service: Mailtrap SMTP (Sandbox)</li>
            </ul>
            <p>You can now proceed to configure email notifications in your system.</p>
        </div>
        <div class="footer">
            <p>Level Up Fitness &copy; 2026 | Gym Management System</p>
            <p>Sent via Mailtrap SMTP Service</p>
        </div>
    </div>
</body>
</html>
HTML;
        
        return self::send(
            $testEmail,
            'Level Up Fitness - SMTP Test Email',
            $htmlBody,
            'This is a test email from Level Up Fitness system.'
        );
    }
}

?>
