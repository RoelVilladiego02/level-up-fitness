# Member Access Quick Reference

## 📱 What Members See in Their Sidebar

```
MAIN MENU
├── Dashboard

MEMBER OPERATIONS
├── Workout Plans
└── Reservations

SETTINGS
└── Logout
```

---

## ✅ Member-Accessible Pages Summary

### Pages Members CAN Access and What They Do:

| Feature | URL | Function |
|---------|-----|----------|
| **Dashboard** | `/dashboard/` | Personal overview, stats, upcoming activities |
| **Workout Plans** | `/modules/workouts/` | View assigned plans, filter, search |
| **Reservations** | `/modules/reservations/` | Reserve equipment, manage bookings |
| **Logout** | `/auth/logout.php` | End session |

### Pages Members CANNOT Access:

Members trying to access these pages will see: **"Access denied"**

- ❌ `/modules/members/` - Member management (Admin only)
- ❌ `/modules/trainers/` - Trainer management (Admin only)
- ❌ `/modules/gyms/` - Gym information (Admin only)
- ❌ `/modules/sessions/` - Training sessions (Trainer/Admin only)
- ❌ `/modules/attendance/` - Attendance tracking (Trainer/Admin only)
- ❌ `/modules/classes/` - Class management (Admin only)
- ❌ `/modules/payments/` - Payments (Admin only)
- ❌ `/modules/reports/` - Business reports (Admin only)

---

## 🔐 Test it Yourself

### Login as Member
- Email: `john@email.com`
- Password: `member123`

### Then try to access:
- ✅ Works: `http://localhost/level-up-fitness/modules/workouts/`
- ✅ Works: `http://localhost/level-up-fitness/modules/reservations/`
- ❌ Denied: `http://localhost/level-up-fitness/modules/members/`
- ❌ Denied: `http://localhost/level-up-fitness/modules/payments/`

---

## 🛡️ How It Works

1. **Sidebar Control**: Only links to allowed pages show up in the sidebar
2. **Backend Protection**: Even if you type a URL directly, the page checks your role and denies access
3. **Session Based**: Access is verified through the user's session `$_SESSION['user_type']`

---

Last Updated: January 24, 2026
