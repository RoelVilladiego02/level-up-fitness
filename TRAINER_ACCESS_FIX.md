# Trainer Access Issue - RESOLVED ✅

**Date:** May 11, 2026  
**Issue:** Trainers receiving "Access denied" when trying to access `/modules/sessions/add.php`

## Root Cause Analysis

### Problem 1: userHasRole() Function Bug
**Location:** `/includes/functions.php` (Line 141)

**Original Code:**
```php
function userHasRole($role) {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === $role;
}
```

**Issue:** 
- Function only checked if `$_SESSION['user_type'] === $role` (strict equality)
- When called with array: `requireRole(['admin', 'trainer'])`
- Comparison: `'trainer' === ['admin', 'trainer']` → FALSE
- Result: Access denied for all trainers

**Solution:** Updated to handle both single roles and arrays
```php
function userHasRole($role) {
    if (!isset($_SESSION['user_type'])) {
        return false;
    }
    
    // Handle array of roles
    if (is_array($role)) {
        return in_array($_SESSION['user_type'], $role, true);
    }
    
    // Handle single role
    return $_SESSION['user_type'] === $role;
}
```

### Problem 2: Poor UX for Trainers in Session Forms
**Locations:** 
- `/modules/sessions/add.php`
- `/modules/sessions/edit.php`

**Issue:**
- Forms showed all trainers in dropdown
- Trainers could select other trainers (though backend validation prevented it)
- Confusing UX for trainers

**Solutions Implemented:**

#### 1. Modified `/modules/sessions/add.php`:
- Lines 15-35: Updated trainer dropdown loading logic
  - Trainers: Only load their own trainer info
  - Admin: Load all active trainers

- Lines 176-186: Updated form display
  - Trainers: Read-only text field showing their name
  - Admin: Dropdown with all trainers

#### 2. Modified `/modules/sessions/edit.php`:
- Lines 23-35: Updated trainer dropdown loading logic
  - Trainers: Only load their own trainer info
  - Admin: Load all active trainers

- Lines 216-230: Updated form display
  - Trainers: Read-only text field showing their name
  - Admin: Dropdown with all trainers

## Security Measures in Place

### Authorization Checks:
1. **Add Session** (`add.php`): Line 125-128
   ```php
   if ($_SESSION['user_type'] === 'trainer' && $_SESSION['user_id'] != $trainerId) {
       throw new Exception('You can only create sessions for yourself');
   }
   ```

2. **Edit Session** (`edit.php`): Line 54-57
   ```php
   if ($_SESSION['user_type'] === 'trainer' && $_SESSION['user_id'] != $session['trainer_id']) {
       setMessage('You do not have permission to edit this session', 'error');
       redirect('modules/sessions/index.php');
   }
   ```

3. **Delete Session** (`delete.php`): Line 31-34
   ```php
   if ($_SESSION['user_type'] === 'trainer' && $_SESSION['user_id'] != $session['trainer_id']) {
       setMessage('You do not have permission to delete this session', 'error');
       redirect('modules/sessions/index.php');
   }
   ```

4. **View Sessions** (`index.php`): Line 40-42
   ```php
   if ($_SESSION['user_type'] === 'trainer') {
       $query .= " AND ts.trainer_id = ?";
       $params[] = $_SESSION['user_id'];
   }
   ```

## Files Modified

1. **`/includes/functions.php`**
   - Modified: `userHasRole()` function (Line 141-154)
   - Change: Added support for array of roles

2. **`/modules/sessions/add.php`**
   - Modified: Trainer dropdown loading (Line 15-35)
   - Modified: Form trainer field display (Line 176-186)
   - Impact: Better UX for trainers

3. **`/modules/sessions/edit.php`**
   - Modified: Trainer dropdown loading (Line 23-35)
   - Modified: Form trainer field display (Line 216-230)
   - Impact: Better UX for trainers

## Testing Checklist

- [x] Trainer can access `/modules/sessions/add.php`
- [x] Trainer sees only their name (read-only)
- [x] Trainer can create sessions for themselves
- [x] Trainer cannot create sessions for other trainers (backend validation)
- [x] Trainer can only see their own sessions in list
- [x] Trainer can only edit their own sessions
- [x] Trainer can only delete their own sessions
- [x] Admin can still access all features
- [x] Admin can create sessions for any trainer
- [x] Admin can see all trainers in dropdown

## How Trainers Can Now Create Sessions

1. **Login** as trainer
2. **Navigate** to: `http://localhost/level-up-fitness/modules/sessions/add.php`
3. **See** their name in read-only trainer field
4. **Fill in** remaining details:
   - Session Name
   - Gym
   - Session Date
   - Session Time
   - Duration
   - Max Capacity
   - Description
5. **Submit** form to create session

## Impact Summary

| Item | Before | After |
|------|--------|-------|
| Trainer Access | ❌ Denied | ✅ Allowed |
| Role Checking | Single roles only | Arrays supported |
| UX for Trainers | Confusing dropdown | Clear read-only field |
| Security | ✅ Protected | ✅ Protected |

## Related Documentation

- See: `/BUG_FIXES_MAY_2026.md` for other fixes
- See: `/PRESENTATION_GUIDE.md` for system overview
- See: `/config/config.php` for role definitions

**Status:** ✅ **RESOLVED & TESTED**
