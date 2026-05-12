# Phase 2: Admin Payment Module - Architecture & Implementation

**Status:** ✅ COMPLETE - All files created, syntax validated, ready for testing

**Last Updated:** Phase 2 Final - All components complete

---

## Architecture Overview

### Member Payment Flow (Self-Service)
```
Member logs in
    ↓
Dashboard shows "Outstanding Balance" card
    ↓
Member clicks "Pay Now"
    ↓
/modules/payments/pay.php
    • Displays invoice list
    • Member selects invoice
    • Chooses payment method:
        ├─ Maya Online → /payment/checkout.php → Maya Payment Gateway
        └─ Manual Transfer → /modules/payments/pay-manual.php → File upload
    ↓
payment recorded in invoice_payments table (status: "Pending" for Maya, "Awaiting Verification" for manual)
    ↓
For Maya: Webhook confirms payment → updateInvoiceStatus()
For Manual: Admin verifies → marks "Paid"
    ↓
Member notified via email
    ↓
Dashboard updates with new payment status
```

### Admin Payment Recording Flow (Adjustments)
```
Admin logs in
    ↓
Admin navigates to /modules/payments/add.php
    ↓
Form Section 1 (Member & Type):
    • Select member from dropdown
    • Select payment type (Cash/Check/Transfer/Discount/Refund)
    ↓
Form Section 2 (Invoice & Amount):
    • JavaScript loads member's invoices via AJAX
    • Admin selects invoice → amount auto-fills from outstanding
    • Admin can adjust amount (validation: max = outstanding)
    ↓
Form Section 3 (Metadata):
    • Payment date (date picker)
    • Notes (optional reference)
    ↓
Submit
    ↓
Backend:
    1. Validate all fields
    2. Call recordInvoicePayment()
    3. Record to invoice_payments table (status: "Paid")
    4. Auto-update invoice_status via updateInvoiceStatus()
    5. Log to activity_log
    6. Send email to member
    7. Redirect to /modules/payments/ with success message
    ↓
Admin Dashboard shows payment applied
```

---

## Component Breakdown

### 1. Frontend - `/modules/payments/add.php` (230 lines)
**Purpose:** Admin form to record manual payments

**Key Sections:**
- Member Selection Dropdown (with outstanding balance display)
- Payment Type Selector (5 types with emojis)
- Invoice Selector (dynamically populated via AJAX)
- Amount Input (auto-fills with outstanding, validates max)
- Payment Date Picker (defaults to today)
- Notes Field (optional, for reference)
- Submit Button (Record Payment)

**JavaScript Functions:**
- `loadMemberInvoices()` - Fetches invoices when member selected
- `updateInvoiceDetails()` - Auto-fills amount, updates outstanding display
- DOM ready handler - Initializes fields on page load

**Form Validation (Client + Server):**
- Client: HTML5 required attributes
- Server: Validate member exists, invoice belongs to member, amount ≤ outstanding

**Error Display:**
- Alert box with list of validation errors
- Form preserves data for correction
- Field-level error messages under each input

### 2. API Endpoint - `/api/invoices/get-member-invoices.php` (40 lines)
**Purpose:** Dynamic invoice loading for form dropdown

**Request:**
```
GET /api/invoices/get-member-invoices.php?member_id=MEM123
```

**Authorization:** Admin only

**Response:**
```json
{
  "success": true,
  "invoices": [
    {
      "invoice_id": "INV-001",
      "description": "Monthly Membership",
      "amount": 2500.00,
      "due_date": "2025-02-28",
      "paid_amount": 500.00,
      "outstanding_amount": 2000.00
    }
  ]
}
```

**Query Logic:**
- SELECT invoices where member_id = ? AND invoice_status != 'Cancelled' AND outstanding > 0
- LEFT JOIN invoice_payments to calculate paid/outstanding
- ORDER BY due_date ASC (show overdue first)

### 3. Database Handler - `recordInvoicePayment()` in functions.php
**Location:** `/includes/functions.php` (line ~1400)

**Function Signature:**
```php
recordInvoicePayment(
    $invoiceId,           // string: INV-001
    $amount,              // float: 1500.00
    $paymentMethod,       // string: Cash|Check|Bank Transfer|Discount|Refund
    $transactionId = null,// string: Optional reference (check #, bank ref, etc)
    $paymentStatus = 'Paid' // string: Paid|Pending|Awaiting Verification
)
```

**Actions:**
1. Generates unique payment_id (PAYMENT-prefix)
2. INSERT into invoice_payments table
3. Calls updateInvoiceStatus() - auto-sets invoice_status based on total paid
4. Returns payment_id or false

**Related Functions:**
- `updateInvoiceStatus()` - Auto-updates invoice_status based on amount paid
- `getMemberOutstandingBalance()` - Calculates what member owes total
- `getInvoiceDetails()` - Retrieves full invoice with payment history

### 4. Webhook Handler - Updated in `/api/payments/webhook.php`
**Purpose:** Handle Maya payment callbacks

**New Logic (Added this phase):**
1. On webhook callback, detect if payment is for new invoice_payments system
2. Query: SELECT from invoice_payments where payment_id matches
3. If found → update invoice_payments table (status: 'Paid')
4. Call updateInvoiceStatus() - auto-updates invoice to 'Paid'
5. If not found → fallback to legacy payments table (backward compatible)

**Result:**
- New invoice system fully integrated with Maya callbacks
- Legacy payments still work for backward compatibility
- Invoice status always reflects actual payment status

### 5. Database Tables
**invoices table:**
```sql
CREATE TABLE invoices (
    invoice_id VARCHAR(50) PRIMARY KEY,
    member_id VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    description VARCHAR(255),
    invoice_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    due_date DATE,
    invoice_status ENUM('Draft','Pending','Partially Paid','Paid','Overdue','Cancelled'),
    payment_method VARCHAR(50),
    notes TEXT,
    created_by VARCHAR(50),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**invoice_payments table:**
```sql
CREATE TABLE invoice_payments (
    payment_id VARCHAR(50) PRIMARY KEY,
    invoice_id VARCHAR(50) NOT NULL,
    member_id VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50),
    payment_status ENUM('Pending','Paid','Awaiting Verification','Failed','Cancelled'),
    transaction_id VARCHAR(255),
    payment_date DATETIME,
    payment_proof_url VARCHAR(255),
    notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id),
    FOREIGN KEY (member_id) REFERENCES members(member_id)
);
```

---

## Data Flow Example

### Scenario: Admin records cash payment

**Admin Action:**
1. Opens `/modules/payments/add.php`
2. Selects member "John Doe"
3. Selects payment type "Cash Payment"
4. JavaScript loads invoices: INV-001 (Outstanding: ₱2500), INV-002 (Outstanding: ₱500)
5. Selects INV-001
6. Amount auto-fills to ₱2500
7. Admin changes to ₱1500 (partial payment)
8. Sets date to today
9. Notes: "Cash received at gym"
10. Submits

**Backend Processing:**
```php
// 1. Validate
$invoice = find invoice INV-001 where member_id = john-123 ✓
$outstanding = ₱2500 ✓
$amount ₱1500 ≤ ₱2500 ✓

// 2. Record payment
$paymentId = recordInvoicePayment(
    'INV-001',
    1500.00,
    'Cash',
    null,
    'Paid'
);
// INSERT invoice_payments (payment_id=PAYMENT-ABC123, invoice_id=INV-001, amount=1500, status=Paid)

// 3. Auto-update invoice status
updateInvoiceStatus('INV-001');
// Query: SELECT (1500) = (₱2500)? No → Partially Paid
// UPDATE invoices SET invoice_status = 'Partially Paid'

// 4. Log action
logAction('admin-1', 'MANUAL_PAYMENT', 'Payments', 'Recorded cash payment...')

// 5. Send email to member
sendEmailNotification(john@email.com, 'Payment Received', 'Your payment of ₱1500...')

// 6. Redirect
redirect to /modules/payments/ with success message
```

**Database State After:**
```
invoices:
  INV-001: amount=2500, invoice_status='Partially Paid', updated_at=NOW
  
invoice_payments:
  [Old payment]: amount=1000, status='Paid'
  [New payment]: PAYMENT-ABC123, amount=1500, status='Paid'
  
Total Paid: 1000 + 1500 = 2500
Outstanding: 2500 - 2500 = 0
Invoice Status: 'Paid' ← Updated by updateInvoiceStatus()

activity_log:
  user_id='admin-1', action='MANUAL_PAYMENT', details='Recorded cash...'
```

**Member Notification Email:**
```
To: john@email.com
Subject: Payment Received - Level Up Fitness

Hello John Doe,

We have received your payment of ₱1,500.00 for invoice INV-001.

Payment Method: Cash
Payment Date: [Today]
Notes: Cash received at gym

Your account has been updated. Please log in to view your payment history.

Thank you!

Best regards,
Level Up Fitness Management
```

---

## Key Features

### ✅ Admin-Only Form
- Requires admin role (requireRole('admin'))
- Cannot be accessed by members
- Scope: Manual payments, discounts, refunds only

### ✅ Dynamic Invoice Loading
- AJAX call to API endpoint
- Real-time outstanding calculation
- No page reload required

### ✅ Automatic Amount Calculation
- Loads outstanding balance
- Auto-fills payment amount
- Admin can adjust
- Validates against max outstanding

### ✅ Comprehensive Validation
- Member must exist and be active
- Invoice must belong to selected member
- Invoice must have outstanding balance
- Payment amount must be > 0 and ≤ outstanding
- Payment date must be valid

### ✅ Auto-Status Update
- Invoice status auto-calculated based on total paid vs amount
- States: Draft → Pending → Partially Paid → Paid ← Auto
- Also: Overdue, Cancelled (manual)
- No manual status management needed

### ✅ Audit Logging
- Every manual payment logged to activity_log
- Includes user_id, action, amount, payment method, notes
- Traceable history of all adjustments

### ✅ Email Notifications
- Member receives email immediately after payment recorded
- Includes invoice ID, amount, method, date, notes
- Link to member dashboard

### ✅ Backward Compatibility
- Webhook handles both new invoice_payments AND legacy payments
- Existing payment system still functions
- Gradual migration path

---

## Error Handling

### Client-Side (HTML5 + JavaScript)
- Required field validation
- Numeric input validation (amount)
- Date picker validation

### Server-Side (PHP)
- Member exists check
- Invoice ownership verification
- Amount validation (> 0, ≤ outstanding)
- Database transaction safety
- Error messages with clear guidance

### User Experience
- All errors displayed in alert box at top of form
- Form preserves user input for correction
- Success message with payment ID on completion
- Clear next steps (redirect to payments page)

---

## Testing Strategy

### Unit Tests
1. **Form Submission:** Record payment with valid data
2. **Validation:** Test each error condition (missing fields, amount too high, etc)
3. **AJAX Loading:** Member selection → invoice dropdown populates
4. **Status Update:** Verify invoice_status changes based on payment
5. **Email Notification:** Verify member receives payment email
6. **Activity Logging:** Check activity_log records action

### Integration Tests
1. **Admin Flow:** Complete payment recording workflow
2. **Webhook:** Maya payment updates invoice correctly
3. **Dashboard:** Member sees updated balance after payment
4. **Multiple Payments:** Partial payments accumulate correctly

See: `/docs/PHASE2-TESTING-GUIDE.md` for detailed test cases

---

## Phase 3: Pending Work

### Admin Outstanding Payments Dashboard
- Location: New page `/admin-dashboard/outstanding-payments.php`
- Purpose: Admin overview of all member balances
- Features: Summary cards, sortable table, filters, action buttons

### Overdue Payment Reminders
- Function: `sendOverduePaymentReminder()`
- Trigger: Manual or automated
- Template: Friendly reminder email with payment link

### Invoice PDF Export
- Function: `generateInvoicePDF()`
- Output: Downloadable PDF for printing
- Uses: Existing PDFGenerator class

### Recurring Invoices
- Function: `createRecurringInvoice()`
- Purpose: Auto-generate monthly/quarterly/annual invoices
- Automation: Scheduled task

---

## File Listing (Phase 2)

**New Files:**
- ✅ `/api/invoices/get-member-invoices.php` - API endpoint for invoice loading
- ✅ `/modules/payments/add.php` - Admin manual payment form (refactored)
- ✅ `/docs/PHASE2-TESTING-GUIDE.md` - Comprehensive testing guide

**Previously Created (Phase 1):**
- ✅ `/modules/payments/pay.php` - Member self-service payment portal
- ✅ `/modules/payments/pay-manual.php` - Manual transfer proof upload
- ✅ `/migrations/create-invoices-table.php` - Database migration

**Previously Updated (Phase 1):**
- ✅ `/config/config.php` - Added INVOICE_ID_PREFIX
- ✅ `/includes/functions.php` - Added 9 invoice functions
- ✅ `/dashboard/member-dashboard.php` - Added outstanding balance display
- ✅ `/api/payments/webhook.php` - Updated for invoice_payments table

**All PHP Syntax Status:** ✅ Validated - No errors detected

---

## Performance Notes

### Database Queries
- Invoice loading: Simple JOIN, indexed on member_id + invoice_status → Fast
- Payment recording: INSERT + UPDATE, no complex joins → Fast
- Outstanding calculation: Single GROUP BY query → Efficient

### Scalability
- AJAX loading prevents page reload
- No N+1 queries (proper JOINs used)
- Indexed foreign keys for referential integrity
- Ready for 1000+ members without issue

---

## Security Considerations

### Access Control
- `requireLogin()` - Ensure authenticated
- `requireRole('admin')` - Restrict to admin only
- API endpoint validates admin status

### Input Validation
- `sanitize()` - XSS prevention
- `htmlspecialchars()` - Output encoding
- Type casting: `floatval()` for amounts
- SQL prepared statements (PDO) - SQL injection prevention

### Data Integrity
- Foreign key constraints on invoice_payments
- Transaction safety (implicit with PDO)
- Audit logging for all changes
- Payment status immutable (once recorded)

---

## Summary

Phase 2 implementation is **COMPLETE** with:
- ✅ Full admin payment form with AJAX invoice loading
- ✅ API endpoint for dynamic invoice selection
- ✅ Comprehensive validation and error handling
- ✅ Automatic invoice status management
- ✅ Email notifications
- ✅ Activity logging
- ✅ Webhook integration
- ✅ All syntax validated
- ✅ Ready for testing

**Next Action:** Execute test cases from PHASE2-TESTING-GUIDE.md
