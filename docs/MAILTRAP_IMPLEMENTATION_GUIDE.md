# 📧 Mailtrap Email API Implementation Guide

## Level Up Fitness - Gym Management System

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Setup Instructions](#setup-instructions)
3. [Email Templates](#email-templates)
4. [Notification Triggers](#notification-triggers)
5. [API Functions](#api-functions)
6. [Configuration](#configuration)
7. [Testing](#testing)
8. [Troubleshooting](#troubleshooting)

---

## Overview

This document describes the complete Mailtrap email API integration for Level Up Fitness. The system automatically sends professional HTML-formatted emails to users for all important updates and transactions.

### Key Features

- ✅ **Professional HTML Email Templates** - Beautifully designed emails with consistent branding
- ✅ **Automatic Triggers** - Emails sent automatically on specific user actions
- ✅ **Reliable Delivery** - Mailtrap API with automatic retry logic
- ✅ **User Preferences** - Members can customize notification settings
- ✅ **Testing Dashboard** - Easy configuration and testing interface
- ✅ **Comprehensive Logging** - All email sends logged for auditing
- ✅ **Template Variables** - Dynamic content filled from database

---

## Setup Instructions

### Step 1: Create Mailtrap Account

1. Visit [https://mailtrap.io](https://mailtrap.io)
2. Sign up for a free account
3. Create a new inbox (e.g., "Level Up Fitness")
4. Note your **Inbox ID** and **API Token**

### Step 2: Get API Token

1. In Mailtrap dashboard, go to **Settings** → **API Tokens**
2. Create a new API token with "Sending" permissions
3. Copy the API token (keep it secure!)

### Step 3: Configure Environment Variables

Create a `.env` file in your project root:

```env
# Mailtrap Configuration
MAILTRAP_API_TOKEN=your_api_token_here
MAILTRAP_INBOX_ID=your_inbox_id_here

# Environment (development or production)
APP_ENV=development
```

**OR** edit `config/mailtrap.php` directly:

```php
define('MAILTRAP_API_TOKEN', 'your_token_here');
define('MAILTRAP_INBOX_ID', 'your_inbox_id');
define('MAILTRAP_SANDBOX_MODE', true); // Set to false in production
```

### Step 4: Verify Configuration

1. Access the setup page: `http://your-domain/level-up-fitness/mailtrap-setup.php`
2. Check that configuration status shows ✓ Configured
3. Run test emails to verify setup works
4. Once working, set `MAILTRAP_SANDBOX_MODE = false` for production

---

## Email Templates

All email templates are located in `/email-templates/` directory.

### Available Templates

| Template | File | Purpose | Used For |
|----------|------|---------|----------|
| Payment Confirmation | `payment-confirmation.html` | Receipt for payments | Payment notifications |
| Reservation Confirmation | `reservation-confirmation.html` | Equipment booking confirmation | Reservations |
| Member Welcome | `member-welcome.html` | Welcome new members | Registration |
| Password Reset | `password-reset.html` | Password reset link | Account recovery |
| Membership Expiring | `membership-expiring-soon.html` | Renewal reminders | Membership management |
| Trainer Assignment | `trainer-assignment.html` | New trainer assignment | Trainer assignments |
| Workout Plan | `workout-plan-created.html` | New workout plan | Training programs |
| Class Reminder | `class-reminder.html` | Upcoming class notification | Class scheduling |
| Reservation Cancelled | `reservation-cancelled.html` | Cancellation notification | Cancellations |

### Template Variables

Templates use `{{VARIABLE_NAME}}` placeholders that are automatically replaced with actual data:

```html
<!-- Example: Payment template uses these variables -->
{{MEMBER_NAME}}           - Member's full name
{{PAYMENT_ID}}           - Payment ID
{{AMOUNT}}              - Payment amount
{{PAYMENT_METHOD}}      - Payment method
{{STATUS}}              - Payment status
{{PAYMENT_DATE}}        - Date/time of payment
{{MEMBERSHIP_TYPE}}     - Type of membership
{{DASHBOARD_URL}}       - Link to dashboard
```

### Customizing Templates

To edit a template:

1. Open `/email-templates/[template-name].html`
2. Modify the HTML while keeping `{{VARIABLE}}` placeholders
3. Test with the setup page
4. Changes take effect immediately

---

## Notification Triggers

Emails are sent automatically when the following events occur:

### 1. **Payment Received** 📧

**When:** Admin records a payment  
**Template:** `payment-confirmation.html`  
**Recipient:** Member's email  
**Variables Used:**
- Payment ID, Amount, Method, Status
- Membership details
- Date/time

**Code Location:** `modules/payments/add.php`

```php
sendPaymentNotification($userId, $memberEmail, $paymentId, $amount, $method, $status);
```

---

### 2. **Reservation Created** ✅

**When:** Member or admin creates a reservation  
**Template:** `reservation-confirmation.html`  
**Recipient:** Member's email  
**Variables Used:**
- Reservation ID, Equipment name
- Date, Start/End time
- Duration in minutes

**Code Location:** `modules/reservations/add.php`

```php
sendReservationNotification($userId, $email, $resId, $equipment, $date, $start, $end);
```

---

### 3. **Member Registration** 👋

**When:** New member account is created  
**Template:** `member-welcome.html`  
**Recipient:** New member's email  
**Variables Used:**
- Username, Member ID
- Membership type & expiry
- Assigned trainer (if any)

**Code Location:** `modules/members/add.php` (to be added)

```php
sendMemberWelcomeEmail($email, $memberName, [
    'username' => $username,
    'member_id' => $memberId,
    'membership_type' => $type,
    'membership_expiry' => $expiry,
    'trainer_name' => $trainerName,
]);
```

---

### 4. **Password Reset** 🔐

**When:** User requests password reset  
**Template:** `password-reset.html`  
**Recipient:** User's email  
**Variables Used:**
- Reset link with token
- Token expiration time
- Request timestamp

**Code Location:** `auth/forgot-password.php` (to be added)

```php
sendPasswordResetEmail($email, $memberName, $resetToken, 24);
```

---

### 5. **Membership Expiring Soon** ⏰

**When:** Scheduled task runs (daily) for expiring memberships  
**Template:** `membership-expiring-soon.html`  
**Recipient:** Member's email  
**Variables Used:**
- Membership type & expiration date
- Days remaining
- Renewal options

**Code Location:** `cron/send-expiration-reminders.php` (to be created)

```php
sendMembershipExpiringEmail($email, $memberName, [
    'membership_type' => $type,
    'expiration_date' => $expiryDate,
    'days_remaining' => $daysLeft,
], $renewalPlans);
```

---

### 6. **Trainer Assigned** 👨‍🏫

**When:** Trainer is assigned to a member  
**Template:** `trainer-assignment.html`  
**Recipient:** Member's email  
**Variables Used:**
- Trainer name, specialization
- Contact information
- First session details (if scheduled)

**Code Location:** `modules/trainers/assign.php` (to be added)

```php
sendTrainerAssignmentEmail($email, $memberName, [
    'trainer_name' => $trainerName,
    'trainer_email' => $trainerEmail,
    'trainer_phone' => $trainerPhone,
    'trainer_specialization' => $specialization,
    'session_date' => $sessionDate,
]);
```

---

### 7. **Workout Plan Created** 📋

**When:** Trainer creates a new workout plan for member  
**Template:** `workout-plan-created.html`  
**Recipient:** Member's email  
**Variables Used:**
- Plan name & description
- Duration & difficulty level
- Focus area
- Sessions per week

**Code Location:** `modules/workouts/add.php` (to be added)

```php
sendWorkoutPlanEmail($email, $memberName, [
    'plan_name' => $planName,
    'trainer_name' => $trainerName,
    'duration_weeks' => $weeks,
    'focus_area' => $focusArea,
    'difficulty_level' => $level,
    'sessions_per_week' => $sessions,
    'description' => $description,
    'plan_id' => $planId,
]);
```

---

### 8. **Class Reminder** 🎯

**When:** Scheduled task runs (24 hours before class)  
**Template:** `class-reminder.html`  
**Recipient:** Registered member's email  
**Variables Used:**
- Class name & trainer
- Date & time
- Location, capacity info
- Cancellation link

**Code Location:** `cron/send-class-reminders.php` (to be created)

```php
sendClassReminderEmail($email, $memberName, [
    'class_name' => $className,
    'trainer_name' => $trainerName,
    'class_date' => $classDate,
    'start_time' => $startTime,
    'end_time' => $endTime,
    'class_location' => $location,
    'current_participants' => $participants,
    'max_capacity' => $capacity,
    'class_id' => $classId,
]);
```

---

### 9. **Reservation Cancelled** ❌

**When:** Member or admin cancels a reservation  
**Template:** `reservation-cancelled.html`  
**Recipient:** Member's email  
**Variables Used:**
- Reservation ID, Equipment name
- Original date & time
- Cancellation reason
- Refund details (if applicable)

**Code Location:** `modules/reservations/cancel.php` (to be added)

```php
sendReservationCancellationEmail($email, $memberName, [
    'reservation_id' => $resId,
    'equipment_name' => $equipment,
    'reservation_date' => $date,
    'start_time' => $startTime,
    'end_time' => $endTime,
    'reason' => $cancellationReason,
    'cancellation_date' => date('M d, Y H:i'),
    'refund_amount' => $refundAmount,
    'refund_days' => 3,
]);
```

---

## API Functions

### Core Email Functions

Located in `/includes/email-notifications.php`

#### 1. `renderEmailTemplate($templateName, $variables)`

Renders an email template with variables.

```php
$html = renderEmailTemplate('payment-confirmation', [
    'member_name' => 'John Doe',
    'payment_id' => 'PAY-123456',
    'amount' => '5000.00',
    'payment_method' => 'Credit Card',
    'status' => 'Success',
]);
```

#### 2. `sendPaymentConfirmationEmail($memberEmail, $memberName, $paymentData)`

Sends payment confirmation email.

```php
sendPaymentConfirmationEmail('member@email.com', 'John Doe', [
    'payment_id' => 'PAY-123456',
    'amount' => 5000,
    'payment_method' => 'Card',
    'status' => 'Paid',
    'payment_date' => 'May 01, 2026 02:30 PM',
]);
```

**Returns:** `['success' => bool, 'message' => string, 'message_id' => string]`

---

#### 3. `sendReservationConfirmationEmail($memberEmail, $memberName, $reservationData)`

Sends reservation confirmation email.

```php
sendReservationConfirmationEmail('member@email.com', 'John Doe', [
    'reservation_id' => 'RES-123456',
    'equipment_name' => 'Treadmill 01',
    'reservation_date' => 'May 05, 2026',
    'start_time' => '09:00 AM',
    'end_time' => '10:00 AM',
    'duration' => 60,
    'trainer_name' => 'Coach Mike',
]);
```

---

#### 4. `sendMemberWelcomeEmail($memberEmail, $memberName, $memberData)`

Sends welcome email to new members.

```php
sendMemberWelcomeEmail('member@email.com', 'John Doe', [
    'username' => 'johndoe',
    'member_id' => 'MEM-123456',
    'membership_type' => 'Premium',
    'membership_expiry' => 'May 01, 2027',
    'trainer_name' => 'Coach Mike',
    'trainer_email' => 'coach@gym.com',
]);
```

---

#### 5. `sendPasswordResetEmail($memberEmail, $memberName, $resetToken, $expirationHours)`

Sends password reset email with reset link.

```php
sendPasswordResetEmail('member@email.com', 'John Doe', 'token_abc123xyz', 24);
```

---

#### 6. `sendMembershipExpiringEmail($memberEmail, $memberName, $membershipData, $renewalPlans)`

Sends membership expiration reminder.

```php
sendMembershipExpiringEmail('member@email.com', 'John Doe', [
    'membership_type' => 'Premium',
    'expiration_date' => 'May 15, 2026',
], [
    ['name' => 'Monthly', 'price' => '999'],
    ['name' => 'Quarterly', 'price' => '2799'],
]);
```

---

#### 7. `sendTrainerAssignmentEmail($memberEmail, $memberName, $trainerData)`

Sends trainer assignment notification.

```php
sendTrainerAssignmentEmail('member@email.com', 'John Doe', [
    'trainer_name' => 'Coach Mike',
    'trainer_email' => 'coach@gym.com',
    'trainer_phone' => '09123456789',
    'trainer_specialization' => 'Strength Training',
    'trainer_bio' => 'Certified personal trainer...',
]);
```

---

#### 8. `sendWorkoutPlanEmail($memberEmail, $memberName, $planData)`

Sends workout plan notification.

```php
sendWorkoutPlanEmail('member@email.com', 'John Doe', [
    'plan_name' => 'Beginner Strength Building',
    'trainer_name' => 'Coach Mike',
    'trainer_email' => 'coach@gym.com',
    'duration_weeks' => 12,
    'focus_area' => 'Strength',
    'difficulty_level' => 'Beginner',
    'sessions_per_week' => 3,
    'description' => 'A comprehensive 12-week program...',
    'plan_id' => 'PLN-123456',
]);
```

---

#### 9. `sendClassReminderEmail($memberEmail, $memberName, $classData)`

Sends class reminder email.

```php
sendClassReminderEmail('member@email.com', 'John Doe', [
    'class_name' => 'HIIT Training',
    'trainer_name' => 'Coach Sarah',
    'class_date' => 'May 08, 2026',
    'start_time' => '06:00 PM',
    'end_time' => '07:00 PM',
    'class_location' => 'Studio A',
    'current_participants' => 15,
    'max_capacity' => 20,
]);
```

---

#### 10. `sendReservationCancellationEmail($memberEmail, $memberName, $cancellationData)`

Sends reservation cancellation email.

```php
sendReservationCancellationEmail('member@email.com', 'John Doe', [
    'reservation_id' => 'RES-123456',
    'equipment_name' => 'Treadmill 01',
    'reservation_date' => 'May 05, 2026',
    'start_time' => '09:00 AM',
    'end_time' => '10:00 AM',
    'reason' => 'Member requested cancellation',
    'cancellation_date' => 'May 01, 2026 02:30 PM',
    'refund_amount' => 500,
    'refund_days' => 3,
]);
```

---

#### 11. `sendCustomEmail($recipient, $subject, $htmlBody, $textBody, $options)`

Send a custom email not covered by templates.

```php
sendCustomEmail(
    'member@email.com',
    'Custom Announcement',
    '<p>Important announcement...</p>',
    'Important announcement text version',
    [
        'cc' => ['admin@gym.com'],
        'bcc' => ['audit@gym.com'],
    ]
);
```

---

### Mailtrap Service Class

Located in `/config/MailtrapService.php`

#### `MailtrapService::send($to, $subject, $htmlBody, $textBody, $options)`

Direct Mailtrap API call for advanced usage.

```php
use MailtrapService;

$result = MailtrapService::send(
    'user@email.com',
    'Test Subject',
    '<p>HTML content</p>',
    'Plain text content'
);

if ($result['success']) {
    echo "Message ID: " . $result['message_id'];
} else {
    echo "Error: " . $result['message'];
}
```

---

#### `MailtrapService::sendBulk($emails)`

Send multiple emails efficiently.

```php
$emails = [
    [
        'to' => 'user1@email.com',
        'subject' => 'Payment Confirmation',
        'html' => '<p>Email 1 content</p>',
    ],
    [
        'to' => 'user2@email.com',
        'subject' => 'Class Reminder',
        'html' => '<p>Email 2 content</p>',
    ],
];

$results = MailtrapService::sendBulk($emails);
```

---

#### `MailtrapService::test($testEmail)`

Test Mailtrap configuration.

```php
$result = MailtrapService::test('test@mailinator.com');
```

---

## Configuration

### Configuration Files

**Main Configuration:** `config/mailtrap.php`

```php
// API Credentials
define('MAILTRAP_API_TOKEN', 'your_token');
define('MAILTRAP_INBOX_ID', 'your_inbox_id');
define('MAILTRAP_API_BASE_URL', 'https://send.api.mailtrap.io');

// Email Addresses
define('MAILTRAP_FROM_EMAIL', 'noreply@levelupfitness.local');
define('MAILTRAP_FROM_NAME', 'Level Up Fitness');
define('MAILTRAP_REPLY_TO_EMAIL', 'support@levelupfitness.local');

// Features
define('MAILTRAP_ENABLED', true);
define('MAILTRAP_SANDBOX_MODE', false); // true for testing, false for production

// Email Templates Directory
define('EMAIL_TEMPLATE_DIR', dirname(__FILE__) . '/../email-templates/');

// Retry Settings
define('MAILTRAP_RETRY_COUNT', 3);
define('MAILTRAP_RETRY_DELAY', 5); // seconds
```

### Environment Variables

Create `.env` file:

```
MAILTRAP_API_TOKEN=your_actual_token
MAILTRAP_INBOX_ID=your_actual_inbox_id
APP_ENV=production
```

### Changing Email From Address

Edit `config/mailtrap.php`:

```php
define('MAILTRAP_FROM_EMAIL', 'your-email@yourdomain.com');
define('MAILTRAP_FROM_NAME', 'Your Gym Name');
```

### Sandbox vs Production Mode

**Sandbox Mode (Testing):**
```php
define('MAILTRAP_SANDBOX_MODE', true);
```
- All emails captured in Mailtrap inbox
- No emails sent to actual recipients
- Ideal for development

**Production Mode:**
```php
define('MAILTRAP_SANDBOX_MODE', false);
```
- Emails sent to actual recipients
- Only use with proper credentials
- Monitor delivery status

---

## Testing

### Using the Setup Dashboard

1. Go to: `http://your-domain/level-up-fitness/mailtrap-setup.php`
2. Check configuration status
3. Choose test type:
   - **Basic Test** - Simple connectivity test
   - **Payment Test** - Payment confirmation sample
   - **Reservation Test** - Reservation confirmation sample
   - **Welcome Test** - New member welcome sample
4. Enter test email
5. Click "Send Test"
6. Check inbox for email

### Manual Testing in Code

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
    echo "Email sent successfully!";
} else {
    echo "Error: " . $result['message'];
}
?>
```

### Checking Mailtrap Inbox

1. Log into Mailtrap.io
2. Select your inbox
3. View all test emails
4. Check HTML rendering
5. Verify email headers

---

## Troubleshooting

### Email Not Sending

**Check 1:** Verify credentials
```
http://your-domain/mailtrap-setup.php
```
Look for ✓ Configured status

**Check 2:** Review error logs
```
tail -f /var/log/php-errors.log
```

**Check 3:** Test connectivity
```php
require_once 'config/MailtrapService.php';
$result = MailtrapService::test('test@mailinator.com');
var_dump($result);
```

### "API Token Not Configured"

**Solution:**
1. Set environment variables in `.env` file, OR
2. Edit `config/mailtrap.php` directly
3. Verify in setup dashboard

### Emails Going to Spam

**Solutions:**
1. Add SPF records to your domain
2. Configure DKIM
3. Use branded domain in From email
4. Check Mailtrap documentation for sending domain setup

### Template Variables Not Replacing

**Check:**
- Variable name matches exactly (case-sensitive)
- Wrapped in `{{UPPERCASE}}`
- No spaces inside braces: `{{ VARIABLE }}` is wrong

**Example Fix:**
```php
// Wrong
$html = renderEmailTemplate('payment', $data);

// Right
$html = renderEmailTemplate('payment-confirmation', [
    'member_name' => 'John',
    'payment_id' => 'PAY-123',
]);
```

### Retry Logic Logging

All retries are logged. Check PHP error log:

```
Email send failed (attempt 1/3), retrying...
Email send failed (attempt 2/3), retrying...
Email sent successfully to: user@email.com | Message ID: xxx-xxx-xxx
```

---

## Best Practices

### 1. **Always Provide Fallbacks**

```php
$memberData['member_name'] = $memberData['member_name'] ?? 'Valued Member';
```

### 2. **Log All Sends**

The system automatically logs all sends. Check error_log():

```php
error_log('Email sent to: ' . $email);
```

### 3. **Batch Operations**

For bulk emails, use `sendBulk()`:

```php
$emails = [/* ... */];
$results = MailtrapService::sendBulk($emails);
```

### 4. **Handle Failures Gracefully**

```php
$result = sendPaymentConfirmationEmail($email, $name, $data);
if (!$result['success']) {
    // Log failure, notify admin
    error_log('Email failed: ' . $result['message']);
    // Continue processing - don't break the transaction
}
```

### 5. **Test Templates**

Before going to production, test all templates in sandbox mode.

### 6. **Monitor Delivery**

Regularly check Mailtrap inbox for delivery issues or bounces.

---

## Integration Checklist

- [ ] Mailtrap account created
- [ ] API token obtained
- [ ] Environment variables configured
- [ ] Setup dashboard accessible
- [ ] Test emails sending successfully
- [ ] Payment emails triggering on payment add
- [ ] Reservation emails triggering on booking
- [ ] All templates customized with your branding
- [ ] Production credentials configured
- [ ] Sandbox mode disabled for production
- [ ] Monitoring/logging reviewed
- [ ] Failure notifications set up
- [ ] User preference settings implemented
- [ ] Documentation reviewed with team

---

## Support & Documentation

- **Mailtrap Docs:** https://mailtrap.io/api/
- **Setup Dashboard:** `mailtrap-setup.php`
- **Email Templates:** `/email-templates/`
- **Functions:** `/includes/email-notifications.php`
- **Service Class:** `/config/MailtrapService.php`

---

## Version History

**v1.0.0** (May 2026)
- Initial Mailtrap integration
- 9 email templates
- All major notification triggers
- Setup dashboard
- Comprehensive documentation
