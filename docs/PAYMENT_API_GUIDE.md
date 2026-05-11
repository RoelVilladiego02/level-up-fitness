# Payment API Implementation - Maya Payment Gateway Integration

**Level Up Fitness - Gym Management System**  
**Version**: 1.0.0  
**Date**: May 11, 2026  
**Environment**: Testing/Sandbox

---

## 📋 Overview

This document describes the complete payment gateway integration for Level Up Fitness, specifically the Maya Payment API implementation for sandbox/testing environment. The system has been designed as an external payment processing module that can be extended to support multiple payment gateways.

### Key Features
- 🔐 Secure online payment processing via Maya
- 🔄 Webhook-based payment status notifications
- 📊 Transaction tracking and audit logging
- 🔌 Extensible architecture for multiple gateways
- ⚙️ Sandbox environment for safe testing
- 📧 Automated payment notifications
- 💳 Support for multiple payment methods

---

## 🏗️ Architecture Overview

```
┌─────────────────────────────────────────────────────────────┐
│                    Level Up Fitness System                  │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌────────────────┐      ┌──────────────────┐               │
│  │  Payment UI    │──→   │  Checkout API    │               │
│  │ (Dashboard)    │      │  (/checkout.php) │               │
│  └────────────────┘      └────────┬─────────┘               │
│                                   │                         │
│  ┌────────────────┐              │                         │
│  │  Admin Panel   │──→   ┌────────▼─────────┐              │
│  │ (Add Payment)  │      │ Maya Service     │              │
│  └────────────────┘      │ (External Module)│              │
│                          └────────┬─────────┘              │
│                                   │                         │
│                          ┌────────▼─────────┐              │
│                          │   Maya API       │              │
│                          │  (Sandbox)       │              │
│                          └────────┬─────────┘              │
│                                   │                         │
│  ┌────────────────┐               │                        │
│  │  Webhook       │◀──────────────┘                        │
│  │  Handler       │                                        │
│  │ (webhook.php)  │                                        │
│  └────────┬───────┘                                        │
│           │                                                 │
│  ┌────────▼────────────────────────┐                       │
│  │  Database Updates                │                       │
│  │  - Payment Status                │                       │
│  │  - Transaction Tracking          │                       │
│  │  - Audit Logs                    │                       │
│  └────────┬────────────────────────┘                        │
│           │                                                  │
│  ┌────────▼────────────────────────┐                        │
│  │  Notifications                   │                        │
│  │  - Email to Member               │                        │
│  │  - In-app Notification           │                        │
│  │  - Admin Alert                   │                        │
│  └──────────────────────────────────┘                        │
│                                                               │
└─────────────────────────────────────────────────────────────┘
```

---

## 📁 File Structure

```
level-up-fitness/
├── config/
│   ├── MayaPaymentService.php          ← Main payment service class
│   ├── payment-gateway.php              ← Gateway configuration
│   ├── database.php
│   └── ...
├── api/
│   └── payments/
│       ├── checkout.php                 ← Initiate payment
│       ├── webhook.php                  ← Receive payment updates
│       ├── status.php                   ← Check transaction status
│       └── ...
├── modules/
│   └── payments/
│       ├── index.php                    ← Payment listing
│       ├── add.php                      ← Add/record payment
│       ├── online-payment-modal.html    ← Online payment UI
│       └── ...
├── add-payment-gateway-tables.php       ← Database migration
├── test-payment-api.php                 ← API test suite
└── docs/
    └── PAYMENT_API_GUIDE.md             ← This file
```

---

## 🗄️ Database Schema

### New/Modified Tables

#### `payments` (Modified)
```sql
ALTER TABLE payments ADD COLUMNS (
    payment_gateway VARCHAR(50) DEFAULT 'manual',
    gateway_transaction_id VARCHAR(100) NULL,
    gateway_reference_number VARCHAR(100) NULL,
    payment_attempt_count INT DEFAULT 0,
    payment_retry_count INT DEFAULT 0,
    last_retry_at TIMESTAMP NULL
);
```

#### `payment_gateway_transactions` (New)
Tracks all external payment gateway transactions:
- transaction_id: Unique transaction identifier
- gateway_name: Payment gateway (maya, manual, etc)
- gateway_transaction_id: External gateway ID
- status: pending, processing, completed, failed, cancelled, refunded
- amount, currency, payment_method
- request_data, response_data: JSON payloads
- Timestamps and retry information
- Error tracking

#### `gateway_webhooks` (New)
Logs all webhook callbacks:
- webhook_id: Unique webhook identifier
- transaction_id: Reference to transaction
- event_type: payment_completed, payment_failed, etc
- payload: Full webhook data (JSON)
- signature_verified: Boolean
- status: received, processing, processed, failed, retrying

#### `gateway_refunds` (New)
Tracks refund requests:
- refund_id: Unique refund identifier
- transaction_id: Reference to original transaction
- status: pending, processing, completed, failed, cancelled
- gateway_refund_id: External gateway refund ID
- Error tracking and timestamps

#### `gateway_logs` (New)
Audit trail for all gateway operations:
- transaction_id: Reference (nullable)
- action: create_payment, check_status, webhook, retry, etc
- method: GET, POST, PUT, WEBHOOK
- request/response data
- HTTP status and error codes
- Performance metrics (duration_ms)

---

## 🔧 Configuration

### Payment Gateway Config: `/config/payment-gateway.php`

```php
// Sandbox Environment (Testing)
'maya' => [
    'sandbox' => [
        'api_key' => 'pk_test_sandbox_key_placeholder',
        'api_secret' => 'sk_test_sandbox_secret_placeholder',
        'merchant_id' => 'TEST_MERCHANT_001',
        'api_url' => 'https://api-sandbox.maya.ph',
        'webhook_secret' => 'webhook_secret_test_key_placeholder',
        // ... more settings
    ],
    'production' => [
        // Real credentials (disabled by default)
    ]
]
```

### Environment Variables

Set these in your `.env` or system:

```bash
# Maya Sandbox (Testing)
MAYA_SANDBOX_API_KEY=pk_test_sandbox_key_placeholder
MAYA_SANDBOX_API_SECRET=sk_test_sandbox_secret_placeholder
MAYA_SANDBOX_MERCHANT_ID=TEST_MERCHANT_001
MAYA_SANDBOX_WEBHOOK_SECRET=webhook_secret_test_key_placeholder

# Maya Production (When ready)
MAYA_PRODUCTION_API_KEY=your_production_key
MAYA_PRODUCTION_API_SECRET=your_production_secret
MAYA_PRODUCTION_MERCHANT_ID=your_merchant_id
MAYA_PRODUCTION_WEBHOOK_SECRET=your_webhook_secret

# Application
APP_URL=http://localhost/level-up-fitness/
```

---

## 🚀 API Endpoints

### 1. Checkout Endpoint
**POST** `/api/payments/checkout.php`

Initiates a new payment transaction.

**Request Body:**
```json
{
    "gateway": "maya",
    "amount": 1500.00,
    "description": "Monthly Gym Membership",
    "payment_id": "PAY-2026-001",
    "payment_type": "E_WALLET"
}
```

**Response (Success):**
```json
{
    "success": true,
    "message": "Payment checkout initiated",
    "code": "CHECKOUT_SUCCESS",
    "data": {
        "transaction_id": "MAYA-1715420400-abc12345",
        "checkout_url": "https://api-sandbox.maya.ph/checkout/MAYA-1715420400-abc12345",
        "reference_number": "REF-2026-001",
        "amount": 1500.00,
        "gateway": "maya",
        "status": "pending"
    }
}
```

**Response (Error):**
```json
{
    "success": false,
    "error": "Failed to create Maya payment: Invalid API credentials",
    "code": "CHECKOUT_FAILED"
}
```

---

### 2. Webhook Endpoint
**POST** `/api/payments/webhook.php`

Receives payment status updates from Maya.

**Webhook Payload:**
```json
{
    "gateway": "maya",
    "reference_number": "MAYA-1715420400-abc12345",
    "status": "COMPLETED",
    "total_amount": 1500.00,
    "event_type": "PAYMENT_COMPLETED",
    "timestamp": "2026-05-11T10:30:00Z",
    "signature": "sha256_hash_signature"
}
```

**Response:**
```json
{
    "success": true,
    "message": "Webhook processed successfully",
    "webhook_id": "WH-1715420400-xyz98765",
    "transaction_id": "MAYA-1715420400-abc12345",
    "code": "WEBHOOK_PROCESSED"
}
```

---

### 3. Status Check Endpoint
**GET** `/api/payments/status.php?transaction_id=MAYA-1715420400-abc12345`

Check current status of a payment transaction.

**Response:**
```json
{
    "success": true,
    "data": {
        "transaction_id": "MAYA-1715420400-abc12345",
        "payment_id": "PAY-2026-001",
        "amount": 1500.00,
        "currency": "PHP",
        "status": "completed",
        "gateway": "maya",
        "created_at": "2026-05-11 10:00:00",
        "completed_at": "2026-05-11 10:05:30"
    }
}
```

---

## 📊 Payment Flow

### Online Payment (Maya) Flow

```
1. Member Initiates Payment
   └→ Dashboard → Payment Modal → Select Maya

2. Frontend Calls Checkout API
   └→ POST /api/payments/checkout.php
      {gateway: "maya", amount: 1500, ...}

3. Service Creates Payment Request
   └→ MayaPaymentService::createPaymentRequest()
      └→ Validates data
      └→ Builds payload
      └→ Signs request
      └→ Sends to Maya API

4. Maya Returns Checkout Link
   └→ Response includes checkout_url
   └→ System stores transaction record

5. Frontend Redirects to Checkout
   └→ window.location.href = checkout_url
   └→ Maya Payment Page Opens

6. Member Completes Payment
   └→ Enters payment details
   └→ Confirms payment
   └→ Maya processes transaction

7. Maya Sends Webhook
   └→ POST /api/payments/webhook.php
      {status: "COMPLETED", reference_number: ...}

8. System Processes Webhook
   └→ Verifies signature
   └→ Updates transaction status
   └→ Updates payment status
   └→ Sends notifications

9. Member Gets Confirmation
   └→ Email notification sent
   └→ In-app notification created
   └→ Dashboard updated
```

### Manual Payment Flow

```
1. Admin Records Payment
   └→ Admin Dashboard → Add Payment
      └→ Select payment_method = "Manual"
      └→ Enter details
      └→ Submit

2. System Creates Payment Record
   └→ Payment created in pending status
   └→ Email notification sent to member
   └→ Activity logged

3. Admin Verifies Payment
   └→ Check bank confirmation
   └→ Check GCash history, etc
   └→ Admin dashboard → Mark as Paid

4. System Updates Status
   └→ Payment status changed to "Paid"
   └→ Confirmation email sent
   └→ In-app notification sent
   └→ Invoice generated
```

---

## 🔐 Security Features

### 1. Signature Verification
All webhook requests are verified using HMAC-SHA256:

```php
$expectedSignature = hash_hmac('sha256', $webhookData, $webhookSecret);
if (!hash_equals($expectedSignature, $receivedSignature)) {
    throw new Exception('Invalid signature');
}
```

### 2. Request Security
- SSL/TLS encryption for all API calls
- Bearer token authentication
- Request ID tracking
- IP whitelisting (optional)

### 3. Data Protection
- Sensitive data encrypted in transit
- Payment data never stored in logs
- Transaction hashes for audit trail
- Prepared statements for SQL queries

### 4. Access Control
- Authenticated requests only
- Role-based access control
- Member can only see own payments
- Admin full access

---

## 🧪 Testing

### Run API Test Suite

```bash
cd /xampp/htdocs/level-up-fitness

# Via PHP CLI
php test-payment-api.php

# Via Browser
http://localhost/level-up-fitness/test-payment-api.php
```

### Test Results Should Show:
- ✓ Service initialization
- ✓ Connection test
- ✓ Data validation
- ✓ Database tables
- ✓ Configuration files
- ✓ PHP extensions
- ✓ Sample transaction

### Manual Testing Steps

1. **Setup Database**
   ```bash
   php add-payment-gateway-tables.php
   ```

2. **Configure Credentials**
   - Get test credentials from Maya Dashboard
   - Update `/config/payment-gateway.php`
   - Set environment variables

3. **Test Checkout Flow**
   - Login as member
   - Go to payment module
   - Click "Pay Online - Maya"
   - Fill amount: 100.00 (test amount)
   - Click "Process Payment"
   - Should redirect to Maya sandbox

4. **Test Webhook Reception**
   - Use cURL or Postman to simulate webhook:
   ```bash
   curl -X POST http://localhost/level-up-fitness/api/payments/webhook.php \
     -H "Content-Type: application/json" \
     -d '{
       "gateway": "maya",
       "reference_number": "MAYA-1715420400-abc12345",
       "status": "COMPLETED",
       "total_amount": 1500,
       "signature": "valid_signature"
     }'
   ```

5. **Check Database**
   - Query `payment_gateway_transactions`
   - Verify status updates
   - Check webhook logs

---

## 🔄 Payment Status Lifecycle

```
┌──────────┐
│ pending  │ ← Initial state when payment created
└────┬─────┘
     │
     ├─→ ┌──────────┐     ┌──────────┐
     │   │processing│ ──→ │ completed│ ← Payment successful
     └─→ └──────────┘     └──────────┘
         │                    ▲
         │                    │
         └─→ ┌──────────┐ ◀──┘
             │  failed  │ ← Payment declined/failed
             └──────────┘

Additional states:
- cancelled: User cancelled the payment
- refunded: Payment was refunded
```

---

## 📧 Notifications

### Payment Successful
**Email To**: Member's registered email  
**Subject**: "Payment Confirmation - Level Up Fitness"

```
Dear [Member Name],

Your payment has been successfully processed.

Payment ID: PAY-2026-001
Amount: ₱1,500.00
Method: Online - Maya
Date: May 11, 2026

Thank you for your payment!

---
Level Up Fitness
```

### Payment Failed
**Email To**: Member's registered email  
**Subject**: "Payment Failed - Level Up Fitness"

```
Dear [Member Name],

Unfortunately, your payment could not be processed.

Amount: ₱1,500.00
Status: Failed

Please try again or contact our support team.

---
Level Up Fitness
```

### Admin Notification
- In-app notification for payment received
- Activity log entry for audit trail
- Email alert for significant amounts (optional)

---

## 🐛 Troubleshooting

### Issue: "Invalid webhook signature"
- **Cause**: Webhook secret mismatch
- **Fix**: Verify webhook secret in `/config/payment-gateway.php` matches Maya Dashboard

### Issue: "Maya API Error: Unknown error"
- **Cause**: API credentials incorrect or network issue
- **Fix**: 
  1. Check credentials in environment variables
  2. Test connection with `php test-payment-api.php`
  3. Verify sandbox API endpoint is reachable

### Issue: Payment created but webhook never received
- **Cause**: Webhook URL misconfigured
- **Fix**:
  1. Update `callback_url` in configuration
  2. Ensure webhook endpoint is publicly accessible
  3. Check firewall/server logs

### Issue: "Transaction not found" in webhook
- **Cause**: Payment record not created before webhook
- **Fix**:
  1. Ensure checkout API creates record
  2. Check `payment_gateway_transactions` table
  3. Review `/add-payment-gateway-tables.php` migration

### Issue: Member not receiving email
- **Cause**: Email service not configured
- **Fix**:
  1. Check `/config/MailtrapService.php`
  2. Verify email credentials
  3. Check spam/junk folder
  4. Review `sendEmailNotification()` function

---

## 🚄 Performance Optimization

### Indexes
Database indexes for fast queries:
```sql
INDEX idx_gateway (payment_gateway)
INDEX idx_gateway_transaction (gateway_transaction_id)
INDEX idx_status (status)
INDEX idx_created_at (created_at)
INDEX idx_transaction_id (transaction_id)
```

### Caching
- Cache payment methods in session
- Cache gateway configuration
- Cache member data during checkout

### Async Processing
- Webhook processing async when possible
- Retry failed webhooks automatically
- Queue email notifications

---

## 📈 Monitoring & Logging

### Log Files
- `/backend/logs/maya-sandbox/` - Sandbox transactions
- `/backend/logs/maya-production/` - Production transactions
- Database table `gateway_logs` - All API operations

### Queries for Monitoring

```sql
-- Payment success rate
SELECT 
    DATE(created_at) as date,
    COUNT(*) as total,
    SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as successful,
    ROUND(SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END)*100/COUNT(*),2) as success_rate
FROM payment_gateway_transactions
GROUP BY DATE(created_at);

-- Failed payments
SELECT * FROM payment_gateway_transactions
WHERE status = 'failed'
ORDER BY created_at DESC;

-- Webhook retry stats
SELECT 
    event_type,
    COUNT(*) as total,
    SUM(CASE WHEN status='processed' THEN 1 ELSE 0 END) as processed,
    SUM(CASE WHEN status='failed' THEN 1 ELSE 0 END) as failed
FROM gateway_webhooks
GROUP BY event_type;
```

---

## 🎯 Future Enhancements

1. **Multiple Payment Gateways**
   - GCash Gateway
   - PayMongo Integration
   - Stripe Integration

2. **Advanced Features**
   - Subscription/Recurring Payments
   - Payment Plans & Installments
   - Multi-currency Support
   - Advanced Analytics Dashboard

3. **Security Enhancements**
   - PCI-DSS Compliance
   - 3D Secure Support
   - Enhanced Fraud Detection

4. **User Experience**
   - Payment Method Saving
   - One-Click Checkout
   - Mobile Payment Optimization

---

## 📞 Support & Resources

### Maya Payment Documentation
- [Maya Developer Portal](https://developers.maya.ph)
- [Maya API Reference](https://developers.maya.ph/docs)
- [Maya Sandbox Environment](https://sandbox.maya.ph)

### Level Up Fitness Documentation
- [System Architecture](./SYSTEM_ARCHITECTURE_ANALYSIS.md)
- [Implementation Guide](./IMPLEMENTATION_GUIDE.md)
- [Database Schema](./docs/DATABASE_RESET_GUIDE.md)

### Contact
- **Support Email**: support@levelupfitness.com
- **Technical Issues**: tech@levelupfitness.com
- **Payment Issues**: payments@levelupfitness.com

---

**Last Updated**: May 11, 2026  
**Version**: 1.0.0  
**Status**: Ready for Testing
