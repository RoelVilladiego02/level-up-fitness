# Workout Plan Access Control Update

## Problem Identified
The original design only allowed **members and admins** to create workout plans, which doesn't match real-world gym operations. Trainers should be able to create personalized workout plans for their assigned members.

---

## Solution Implemented

### 1. **Updated Access Control**

#### Viewing Workout Plans
- **Before**: Members and Admins only
- **After**: Members, Trainers, AND Admins
- **File**: [modules/workouts/index.php](modules/workouts/index.php)

#### Creating Workout Plans
- **Before**: Any logged-in user (no restriction)
- **After**: Trainers and Admins only
- **File**: [modules/workouts/add.php](modules/workouts/add.php)

### 2. **Role-Based Form Logic**

#### For Trainers
- Trainers can only create plans for active members
- Trainer field is **auto-assigned** to themselves (disabled/read-only)
- Cannot manually select a different trainer

#### For Admins
- Admins can create plans for any member
- Can select ANY trainer from dropdown (or leave unassigned)
- Full control over plan assignments

**Implementation**: Added trainer ID detection and conditional form display

### 3. **Updated Sidebar Navigation**
- **File**: [includes/sidebar.php](includes/sidebar.php)
- Trainers now see "Workout Plans" in their TRAINER OPERATIONS section
- Allows trainers quick access to create and view workout plans

---

## Access Matrix - Workout Plans

```
Feature                  | Admin | Trainer | Member
------------------------+-------+---------+--------
View Workout Plans       |  ✅   |   ✅    |  ✅
Create Workout Plans     |  ✅   |   ✅    |  ❌
Create for Members       |  ✅   |   ✅*   |  N/A
Assign Trainer           |  ✅   |   Auto  |  N/A
Edit Plans (own)         |  ✅   |   ✅*   |  ❌
Delete Plans             |  ✅   |   ❌**  |  ❌

* Trainers can only edit their own plans
** To be implemented - only admins can delete
```

---

## How It Works

### Trainer Workflow
1. Trainer logs in
2. Sees "Workout Plans" in sidebar under TRAINER OPERATIONS
3. Clicks "Create New Plan"
4. Selects a member from the dropdown
5. Fills in plan details (name, goal, duration, details)
6. System **automatically assigns** the plan to the trainer
7. Plan is created and saved with trainer_id = their ID

### Admin Workflow
1. Admin logs in
2. Sees "Workout Plans" in sidebar (can be added to ADMIN section)
3. Clicks "Create New Plan"
4. Selects a member from dropdown
5. Can optionally select a trainer to assign (or leave unassigned)
6. Fills in plan details
7. Plan is created with optional trainer assignment

### Member Workflow
1. Member logs in
2. Sees "Workout Plans" in sidebar under MEMBER OPERATIONS
3. Can view assigned workout plans
4. Cannot create, edit, or delete plans

---

## Database Schema Consistency

The `workout_plans` table already supports this:
```sql
CREATE TABLE workout_plans (
    workout_plan_id VARCHAR(50) PRIMARY KEY,
    member_id VARCHAR(50) NOT NULL,
    trainer_id VARCHAR(50),              -- Optional trainer assignment
    plan_name VARCHAR(255) NOT NULL,
    goal VARCHAR(255) NOT NULL,
    duration_weeks INT NOT NULL,
    details LONGTEXT NOT NULL,
    ...
)
```

The `trainer_id` field is **nullable**, allowing:
- Plans created by trainers for their members
- Plans created by admins without trainer assignment
- Plans assigned to specific trainers by admins

---

## Files Modified

1. ✅ [modules/workouts/add.php](modules/workouts/add.php)
   - Added access control (trainers + admins only)
   - Added trainer ID detection
   - Added conditional form logic
   - Auto-assign trainer for trainer users

2. ✅ [modules/workouts/index.php](modules/workouts/index.php)
   - Updated view access to include trainers

3. ✅ [includes/sidebar.php](includes/sidebar.php)
   - Moved "Workout Plans" from MEMBER OPERATIONS to TRAINER OPERATIONS
   - Trainers now see it alongside Sessions and Attendance

---

## Testing Checklist

- [ ] **Trainer Login**: Can access workout plan creation
- [ ] **Trainer Create**: Trainer field is auto-assigned and disabled
- [ ] **Trainer View**: Can see all plans they created
- [ ] **Admin Login**: Can create plans with trainer selection
- [ ] **Admin Create**: Full trainer dropdown available
- [ ] **Member Login**: Cannot access create form (access denied)
- [ ] **Member View**: Can see assigned plans in their dashboard
- [ ] **Sidebar**: Trainers see "Workout Plans" in TRAINER OPERATIONS

---

## Default Test Credentials

### Trainer Account
- **Email**: trainer@levelupfitness.com
- **Password**: trainer123
- **Name**: Jane Smith
- **Accessible**: Dashboard, Workout Plans, Sessions, Attendance

### Admin Account
- **Email**: admin@levelupfitness.com
- **Password**: admin123
- **Accessible**: All features

### Member Account
- **Email**: john@email.com
- **Password**: member123
- **Name**: John Doe
- **Accessible**: Dashboard, Workout Plans (view only), Reservations

---

## Future Enhancements

1. **Edit Plans**: Allow trainers to edit their own plans
2. **Delete Plans**: Only admins can delete plans
3. **Plan History**: Track plan changes and assignments
4. **Plan Review**: Members can provide feedback on plans
5. **Progress Tracking**: Track member progress against plans

---

## Business Logic Summary

**Trainers should be able to:**
- Create personalized workout plans for their assigned members
- View and manage plans they created
- Get notifications when members complete workouts
- Update plans based on member progress

**Admins should be able to:**
- Create plans for any member
- Assign trainers to existing plans
- Manage all gym operations
- Generate reports on plan effectiveness

**Members should be able to:**
- View their assigned workout plans
- Track progress against plans
- Request plan modifications from their trainer
- Get notifications about new plans

---

Last Updated: January 24, 2026
