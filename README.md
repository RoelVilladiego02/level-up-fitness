# Level Up Fitness - Gym Management System

A comprehensive, multi-user gym management system built with PHP and MySQL. This system provides complete solutions for managing members, trainers, workout plans, sessions, payments, attendance, and reservations.

## 📋 Features

- **User Authentication**: Role-based login (Admin, Member, Trainer)
- **Member Management**: Add, edit, delete, and track members with status management
- **Trainer Management**: Manage trainers and their availability
- **Workout Plans**: Create and manage personalized workout plans
- **Session Scheduling**: Schedule and manage gym sessions
- **Payment Tracking**: Track member payments with multiple payment methods
- **Attendance System**: Check-in/Check-out functionality for members
- **Reservation System**: Book trainers and facilities
- **Responsive Dashboard**: Real-time statistics and quick actions

## 🛠️ Installation & Setup

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web Server (Apache with mod_rewrite enabled)
- Composer (optional, for PHP package management)

### Step 1: Database Setup

1. Open phpMyAdmin or your MySQL client
2. Execute the SQL script from `sql/database.sql`:
   ```sql
   Source /path/to/level-up-fitness/sql/database.sql;
   ```
   Or import the file through phpMyAdmin

### Step 2: Configure Database Connection

1. Open `config/database.php`
2. Update the database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'level_up_fitness');
   ```

### Step 3: Configure Application

1. Open `config/config.php`
2. Update the application URL:
   ```php
   define('APP_URL', 'http://your-domain.com/level-up-fitness/');
   ```

### Step 4: Set Permissions

On Linux/Mac:
```bash
chmod 755 -R /path/to/level-up-fitness
chmod 777 -R /path/to/level-up-fitness/uploads (if uploads directory exists)
```

### Step 5: Access the Application

1. Open your browser
2. Navigate to: `http://localhost/level-up-fitness/`
3. Login with the default admin credentials:
   - **Email**: admin@levelupfitness.com
   - **Password**: password

## 📁 Project Structure

```
level-up-fitness/
├── index.php                    # Entry point
├── config/
│   ├── database.php            # Database connection
│   └── config.php              # Global configuration
├── includes/
│   ├── header.php              # HTML header template
│   ├── footer.php              # HTML footer template
│   └── functions.php           # Reusable functions
├── auth/
│   ├── login.php               # Login page
│   └── logout.php              # Logout handler
├── dashboard/
│   └── index.php               # Main dashboard
├── modules/
│   ├── members/                # Member management
│   ├── trainers/               # Trainer management
│   ├── workouts/               # Workout plans
│   ├── sessions/               # Session management
│   ├── payments/               # Payment tracking
│   ├── attendance/             # Attendance system
│   ├── gyms/                   # Gym information
│   └── reservations/           # Reservation system
├── assets/
│   ├── css/
│   │   └── style.css           # Main stylesheet
│   ├── js/
│   │   └── main.js             # Main JavaScript
│   └── images/                 # Images and logos
└── sql/
    └── database.sql            # Database schema
```

## 🗄️ Database Schema

The system uses 9 main tables:

1. **users** - User authentication and roles
2. **members** - Member profiles and information
3. **trainers** - Trainer profiles and specialization
4. **gyms** - Gym branch information
5. **workout_plans** - Personalized workout plans
6. **sessions** - Gym session scheduling
7. **payments** - Payment transactions
8. **attendance** - Member check-in/out records
9. **reservations** - Trainer and facility bookings

## 🔐 Security Features

- Password hashing using `password_hash()`
- SQL Injection prevention with prepared statements (PDO)
- Session management with automatic timeout (30 minutes)
- CSRF token generation and verification
- Input sanitization with `htmlspecialchars()`
- Role-based access control
- User activity logging

## 🎨 UI/UX Features

- **Responsive Design**: Works on desktop, tablet, and mobile
- **Bootstrap 5 Framework**: Professional UI components
- **Font Awesome Icons**: Extensive icon library
- **Color-coded Status**: Easy visual identification
- **Data Tables**: Sortable and searchable lists
- **Modal Dialogs**: For confirmations and actions
- **Toast Notifications**: User feedback messages
- **Dashboard Cards**: Quick statistics overview

## 📊 User Roles & Permissions

### Admin
- Full system access
- User management
- System configuration
- Reports and analytics

### Trainer
- Manage assigned members
- Create workout plans
- View schedule and reservations
- Track attendance

### Member
- View personal workout plans
- Book trainer sessions
- Track attendance and payments
- Update profile information

## 🚀 Development Roadmap

### Phase 1: Foundation ✅
- [x] Database setup
- [x] Authentication system
- [x] Basic dashboard
- [x] Project structure

### Phase 2: Core Modules (In Progress)
- [ ] Member management CRUD
- [ ] Trainer management CRUD
- [ ] Gym information module

### Phase 3: Extended Features
- [ ] Workout plans
- [ ] Session scheduling
- [ ] Payment tracking
- [ ] Attendance system

### Phase 4: Advanced Features
- [ ] Reservation system
- [ ] Reports/Analytics
- [ ] Search and filtering
- [ ] PDF generation

### Phase 5: Polish
- [ ] Responsive refinement
- [ ] Performance optimization
- [ ] Security hardening
- [ ] Testing and bug fixes

## 📧 Email Notifications (Future)

- Welcome emails for new members
- Payment reminders
- Reservation confirmations
- Membership expiry alerts

## 📊 Reports (Future)

- Monthly revenue reports
- Attendance analytics
- Member growth charts
- Trainer performance metrics

## 🛡️ License

This project is proprietary and confidential.

## 👥 Support & Contribution

For support or contributions, please contact the development team.

## 🔄 Version History

- **v1.0.0** (Jan 2026) - Initial release with foundation setup

---

**Last Updated**: January 20, 2026
**Status**: Active Development
