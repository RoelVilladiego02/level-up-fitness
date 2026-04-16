# 📬 Level Up Fitness - Notification System Documentation

**Version**: 1.0  
**Release Date**: Phase 3  
**Status**: ✅ Fully Implemented

---

## 📋 Overview

The notification system provides a complete solution for sending both **in-app** and **email notifications** to users based on system events. It includes a notification bell in the header, a dedicated notification center page, and automatic email confirmations.

### Key Features

✅ **Dual Notification Delivery**: Email + In-App  
✅ **Real-time Notification Bell**: Shows unread count in header  
✅ **Notification Center**: Dedicated page to manage all notifications  
✅ **Priority Levels**: Low, Normal, High, Urgent  
✅ **User Preferences**: Control notification types and delivery methods  
✅ **Activity Tracking**: Email delivery status and read/unread tracking  
✅ **Auto-expiration**: Old notifications can auto-delete  
✅ **Role-based**: Notifications for Members, Trainers, and Admins

---

## 🔧 Installation

### Step 1: Run Setup Script

1. Log in as an Admin user
2. Navigate to: `http://your-domain/level-up-fitness/setup-notifications.php`
3. The script will automatically create necessary database tables

### Step 2: Verify Installation

Database tables created:
- **notifications** - Stores all notifications
- **notification_preferences** - Stores user notification preferences

---

## 🎯 Current Implementations

### 1. Payment Notifications

When a payment is recorded, the member receives:

**Email**: Professional HTML-formatted receipt
- Payment ID
- Amount paid (formatted with currency symbol)
- Payment method
- Payment status

**In-App Notification**:
- Icon: Credit card
- Color: Success (green)
- Action URL: Link to invoice page
- Title: "Payment Confirmation"

**Location**: `modules/payments/add.php`

```php
// Usage in code
$memberStmt = $pdo->prepare("SELECT user_id, email FROM members WHERE member_id = ?");
$memberStmt->execute([$memberId]);
$memberData = $memberStmt->fetch();

sendPaymentNotification(
    $memberData['user_id'],           // User ID to notify
    $memberData['email'],             // Email address
    $paymentId,                       // Payment ID
    $amount,                          // Amount
    $paymentMethod,                   // Card, Cash, etc.
    $status                           // Paid, Pending, etc.
);
```

### 2. Reservation Notifications

When a reservation is created, the member receives:

**Email**: Detailed reservation confirmation
- Reservation ID
- Equipment name
- Date and time
- Arrival reminder

**In-App Notification**:
- Icon: Calendar check
- Color: Success (green)
- Action URL: Link to reservation details
- Title: "Reservation Confirmed"

**Location**: `modules/reservations/add.php`

```php
// Usage in code
sendReservationNotification(
    $memberData['user_id'],           // User ID
    $memberData['email'],             // Email
    $reservationId,                   // Reservation ID
    $equipmentName,                   // Equipment name
    $reservationDate,                 // Date
    $startTime,                       // Start time
    $endTime                          // End time
);
```

---

## 🛠️ API Reference

### Core Functions

#### `createNotification()`

Create an in-app notification only (no email).

```php
$notificationId = createNotification(
    $userId,              // int - User to notify
    'payment',            // string - Type: payment, reservation, account, system
    'Payment Received',   // string - Title
    'Your payment has been processed', // string - Message
    [
        'icon' => 'credit-card',      // optional - Font Awesome icon
        'color' => 'success',          // optional - Bootstrap color class
        'entity_type' => 'payment',    // optional - Type of related entity
        'entity_id' => 'PAY-001',      // optional - ID of related entity
        'action_url' => '/payments/1', // optional - URL to view
        'priority' => 'normal',        // optional - low, normal, high, urgent
        'expires_at' => date(...)      // optional - When to auto-delete
    ]
);

// Returns: notification_id (int) or false
```

#### `sendNotification()`

Send complete notification (in-app + email).

```php
$success = sendNotification(
    $userId,              // int - User to notify
    'payment',            // string - Notification type
    'Payment Confirmation', // string - Title
    'Your payment was processed', // string - Message
    [                     // array - Email data (optional)
        'recipient_email' => 'user@example.com',
        'subject' => 'Payment Confirmation',
        'body' => '<html>...</html>'
    ],
    [                     // array - Additional options
        'icon' => 'credit-card',
        'color' => 'success',
        'priority' => 'normal'
    ]
);

// Returns: true or false
```

#### `sendPaymentNotification()`

Convenience function for payment notifications.

```php
sendPaymentNotification(
    $userId,              // int
    $email,               // string
    $paymentId,           // string
    $amount,              // float
    $paymentMethod,       // string
    $status               // string - Paid, Pending, etc.
);
```

#### `sendReservationNotification()`

Convenience function for reservation notifications.

```php
sendReservationNotification(
    $userId,              // int
    $email,               // string
    $reservationId,       // int or string
    $equipmentName,       // string
    $reservationDate,     // date string
    $startTime,           // time string
    $endTime              // time string
);
```

#### `getUserNotifications()`

Retrieve user's notifications.

```php
$notifications = getUserNotifications(
    $userId,              // int
    false,                // bool - $unreadOnly (optional)
    50,                   // int - limit (optional)
    0                     // int - offset/pagination (optional)
);

// Returns: array of notification records
```

#### `getUnreadNotificationCount()`

Get count of unread notifications for a user.

```php
$unreadCount = getUnreadNotificationCount($userId);
// Returns: int
```

#### `getUnreadNotifications()`

Get recent unread notifications (for header dropdown).

```php
$unread = getUnreadNotifications(
    $userId,              // int
    10                    // int - limit (optional)
);

// Returns: array of unread notifications (most recent first)
```

#### `markNotificationAsRead()`

Mark a specific notification as read.

```php
$success = markNotificationAsRead($notificationId, $userId);
// Returns: true or false
```

#### `markAllNotificationsAsRead()`

Mark all user notifications as read.

```php
$success = markAllNotificationsAsRead($userId);
// Returns: true or false
```

#### `deleteNotification()`

Delete a specific notification.

```php
$success = deleteNotification($notificationId, $userId);
// Returns: true or false
```

#### `deleteReadNotifications()`

Delete all read notifications for a user (cleanup).

```php
$success = deleteReadNotifications($userId);
// Returns: true or false
```

---

## 🎨 UI Components

### 1. Notification Bell (Header)

Located in `includes/header.php` - Shows:
- Bell icon from Font Awesome
- Red badge with unread count (max "99+")
- Dropdown with 5 most recent unread notifications
- "View All" link to notification center
- "Mark All as Read" button

### 2. Notification Center

**URL**: `/modules/notifications/`

Features:
- All notifications with filters (All, Unread, Read)
- Individual notification cards with:
  - Icon and colored background
  - Title and message preview
  - Time ago (e.g., "5 minutes ago")
  - Action buttons (Mark as Read, View, Delete)
- Pagination (20 per page)
- Bulk actions (Mark All Read, Delete Read)

---

## 📧 Email Templates

### Payment Confirmation Email

```html
From: Level Up Fitness <noreply@levelupfitness.local>
Subject: Payment Confirmation - [PAYMENT_ID]

Body contains:
- Payment ID
- Amount (with ₱ currency symbol)
- Payment method
- Status
- Company footer with copyright
```

### Reservation Confirmation Email

```html
From: Level Up Fitness <noreply@levelupfitness.local>
Subject: Reservation Confirmed - [RESERVATION_ID]

Body contains:
- Reservation ID
- Equipment name
- Date and time
- Arrival reminder (5 mins early)
- Company footer
```

---

## 🔌 AJAX API

**Endpoint**: `/api/notifications.php`

### Mark as Read

```javascript
$.ajax({
    url: APP_URL + 'api/notifications.php',
    method: 'POST',
    data: {
        action: 'mark_read',
        notification_id: 123
    }
});
```

### Mark All as Read

```javascript
$.ajax({
    url: APP_URL + 'api/notifications.php',
    method: 'POST',
    data: {
        action: 'mark_all_read'
    }
});
```

### Delete Notification

```javascript
$.ajax({
    url: APP_URL + 'api/notifications.php',
    method: 'POST',
    data: {
        action: 'delete',
        notification_id: 123
    }
});
```

### Get Unread Count

```javascript
$.ajax({
    url: APP_URL + 'api/notifications.php',
    method: 'POST',
    data: {
        action: 'get_unread_count'
    },
    success: function(response) {
        console.log('Unread: ' + response.unread_count);
    }
});
```

### Get Unread Notifications

```javascript
$.ajax({
    url: APP_URL + 'api/notifications.php',
    method: 'POST',
    data: {
        action: 'get_unread',
        limit: 10
    },
    success: function(response) {
        console.log(response.notifications);
    }
});
```

---

## 🚀 Adding New Notification Types

### Step 1: Create a Helper Function

Add to `includes/functions.php`:

```php
function sendTrainerAssignmentNotification($userId, $email, $memberId, $trainerName) {
    $title = 'Trainer Assignment';
    $message = 'Your trainer ' . $trainerName . ' has been assigned to your account.';
    
    $emailBody = "
    <html>
        <body>
            <h2>Trainer Assignment</h2>
            <p>Your trainer <strong>$trainerName</strong> has been assigned.</p>
        </body>
    </html>
    ";
    
    return sendNotification(
        $userId,
        'trainer-assignment',  // New type
        $title,
        $message,
        [
            'recipient_email' => $email,
            'subject' => $title,
            'body' => $emailBody
        ],
        [
            'icon' => 'user-tie',
            'color' => 'info',
            'entity_type' => 'trainer',
            'entity_id' => $trainerId,
            'priority' => 'high'
        ]
    );
}
```

### Step 2: Call from Relevant Module

Example: In `modules/members/add.php`:

```php
// After assigning trainer to member
if ($trainerId) {
    $trainerStmt = $pdo->prepare("SELECT trainer_name FROM trainers WHERE trainer_id = ?");
    $trainerStmt->execute([$trainerId]);
    $trainerData = $trainerStmt->fetch();
    
    sendTrainerAssignmentNotification(
        $memberData['user_id'],
        $memberData['email'],
        $memberId,
        $trainerData['trainer_name']
    );
}
```

### Step 3: Add to User Preferences (Optional)

Update `notification_preferences` table schema if you want user-level control:

```sql
ALTER TABLE notification_preferences 
ADD COLUMN in_app_trainer_assignments TINYINT DEFAULT 1;
ALTER TABLE notification_preferences 
ADD COLUMN email_trainer_assignments TINYINT DEFAULT 1;
```

---

## 🔐 Security Considerations

1. **User Validation**: All functions verify user ownership
2. **XSS Protection**: All output is HTML-escaped
3. **SQL Injection**: All queries use prepared statements
4. **CSRF Protection**: Can extend with token verification

---

## 📊 Database Schema

### notifications table

| Column | Type | Notes |
|--------|------|-------|
| notification_id | INT PK | Auto-increment |
| user_id | INT FK | User receiving notification |
| notification_type | VARCHAR(50) | payment, reservation, account, system |
| notification_title | VARCHAR(255) | Notification title |
| notification_message | LONGTEXT | Full message |
| notification_icon | VARCHAR(50) | Font Awesome icon name |
| icon_color | VARCHAR(20) | Bootstrap color class |
| related_entity_type | VARCHAR(50) | Type of related object |
| related_entity_id | VARCHAR(50) | ID of related object |
| action_url | VARCHAR(500) | Link to view/act on notification |
| is_read | TINYINT | 0 = unread, 1 = read |
| read_at | DATETIME | When marked as read |
| email_sent | TINYINT | Whether email was sent |
| email_sent_at | DATETIME | When email was sent |
| priority | ENUM | low, normal, high, urgent |
| created_at | TIMESTAMP | Creation time |
| expires_at | DATETIME | Auto-delete time (optional) |

### notification_preferences table

| Column | Type | Notes |
|--------|------|-------|
| preference_id | INT PK | Auto-increment |
| user_id | INT FK UK | Unique per user |
| email_payments | TINYINT | Whether to email payment notifications |
| email_reservations | TINYINT | Whether to email reservation notifications |
| email_account | TINYINT | Whether to email account notifications |
| email_system | TINYINT | Whether to email system notifications |
| in_app_* | TINYINT | In-app equivalents |
| created_at | TIMESTAMP | Creation time |
| updated_at | TIMESTAMP | Last update time |

---

## 🐛 Troubleshooting

### Notifications not showing in dropdown

1. Check if `getUnreadNotifications()` is working
2. Verify JavaScript is loading (`initNotificationSystem()`)
3. Check browser console for AJAX errors
4. Ensure `APP_URL` is set correctly in JavaScript

### Emails not sending

1. Check PHP mail configuration:
   ```bash
   php -m | grep mail
   ```

2. Check error logs:
   ```bash
   tail -f /var/log/mail.log
   ```

3. Verify email configuration in `includes/functions.php`

4. Check `FROM_EMAIL` and `SUPPORT_EMAIL` constants are defined

### Notification table not created

1. Run setup script again: `/setup-notifications.php`
2. Check MySQL error logs
3. Verify database permissions
4. Run migration script manually: `/sql/add-notifications-table.sql`

---

## 📈 Performance Optimization

### Indexes Created

- `idx_user_read` - For unread count queries
- `idx_user_created` - For notification list queries
- `idx_unread_count` - Optimized for bell counter
- `idx_user_id` - General user lookups

### Cleanup Strategy

Delete old notifications periodically:

```php
// Delete notifications older than 30 days
$stmt = $pdo->prepare("
    DELETE FROM notifications 
    WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)
    AND is_read = 1
");
$stmt->execute();
```

---

## 🔄 Future Enhancements

1. **SMS Notifications** - Twilio integration for SMS alerts
2. **Push Notifications** - Browser push notifications
3. **Notification Templates** - Customizable templates per type
4. **Scheduled Notifications** - Send at specific times
5. **Digest Emails** - Daily/weekly summary emails
6. **Read Receipts** - Track when notifications are opened
7. **Notification Groups** - Group related notifications
8. **Unsubscribe Links** - Per notification type preferences

---

## 📞 Support

For issues or questions about the notification system:

1. Check this documentation
2. Review the API reference section  
3. Check troubleshooting section
4. Review implementation examples in payment/reservation modules
5. Contact system administrator

---

**End of Documentation**  
Last Updated: 2026-04-16  
Version: 1.0
