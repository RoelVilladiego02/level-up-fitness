# Maya Payment Gateway - Complete Setup Guide

## Current Status: ✅ READY FOR TESTING

Your system is now configured for **SANDBOX TESTING** with mock responses enabled.

---

## 🧪 Testing the Payment Flow (Without Real API Credentials)

### Step 1: Record a Test Payment
1. Go to **Payments → Add Payment**
2. Select a member
3. Enter an amount (e.g., ₱100)
4. Select **"Maya (Online)"**
5. Click **Record Payment**

### Step 2: Mock Checkout Page
You'll see a professional checkout page with test options:
- ✅ **Successful Payment** - Simulates successful transaction
- ❌ **Failed Payment** - Simulates payment failure
- ⏹️ **Cancel Payment** - Simulates user cancellation

### Step 3: Test Results
Select an option and click "Proceed" - the system will:
- Update payment status in database
- Send notification to member
- Update transaction history

---

## 🔑 Getting Real Maya API Credentials

### Step 1: Create Maya Merchant Account

1. Go to **https://maya.ph**
2. Sign up for **Merchant Account** (not consumer account)
3. Complete verification:
   - Business information
   - Bank details
   - Tax ID

### Step 2: Access Merchant Dashboard

1. Log in to **https://merchant.maya.ph**
2. Navigate to **Settings → API Keys**
3. Copy your credentials:
   - **Sandbox Public Key** (starts with `pk_test_`)
   - **Sandbox Secret Key** (starts with `sk_test_`)
   - **Merchant ID**
   - **Webhook Secret**

### Step 3: Get Production Credentials

Once ready for live payments:
1. In same API Keys section, find **Production** credentials
2. Production keys start with:
   - Public: `pk_live_`
   - Secret: `sk_live_`

---

## 📋 Configuration File Locations

### Main Configuration
**File:** `config/payment-gateway.php`

```php
'sandbox' => [
    'api_key' => 'pk_test_xxxxx',              // Your sandbox API key
    'api_secret' => 'sk_test_xxxxx',           // Your sandbox secret
    'merchant_id' => 'MERCHANT_xxxxx',         // Your merchant ID
    'webhook_secret' => 'webhook_secret_xxx',  // For webhook verification
    'mock_responses' => true,                  // Set to false when using real API
]
```

### Environment Variables (Recommended)
Set in your server or `.env` file:

```bash
MAYA_SANDBOX_API_KEY=pk_test_xxxxx
MAYA_SANDBOX_API_SECRET=sk_test_xxxxx
MAYA_SANDBOX_MERCHANT_ID=MERCHANT_xxxxx
MAYA_SANDBOX_WEBHOOK_SECRET=webhook_secret_xxx

MAYA_PRODUCTION_API_KEY=pk_live_xxxxx
MAYA_PRODUCTION_API_SECRET=sk_live_xxxxx
MAYA_PRODUCTION_MERCHANT_ID=MERCHANT_xxxxx
MAYA_PRODUCTION_WEBHOOK_SECRET=webhook_secret_xxx
```

---

## 🔄 Payment Flow Architecture

### Current Flow (Sandbox/Mock)
```
Admin Records Payment (Maya)
    ↓
System creates payment record (status: Pending)
    ↓
MayaPaymentService checks mock_responses = true
    ↓
Returns mock checkout URL
    ↓
Redirects to /payment/mock-checkout.php
    ↓
User selects test scenario (Success/Failed/Cancel)
    ↓
Mock webhook processes result
    ↓
Payment status updated in database
    ↓
Member receives notification
```

### Real API Flow (When Using Actual Credentials)
```
Admin Records Payment (Maya)
    ↓
System creates payment record (status: Pending)
    ↓
MayaPaymentService sends to Maya API
    ↓
Maya returns checkout_url
    ↓
User redirected to Maya's secure checkout
    ↓
User completes payment on Maya
    ↓
Maya sends webhook to /api/payments/webhook.php
    ↓
System verifies webhook signature
    ↓
Payment status updated automatically
    ↓
Member receives email confirmation
```

---

## 🔐 Switching from Sandbox to Real API

### Step 1: Update Configuration

**File:** `config/payment-gateway.php`

```php
'sandbox' => [
    'enabled' => true,
    'mock_responses' => false,  // CHANGE: Disable mock responses
    'api_key' => getenv('MAYA_SANDBOX_API_KEY'),
    'api_secret' => getenv('MAYA_SANDBOX_API_SECRET'),
],

'production' => [
    'enabled' => false,  // Change to true only when ready
    'mock_responses' => false,
    'api_key' => getenv('MAYA_PRODUCTION_API_KEY'),
    'api_secret' => getenv('MAYA_PRODUCTION_API_SECRET'),
],
```

### Step 2: Set Environment Variables

Replace placeholder values with your actual Maya credentials:

```bash
# Sandbox (for testing with real API)
MAYA_SANDBOX_API_KEY=pk_test_your_actual_key
MAYA_SANDBOX_API_SECRET=sk_test_your_actual_secret

# Production (for live payments)
MAYA_PRODUCTION_API_KEY=pk_live_your_actual_key
MAYA_PRODUCTION_API_SECRET=sk_live_your_actual_secret
```

### Step 3: Test Thoroughly

1. Test with sandbox credentials first
2. Create test payments
3. Verify webhook handling
4. Check email notifications
5. Verify database updates

### Step 4: Enable Production

When ready for live payments:

```php
'production' => [
    'enabled' => true,  // Enable production
    ...
]
```

**Warning:** Once production is enabled, ALL payments will be real transactions!

---

## 🧪 Testing Sandbox Payment Methods

### Test Cards in Maya Sandbox

Use these test card details on the Maya checkout page:

**Successful Payment:**
- Card Number: `4242 4242 4242 4242`
- Exp: `12/25`
- CVV: `123`

**Failed Payment:**
- Card Number: `4000 0000 0000 0002`
- Exp: `12/25`
- CVV: `123`

---

## 📊 Database Tables

### payment_gateway_transactions
Stores all payment transactions:
```sql
- transaction_id: Unique transaction ID (Primary Key)
- payment_id: Links to payments table
- member_id: The member being charged
- gateway_name: 'maya' or 'manual'
- gateway_transaction_id: Reference from Maya
- amount: Payment amount
- status: pending|completed|failed
- request_data: JSON of request sent
- response_data: JSON of response received
- webhook_data: JSON of webhook received
```

### gateway_webhooks
Logs all webhook callbacks:
```sql
- webhook_id: Unique ID (Primary Key)
- transaction_id: Links to transaction
- gateway_name: 'maya'
- payload: Full webhook JSON
- signature_verified: boolean
- status: processing|processed
```

### payments (Updated)
Payment records now include:
```sql
- payment_gateway: 'maya' or 'manual'
- gateway_transaction_id: Links to payment_gateway_transactions
- gateway_reference_number: Maya reference
```

---

## 🔍 Debugging & Logs

### PHP Error Logs
Located in: `backend/logs/maya-sandbox/` (when enabled)

### Database Logs
Query the webhook table:
```sql
SELECT * FROM gateway_webhooks WHERE gateway_name = 'maya' ORDER BY created_at DESC;
```

### Transaction Status Check
```sql
SELECT * FROM payment_gateway_transactions WHERE member_id = 'MEM001' ORDER BY created_at DESC;
```

---

## 🚀 Going Live Checklist

- [ ] Obtained production API keys from Maya
- [ ] Set `mock_responses = false` in production config
- [ ] Tested with real sandbox API (mock_responses = false, using test credentials)
- [ ] Verified webhook signature verification works
- [ ] Tested email notifications send correctly
- [ ] Verified database updates happen properly
- [ ] Set production credentials in environment variables
- [ ] Changed `production.enabled = true`
- [ ] Tested one small live transaction
- [ ] Verified payment shows as "Paid" in database
- [ ] Confirmed member receives confirmation email

---

## 📞 Support Resources

**Maya Documentation:** https://docs.maya.ph
**Maya Support:** support@maya.ph
**Maya Merchant Portal:** https://merchant.maya.ph

---

## Quick Reference

| Setting | Sandbox Value | Production Value |
|---------|---------------|------------------|
| API URL | `api-sandbox.maya.ph` | `api.maya.ph` |
| API Key | `pk_test_xxx` | `pk_live_xxx` |
| Mock Responses | `true` (for now) | `false` |
| Test Cards | See section above | Real cards only |
| Enabled | `true` | `false` until ready |

