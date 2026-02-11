# Implementation Guide - Level Up Fitness

## Overview
This guide provides step-by-step instructions for implementing each module of the Level Up Fitness system.

---

## Module Implementation Pattern

All modules should follow this consistent pattern:

### File Structure
```
/modules/[module_name]/
├── index.php       (List/Dashboard view)
├── add.php         (Add new record form)
├── edit.php        (Edit existing record form)
├── view.php        (View single record details - optional)
├── delete.php      (Delete record handler - optional)
└── functions.php   (Module-specific functions - optional)
```

### Typical Page Structure

```php
<?php
/**
 * [Module Name] - [Page Description]
 * Level Up Fitness - Gym Management System
 */

// Include required files
require_once dirname(dirname(dirname(__FILE__))) . '/includes/header.php';

// Require login
requireLogin();

// Get user info
$userInfo = getUserInfo();

// Your implementation code here
?>

<!-- HTML Content -->

<?php require_once dirname(dirname(dirname(__FILE__))) . '/includes/footer.php'; ?>
```

---

## Phase 2: Core Modules Implementation

### 1. MEMBERS MANAGEMENT

#### 1.1 Members List (`/modules/members/index.php`)

**Key Features:**
- Display all members in a table
- Search by name, email, or phone
- Filter by status (Active, Inactive, Expired)
- Pagination (10 per page)
- Action buttons (View, Edit, Delete)
- Add new member button

**Database Query:**
```php
// With search and filters
$query = "SELECT * FROM members WHERE 1=1";
if (!empty($searchTerm)) {
    $query .= " AND (member_name LIKE ? OR email LIKE ?)";
}
if (!empty($filterStatus)) {
    $query .= " AND status = ?";
}
$query .= " ORDER BY created_at DESC LIMIT ?, ?";
```

**Template Reference:** See `TEMPLATE_LIST_MODULE.php`

---

#### 1.2 Add Member (`/modules/members/add.php`)

**Form Fields:**
- Member Name (required, text)
- Email (required, email, unique)
- Contact Number (required, text)
- Membership Type (required, dropdown: Monthly/Quarterly/Annual)
- Status (optional, dropdown: Active/Inactive)

**Processing:**
1. Validate form inputs
2. Check email uniqueness
3. Create user account in `users` table
4. Generate member ID (M prefix)
5. Insert member record in `members` table
6. Set success message and redirect

**Code Example:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $memberName = sanitize($_POST['member_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    
    // Check email exists
    $checkStmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
    $checkStmt->execute([$email]);
    if ($checkStmt->rowCount() > 0) {
        $error = "Email already exists";
    }
    
    // Create user
    $password = password_hash('default123', PASSWORD_BCRYPT);
    $userStmt = $pdo->prepare("INSERT INTO users (email, password, user_type) VALUES (?, ?, ?)");
    $userStmt->execute([$email, $password, 'member']);
    $userId = $pdo->lastInsertId();
    
    // Create member
    $memberId = generateID(MEMBER_ID_PREFIX);
    $stmt = $pdo->prepare("INSERT INTO members (member_id, user_id, member_name, ...) VALUES (?, ?, ?, ...)");
    $stmt->execute([$memberId, $userId, $memberName, ...]);
    
    setMessage("Member added successfully", "success");
    redirect(APP_URL . 'modules/members/');
}
```

---

#### 1.3 Edit Member (`/modules/members/edit.php`)

**Steps:**
1. Get member ID from URL (`$_GET['id']`)
2. Load existing member data
3. Display form with prefilled values
4. On submit, validate and update
5. Update both `users` and `members` tables as needed

---

#### 1.4 View Member (`/modules/members/view.php`)

**Displays:**
- Member profile information
- Status and membership details
- Join date and expiration
- Contact information
- Workout plans assigned
- Recent attendance records
- Payment history
- Action buttons (Edit, Delete)

---

#### 1.5 Delete Member (`/modules/members/delete.php`)

**Steps:**
1. Get member ID from URL
2. Confirm deletion (or via AJAX)
3. Delete from `members` table (cascades to related records)
4. Optionally archive instead of delete
5. Redirect to members list

---

### 2. TRAINERS MANAGEMENT

Similar structure to Members:

#### 2.1 Trainers List (`/modules/trainers/index.php`)
- Table view with all trainers
- Search by name or specialization
- Filter by status (Active/Inactive)
- Add trainer button

#### 2.2 Add Trainer (`/modules/trainers/add.php`)
- **Fields:** Name, Email, Specialization, Contact, Status
- Generate trainer ID (T prefix)
- Create user account with 'trainer' role

#### 2.3 Edit Trainer (`/modules/trainers/edit.php`)
- Update trainer information
- Modify specialization

#### 2.4 Delete Trainer (`/modules/trainers/delete.php`)
- Delete trainer record
- Handle cascading deletions

---

### 3. GYM INFORMATION

#### 3.1 Gym List (`/modules/gyms/index.php`)
- Display all gym branches
- Show contact information
- Edit and delete options

#### 3.2 Add Gym (`/modules/gyms/add.php`)
- **Fields:** Gym Branch Name, Contact Number
- Generate gym ID (G prefix)

---

## Phase 3: Extended Features Implementation

### 4. WORKOUT PLANS

#### Structure:
- **index.php** - List all plans, filter by member/trainer
- **add.php** - Create new plan for a member
- **edit.php** - Modify workout plan
- **view.php** - View plan details with exercises
- **delete.php** - Remove plan

#### Key Features:
- Link to specific member and trainer
- Weekly schedule breakdown
- Detailed exercises and sets/reps
- PDF export capability

---

### 5. SESSION SCHEDULING

#### Structure:
- **index.php** - View all sessions with calendar view
- **add.php** - Create new session
- **edit.php** - Modify session details

#### Key Features:
- Date and time selection
- Day of week calculation
- Trainer assignment
- Session description/plan

---

### 6. PAYMENT TRACKING

#### Structure:
- **index.php** - List all payments with filters
- **add.php** - Record new payment
- **view.php** - View payment receipt

#### Key Features:
- Member selection
- Amount and method selection
- Status tracking (Paid/Pending/Overdue)
- Payment receipt generation
- Monthly collection summary

---

### 7. ATTENDANCE SYSTEM

#### Structure:
- **index.php** - View attendance records
- **checkin.php** - Member check-in interface
- **checkout.php** - Member check-out interface

#### Key Features:
- Quick check-in by member ID or email
- Timestamp recording
- Daily attendance report
- Monthly attendance statistics
- Export attendance data

---

### 8. RESERVATIONS

#### Structure:
- **index.php** - View all reservations
- **book.php** - Create new reservation
- **confirm.php** - Confirm/cancel reservation
- **view.php** - View reservation details

#### Key Features:
- Member and trainer selection
- Date and time picking
- Availability checking
- Status management (Confirmed/Cancelled/Completed)
- Email notifications

---

## Common Implementation Patterns

### 1. Form Validation
```php
if (empty($email) || !isValidEmail($email)) {
    setMessage("Invalid email address", "error");
    $errors[] = "email";
}
```

### 2. Database Insert
```php
try {
    $stmt = $pdo->prepare("INSERT INTO table_name (col1, col2) VALUES (?, ?)");
    $stmt->execute([$value1, $value2]);
    setMessage("Record added successfully", "success");
} catch (Exception $e) {
    setMessage("Error: " . $e->getMessage(), "error");
}
```

### 3. Database Update
```php
$stmt = $pdo->prepare("UPDATE table_name SET col1 = ?, col2 = ? WHERE id = ?");
$stmt->execute([$value1, $value2, $id]);
```

### 4. Database Delete
```php
$stmt = $pdo->prepare("DELETE FROM table_name WHERE id = ?");
$stmt->execute([$id]);
```

### 5. Display Search Results
```php
if (empty($results)) {
    echo "<div class='alert alert-info'>No records found.</div>";
} else {
    // Display table
}
```

---

## Recommended Development Order

1. **Start with Members** (most frequently used)
   - CRUD foundation
   - Common patterns
   - Reusable template

2. **Then Trainers** (similar to Members)
   - Apply what you learned
   - Slight variations in fields

3. **Then Payments** (important for operations)
   - Financial tracking
   - Status management

4. **Then Attendance** (operational necessity)
   - Check-in/out system
   - Timestamps

5. **Then Workout Plans** (links members and trainers)
   - Relationships practice

6. **Then Sessions** (scheduling)
   - Calendar concepts

7. **Then Reservations** (advanced)
   - Complex relationships

8. **Then Gyms** (supporting)
   - Simple CRUD

---

## Testing Each Module

### Unit Testing Checklist
- [ ] Add new record - verify in database
- [ ] Edit record - verify updates
- [ ] Delete record - verify removal
- [ ] Search functionality - test with various terms
- [ ] Filter functionality - test each filter option
- [ ] Pagination - test navigation
- [ ] Form validation - test with invalid data
- [ ] Error messages - display correctly
- [ ] Success messages - display correctly
- [ ] Authorization - verify role-based access

---

## Tips for Success

1. **Start Simple**: Get basic CRUD working before adding complexity
2. **Test Frequently**: Don't wait until the end to test
3. **Use Console**: Check browser and PHP error logs
4. **Follow Patterns**: Use established patterns consistently
5. **Comment Code**: Especially complex logic
6. **Validate Input**: Always sanitize and validate
7. **Test Edge Cases**: Empty results, large datasets, etc.
8. **Check Security**: Prevent SQL injection, XSS, etc.

---

## Common Issues & Solutions

### Issue: "SQL Syntax Error"
- **Solution**: Check prepared statement placeholders match parameters

### Issue: "Data not displaying"
- **Solution**: Verify database query, check for errors in try-catch

### Issue: "Form not submitting"
- **Solution**: Check form method is POST, check action attribute

### Issue: "Updates not saving"
- **Solution**: Verify correct table/column names, check WHERE clause

### Issue: "Page keeps redirecting"
- **Solution**: Check for infinite redirect loops in logic

---

## Useful Debugging Techniques

1. **Print Database Query:**
```php
echo "SELECT * FROM members WHERE id = ?";
echo "Parameter: " . $id;
```

2. **Check Variable Values:**
```php
var_dump($_POST);  // See all form data
var_dump($results); // See query results
```

3. **Check Browser Console:**
```javascript
console.log("Debug message");
```

4. **Check PHP Errors:**
- Look in browser Network tab
- Check web server error logs
- Enable error reporting in PHP

---

## Performance Considerations

1. **Use Indexes**: Add indexes to frequently searched columns
2. **Pagination**: Don't load thousands of records at once
3. **Prepared Statements**: Use for all queries (already in place)
4. **Limit Queries**: Only select needed columns
5. **Caching**: Cache frequently accessed data

---

## Security Reminders

✅ Always use prepared statements  
✅ Always sanitize user input  
✅ Always verify user authentication  
✅ Always validate data types  
✅ Always hash passwords  
✅ Never display SQL errors to users  
✅ Never store sensitive data in plain text  
✅ Always use HTTPS in production  

---

**Good luck with implementation!**

For questions, refer to README.md or SETUP.md

Last Updated: January 20, 2026
