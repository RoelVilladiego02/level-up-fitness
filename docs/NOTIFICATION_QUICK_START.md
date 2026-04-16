# 🔔 Notification System - Quick Start Guide

**Level Up Fitness - Gym Management System**

---

## ⚡ 30-Second Setup

1. **Login as Admin**
2. **Go to**: `http://your-domain/level-up-fitness/setup-notifications.php`
3. **Done!** ✅ System is ready

---

## 🎯 What Works Now

### 1. **Notification Bell in Header**
   - Shows unread notification count
   - Click to see recent notifications
   - Real-time updates every 30 seconds

### 2. **Automatic Payment Notifications**
   - When admin records a payment → Member gets email + in-app notification
   - Shows: Payment ID, Amount, Method, Status

### 3. **Automatic Reservation Notifications**
   - When member books equipment → Email + in-app notification
   - Shows: Equipment name, Date, Time, Reservation ID

### 4. **Notification Center** 
   - URL: `/modules/notifications/`
   - View all, filter by read/unread
   - Delete or mark as read
   - Full message display with timestamps

---

## 📧 Email Features

✅ Professional HTML-formatted emails  
✅ Automatic sent to member email  
✅ Includes all relevant details (ID, amount, date, time, etc.)  
✅ Company branding and footer  

---

## 🎨 User Interface

### Header Notification Bell
```
[Bell Icon] 5  ← Unread count badge
    ↓
    Recent notifications dropdown
    "View All Notifications" link
    "Mark All as Read" button
```

### Notification Center Page
```
Status: Unread ← Filter buttons
Content: 
  ├─ Icon | Title | Time | Actions
  ├─ Icon | Title | Time | Actions
  ├─ Icon | Title | Time | Actions
Pagination for older messages
```

---

## 🚀 For Developers

### Add Notifications to New Features

**Simple 3-step process:**

**Step 1**: Define convenience function
```php
function sendMyNotification($userId, $email, ...) {
    return sendNotification(
        $userId, 'my-type', 'Title', 'Message',
        ['recipient_email' => $email, ...],
        ['icon' => 'star', 'color' => 'success', ...]
    );
}
```

**Step 2**: Call from your module
```php
sendMyNotification($userId, $email, $data);
```

**Step 3**: Done! 🎉
- In-app notification created ✓
- Email sent ✓
- Tracked in database ✓

---

## 📊 Current Coverage

| Feature | Email | In-App |
|---------|-------|--------|
| **Payments** | ✅ | ✅ |
| **Reservations** | ✅ | ✅ |
| **Walkthrough** | ⏳ | ⏳ |
| **Trainer Assignment** | ⏳ | ⏳ |
| **Membership Expiry** | ⏳ | ⏳ |

✅ = Implemented  
⏳ = Easy to add (see developer section)

---

## 🧪 Quick Testing

**Test Payment Notification:**
1. Go to Payments → Add Payment
2. Select a member and amount
3. Click Save
4. Check member's notification bell (should show "1")
5. Check their email inbox

**Test Reservation Notification:**
1. Login as Member
2. Go to Reservations → New Reservation
3. Select equipment and date/time
4. Click Save
5. Check notification bell
6. Check email

---

## ❌ Troubleshooting

| Issue | Solution |
|-------|----------|
| Bell not showing | Check if logged in, refresh page |
| No unread count | Check database: run setup-notifications.php |
| Emails not sending | Check: 1) Email valid, 2) SMTP configured, 3) error logs |
| Notifications all read | Go to Notifications center → "Mark All as Read" is opposite |
| Setup fails | Check database: user has CREATE TABLE permission |

---

## 📋 File Structure

```
level-up-fitness/
├── setup-notifications.php          ← Run this once
├── api/
│   └── notifications.php            ← AJAX handler
├── modules/
│   └── notifications/
│       └── index.php                ← Notification center
├── includes/
│   ├── header.php                   ← Bell icon
│   ├── footer.php
│   └── functions.php                ← Core functions
├── assets/
│   ├── css/style.css                ← Notification styles
│   └── js/main.js                   ← Notification JS
├── sql/
│   └── add-notifications-table.sql  ← Database schema
└── docs/
    └── NOTIFICATION_SYSTEM_GUIDE.md ← Full documentation
```

---

## 🔑 Key Functions

```php
// Send notification with email
sendNotification($userId, $type, $title, $message, $emailData, $options);

// Send payment notification
sendPaymentNotification($userId, $email, $paymentId, $amount, $method, $status);

// Send reservation notification
sendReservationNotification($userId, $email, $resId, $equipment, $date, $start, $end);

// Get user notifications
$notifications = getUserNotifications($userId, $unreadOnly, $limit, $offset);

// Mark as read
markNotificationAsRead($notificationId, $userId);
markAllNotificationsAsRead($userId);

// Get counts
$count = getUnreadNotificationCount($userId);
$unread = getUnreadNotifications($userId, $limit);
```

---

## 🎓 Learning Path

1. **New User**: Just use the notification bell - it works out of the box ✅
2. **Admin**: Monitor notifications in /modules/notifications/ ✅
3. **Developer**: Read [NOTIFICATION_SYSTEM_GUIDE.md](./NOTIFICATION_SYSTEM_GUIDE.md)
4. **Developer**: Add notifications to your new features
5. **Advanced**: Extend with SMS, push notifications, templates, etc.

---

## ✅ Checklist

- [ ] Run `/setup-notifications.php` to initialize
- [ ] See notification bell in header
- [ ] Test by creating a payment
- [ ] Check notification appears
- [ ] Check email was sent
- [ ] Visit `/modules/notifications/` center
- [ ] Try filtering (All, Unread, Read)
- [ ] Read full guide for advanced features

---

## 📞 Need Help?

1. Check the [Full Guide](./NOTIFICATION_SYSTEM_GUIDE.md)
2. Review examples in:
   - `/modules/payments/add.php`
   - `/modules/reservations/add.php`
3. Check browser console for JavaScript errors
4. Check PHP error logs for backend issues

---

**Status**: ✅ Production Ready  
**Version**: 1.0  
**Last Updated**: 2026-04-16
