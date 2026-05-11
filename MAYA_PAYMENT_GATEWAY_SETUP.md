# Maya Payment Gateway Configuration & Integration Guide

## Overview
Your Level Up Fitness system now has Maya payment gateway fully integrated. The system redirects users to Maya for online payments.

---

## 1. Configuration Files

### Primary Configuration: `config/payment-gateway.php`
This file contains all Maya payment gateway settings:

```php
'maya' => [
    'sandbox' => [
        'api_key' => getenv('MAYA_SANDBOX_API_KEY') ?: 'pk_test_sandbox_key_placeholder',
        'api_secret' => getenv('MAYA_SANDBOX_API_SECRET') ?: 'sk_test_sandbox_secret_placeholder',
        'api_url' => 'https://api-sandbox.maya.ph',
        'merchant_id' => getenv('MAYA_SANDBOX_MERCHANT_ID') ?: 'TEST_MERCHANT_001',
        'callback_url' => (getenv('APP_URL') ?: 'http://localhost/level-up-fitness/') . 'payment/callback',
        'webhook_url' => (getenv('APP_URL') ?: 'http://localhost/level-up-fitness/') . 'api/payments/webhook.php',
    ],
    'production' => [
        // Production credentials (disabled by default)
    ]
]
```

### Environment Variables Required
Set these in your `.env` or server environment:

```
MAYA_SANDBOX_API_KEY=your_sandbox_api_key
MAYA_SANDBOX_API_SECRET=your_sandbox_api_secret
MAYA_SANDBOX_MERCHANT_ID=your_merchant_id
MAYA_SANDBOX_WEBHOOK_SECRET=your_webhook_secret
MAYA_PRODUCTION_API_KEY=your_production_key
MAYA_PRODUCTION_API_SECRET=your_production_secret
```

---

## 2. Payment Method Option Location

**File:** `modules/payments/add.php`

The "Maya" payment method has been added to the payment recording form dropdown:

```php
<option value="Maya">Maya (Online)</option>
```

✅ **What Changed:**
- Added Maya to the payment methods list
- Added help text: "Select 'Maya (Online)' to redirect member to Maya payment gateway"
- Added Maya to Quick Reference section

---

## 3. Payment Flow

### Step 1: Admin Records Payment
Location: `modules/payments/add.php`

When admin selects **"Maya (Online)"**:
- Payment is created with status: **Pending**
- System redirects to checkout API

### Step 2: Checkout Initiation
Location: `api/payments/checkout.php`

The checkout endpoint:
1. Receives payment details (amount, member ID, description)
2. Calls `MayaPaymentService::createPaymentRequest()`
3. Gets checkout URL from Maya API
4. Stores transaction in `payment_gateway_transactions` table
5. Redirects user to Maya checkout URL (opens in new tab/window)

**Request Format:**
```
/api/payments/checkout.php?payment_id=PAY123&gateway=maya&amount=500&description=Gym%20Membership
```

### Step 3: Maya Payment Processing
User completes payment on Maya's secure checkout page (opens in new tab)

### Step 4: Webhook Callback
Location: `api/payments/webhook.php`

Maya sends webhook when payment completes:
- Updates transaction status
- Updates payment status (Paid/Failed)
- Sends notification to member
- Sends confirmation email

---

## 4. Key Classes & Methods

### MayaPaymentService Class
Location: `config/MayaPaymentService.php`

**Main Methods:**
```php
// Create payment request
$maya = new MayaPaymentService('sandbox');
$response = $maya->createPaymentRequest($paymentData);
// Returns: ['checkout_url' => '...', 'transaction_id' => '...', 'reference_number' => '...']

// Check payment status
$status = $maya->checkTransactionStatus($transactionId);

// Verify webhook signature
$verified = $maya->verifyWebhookSignature($data, $signature);

// Process webhook callback
$result = $maya->processWebhookCallback($webhookData);
```

---

## 5. Database Tables

### New/Updated Tables

**payment_gateway_transactions**
```sql
- transaction_id (PRIMARY KEY)
- payment_id (FOREIGN KEY)
- member_id (FOREIGN KEY)
- gateway_name (maya, manual)
- gateway_transaction_id
- gateway_reference_number
- amount
- currency (PHP)
- status (pending, completed, failed)
- request_data (JSON)
- response_data (JSON)
- webhook_data (JSON)
```

**gateway_webhooks**
```sql
- webhook_id (PRIMARY KEY)
- transaction_id
- gateway_name
- event_type
- payload (JSON)
- signature_verified (boolean)
- status (processing, processed)
```

**payments** (Updated)
```sql
- payment_method (now includes 'Maya')
- payment_gateway (populated for online payments)
- gateway_transaction_id (links to payment_gateway_transactions)
- gateway_reference_number
```

---

## 6. Payment Status Mapping

| Maya Status | System Status | Action |
|------------|--------------|--------|
| succeeded | Paid | Notification sent |
| failed | Overdue/Failed | Retry available |
| pending | Pending | Awaiting processing |
| cancelled | Overdue | Can retry |

---

## 7. Usage Instructions

### For Admins Recording Payments:

1. Go to **Payments → Add Payment**
2. Select member
3. Enter amount
4. Select **"Maya (Online)"** from Payment Method
5. (Optional) Enter payment date and notes
6. Click **Record Payment**
7. System creates payment record and redirects to Maya
8. Member completes payment in new tab
9. System automatically updates payment status via webhook

### For Members Making Online Payments:

Members can access online payment through:
- Member Dashboard payment module
- Online payment modal (if integrated in frontend)
- Admin-provided checkout links

---

## 8. Testing Checklist

### Sandbox Testing:
- [ ] Update `.env` with sandbox credentials
- [ ] Create payment with Maya method
- [ ] Verify redirect to Maya sandbox
- [ ] Use Maya test cards to complete payment
- [ ] Verify webhook received and status updated
- [ ] Check payment marked as "Paid"

### Production Setup:
- [ ] Get production credentials from Maya
- [ ] Update `.env` with production keys
- [ ] Enable production in `payment-gateway.php`
- [ ] Test with small amount
- [ ] Monitor webhook logs

---

## 9. Troubleshooting

### Payment Not Redirecting to Maya
- Check `config/payment-gateway.php` for valid API keys
- Verify `MayaPaymentService.php` is loading correctly
- Check error logs in `backend/logs/maya-sandbox/`

### Webhook Not Updating Payment
- Verify webhook URL is accessible: `/api/payments/webhook.php`
- Check webhook signature verification
- Look for webhook logs in `gateway_webhooks` table
- Ensure database connection in webhook handler

### Transaction Not Found
- Verify payment_id format matches `PAY*`
- Check `payment_gateway_transactions` table for entry
- Verify transaction_id passed to webhook

---

## 10. Configuration Summary

| Setting | Location | Status |
|---------|----------|--------|
| API Credentials | `.env` or `payment-gateway.php` | ⚙️ Setup Required |
| Payment Method Option | `modules/payments/add.php` | ✅ Done |
| Checkout Endpoint | `api/payments/checkout.php` | ✅ Done |
| Webhook Handler | `api/payments/webhook.php` | ✅ Done |
| Service Class | `config/MayaPaymentService.php` | ✅ Done |
| Database Tables | Migration scripts | ✅ Done |
| Constants | `config/config.php` | ✅ Done |

---

## Next Steps

1. **Get Maya Credentials:**
   - Go to Maya.ph merchant dashboard
   - Get sandbox credentials first
   - Later, get production credentials

2. **Update Environment Variables:**
   ```bash
   export MAYA_SANDBOX_API_KEY="pk_..."
   export MAYA_SANDBOX_API_SECRET="sk_..."
   export MAYA_SANDBOX_MERCHANT_ID="MERCHANT_..."
   ```

3. **Test Sandbox:**
   - Record a test payment with Maya
   - Complete payment with test card
   - Verify status updates

4. **Go Live:**
   - Switch to production credentials
   - Enable production in config
   - Start accepting real payments

---

## File Changes Made

### Modified Files:
1. **config/config.php**
   - Added: `define('PAYMENT_MAYA', 'Maya');`

2. **modules/payments/add.php**
   - Added Maya option to payment method dropdown
   - Added redirect logic for Maya payments
   - Added help text and reference information
   - Updated Quick Reference guide

### Related Existing Files:
- `config/payment-gateway.php` - Maya configuration
- `config/MayaPaymentService.php` - Payment service class
- `api/payments/checkout.php` - Checkout endpoint
- `api/payments/webhook.php` - Webhook handler
- `api/payments/status.php` - Status check endpoint
