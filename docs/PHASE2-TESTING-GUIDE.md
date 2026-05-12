# Phase 2 Admin Payment Module - Testing Guide

## What's Been Built (Phase 2 Complete)

### New/Updated Files
1. **`/modules/payments/add.php`** ✅
   - Completely refactored from payment entry point to admin-only adjustment form
   - Full form with validation, error display, and submission handler
   - JavaScript for dynamic invoice loading and amount calculation

2. **`/api/invoices/get-member-invoices.php`** ✅
   - New API endpoint returning JSON list of member invoices
   - Used by add.php to dynamically populate invoice dropdown
   - Includes outstanding balance calculation

3. **`/api/payments/webhook.php`** ✅ (Previously updated)
   - Now detects and handles both invoice_payments AND legacy payments tables
   - Automatically updates invoice_status when payment confirmed
   - Backward compatible with existing payment system

4. **`/modules/payments/pay.php`** ✅ (Previously created)
   - Member self-service payment portal
   - Invoice selection, amount calculation, payment method toggle
   - Redirects to Maya checkout or manual transfer handler

5. **`/modules/payments/pay-manual.php`** ✅ (Previously created)
   - Handles manual bank transfer payment proof upload
   - File storage, admin notification, status tracking

6. **`/includes/functions.php`** ✅ (9 new functions added)
   - recordInvoicePayment(), getMemberOutstandingBalance(), createInvoice()
   - updateInvoiceStatus(), getInvoiceDetails(), getInvoicePayments()
   - cancelInvoice(), sendInvoiceEmail(), and more

---

## Testing Checklist

### TEST 1: Admin Manual Payment Recording
**Location:** `/modules/payments/add.php`
**As:** Admin user

1. Click "Record Manual Payment"
2. Select a member (one with outstanding balance)
3. Observe: Outstanding balance displays next to member name
4. Select payment type (e.g., Cash Payment)
5. Select an invoice
6. Observe: Outstanding amount auto-fills in payment amount field
7. Adjust amount if needed (test validation: should error if > outstanding)
8. Set payment date to today
9. Add notes (optional)
10. Click "Record Payment"

**Expected Results:**
- ✅ Form submits without errors
- ✅ Redirects to /modules/payments/
- ✅ Success message shows payment ID
- ✅ Check database:
  - `invoice_payments` table: new row with status 'Paid'
  - `invoices` table: invoice_status updated to 'Paid' or 'Partially Paid'
  - `activity_log`: entry logged with MANUAL_PAYMENT action
- ✅ Member receives email notification about payment

**Database Verification:**
```sql
-- Check payment recorded
SELECT * FROM invoice_payments ORDER BY created_at DESC LIMIT 1;

-- Check invoice status updated
SELECT invoice_id, invoice_status, amount FROM invoices WHERE invoice_id = '[INVOICE_ID]';

-- Check activity log
SELECT * FROM activity_log WHERE action = 'MANUAL_PAYMENT' ORDER BY created_at DESC LIMIT 1;
```

---

### TEST 2: Dynamic Invoice Loading (AJAX)
**Location:** `/modules/payments/add.php`
**As:** Admin user

1. Open form
2. Select a member from dropdown
3. Observe: Invoice dropdown changes from empty to "Loading invoices..."
4. Wait 1-2 seconds

**Expected Results:**
- ✅ Invoice dropdown populates with member's pending invoices
- ✅ Each option shows: INV-ID - Description (Outstanding: Amount)
- ✅ If member has no outstanding invoices, shows "-- Select Invoice --" only
- ✅ No JavaScript console errors (F12 → Console tab)

**Troubleshooting:**
- If "Loading invoices..." stays: Check browser console for CORS/404 errors
- Verify API endpoint `/api/invoices/get-member-invoices.php?member_id=MEMBER123` returns JSON

---

### TEST 3: Amount Validation
**Location:** `/modules/payments/add.php`
**As:** Admin user

1. Select member and invoice (outstanding = ₱1000)
2. In amount field, enter ₱1500 (more than outstanding)
3. Click "Record Payment"

**Expected Results:**
- ✅ Form displays error: "Payment amount exceeds outstanding balance of ₱1000.00"
- ✅ Form does NOT submit
- ✅ Data remains in form fields (preserve for correction)

**Additional Test:**
- Try amount = 0 → Error "Amount must be greater than 0"
- Try amount = ₱500 (less than outstanding) → Should ACCEPT and record

---

### TEST 4: Member Email Notification
**Location:** Check member email after recording payment
**Trigger:** Record any manual payment via add.php

**Expected Results:**
- ✅ Member receives email with:
  - Subject: "Payment Received - Level Up Fitness"
  - Payment amount and invoice ID
  - Payment method (Cash/Check/Transfer)
  - Payment date
  - Link to log in and view payment history

**Troubleshooting:**
- Check `/config/MailtrapService.php` or SMTP configuration if email not received
- Check `/modules/payments/add.php` around line 90-110 for sendEmailNotification() call

---

### TEST 5: Integration with Webhook (Invoice Status Auto-Update)
**Location:** `/api/payments/webhook.php` (called by Maya)
**Precondition:** Invoice in "Pending" status with partial payment

1. Manually create invoice with amount 2500 and 1000 already paid via webhook
2. Verify invoice_status = 'Partially Paid'
3. Record second manual payment of 1500
4. Check database: invoice_status should now = 'Paid'

**Expected Results:**
- ✅ updateInvoiceStatus() automatically called after payment recorded
- ✅ invoice_status correctly reflects: Paid/Partially Paid/Pending
- ✅ Outstanding balance calculations match actual: amount - total_paid

**Database Check:**
```sql
SELECT 
    i.invoice_id,
    i.invoice_status,
    i.amount,
    COALESCE(SUM(ip.amount), 0) as total_paid,
    (i.amount - COALESCE(SUM(ip.amount), 0)) as outstanding
FROM invoices i
LEFT JOIN invoice_payments ip ON i.invoice_id = ip.invoice_id
WHERE i.invoice_id = '[INVOICE_ID]'
GROUP BY i.invoice_id;
```

---

### TEST 6: Payment Method Types
**Location:** `/modules/payments/add.php` Payment Type dropdown
**Test Each Type:** Cash Payment, Check Payment, Bank Transfer, Discount, Refund

1. Select each payment type
2. Record payment
3. Check database payment_method field

**Expected Results:**
- ✅ Cash Payment → payment_method = 'Cash'
- ✅ Check Payment → payment_method = 'Check'
- ✅ Bank Transfer → payment_method = 'Bank Transfer'
- ✅ Discount → payment_method = 'Discount'
- ✅ Refund → payment_method = 'Refund'

---

### TEST 7: Member Dashboard Still Works
**Location:** `/dashboard/member-dashboard.php`
**As:** Member user

1. Log in as member
2. Check dashboard loads without errors
3. Verify "Outstanding Balance" card displays:
   - Total Due
   - Already Paid
   - Total Billed
   - "Pay Now" button (if amount due > 0)

**Expected Results:**
- ✅ Dashboard displays outstanding balance correctly
- ✅ Amount matches: Total Billed - Already Paid
- ✅ Color coding: red if due, green if paid
- ✅ "Pay Now" button links to `/modules/payments/pay.php`

---

## Next: Webhook Testing with Maya

Once admin form testing passes, test webhook with actual Maya payment:

1. Member initiates payment via `/modules/payments/pay.php`
2. Member completes payment in Maya checkout
3. Maya sends webhook callback to `/api/payments/webhook.php`
4. Verify:
   - invoice_payments status updated from "Pending" to "Paid"
   - invoice_status auto-updated to "Paid"
   - Member receives confirmation email
   - Dashboard shows payment applied

---

## Quick Links
- **Member Payment:** `/modules/payments/pay.php`
- **Manual Payment:** `/modules/payments/add.php`
- **Manual Transfer Proof:** `/modules/payments/pay-manual.php`
- **Admin Dashboard:** Not yet created (Phase 3)
- **Database:** Check tables: `invoices`, `invoice_payments`, `activity_log`

## Files Syntax Status
- ✅ add.php - No syntax errors
- ✅ get-member-invoices.php - No syntax errors
- ✅ webhook.php - No syntax errors
- ✅ pay.php - No syntax errors (created earlier)
- ✅ pay-manual.php - No syntax errors (created earlier)
