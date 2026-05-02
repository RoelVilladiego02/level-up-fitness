# 🚀 SMTP Quick Reference Guide

## Level Up Fitness - Email System

---

## ⚡ Quick Start

### Test SMTP Setup:
```bash
cd c:\xampp\htdocs\level-up-fitness
php test-smtp.php
```

### Access Admin Dashboard:
```
http://localhost/level-up-fitness/smtp-setup.php
```

---

## 📧 Common Email Tasks

### Send Welcome Email
```php
require 'includes/email-notifications.php';

sendMemberWelcomeEmail('member@example.com', 'John Doe', [
    'member_id' => 'M-001',
    'username' => 'johndoe',
    'membership_type' => 'Monthly'
]);
```

### Send Payment Confirmation
```php
sendPaymentConfirmationEmail('member@example.com', 'John Doe', [
    'payment_id' => 'PAY-001',
    'amount' => 500,
    'payment_method' => 'Card',
    'status' => 'Paid',
    'membership_type' => 'Monthly'
]);
```

### Send Reservation Confirmation
```php
sendReservationConfirmationEmail('member@example.com', 'John Doe', [
    'reservation_id' => 'RES-001',
    'equipment_name' => 'Treadmill',
    'reservation_date' => '2026-05-03',
    'start_time' => '10:00',
    'end_time' => '11:00'
]);
```

### Send Password Reset
```php
sendPasswordResetEmail('member@example.com', 'John Doe', 'reset_token_here');
```

### Send Custom Email
```php
$result = SMTPMailService::send(
    'recipient@example.com',
    'Email Subject',
    '<h1>HTML Content</h1><p>Message body</p>',
    'Plain text version'
);

if ($result['success']) {
    echo "Sent! ID: " . $result['message_id'];
} else {
    echo "Error: " . $result['message'];
}
```

---

## 📋 All Available Functions

| Function | Purpose |
|----------|---------|
| `sendPaymentConfirmationEmail()` | Payment receipts |
| `sendReservationConfirmationEmail()` | Booking confirmations |
| `sendMemberWelcomeEmail()` | New member welcome |
| `sendPasswordResetEmail()` | Password recovery |
| `sendMembershipExpiringEmail()` | Expiration reminders |
| `sendTrainerAssignmentEmail()` | Trainer notifications |
| `sendWorkoutPlanEmail()` | Workout plan alerts |
| `sendClassReminderEmail()` | Class reminders |
| `sendReservationCancellationEmail()` | Cancellation notices |
| `SMTPMailService::send()` | Generic email |
| `SMTPMailService::sendBulk()` | Multiple emails |
| `SMTPMailService::sendTest()` | Test email |
| `SMTPMailService::testConnection()` | Connection test |

---

## ⚙️ Configuration Files

### `config/smtp.php`
Main SMTP configuration. Update these constants:
```php
define('SMTP_HOST', 'sandbox.smtp.mailtrap.io');
define('SMTP_PORT', 2525);
define('SMTP_USERNAME', '291e1c42b01af7');
define('SMTP_PASSWORD', '31a6dcc7c10c44');
```

### `config/SMTPMailService.php`
Main email service class. Use `SMTPMailService::*` methods.

### `includes/email-notifications.php`
Helper functions for common email types.

---

## 🧪 Testing

### Test Script:
```bash
php test-smtp.php
```

### Test Connection Only:
```php
require 'config/SMTPMailService.php';
$result = SMTPMailService::testConnection();
print_r($result);
```

### Test With Actual Email:
```php
require 'config/SMTPMailService.php';
$result = SMTPMailService::sendTest('your@email.com');
print_r($result);
```

---

## 🎯 Response Format

All methods return:
```php
[
    'success' => true|false,      // Was email sent?
    'message' => 'Status message', // Details
    'message_id' => 'ID string'    // Email ID if sent
]
```

Example:
```php
[
    'success' => true,
    'message' => 'Email sent successfully',
    'message_id' => '<MessageID12345@server>'
]
```

---

## 🔧 Advanced: Custom Options

```php
SMTPMailService::send(
    $to,
    $subject,
    $html,
    $textBody,
    [
        'cc' => 'cc@example.com',
        'bcc' => 'bcc@example.com',
        'attachments' => [
            '/path/to/file1.pdf',
            '/path/to/file2.jpg'
        ],
        'headers' => [
            'X-Priority' => '1',
            'X-Custom' => 'Value'
        ],
        'reply_to' => 'reply@example.com'
    ]
);
```

---

## 📧 Email Template Variables

Templates use `{{VARIABLE}}` syntax. Common variables:

**Member Data:**
- `{{MEMBER_NAME}}` - Full name
- `{{MEMBER_ID}}` - Member ID
- `{{EMAIL}}` - Email address
- `{{USERNAME}}` - Login username

**Payment Data:**
- `{{AMOUNT}}` - Payment amount
- `{{PAYMENT_ID}}` - Transaction ID
- `{{PAYMENT_METHOD}}` - How it was paid
- `{{STATUS}}` - Payment status

**Membership Data:**
- `{{MEMBERSHIP_TYPE}}` - Plan type
- `{{MEMBERSHIP_START}}` - Start date
- `{{MEMBERSHIP_END}}` - Expiration
- `{{DAYS_REMAINING}}` - Days until expiry

**Reservation/Class Data:**
- `{{EQUIPMENT_NAME}}` - Equipment name
- `{{RESERVATION_DATE}}` - Date
- `{{START_TIME}}` - Start time
- `{{END_TIME}}` - End time
- `{{TRAINER_NAME}}` - Trainer name

**System URLs:**
- `{{DASHBOARD_URL}}` - Member dashboard
- `{{SUPPORT_URL}}` - Support page
- `{{WEBSITE_URL}}` - Main site

---

## 📁 File Locations

```
config/
  smtp.php ..................... SMTP configuration
  SMTPMailService.php .......... Email service class

includes/
  email-notifications.php ...... Helper functions

smtp-setup.php ................ Admin testing dashboard
test-smtp.php ................. Quick test script

email-templates/
  payment-confirmation.html .... Payment emails
  reservation-confirmation.html  Booking emails
  member-welcome.html ......... Welcome emails
  password-reset.html ......... Reset emails
  (+ 5 more templates)
```

---

## 🐛 Debug Mode

Enable debug output:
```php
// In config/smtp.php
define('MAIL_DEBUG', true);
```

Then check logs in PHP error log for SMTP debug output.

---

## 🎓 Examples by Use Case

### 1. User Registration
```php
sendMemberWelcomeEmail($email, $name, [
    'member_id' => $memberId,
    'username' => $username,
    'membership_type' => 'Monthly'
]);
```

### 2. Payment Received
```php
sendPaymentConfirmationEmail($email, $name, [
    'payment_id' => $paymentId,
    'amount' => $amount,
    'payment_method' => 'Card',
    'status' => 'Paid',
    'membership_type' => 'Monthly'
]);
```

### 3. Forgot Password
```php
$token = bin2hex(random_bytes(32));
sendPasswordResetEmail($email, $name, $token, 24);
```

### 4. Trainer Assignment
```php
sendTrainerAssignmentEmail($email, $name, [
    'trainer_name' => 'John Smith',
    'trainer_email' => 'john@gym.com',
    'trainer_specialization' => 'Strength Training'
]);
```

### 5. Class Reminder (24 hours before)
```php
sendClassReminderEmail($email, $name, [
    'class_name' => 'Yoga',
    'trainer_name' => 'Jane Doe',
    'class_date' => '2026-05-03',
    'start_time' => '10:00',
    'class_location' => 'Studio A'
]);
```

---

## 🚨 Error Handling

```php
require 'config/SMTPMailService.php';

try {
    $result = SMTPMailService::send(
        'user@example.com',
        'Subject',
        '<h1>Content</h1>'
    );
    
    if ($result['success']) {
        error_log("Email sent: " . $result['message_id']);
    } else {
        error_log("Email failed: " . $result['message']);
        // Handle error
    }
} catch (Exception $e) {
    error_log("Email exception: " . $e->getMessage());
}
```

---

## 📞 Support

Check these when troubleshooting:
1. Run `php test-smtp.php`
2. Visit `http://localhost/level-up-fitness/smtp-setup.php`
3. Check PHP error log
4. Verify credentials at https://mailtrap.io
5. Check Mailtrap inbox for sent emails

---

**Reference Guide v1.0 - May 2, 2026**
