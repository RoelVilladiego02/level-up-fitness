<?php
/**
 * Payment Gateway Configuration
 * Level Up Fitness - Gym Management System
 * 
 * This configuration file contains all payment gateway settings
 * for different environments (sandbox/testing, production)
 */

return [
    'enabled_gateways' => ['maya', 'manual'],
    'default_gateway' => 'maya',
    
    // ====================================================================
    // MAYA PAYMENT GATEWAY CONFIGURATION
    // ====================================================================
    'maya' => [
        
        // SANDBOX/TESTING ENVIRONMENT
        // ============================
        // For development and testing purposes
        // Uses Maya's sandbox API with test credentials
        'sandbox' => [
            'enabled' => true,
            'environment' => 'sandbox',
            'environment_label' => 'Testing Environment',
            
            // API Credentials (Test/Sandbox)
            // Note: Using real Maya sandbox credentials from .env
            'api_key' => getenv('MAYA_SANDBOX_API_KEY') ?: 'pk-Z0OSzLvIcOI2UIvDhdTGVVfRSSeiGStnceqwUE7n0Ah',
            'api_secret' => getenv('MAYA_SANDBOX_API_SECRET') ?: 'sk-X8qolYjy62kIzEbr0QRK1h4b4KDVHaNcwMYk39jInSl',
            
            // API Endpoints
            'api_url' => 'https://api-sandbox.maya.ph', // Test API endpoint
            
            // Merchant Information (Test Merchant)
            'merchant_id' => getenv('MAYA_SANDBOX_MERCHANT_ID') ?: 'LEVELUP_SANDBOX_001',
            'merchant_name' => 'Level Up Fitness Test',
            'merchant_logo_url' => 'https://levelupfitness.local/assets/img/logo.png',
            
            // Callback & Webhook Configuration
            'callback_url' => (getenv('APP_URL') ?: 'http://localhost/level-up-fitness/') . 'payment/callback',
            'webhook_url' => (getenv('APP_URL') ?: 'http://localhost/level-up-fitness/') . 'api/payments/webhook.php',
            'webhook_secret' => getenv('MAYA_SANDBOX_WEBHOOK_SECRET') ?: 'webhook_secret_test_key_placeholder',
            
            // Payment Settings
            'payment_timeout' => 30 * 60, // 30 minutes
            'auto_retry_failed' => false,
            'retry_attempts' => 3,
            'retry_interval' => 5 * 60, // 5 minutes
            
            // Transaction Logging
            'enable_transaction_logging' => true,
            'log_path' => dirname(dirname(__FILE__)) . '/backend/logs/maya-sandbox/',
            
            // Supported Payment Methods (Testing)
            'supported_methods' => [
                'MAYA' => 'Maya Wallet',
                'CREDIT_CARD' => 'Credit Card',
                'DEBIT_CARD' => 'Debit Card',
                'BANK_TRANSFER' => 'Bank Transfer',
                'ONLINE_BANKING' => 'Online Banking',
                'OTC' => 'Over the Counter',
            ],
            
            // Transaction Limits (Testing)
            'minimum_amount' => 1.00,
            'maximum_amount' => 100000.00,
            
            // Test Mode Features
            'test_mode' => true,
            'mock_responses' => true, // Using mock responses for testing
            'debug_mode' => true,
        ],
        
        // PRODUCTION ENVIRONMENT
        // =====================
        // For live payments - requires real credentials
        'production' => [
            'enabled' => false, // Disabled by default - enable only when ready
            'environment' => 'production',
            'environment_label' => 'Production',
            
            // API Credentials (Production)
            // Note: Replace with actual production credentials from Maya Dashboard
            'api_key' => getenv('MAYA_PRODUCTION_API_KEY') ?: '',
            'api_secret' => getenv('MAYA_PRODUCTION_API_SECRET') ?: '',
            
            // API Endpoints
            'api_url' => 'https://api.maya.ph', // Production API endpoint
            
            // Merchant Information (Production Merchant)
            'merchant_id' => getenv('MAYA_PRODUCTION_MERCHANT_ID') ?: '',
            'merchant_name' => 'Level Up Fitness',
            'merchant_logo_url' => 'https://levelupfitness.com/assets/img/logo.png',
            
            // Callback & Webhook Configuration
            'callback_url' => (getenv('APP_URL') ?: 'https://levelupfitness.com/') . 'payment/callback',
            'webhook_url' => (getenv('APP_URL') ?: 'https://levelupfitness.com/') . 'api/payments/webhook.php',
            'webhook_secret' => getenv('MAYA_PRODUCTION_WEBHOOK_SECRET') ?: '',
            
            // Payment Settings
            'payment_timeout' => 60 * 60, // 1 hour
            'auto_retry_failed' => true,
            'retry_attempts' => 5,
            'retry_interval' => 15 * 60, // 15 minutes
            
            // Transaction Logging
            'enable_transaction_logging' => true,
            'log_path' => dirname(dirname(__FILE__)) . '/backend/logs/maya-production/',
            
            // Supported Payment Methods (Production)
            'supported_methods' => [
                'MAYA' => 'Maya Wallet',
                'CREDIT_CARD' => 'Credit Card',
                'DEBIT_CARD' => 'Debit Card',
                'BANK_TRANSFER' => 'Bank Transfer',
                'ONLINE_BANKING' => 'Online Banking',
                'INSTALLMENT' => 'Installment Plans',
                'OTC' => 'Over the Counter',
            ],
            
            // Transaction Limits (Production)
            'minimum_amount' => 50.00,
            'maximum_amount' => 500000.00,
            
            // Production Mode Features
            'test_mode' => false,
            'mock_responses' => false,
            'debug_mode' => false,
        ],
    ],
    
    // ====================================================================
    // MANUAL PAYMENT GATEWAY (FALLBACK)
    // ====================================================================
    // For manual payment processing (Cash, Bank Transfer, etc.)
    'manual' => [
        'enabled' => true,
        'gateway_name' => 'Manual Payment Processing',
        'description' => 'For recording manual payments (Cash, Check, Bank Transfer)',
        'supported_methods' => [
            'Cash' => 'Physical Cash',
            'Bank Transfer' => 'Bank Transfer',
            'Check' => 'Check',
            'GCash' => 'GCash Transfer',
        ],
        'requires_verification' => true,
        'verification_timeout' => 48 * 60 * 60, // 48 hours
    ],
    
    // ====================================================================
    // GENERAL PAYMENT SETTINGS
    // ====================================================================
    'general' => [
        'currency' => 'PHP',
        'currency_symbol' => '₱',
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i:s',
        'timezone' => 'Asia/Manila',
        
        // Payment Notifications
        'enable_payment_notifications' => true,
        'notification_email' => getenv('PAYMENT_NOTIFICATION_EMAIL') ?: 'payments@levelupfitness.com',
        'notify_admin_on_payment' => true,
        'notify_member_on_payment' => true,
        
        // Payment Retry Policy
        'max_retry_attempts' => 3,
        'retry_delay' => 300, // 5 minutes
        'failed_payment_timeout' => 24 * 60 * 60, // 24 hours
        
        // Transaction Recording
        'auto_record_transactions' => true,
        'transaction_reconciliation_enabled' => true,
        'reconciliation_schedule' => 'daily', // daily, weekly, monthly
        
        // Security
        'enable_ssl_verification' => true,
        'ip_whitelist_enabled' => false,
        'ip_whitelist' => [],
        
        // Logging & Audit
        'enable_audit_logging' => true,
        'log_successful_payments' => true,
        'log_failed_payments' => true,
        'log_webhook_events' => true,
        'retention_days' => 365,
    ],
    
    // ====================================================================
    // WEBHOOK CONFIGURATION
    // ====================================================================
    'webhooks' => [
        'verify_signature' => true,
        'retry_failed_webhooks' => true,
        'webhook_timeout' => 30,
        'events' => [
            'PAYMENT_COMPLETED' => 'Payment successfully completed',
            'PAYMENT_PENDING' => 'Payment is pending',
            'PAYMENT_FAILED' => 'Payment has failed',
            'PAYMENT_CANCELLED' => 'Payment was cancelled',
            'REFUND_COMPLETED' => 'Refund has been processed',
            'TRANSACTION_UPDATED' => 'Transaction details updated',
        ]
    ],
    
    // ====================================================================
    // TEST DATA (FOR SANDBOX ONLY)
    // ====================================================================
    'test_data' => [
        'test_cards' => [
            [
                'card_number' => '4111111111111111',
                'expiry' => '12/25',
                'cvv' => '123',
                'result' => 'success',
                'description' => 'Visa - Success'
            ],
            [
                'card_number' => '4000000000000002',
                'expiry' => '12/25',
                'cvv' => '123',
                'result' => 'failed',
                'description' => 'Visa - Failed'
            ],
            [
                'card_number' => '5555555555554444',
                'expiry' => '12/25',
                'cvv' => '123',
                'result' => 'success',
                'description' => 'Mastercard - Success'
            ]
        ],
        'test_amounts' => [
            '1.00' => 'Minimum transaction',
            '100.00' => 'Standard transaction',
            '999.99' => 'Large transaction',
            '50000.00' => 'Maximum transaction'
        ]
    ]
];
?>
