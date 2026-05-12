# Level Up Fitness - Quick Reference Guide

**System**: Gym Management Platform  
**Date**: May 11, 2026  
**Version**: 1.0

---

## 🎯 System Quick Facts

| Aspect | Details |
|--------|---------|
| **Purpose** | Multi-user gym management system |
| **User Roles** | Admin, Member, Trainer |
| **Core Tables** | 9 (users, members, trainers, gyms, workout_plans, sessions, payments, classes, attendance) |
| **Tech Stack** | PHP 7.4+, MySQL 5.7+, Bootstrap 5 |
| **Email Service** | Mailtrap/SMTP |
| **Authentication** | Session-based with bcrypt |
| **Session Timeout** | 30 minutes inactivity |

---

## 📁 Directory Structure Quick Reference

```
level-up-fitness/
├── auth/                    # Login, logout, verification
├── dashboard/               # Role-based dashboards
├── modules/                 # Feature modules
│   ├── members/            # Member management
│   ├── trainers/           # Trainer management
│   ├── payments/           # Payment tracking
│   ├── sessions/           # Session scheduling
│   ├── classes/            # Class management
│   ├── attendance/         # Check-in/check-out
│   ├── gyms/              # Gym information
│   └── reservations/       # Reservations
├── api/                     # API endpoints
├── config/                  # Configuration files
├── includes/                # Helper functions
├── email-templates/         # Email templates
├── assets/                  # CSS, JS, images
├── sql/                     # Database schema
└── docs/                    # Documentation
```

---

## 🔐 Authentication Flow (TL;DR)

1. **User enters email/password** → Form submitted
2. **Sanitize input** → Prevent injection
3. **Query database** → Find user by email
4. **Verify password** → bcrypt comparison
5. **Check verification status** → Must be verified
6. **Create session** → Set $_SESSION variables
7. **Log activity** → Record login
8. **Redirect** → To appropriate dashboard

**Key Functions**:
- `sanitize()` - Input sanitization
- `verifyPassword()` - Password validation
- `isLoggedIn()` - Session check
- `logAction()` - Activity logging

---

## 📧 Email System Flow (TL;DR)

1. **Event triggered** (registration, payment, etc.)
2. **Build email content** → HTML template
3. **Call email service** → MailtrapService::send()
4. **API request** → Send to Mailtrap
5. **Confirmation** → Message ID returned
6. **Log sending** → Record in activity
7. **Send notification** → Create DB record
8. **User receives** → Email delivered

**Key Classes**:
- `MailtrapService` - Email sending via Mailtrap
- `SMTPMailService` - Email sending via SMTP
- Email notification functions in `email-notifications.php`

---

## 💾 Database Schema Quick Reference

### Core Tables

#### `users`
- Primary authentication table
- Fields: user_id, email, password, user_type, created_at, last_login
- Indexes: email, user_type

#### `members`
- Member-specific information
- FK: user_id, trainer_id
- Fields: member_id, member_name, status, membership_type, join_date

#### `trainers`
- Trainer-specific information
- FK: user_id
- Fields: trainer_id, trainer_name, specialization, availability

#### `sessions`
- Training sessions
- FK: member_id, trainer_id
- Fields: session_id, session_date, session_time, session_status

#### `payments`
- Payment records
- FK: member_id, session_id
- Fields: payment_id, amount, payment_method, payment_status, payment_date

#### `classes`
- Group fitness classes
- FK: trainer_id
- Fields: class_id, class_name, schedule_day, max_capacity

#### `class_attendance`
- Class enrollment tracking
- FK: class_id, member_id
- Fields: attendance_id, enrollment_date, attendance_status

#### `attendance`
- General gym check-in/check-out
- FK: member_id
- Fields: attendance_id, check_in_time, check_out_time

---

## 🔄 Key Workflows at a Glance

### 1️⃣ User Registration
- Form → Validation → Create User → Create Member → Send Verification → Wait for Click → Activate

### 2️⃣ User Login
- Email + Password → Validate → Check Status → Create Session → Redirect Dashboard

### 3️⃣ Member Creation (by Admin)
- Form → Validate → Create User + Member → Generate Token → Send Email → Member Verifies

### 4️⃣ Payment Recording
- Form → Validate Amount → Create Payment → Create Notification → Send Email → Log Activity

### 5️⃣ Session Booking
- View Availability → Check Conflicts → Create Session → Notify Parties → Send Emails

### 6️⃣ Class Enrollment
- View Classes → Check Capacity → Enroll → Add to Attendance → Send Confirmation

### 7️⃣ Notification Polling
- AJAX Poll (every 5s) → Query Unread → Update Badge → Show Dropdown on Click

---

## 🛠️ API Endpoints Quick Reference

### Notifications API
```
POST /api/notifications.php?action=get_unread_count
POST /api/notifications.php?action=get_unread&limit=10
POST /api/notifications.php?action=mark_read&notification_id=123
POST /api/notifications.php?action=mark_all_read
POST /api/notifications.php?action=delete&notification_id=123
```

### Response Format
```json
{
  "success": true/false,
  "message": "Description",
  "data": {}  // Optional payload
}
```

---

## 🔧 Common Functions Reference

### Authentication
```php
isLoggedIn()                          // Check if user authenticated
sanitize($data)                       // Sanitize user input
verifyPassword($plain, $hashed)       // Verify bcrypt password
hashPassword($password)               // Create bcrypt hash
```

### Database
```php
generateID($prefix)                   // Generate unique ID with prefix
```

### Email
```php
MailtrapService::send($to, $subject, $html, $text, $options)
sendVerificationEmail($email, $token, $userName)
sendPaymentConfirmation($email, $member, $payment)
sendSessionNotification($email, $session, $type)
```

### Utilities
```php
logAction($user_id, $action, $module, $description)
redirect($url)
json_response($success, $message, $data)
```

---

## 🔍 Debugging Tips

### Email Not Sending?
1. Check `config/mailtrap.php` - API key correct?
2. Run `/mailtrap-setup.php` - Test credentials
3. Check error logs - PHP errors?
4. Verify email template - HTML valid?
5. Check Mailtrap dashboard - Inbox/spam?

### Database Errors?
1. Check connection string - Host, user, password?
2. Run `/check-tables.php` - Tables exist?
3. Run `/verify-notification-integration.php` - All fields present?
4. Check error logs - SQL errors?
5. Verify foreign keys - References valid?

### Login Issues?
1. Check `/check-users-schema.php` - Users table OK?
2. Verify password hashing - bcrypt correct?
3. Check session timeout - Too short?
4. Clear browser cookies - Stale session?
5. Check error logs - Auth errors?

### Session Issues?
1. Verify `session_start()` called - On every page?
2. Check `$_SESSION` variables - Set correctly?
3. Check timeout logic - Timer working?
4. Clear cookies - Browser cache?
5. Check PHP config - Sessions enabled?

---

## 📊 Database Query Examples

### Get member with trainer details
```php
$stmt = $pdo->prepare("
  SELECT m.*, t.trainer_name, u.email 
  FROM members m
  LEFT JOIN trainers t ON m.trainer_id = t.trainer_id
  JOIN users u ON m.user_id = u.user_id
  WHERE m.member_id = ?
");
$stmt->execute([$member_id]);
$member = $stmt->fetch();
```

### Get unread notifications for user
```php
$stmt = $pdo->prepare("
  SELECT * FROM notifications 
  WHERE user_id = ? AND is_read = 0 
  ORDER BY created_at DESC 
  LIMIT 10
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();
```

### Get payment summary for member
```php
$stmt = $pdo->prepare("
  SELECT 
    COUNT(*) as total_payments,
    SUM(amount) as total_amount,
    payment_status
  FROM payments 
  WHERE member_id = ?
  GROUP BY payment_status
");
$stmt->execute([$member_id]);
$summary = $stmt->fetchAll();
```

---

## 🎨 UI Components Quick Reference

### Bootstrap Classes Used
- `.container` - Main wrapper
- `.row` - Row in grid
- `.col-*` - Column sizing
- `.table` - Table styling
- `.form-control` - Form input
- `.btn` - Button styling
- `.alert` - Alert messages
- `.card` - Card container
- `.badge` - Badge labels

### JavaScript Patterns
- AJAX calls for API
- Form validation
- Dynamic DOM updates
- Event listeners
- Toast notifications

---

## ⚙️ Configuration Files Overview

### `config/config.php`
Global application settings:
- APP_NAME - Application name
- APP_URL - Base URL
- MAIL_FROM - Email from address
- Session timeout

### `config/database.php`
Database connection:
- DB_HOST - MySQL host
- DB_USER - MySQL user
- DB_PASSWORD - MySQL password
- DB_NAME - Database name
- DB_PORT - Port number

### `config/mailtrap.php`
Email service configuration:
- MAILTRAP_ENABLED - Enable/disable
- MAILTRAP_API_KEY - API key
- MAILTRAP_FROM_EMAIL - From address
- MAILTRAP_FROM_NAME - From name

---

## 🚀 Development Workflow

### Adding a New Feature
1. Create module folder: `/modules/feature_name/`
2. Create main file: `index.php`
3. Add menu item to dashboard
4. Create database migration if needed
5. Add helper functions in `includes/functions.php`
6. Add email templates in `email-templates/`
7. Add AJAX endpoint if needed in `api/`
8. Test all workflows
9. Document in `docs/`

### Typical Module Structure
```php
// /modules/feature/index.php
<?php
require_once '../../config/config.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check authentication
if (!isLoggedIn()) redirect(APP_URL . 'auth/login.php');

// Check role (optional)
if ($_SESSION['user_type'] !== 'admin') redirect(APP_URL);

// Get action
$action = $_GET['action'] ?? 'list';

switch($action) {
    case 'list':
        // Display list
        break;
    case 'create':
        // Show create form
        break;
    case 'save':
        // Save to database
        break;
    case 'edit':
        // Show edit form
        break;
    case 'update':
        // Update in database
        break;
    case 'delete':
        // Delete from database
        break;
}

// Include template
include '../../../includes/header.php';
// Page content here
include '../../../includes/footer.php';
?>
```

---

## 🔐 Security Best Practices

### Always Do
- ✅ Use `sanitize()` on all user input
- ✅ Use PDO prepared statements
- ✅ Hash passwords with bcrypt
- ✅ Validate user roles server-side
- ✅ Log all important actions
- ✅ Escape output with `htmlspecialchars()`
- ✅ Use HTTPS in production
- ✅ Set secure session cookies

### Never Do
- ❌ Concatenate user input in SQL
- ❌ Store plain text passwords
- ❌ Trust client-side validation alone
- ❌ Expose database errors to users
- ❌ Log sensitive data
- ❌ Use GET for sensitive operations
- ❌ Skip input validation
- ❌ Commit credentials to version control

---

## 📱 User Experience Flows

### Admin User Journey
1. Login → Dashboard → Select Module → Perform Action → View Result → Logout

### Member User Journey
1. Register → Verify Email → Login → Dashboard → Book Class/Session → View History

### Trainer User Journey
1. Login → Dashboard → View Members → Schedule Session → Track Progress

---

## 🎯 Key Metrics to Track

### System Health
- [ ] Page load time < 2 seconds
- [ ] Email delivery success rate > 95%
- [ ] Database query time < 500ms
- [ ] Uptime > 99%

### User Engagement
- [ ] Daily active users
- [ ] Session duration
- [ ] Feature usage
- [ ] Error rate

### Business Metrics
- [ ] New member registrations
- [ ] Payment collection rate
- [ ] Class attendance rate
- [ ] Trainer utilization

---

## 📞 Support Resources

### Quick Diagnostic Commands
```bash
# Check database connection
php /check-tables.php

# Test Mailtrap setup
php /mailtrap-setup.php

# Test email sending
php /test-smtp.php

# Verify notification system
php /verify-notification-integration.php

# Reset database
php /reset-database.php
```

### Documentation Files
- [SYSTEM_ARCHITECTURE_ANALYSIS.md](SYSTEM_ARCHITECTURE_ANALYSIS.md) - Full system design
- [DEVELOPMENT_IMPLEMENTATION_PLAN.md](DEVELOPMENT_IMPLEMENTATION_PLAN.md) - Implementation guide
- [docs/00_START_HERE.md](docs/00_START_HERE.md) - Quick start
- [docs/IMPLEMENTATION_GUIDE.md](docs/IMPLEMENTATION_GUIDE.md) - Development guide

---

## ✅ Pre-Launch Checklist

- [ ] All modules implemented
- [ ] All workflows tested
- [ ] Email system working
- [ ] Notifications working
- [ ] Database optimized
- [ ] Security audit completed
- [ ] Performance testing done
- [ ] User documentation ready
- [ ] Admin training completed
- [ ] Backup system active
- [ ] Monitoring configured
- [ ] Support team trained

---

**Last Updated**: May 11, 2026  
**Maintained By**: Development Team  
**Version**: 1.0

For detailed information, refer to the full [SYSTEM_ARCHITECTURE_ANALYSIS.md](SYSTEM_ARCHITECTURE_ANALYSIS.md)
