# ⚡ Mailtrap Implementation - Quick Start (5 Minutes)

## Level Up Fitness Gym Management System

---

## 🚀 Get Started in 5 Minutes

### Step 1: Get Your Credentials (1 min)

1. Go to **[mailtrap.io](https://mailtrap.io)** → Sign up free
2. Create a new inbox: **"Level Up Fitness"**
3. Go to **Settings** → **API Tokens**
4. Create token with **"Sending"** permission
5. Copy your **API Token** and **Inbox ID**

### Step 2: Configure System (2 min)

**Option A: Using Environment Variables (Recommended)**

Create `.env` file in project root:

```env
MAILTRAP_API_TOKEN=paste_your_token_here
MAILTRAP_INBOX_ID=paste_your_inbox_id
APP_ENV=development
```

**Option B: Direct Configuration**

Edit `config/mailtrap.php`:

```php
define('MAILTRAP_API_TOKEN', 'paste_your_token_here');
define('MAILTRAP_INBOX_ID', 'paste_your_inbox_id');
```

### Step 3: Test Configuration (2 min)

1. Open: `http://your-domain/level-up-fitness/mailtrap-setup.php`
2. Check configuration shows ✓ Configured
3. Click **"Send Basic Test"**
4. Check your email inbox
5. ✅ Done!

---

## 📧 What Works Automatically Now

### ✅ Payment Notifications
When admin records payment → Email sent to member

```php
sendPaymentNotification($userId, $email, $paymentId, $amount, $method, $status);
```

### ✅ Reservation Confirmations  
When member books equipment → Email sent immediately

```php
sendReservationNotification($userId, $email, $resId, $equipment, $date, $start, $end);
```

### ✅ Welcome Emails
When new member registers → Welcome email sent

```php
sendMemberWelcomeEmail($email, $name, $memberData);
```

### ✅ Password Reset
When user forgets password → Reset link sent

```php
sendPasswordResetEmail($email, $name, $resetToken, 24);
```

### ✅ Membership Expiring
Scheduled daily → Expiration reminders sent

```php
sendMembershipExpiringEmail($email, $name, $membershipData);
```

### ✅ Trainer Assignment
When trainer assigned → Notification email sent

```php
sendTrainerAssignmentEmail($email, $name, $trainerData);
```

### ✅ Workout Plans
When trainer creates plan → Email notification sent

```php
sendWorkoutPlanEmail($email, $name, $planData);
```

### ✅ Class Reminders
Scheduled 24 hours before → Reminder email sent

```php
sendClassReminderEmail($email, $name, $classData);
```

### ✅ Cancellations
When reservation cancelled → Cancellation email sent

```php
sendReservationCancellationEmail($email, $name, $cancellationData);
```

---

## 📁 File Structure

```
level-up-fitness/
├── config/
│   ├── mailtrap.php                 ← Configuration
│   └── MailtrapService.php          ← API Service
├── email-templates/                 ← All email designs
│   ├── payment-confirmation.html
│   ├── reservation-confirmation.html
│   ├── member-welcome.html
│   ├── password-reset.html
│   ├── membership-expiring-soon.html
│   ├── trainer-assignment.html
│   ├── workout-plan-created.html
│   ├── class-reminder.html
│   └── reservation-cancelled.html
├── includes/
│   ├── functions.php               ← Updated sendEmailNotification()
│   └── email-notifications.php     ← All helper functions
├── mailtrap-setup.php              ← Setup & Testing Dashboard
└── docs/
    └── MAILTRAP_IMPLEMENTATION_GUIDE.md  ← Full documentation
```

---

## 🧪 Testing Emails

### Dashboard Testing (Easy)

1. Go to: `http://your-domain/mailtrap-setup.php`
2. Choose test type:
   - **Basic Test** - Simple connectivity
   - **Payment Test** - Payment sample
   - **Reservation Test** - Booking sample
   - **Welcome Test** - Registration sample
3. Enter test email
4. Click Send
5. Check inbox

### Code Testing

```php
<?php
require_once 'config/config.php';
require_once 'includes/email-notifications.php';

// Test payment email
$result = sendPaymentConfirmationEmail('test@email.com', 'Test User', [
    'payment_id' => 'TEST-123456',
    'amount' => 1000,
    'payment_method' => 'Card',
    'status' => 'Success',
]);

if ($result['success']) {
    echo "✓ Email sent!";
    echo "Message ID: " . $result['message_id'];
} else {
    echo "✗ Error: " . $result['message'];
}
?>
```

---

## 🎨 Email Templates

All templates are professional HTML with consistent branding.

**Edit templates:**
- Location: `/email-templates/`
- Use `{{VARIABLE_NAME}}` for dynamic content
- Changes apply immediately

**Example template variables:**
- `{{MEMBER_NAME}}` - Member's name
- `{{PAYMENT_ID}}` - Payment ID
- `{{AMOUNT}}` - Amount
- `{{DASHBOARD_URL}}` - Link to dashboard

---

## ⚙️ Configuration Options

**File:** `config/mailtrap.php`

```php
// Enable/disable service
define('MAILTRAP_ENABLED', true);

// Testing vs Production
define('MAILTRAP_SANDBOX_MODE', true);  // true = testing, false = production

// Retry settings
define('MAILTRAP_RETRY_COUNT', 3);
define('MAILTRAP_RETRY_DELAY', 5);

// Email addresses
define('MAILTRAP_FROM_EMAIL', 'noreply@levelupfitness.local');
define('MAILTRAP_FROM_NAME', 'Level Up Fitness');
```

---

## 🚦 Production Deployment

When ready for production:

1. **Update credentials** in `.env` or `config/mailtrap.php`
2. **Set to production mode:**
   ```php
   define('MAILTRAP_SANDBOX_MODE', false);
   ```
3. **Use custom domain** (recommended):
   ```php
   define('MAILTRAP_FROM_EMAIL', 'noreply@yourdomain.com');
   ```
4. **Configure SPF/DKIM** in your domain DNS
5. **Test thoroughly** before going live

---

## 🔧 Using the API Functions

### Payment Notification

```php
sendPaymentConfirmationEmail($email, $name, [
    'payment_id' => 'PAY-123456',
    'amount' => 5000,
    'payment_method' => 'Card',
    'status' => 'Paid',
]);
```

### Reservation Notification

```php
sendReservationConfirmationEmail($email, $name, [
    'reservation_id' => 'RES-123456',
    'equipment_name' => 'Treadmill 01',
    'reservation_date' => 'May 05, 2026',
    'start_time' => '09:00 AM',
    'end_time' => '10:00 AM',
]);
```

### Welcome Email

```php
sendMemberWelcomeEmail($email, $name, [
    'username' => 'johndoe',
    'member_id' => 'MEM-123456',
    'membership_type' => 'Premium',
]);
```

### All Functions

See full documentation: `/docs/MAILTRAP_IMPLEMENTATION_GUIDE.md`

---

## ❓ Quick Help

| Issue | Solution |
|-------|----------|
| Emails not sending | Check setup dashboard for ✓ Configured status |
| Token error | Verify credentials in `.env` or `config/mailtrap.php` |
| Emails in spam | Set production mode, configure SPF/DKIM |
| Template errors | Check `{{VARIABLE_NAME}}` format (uppercase, no spaces) |
| Can't access setup page | Verify you're logged in as admin |

---

## 📞 Support

- **Setup Dashboard:** `mailtrap-setup.php`
- **Full Guide:** `/docs/MAILTRAP_IMPLEMENTATION_GUIDE.md`
- **Mailtrap Docs:** https://mailtrap.io/api/
- **Functions Reference:** `/includes/email-notifications.php`

---

## ✅ Implementation Checklist

- [ ] Mailtrap account created
- [ ] API token obtained
- [ ] Credentials added to `.env` or config
- [ ] Setup page accessible
- [ ] Test email sent successfully
- [ ] Payment emails working
- [ ] Reservation emails working
- [ ] Other templates tested
- [ ] Production mode enabled (when ready)

---

Done! 🎉 Your email system is ready to go!
