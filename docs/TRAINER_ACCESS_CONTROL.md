# Role-Based Access Control - Trainer Links

## Trainer Access Links (Updated January 24, 2026)

### ✓ ACCESSIBLE TO TRAINERS

1. **Dashboard**
   - Link: `/dashboard/`
   - Status: ✓ Trainers have dedicated trainer-dashboard.php
   - Access Level: Read-Only

2. **Sessions Management**
   - Link: `/modules/sessions/`
   - Status: ✓ Trainers AND Admins only
   - Features: View trainer's sessions, manage assigned members' sessions
   - Access Level: Full (Create, Edit, Delete own sessions)

3. **Attendance**
   - Link: `/modules/attendance/`
   - Status: ✓ Trainers AND Admins only
   - Features: Track member attendance in trainer's sessions
   - Access Level: Full (Mark attendance for assigned members)

---

### ✗ BLOCKED FROM TRAINERS

1. **Members Management**
   - Link: `/modules/members/`
   - Status: ✗ ADMIN ONLY
   - Reason: Trainers should not manage member accounts
   - Error Message: "Access denied: You do not have permission to access this page."

2. **Trainers Management**
   - Link: `/modules/trainers/`
   - Status: ✗ ADMIN ONLY
   - Reason: Trainers should not manage other trainers
   - Error Message: "Access denied: You do not have permission to access this page."

3. **Gym Information**
   - Link: `/modules/gyms/`
   - Status: ✗ ADMIN ONLY
   - Reason: Only admins manage gym branches
   - Error Message: "Access denied: You do not have permission to access this page."

4. **Payments**
   - Link: `/modules/payments/`
   - Status: ✗ ADMIN ONLY
   - Reason: Trainers should not access payment data
   - Error Message: "Access denied: You do not have permission to access this page."

5. **Workout Plans**
   - Link: `/modules/workouts/`
   - Status: ✗ MEMBERS & ADMINS only
   - Reason: Trainers can manage sessions but not member's personal workout plans
   - Error Message: "Access denied: Only members and admins can view workout plans."

6. **Reservations**
   - Link: `/modules/reservations/`
   - Status: ✗ MEMBERS & ADMINS only
   - Reason: Trainers do not handle class reservations
   - Error Message: "Access denied: Only members and admins can make reservations."

---

## Implementation Details

### Navigation (Sidebar)
- **Dynamic sidebar.php** now shows different menu items based on user role
- Trainers only see:
  - Dashboard
  - Sessions
  - Attendance
  - Logout

### Module Access Control
All modules have role-based access checks at the top:

**Admin-Only Modules:**
```php
requireRole('admin');
```

**Trainer + Admin Modules:**
```php
if ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'trainer') {
    die('Access denied: Only trainers and admins can access this page.');
}
```

**Member + Admin Modules:**
```php
if ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'member') {
    die('Access denied: Only members and admins can view this page.');
}
```

---

## Files Updated (January 24, 2026)

1. **includes/sidebar.php** - Added role-based menu rendering
2. **modules/members/index.php** - Added `requireRole('admin')`
3. **modules/trainers/index.php** - Added `requireRole('admin')`
4. **modules/gyms/index.php** - Added `requireRole('admin')`
5. **modules/payments/index.php** - Added `requireRole('admin')`
6. **modules/sessions/index.php** - Added trainer access check
7. **modules/attendance/index.php** - Added trainer access check
8. **modules/workouts/index.php** - Added member access check
9. **modules/reservations/index.php** - Added member access check

---

## Testing Trainer Access

To verify trainers have correct access:

1. Log in as a **Trainer** user
2. Check sidebar - should show only: Dashboard, Sessions, Attendance, Logout
3. Try accessing `/modules/members/` - Should get "Access denied" error
4. Try accessing `/modules/sessions/` - Should work ✓
5. Try accessing `/modules/attendance/` - Should work ✓

---

## Summary

✓ **Trainers can now:**
- View their dedicated trainer dashboard
- Manage their assigned sessions
- Track attendance for members in their sessions

✗ **Trainers cannot:**
- Access member/trainer management
- Access admin financial data (payments)
- Access gym information settings
- Manage other system administrative functions

All access is properly validated both in:
1. **Sidebar Navigation** - Only shows accessible links
2. **Module Entry** - Blocks direct URL access
