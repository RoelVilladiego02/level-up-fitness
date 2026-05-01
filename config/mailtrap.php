<?php
/**
 * Mailtrap Configuration
 * Level Up Fitness - Gym Management System
 * 
 * API Documentation: https://mailtrap.io/api/
 */

// Mailtrap API Credentials
define('MAILTRAP_API_TOKEN', getenv('MAILTRAP_API_TOKEN') ?: 'YOUR_MAILTRAP_API_TOKEN');
define('MAILTRAP_INBOX_ID', getenv('MAILTRAP_INBOX_ID') ?: 'YOUR_INBOX_ID');
define('MAILTRAP_API_BASE_URL', 'https://send.api.mailtrap.io');

// Email Configuration
define('MAILTRAP_FROM_EMAIL', 'noreply@levelupfitness.local');
define('MAILTRAP_FROM_NAME', 'Level Up Fitness');
define('MAILTRAP_REPLY_TO_EMAIL', 'support@levelupfitness.local');
define('MAILTRAP_REPLY_TO_NAME', 'Level Up Fitness Support');

// Email Features
define('MAILTRAP_ENABLED', true);
define('MAILTRAP_SANDBOX_MODE', getenv('APP_ENV') !== 'production' ? true : false);

// Email Template Configuration
define('EMAIL_TEMPLATE_DIR', dirname(__FILE__) . '/../email-templates/');

// Retry Configuration
define('MAILTRAP_RETRY_COUNT', 3);
define('MAILTRAP_RETRY_DELAY', 5); // seconds

// Development/Testing
if (MAILTRAP_SANDBOX_MODE) {
    define('MAILTRAP_TEST_EMAIL', 'test@mailinator.com'); // For testing in sandbox mode
}

/**
 * Initialize Mailtrap Configuration from Environment Variables
 * 
 * You can set these in a .env file or server environment:
 * MAILTRAP_API_TOKEN=your_token
 * MAILTRAP_INBOX_ID=your_inbox_id
 * APP_ENV=production|development
 */

// Validate configuration on production
if (!MAILTRAP_SANDBOX_MODE && (MAILTRAP_API_TOKEN === 'YOUR_MAILTRAP_API_TOKEN' || MAILTRAP_INBOX_ID === 'YOUR_INBOX_ID')) {
    error_log('WARNING: Mailtrap API credentials not properly configured for production mode');
}
?>
