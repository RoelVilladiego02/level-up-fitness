# Database Reset Tools
## Level Up Fitness - Gym Management System

### Overview
Two methods to reset the database and clear all data while preserving the structure.

---

## Method 1: Command Line Reset (Recommended)

### Usage
```bash
cd C:\xampp\htdocs\level-up-fitness
php reset-database.php
```

### Features
- ✅ Interactive confirmation (type "RESET" to confirm)
- ✅ Clears all data from all tables
- ✅ Re-inserts initial admin user and sample data
- ✅ Shows progress with checkmarks
- ✅ Displays final credentials

### What Gets Cleared
- All users (members, trainers, admins)
- All members
- All trainers
- All sessions
- All workouts
- All reservations
- All payments
- All attendance records
- All activity logs
- All classes and class attendance

### What Gets Re-created
- 1x Admin user (admin@levelupfitness.com / admin123)
- 1x Sample member (John Doe)
- 1x Trainer user (trainer@levelupfitness.com / trainer123)
- 1x Sample trainer (Jane Smith)
- 1x Sample gym (Main Branch)
- 5x Sample equipment items:
  - Treadmill
  - Dumbbells Set
  - Bench Press
  - Rowing Machine
  - Yoga Mat

### Example Output
```
╔════════════════════════════════════════════════════════╗
║       Level Up Fitness - DATABASE RESET                ║
║  WARNING: This will DELETE ALL data!                  ║
╚════════════════════════════════════════════════════════╝

⚠️  Are you sure? This action cannot be undone!
Type 'RESET' to confirm: RESET

⏳ Resetting database...

Truncating tables:
  ✓ activity_log
  ✓ attendance
  ✓ class_attendance
  ✓ classes
  ✓ equipment
  ✓ gyms
  ✓ members
  ✓ payments
  ✓ reservations
  ✓ sessions
  ✓ trainers
  ✓ users
  ✓ workout_plans

✓ All tables cleared

Inserting initial data:
  ✓ Admin user
  ✓ Sample member: John Doe
  ✓ Sample trainer: Jane Smith
  ✓ Sample gym: Main Branch
  ✓ Sample equipment (5 items)

╔════════════════════════════════════════════════════════╗
║  ✅ DATABASE RESET SUCCESSFUL!                        ║
╚════════════════════════════════════════════════════════╝
```

---

## Method 2: Web-Based Reset

### Access
Navigate to: `http://localhost/level-up-fitness/admin/reset-database.php`

**Requirements:** Must be logged in as an admin user

### Features
- ✅ Beautiful web interface with warnings
- ✅ Checkbox confirmation before reset
- ✅ Shows initial credentials after reset
- ✅ Error handling with friendly messages
- ✅ Mobile-responsive design

### Steps
1. Log in as admin
2. Navigate to `/admin/reset-database.php`
3. Read the warning carefully
4. Check the confirmation checkbox
5. Click "Reset Database" button
6. Confirm on the browser dialog
7. View success message with credentials

### Security Features
- Only admins can access
- Double confirmation (checkbox + browser dialog)
- Session check prevents direct access
- Clear warning messages

---

## Initial Credentials After Reset

```
ADMIN:
  Email: admin@levelupfitness.com
  Password: admin123
  Role: Administrator

TRAINER:
  Email: trainer@levelupfitness.com
  Password: trainer123
  Role: Trainer
```

---

## When to Use

### Use CLI Reset When:
- Automated/scripted reset needed
- Server-side execution preferred
- Want simple, fast reset
- Running from terminal/deployment script

### Use Web Reset When:
- Manual reset during development
- Testing admin functionality
- Need visual feedback
- Prefer web interface

---

## Important Notes

⚠️ **WARNING**: 
- This operation **CANNOT BE UNDONE**
- All data will be permanently deleted
- Make sure to have backups if needed
- Only use in development environment

✅ **What's Preserved:**
- Database structure/schema
- Table definitions
- Foreign key relationships
- Column types and constraints

❌ **What's Deleted:**
- ALL DATA in every table
- User accounts
- Transactions
- Records of any kind
- Activity logs

---

## Troubleshooting

### "Access denied" error in web interface
- Make sure you're logged in as an admin
- Session may have expired, log in again

### "FOREIGN_KEY_CHECKS error"
- Script automatically disables/re-enables this
- If error persists, check database permissions

### Password hashing errors
- Ensure bcrypt is available in PHP
- Usually included by default in modern PHP

### Database connection error
- Verify database credentials in `config/database.php`
- Ensure MySQL/MariaDB is running

---

## Files

- **CLI Reset**: `reset-database.php`
- **Web Reset**: `admin/reset-database.php`

Both scripts:
- Share same reset logic
- Clear all data
- Re-insert initial data
- Are fully automated
