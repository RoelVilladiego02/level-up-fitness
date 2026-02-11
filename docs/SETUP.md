# SETUP GUIDE - Level Up Fitness

## Quick Start (5 minutes)

### Step 1: Import Database
```sql
-- Open phpMyAdmin or MySQL client
-- Copy and paste the entire content of: sql/database.sql
-- Click Execute
```

### Step 2: Update Database Config
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Your MySQL password
define('DB_NAME', 'level_up_fitness');
```

### Step 3: Update App URL
Edit `config/config.php`:
```php
define('APP_URL', 'http://localhost/level-up-fitness/');
```

### Step 4: Place Files
Ensure all files are in your web server's root directory:
- Windows (XAMPP): `C:\xampp\htdocs\level-up-fitness\`
- Mac (MAMP): `/Applications/MAMP/htdocs/level-up-fitness/`
- Linux: `/var/www/html/level-up-fitness/`

### Step 5: Access Application
Open browser and go to: `http://localhost/level-up-fitness/`

---

## Login Credentials

### Default Admin Account
- **Email**: admin@levelupfitness.com
- **Password**: password

**⚠️ IMPORTANT**: Change this password immediately in production!

---

## Troubleshooting

### "Database connection failed"
- Check MySQL is running
- Verify credentials in `config/database.php`
- Ensure database name matches in the SQL script

### "Session not working"
- Check PHP session settings in `php.ini`
- Verify session save path is writable
- Check browser cookies are enabled

### "Files not accessible"
- Check file permissions (755 for files, 777 for upload directories)
- Ensure web server user can read files
- Check .htaccess if using Apache

### "CSS/JS not loading"
- Verify APP_URL in `config/config.php` is correct
- Check browser console for 404 errors
- Clear browser cache (Ctrl+Shift+Delete)

---

## Next Steps

1. **Create Test Data**
   - Add sample members
   - Add sample trainers
   - Create test workout plans

2. **Customize Settings**
   - Update gym information
   - Configure payment methods
   - Set membership prices

3. **Implement Remaining Modules**
   - Review the module templates in `modules/`
   - Follow the CRUD pattern established
   - Test functionality

4. **Deploy to Production**
   - Enable HTTPS in `config/config.php`
   - Use strong database password
   - Set up regular backups
   - Configure email notifications

---

## File Structure Quick Reference

```
📁 level-up-fitness/
  📄 index.php                 ← Start here
  📄 README.md                 ← Full documentation
  📄 SETUP.md                  ← This file
  
  📁 config/
    📄 database.php            ← Database credentials (EDIT THIS)
    📄 config.php              ← App settings (EDIT THIS)
  
  📁 includes/
    📄 header.php              ← Page header
    📄 footer.php              ← Page footer
    📄 functions.php           ← Reusable PHP functions
  
  📁 auth/
    📄 login.php               ← Login page
    📄 logout.php              ← Logout handler
  
  📁 dashboard/
    📄 index.php               ← Main dashboard
  
  📁 modules/                  ← Feature modules (to be completed)
  
  📁 assets/
    📁 css/
      📄 style.css             ← Main stylesheet
    📁 js/
      📄 main.js               ← Main JavaScript
  
  📁 sql/
    📄 database.sql            ← Database schema
```

---

## Key Functions Available

### Authentication Functions
- `isLoggedIn()` - Check if user is logged in
- `requireLogin()` - Redirect if not logged in
- `userHasRole($role)` - Check user role
- `requireRole($role)` - Require specific role

### Data Handling Functions
- `sanitize($data)` - Sanitize user input
- `generateID($prefix)` - Generate unique IDs
- `hashPassword($password)` - Hash password
- `verifyPassword($password, $hash)` - Verify password

### Message Functions
- `setMessage($message, $type)` - Set flash message
- `getMessage()` - Get flash message
- `displayMessage()` - Display message HTML

### Format Functions
- `formatDate($date, $format)` - Format date
- `formatCurrency($amount)` - Format currency (PHP)
- `getMembershipExpiryDate($joinDate, $type)` - Get expiry date

### Database Functions
- `logAction($userId, $action, $module, $details)` - Log actions

---

## Example: Creating a Simple Page

```php
<?php
// Include header with all setup
require_once dirname(dirname(__FILE__)) . '/includes/header.php';

// Check login
requireLogin();

// Get user info
$userInfo = getUserInfo();

// Your code here
?>

<!-- HTML content -->

<?php 
// Include footer
require_once dirname(dirname(__FILE__)) . '/includes/footer.php'; 
?>
```

---

## Database Connection Example

```php
<?php
// Database query example
require_once dirname(dirname(__FILE__)) . '/config/database.php';

try {
    $stmt = $pdo->prepare("SELECT * FROM members WHERE member_id = ?");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();
    
    if ($member) {
        echo "Member found: " . $member['member_name'];
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
```

---

## CSRF Protection Example

```php
<?php
// In form
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
    <!-- form fields -->
</form>

// In form submission
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    die('CSRF token validation failed');
}
?>
```

---

## Color Reference

```css
Primary: #4A90E2 (Blue)
Success: #28A745 (Green)
Warning: #FFC107 (Yellow)
Danger:  #DC3545 (Red)
Neutral: #6C757D (Gray)
```

---

## ID Generation Examples

```php
// Generate member ID: M1705772340345
$memberId = generateID(MEMBER_ID_PREFIX);

// Generate trainer ID: T1705772340456
$trainerId = generateID(TRAINER_ID_PREFIX);

// Generate payment ID: P1705772340567
$paymentId = generateID(PAYMENT_ID_PREFIX);
```

---

## Useful SQL Queries

```sql
-- Get active members
SELECT * FROM members WHERE status = 'Active';

-- Count pending payments
SELECT COUNT(*) FROM payments WHERE payment_status = 'Pending';

-- Get member attendance for date
SELECT * FROM attendance WHERE attendance_date = CURDATE();

-- List active trainers
SELECT * FROM trainers WHERE status = 'Active';

-- Get member's workout plans
SELECT * FROM workout_plans WHERE member_id = 'M1001';
```

---

## Support & Feedback

For issues or questions, check:
1. README.md for full documentation
2. Browser console for JavaScript errors
3. PHP error logs
4. Database connection status

---

**Created**: January 20, 2026
**Version**: 1.0
