<?php
/**
 * SMTP Configuration for Mailtrap
 * Level Up Fitness - Gym Management System
 * 
 * Uses direct SMTP connection via PHPMailer
 */

// SMTP Server Configuration
define('SMTP_HOST', getenv('MAIL_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('MAIL_PORT') ?: 587);
define('SMTP_USERNAME', getenv('MAIL_USERNAME') ?: 'levelupfitnessnoreply@gmail.com');
define('SMTP_PASSWORD', getenv('MAIL_PASSWORD') ?: 'meub jxho lmbw qcib');
define('SMTP_ENCRYPTION', getenv('MAIL_ENCRYPTION') ?: 'tls'); // 'tls' or 'ssl'

// Email Configuration
define('MAIL_FROM_EMAIL', 'levelupfitnessnoreply@gmail.com');
define('MAIL_FROM_NAME', 'Level Up Fitness');
define('MAIL_REPLY_TO_EMAIL', 'levelupfitnessnoreply@gmail.com');
define('MAIL_REPLY_TO_NAME', 'Level Up Fitness Support');

// Email Features
define('MAIL_ENABLED', true);
define('MAIL_DEBUG', getenv('APP_ENV') === 'development' ? true : false);

// Email Template Configuration
define('EMAIL_TEMPLATE_DIR', dirname(__FILE__) . '/../email-templates/');

// Retry Configuration
define('MAIL_RETRY_COUNT', 3);
define('MAIL_RETRY_DELAY', 2); // seconds

/**
 * Validate SMTP Configuration
 * 
 * @return bool
 */
function validateSmtpConfig() {
    return !empty(SMTP_HOST) && 
           !empty(SMTP_PORT) &&
           !empty(SMTP_USERNAME) &&
           !empty(SMTP_PASSWORD);
}

?>
