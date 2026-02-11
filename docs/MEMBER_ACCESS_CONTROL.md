# Member Access Control - Level Up Fitness

## Overview
This document defines what pages and features are accessible to each user role in the Level Up Fitness Gym Management System.

---

## 📋 Member Access Permissions

### ✅ Pages Members CAN Access

| Page | URL | Purpose |
|------|-----|---------|
| Dashboard | `/dashboard/` | View personal dashboard with overview |
| Workout Plans | `/modules/workouts/` | View and manage personal workout plans assigned by trainers |
| Reservations | `/modules/reservations/` | Reserve equipment for use at the gym |
| Logout | `/auth/logout.php` | Logout from the system |

### ❌ Pages Members CANNOT Access

| Page | URL | Reason |
|------|-----|--------|
| Members Management | `/modules/members/` | Admin only - User management |
| Trainers Management | `/modules/trainers/` | Admin only - Staff management |
| Gym Information | `/modules/gyms/` | Admin only - Facility management |
| Sessions | `/modules/sessions/` | Trainer/Admin only - Trainer conducts sessions |
| Attendance | `/modules/attendance/` | Trainer/Admin only - Attendance tracking |
| Classes | `/modules/classes/` | Not restricted in sidebar but admin/trainer specific |
| Payments | `/modules/payments/` | Admin only - Financial management |
| Reports (Members) | `/modules/reports/members.php` | Admin only - Business analytics |
| Reports (Revenue) | `/modules/reports/revenue.php` | Admin only - Financial analytics |

---

## 🛡️ Security Implementation

### Backend Protection
Each page includes access control checks:

```php
requireLogin();  // Ensure user is logged in
requireRole('member');  // Ensure user has correct role (if needed)
```

### Frontend Protection
The sidebar (include/sidebar.php) conditionally displays navigation items based on user role:

```php
<?php if ($userRole === 'admin' || $userRole === 'member'): ?>
    <!-- Member operations section -->
<?php endif; ?>
```

---

## 📊 Access Matrix by Role

```
Feature                 | Admin | Trainer | Member
-----------------------+-------+---------+--------
Dashboard              |  ✅   |   ✅    |  ✅
Workout Plans          |  ✅   |   ❌    |  ✅
Reservations          |  ✅   |   ❌    |  ✅
Sessions              |  ✅   |   ✅    |  ❌
Attendance            |  ✅   |   ✅    |  ❌
Classes               |  ✅   |   ✅    |  ❌
Members Management    |  ✅   |   ❌    |  ❌
Trainers Management   |  ✅   |   ❌    |  ❌
Gym Information       |  ✅   |   ❌    |  ❌
Payments              |  ✅   |   ❌    |  ❌
Reports               |  ✅   |   ❌    |  ❌
```

---

## 🔐 Default Test Credentials

### Member Account
- **Email**: john@email.com
- **Password**: member123
- **Name**: John Doe
- **Member ID**: MEM001
- **Accessible Features**: Dashboard, Workouts, Reservations

### Trainer Account
- **Email**: trainer@levelupfitness.com
- **Password**: trainer123
- **Name**: Jane Smith
- **Trainer ID**: TRN001
- **Accessible Features**: Dashboard, Sessions, Attendance

### Admin Account
- **Email**: admin@levelupfitness.com
- **Password**: admin123
- **Accessible Features**: All features and admin controls

---

## 📝 What Members Can Do

### Workout Plans
- View assigned workout plans
- View plan details and exercises
- Track workout progress
- Filter and search plans

### Reservations
- Reserve gym equipment
- View available equipment and time slots
- Manage their reservations
- Cancel reservations if needed
- View reservation history

### Dashboard
- See personal profile information
- View upcoming workouts
- Check active reservations
- See membership status

---

## ⚠️ What Members CANNOT Do

- ❌ Manage other members' accounts
- ❌ Manage trainers
- ❌ Create or manage gym information
- ❌ Conduct or manage training sessions
- ❌ Mark attendance
- ❌ View or manage payments (billing handled by admin)
- ❌ Access business reports and analytics

---

## 🔧 How to Grant Additional Permissions

To allow members to access a new feature:

1. **Update the page access control**:
   ```php
   // In the page file (e.g., modules/classes/index.php)
   if ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'member') {
       die('Access denied: Only members and admins can access classes.');
   }
   ```

2. **Update the sidebar** (includes/sidebar.php):
   ```php
   <?php if ($userRole === 'admin' || $userRole === 'member'): ?>
       <li class="nav-item">
           <a class="nav-link" href="<?php echo APP_URL; ?>modules/classes/">
               <i class="fas fa-book"></i> Classes
           </a>
       </li>
   <?php endif; ?>
   ```

---

## 🧪 Testing Member Access

To test that access control is working:

1. Log in as a member (john@email.com / member123)
2. Try to access restricted URLs directly:
   - http://localhost/level-up-fitness/modules/members/ → Should be denied
   - http://localhost/level-up-fitness/modules/trainers/ → Should be denied
   - http://localhost/level-up-fitness/modules/payments/ → Should be denied
3. Verify only allowed pages appear in sidebar
4. Verify allowed pages are accessible and functional

---

Last Updated: January 24, 2026
