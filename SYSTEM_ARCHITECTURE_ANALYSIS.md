# Level Up Fitness - System Architecture & Sequence Diagrams

**Date**: May 11, 2026  
**Version**: 1.0  
**System Type**: Multi-user Gym Management System

---

## 📋 Table of Contents

1. [System Overview](#system-overview)
2. [Architecture Layers](#architecture-layers)
3. [Core Actors](#core-actors)
4. [Key Workflows](#key-workflows)
5. [Sequence Diagrams](#sequence-diagrams)
6. [Database Relationships](#database-relationships)
7. [Integration Points](#integration-points)

---

## 🏗️ System Overview

### Purpose
Level Up Fitness is a comprehensive gym management system that handles:
- Multi-role user authentication and authorization
- Member lifecycle management
- Trainer management and availability
- Workout planning and session scheduling
- Payment tracking and management
- Attendance and check-in systems
- Class enrollment and management
- Email notifications and verification
- Activity logging and audit trails

### Technology Stack
- **Frontend**: HTML5, CSS3 (Bootstrap 5), JavaScript
- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Email Service**: Mailtrap/SMTP
- **Authentication**: Session-based with bcrypt password hashing
- **API Communication**: AJAX (JSON)

### Key Characteristics
- ✅ Multi-tenant architecture (Admin, Member, Trainer roles)
- ✅ Role-based access control (RBAC)
- ✅ PDO-based database abstraction
- ✅ Email verification workflow
- ✅ Activity logging for audit trails
- ✅ Real-time notifications via API
- ✅ Session timeout (30 minutes)
- ✅ Input sanitization and SQL injection prevention

---

## 🔄 Architecture Layers

```
┌─────────────────────────────────────────────────────────────┐
│                    PRESENTATION LAYER                        │
│  (HTML Templates, Bootstrap UI, JavaScript/AJAX, Forms)      │
└────────────────┬────────────────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────────────────┐
│                    APPLICATION LAYER                         │
│  (PHP Controllers, Business Logic, Functions, API Routes)    │
├──────────────────────────────────────────────────────────────┤
│  • Authentication (login.php, logout.php)                    │
│  • Email Verification (verify-email.php)                     │
│  • Dashboard Management (dashboards/)                        │
│  • Module Handlers (modules/*/index.php)                     │
│  • API Endpoints (api/notifications.php)                     │
└────────────────┬────────────────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────────────────┐
│                    SERVICE LAYER                             │
│  (Business Logic, Utilities, External Integrations)          │
├──────────────────────────────────────────────────────────────┤
│  • MailtrapService (config/MailtrapService.php)             │
│  • SMTPMailService (config/SMTPMailService.php)             │
│  • Email Functions (includes/email-notifications.php)        │
│  • Helper Functions (includes/functions.php)                │
│  • Configuration (config/*.php)                              │
└────────────────┬────────────────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────────────────┐
│                    DATA ACCESS LAYER                         │
│  (PDO Database Abstraction)                                  │
├──────────────────────────────────────────────────────────────┤
│  • Database Connection (config/database.php)                 │
│  • SQL Queries (prepared statements)                         │
│  • Transaction Management                                    │
└────────────────┬────────────────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────────────────┐
│                    DATA LAYER                                │
│  (MySQL Database)                                            │
├──────────────────────────────────────────────────────────────┤
│  9 Core Tables: users, members, trainers, gyms,             │
│  workout_plans, sessions, payments, classes,                │
│  class_attendance, attendance                                │
└──────────────────────────────────────────────────────────────┘
                 │
┌────────────────▼────────────────────────────────────────────┐
│                 EXTERNAL SERVICES                            │
│  (Email Service Providers)                                   │
├──────────────────────────────────────────────────────────────┤
│  • Mailtrap API                                              │
│  • SMTP Server                                               │
└──────────────────────────────────────────────────────────────┘
```

---

## 👥 Core Actors

### 1. **Admin User**
- **Role**: System Administrator
- **Capabilities**:
  - Manage all members and trainers
  - Create and manage gyms
  - View all payments and attendance
  - System configuration
  - Generate reports
  - Access activity logs
- **Access Control**: Full system access
- **Authentication**: Email + Password

### 2. **Member User**
- **Role**: Gym Member
- **Capabilities**:
  - View personal dashboard
  - Book trainers (reservations)
  - View assigned workout plans
  - Check attendance history
  - View personal payments
  - Manage account settings
- **Access Control**: View only personal data
- **Requires**: Email verification after signup

### 3. **Trainer User**
- **Role**: Fitness Trainer
- **Capabilities**:
  - View trainer dashboard
  - Manage assigned members
  - Create workout plans for members
  - Schedule sessions and classes
  - Track member progress
  - Manage availability
- **Access Control**: View assigned members only
- **Restrictions**: Cannot modify other trainers' data

### 4. **System (Automated)**
- **Role**: Background processes
- **Capabilities**:
  - Send email notifications
  - Log activities
  - Generate tokens for verification
  - Trigger notifications on events

---

## 🔑 Key Workflows

### Workflow 1: User Registration & Email Verification

**Actors**: Member, System, Email Service  
**Precondition**: User has email access  
**Postcondition**: Member can log in with verified email

**Steps**:
1. Member fills registration form
2. System validates input
3. System generates verification token
4. System creates user record (is_verified = 0)
5. System sends verification email
6. Member clicks link with token
7. System validates token
8. System sets is_verified = 1
9. Member can now login

---

### Workflow 2: User Authentication

**Actors**: User, System, Database  
**Precondition**: User account exists and is verified  
**Postcondition**: Session established, user logged in

**Steps**:
1. User enters email and password
2. System sanitizes input
3. System queries user by email
4. System verifies password (bcrypt)
5. System checks verification status
6. System creates session variables
7. System retrieves user-specific data
8. System logs login activity
9. System redirects to dashboard

---

### Workflow 3: Member Creation (Admin)

**Actors**: Admin, System, Database, Email Service  
**Precondition**: Admin logged in  
**Postcondition**: Member account created with verification email sent

**Steps**:
1. Admin accesses member creation form
2. Admin enters member details
3. System generates unique member_id
4. System creates user record
5. System creates member record
6. System generates verification token
7. System sends verification email to member
8. System logs action
9. Member receives email and can verify

---

### Workflow 4: Payment Processing

**Actors**: Admin, Member, System, Database  
**Precondition**: Member session exists, payment details provided  
**Postcondition**: Payment recorded in database

**Steps**:
1. Admin/Member initiates payment entry
2. System validates payment details
3. System calculates payment amount
4. System determines payment status (Paid/Pending)
5. System creates payment record
6. System updates member account if applicable
7. System sends payment confirmation notification
8. System logs transaction
9. Database records payment history

---

### Workflow 5: Session Scheduling & Notification

**Actors**: Trainer, Member, System, Email Service  
**Precondition**: Member and Trainer accounts exist  
**Postcondition**: Session scheduled, notifications sent

**Steps**:
1. Trainer creates new session
2. System assigns member to session
3. System validates schedule conflicts
4. System creates session record
5. System sends notification to member
6. System sends notification to trainer
7. System logs session creation
8. Member receives email/notification

---

### Workflow 6: Notification System

**Actors**: System, Member, Email Service, Browser  
**Precondition**: Notification event triggered  
**Postcondition**: User receives notification

**Steps**:
1. System event triggers (payment, session, etc.)
2. System creates notification record
3. System queues email notification
4. Email service sends email
5. System marks email as sent
6. Member sees notification on dashboard
7. Member can mark as read via AJAX
8. Notification status updates in database

---

### Workflow 7: Class Enrollment & Attendance

**Actors**: Member, Trainer, Admin, System  
**Precondition**: Class exists and has availability  
**Postcondition**: Member enrolled, attendance tracked

**Steps**:
1. Member views available classes
2. Member clicks "Enroll"
3. System checks capacity
4. System creates class_attendance record
5. System sends enrollment confirmation
6. On class day: Trainer marks attendance
7. System records attendance_status
8. System generates attendance reports
9. Member can view attendance history

---

## 📊 Sequence Diagrams

### Diagram 1: User Registration & Email Verification Flow

```
Member                Browser              Application          Database        Email Service
  |                     |                      |                    |                 |
  |--[Fill Form]------->|                      |                    |                 |
  |                     |--[POST /register]--->|                    |                 |
  |                     |                      |                    |                 |
  |                     |                      |--[Validate Input]  |                 |
  |                     |                      |                    |                 |
  |                     |                      |--[Generate Token]  |                 |
  |                     |                      |                    |                 |
  |                     |                      |--[Create User]---->|                 |
  |                     |                      |<--[user_id]--------|                 |
  |                     |                      |                    |                 |
  |                     |                      |--[Create Member]-->|                 |
  |                     |                      |<--[Success]--------|                 |
  |                     |                      |                    |                 |
  |                     |                      |--[Build Email]     |                 |
  |                     |                      |--[Send Email]------|---[SMTP/API]--->|
  |                     |                      |                    |<--[Queued]------|
  |                     |<--[Success]---------|                    |                 |
  |<--[Confirmation]---[HTML Response]        |                    |                 |
  |                     |                      |                    |         |--[Send]-->|
  |--[Click Link]------>|                      |                    |         |           |
  |                     |--[GET /verify?token]>|                    |                 |
  |                     |                      |--[Validate Token]  |                 |
  |                     |                      |--[Query User]----->|                 |
  |                     |                      |<--[user_id]--------|                 |
  |                     |                      |                    |                 |
  |                     |                      |--[Update: is_verified=1]--->|        |
  |                     |                      |<--[Success]--------|                 |
  |                     |                      |                    |                 |
  |                     |                      |--[Log Activity]    |                 |
  |                     |                      |                    |                 |
  |                     |<--[Verified!]------|                    |                 |
  |<--[Can Login]------|                      |                    |                 |
```

---

### Diagram 2: User Authentication & Session Flow

```
User                Browser              Application          Database          Session
  |                   |                      |                   |                 |
  |--[Enter Email]--->|                      |                   |                 |
  |--[Enter Password]-|                      |                   |                 |
  |                   |--[POST /login]------>|                   |                 |
  |                   |                      |--[Sanitize Input]  |                 |
  |                   |                      |--[Query User]----->|                 |
  |                   |                      |<--[User Record]----|                 |
  |                   |                      |                   |                 |
  |                   |                      |--[Verify Password] |                 |
  |                   |                      |(bcrypt_verify)     |                 |
  |                   |                      |                   |                 |
  |                   |                      |--[Check if Verified]               |
  |                   |                      |                   |                 |
  |                   |                      |--[Create Session]---|----[Start]---->|
  |                   |                      |$_SESSION['user_id']=X             |
  |                   |                      |$_SESSION['user_type']=member      |
  |                   |                      |$_SESSION['email']=user@email      |
  |                   |                      |                   |                 |
  |                   |                      |--[Get User Type]---|                 |
  |                   |                      |--[Get Name]------->|                 |
  |                   |                      |<--[member_name]----|                 |
  |                   |                      |$_SESSION['name']=member_name      |
  |                   |                      |                   |                 |
  |                   |                      |--[Log Activity]    |                 |
  |                   |                      |                   |                 |
  |                   |                      |--[Update last_login]-->            |
  |                   |                      |<--[Updated]--------|                 |
  |                   |<--[Redirect]---------|                   |                 |
  |                   |  (to /dashboard)    |                   |                 |
  |<--[Dashboard]-----|                      |                   |                 |
```

---

### Diagram 3: Member Creation by Admin

```
Admin              Browser              Application          Database        Email Service
  |                  |                      |                    |                 |
  |--[Access Form]-->|                      |                    |                 |
  |                  |--[GET /members/add]->|                    |                 |
  |                  |<--[Form HTML]--------|                    |                 |
  |--[Fill Form]---->|                      |                    |                 |
  |                  |--[POST /members]---->|                    |                 |
  |                  |                      |--[Validate Input]   |                 |
  |                  |                      |--[Check Email]----->|                 |
  |                  |                      |<--[Not exists]------|                 |
  |                  |                      |                    |                 |
  |                  |                      |--[Generate ID]      |                 |
  |                  |                      |  (member_id)        |                 |
  |                  |                      |--[Hash Password]    |                 |
  |                  |                      |                    |                 |
  |                  |                      |--[Create User]----->|                 |
  |                  |                      |<--[user_id]---------|                 |
  |                  |                      |                    |                 |
  |                  |                      |--[Create Member]----->              |
  |                  |                      |<--[Success]---------|                 |
  |                  |                      |                    |                 |
  |                  |                      |--[Generate Token]   |                 |
  |                  |                      |                    |                 |
  |                  |                      |--[Build Email]      |                 |
  |                  |                      |  (with verification link)            |
  |                  |                      |--[Send Email]-------|---[SMTP/API]--->|
  |                  |                      |                    |<--[Message ID]--|
  |                  |                      |                    |                 |
  |                  |                      |--[Log Activity]     |                 |
  |                  |                      |  ("Member created by admin")         |
  |                  |<--[Success Message]--|                    |                 |
  |<--[Member Added]--|                      |                    |                 |
  |                  |                      |                    |         |--[Send]-->|
```

---

### Diagram 4: Payment Processing Flow

```
Admin/Member       Browser              Application          Database        Notifications
  |                  |                      |                    |                 |
  |--[View Payments]->|                      |                    |                 |
  |                  |--[GET /payments]---->|                    |                 |
  |                  |                      |--[Query Members]----->              |
  |                  |                      |<--[Members List]----|                 |
  |                  |<--[Payment Form]-----|                    |                 |
  |--[Enter Amount]-->|                      |                    |                 |
  |--[Select Method]->|                      |                    |                 |
  |--[Submit]-------->|                      |                    |                 |
  |                  |--[POST /payments]----->|                    |                 |
  |                  |                      |                    |                 |
  |                  |                      |--[Validate Input]   |                 |
  |                  |                      |--[Calculate Amount] |                 |
  |                  |                      |                    |                 |
  |                  |                      |--[Generate ID]      |                 |
  |                  |                      |  (payment_id)       |                 |
  |                  |                      |                    |                 |
  |                  |                      |--[Create Payment]-->|                 |
  |                  |                      |<--[Success]---------|                 |
  |                  |                      |                    |                 |
  |                  |                      |--[Trigger Event]    |                 |
  |                  |                      |  (payment_created)  |                 |
  |                  |                      |                    |--[Insert Notification]
  |                  |                      |                    |<--[notification_id]
  |                  |                      |                    |                 |
  |                  |                      |--[Send Email]------|---[Queue Email]--->|
  |                  |                      |                    |                 |
  |                  |                      |--[Log Activity]     |                 |
  |                  |                      |  ("Payment recorded")               |
  |                  |<--[Success]---------|                    |                 |
  |<--[Payment Done]--|                      |                    |                 |
```

---

### Diagram 5: Real-time Notification Flow

```
System Event    Application          Database          Browser AJAX      Frontend JS
  |               |                    |                   |                 |
  |--[Trigger]-->|                    |                   |                 |
  |    (e.g.,    |                    |                   |                 |
  |  new session)|--[Create Event]    |                   |                 |
  |               |                    |                   |                 |
  |               |--[Query User]----->|                   |                 |
  |               |<--[User Data]------|                   |                 |
  |               |                    |                   |                 |
  |               |--[Insert Notif]---->|                   |                 |
  |               |<--[notif_id]--------|                   |                 |
  |               |                    |                   |                 |
  |               |--[Queue Email]     |                   |                 |
  |               |                    |                   |                 |
  |               |               [User's Browser]          |                 |
  |               |                    |<--[Poll]--[/api/notifications]-|
  |               |                    |--[Query Unread]----->         |
  |               |                    |<--[Notification Data]-|-------[GET]
  |               |                    |                   |                 |
  |               |                    |                   |<--[JSON]--------|
  |               |                    |                   |                 |
  |               |                    |                   |     |-[Display Toast]-->|
  |               |                    |                   |     |-[Update Badge]---|
  |               |                    |                   |     |-[Play Sound]-----|
  |               |                    |                   |     |                 |
  |               |                    |                   |<--[Mark Read]--[AJAX POST]
  |               |                    |--[Update notif]---->  |                 |
  |               |                    |<--[Success]--------|                 |
  |               |                    |                   |     |-[Remove Toast]-->|
```

---

### Diagram 6: Class Enrollment & Attendance

```
Member           Browser              Application          Database        Trainer
  |                 |                      |                   |              |
  |--[View Classes]->|                      |                   |              |
  |                 |--[GET /classes]----->|                   |              |
  |                 |                      |--[Query Classes]-->|              |
  |                 |                      |<--[Classes List]---|              |
  |                 |<--[Display Classes]--|                   |              |
  |--[Click Enroll]->|                      |                   |              |
  |                 |--[POST /enroll]----->|                   |              |
  |                 |                      |                   |              |
  |                 |                      |--[Check Capacity]  |              |
  |                 |                      |--[Check Duplicate]-->             |
  |                 |                      |<--[Valid]---------|              |
  |                 |                      |                   |              |
  |                 |                      |--[Add Enrollment]->|              |
  |                 |                      |<--[attendance_id]--|             |
  |                 |                      |                   |              |
  |                 |                      |--[Send Notification]            |
  |                 |                      |--[Log Activity]    |              |
  |                 |<--[Success]---------|                   |              |
  |<--[Enrolled!]---|                      |                   |              |
  |                 |                      |                   |              |
  |                 |              [On Class Date]             |              |
  |                 |                      |              [Trainer checks in]
  |                 |                      |<--[Mark Attendance]             |
  |                 |                      |--[Record Status]-->|              |
  |                 |                      |<--[Updated]--------|              |
  |                 |                      |                   |              |
  |--[View History]->|                      |                   |              |
  |                 |--[GET /attendance]-->|                   |              |
  |                 |                      |--[Query Records]-->|              |
  |                 |                      |<--[Attendance]-----|              |
  |                 |<--[Display History]--|                   |              |
```

---

### Diagram 7: Trainer Reservation & Session Booking

```
Member           Browser              Application          Database        Trainer/System
  |                 |                      |                   |              |
  |--[View Trainers]>|                      |                   |              |
  |                 |--[GET /trainers]----->|                   |              |
  |                 |                      |--[Query Trainers]-->             |
  |                 |                      |<--[Trainers List]--|             |
  |                 |<--[Display Trainers]--                   |              |
  |--[Click Trainer]->|                      |                   |              |
  |                 |--[GET /trainer/:id]-->|                   |              |
  |                 |                      |--[Get Trainer]---->|              |
  |                 |                      |<--[Trainer Details]|              |
  |                 |                      |--[Get Availability]|              |
  |                 |                      |<--[Availability]---|              |
  |                 |<--[Trainer Profile]---                   |              |
  |--[Select Time]-->|                      |                   |              |
  |--[Book Session]->|                      |                   |              |
  |                 |--[POST /reserve]------>|                   |              |
  |                 |                      |--[Validate Time]   |              |
  |                 |                      |--[Check Conflicts]->             |
  |                 |                      |<--[No conflicts]---|             |
  |                 |                      |                   |              |
  |                 |                      |--[Create Session]->|              |
  |                 |                      |<--[session_id]-----|              |
  |                 |                      |                   |              |
  |                 |                      |--[Send to Trainer]---[Notify]---->|
  |                 |                      |--[Log Activity]    |              |
  |                 |<--[Booked!]----------|                   |              |
  |<--[Confirmation]--                      |                   |              |
```

---

## 📐 Database Relationships

### Entity Relationship Diagram (ERD)

```
┌─────────────┐
│   users     │
├─────────────┤
│ user_id (PK)│
│ email       │
│ password    │
│ user_type   │
│ created_at  │
│ last_login  │
└──────┬──────┘
       │
       ├─────────────┬──────────────┐
       │             │              │
       ▼             ▼              ▼
┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│   members    │ │   trainers   │ │    admin     │
├──────────────┤ ├──────────────┤ ├──────────────┤
│member_id(PK) │ │trainer_id(PK)│ │ (implicit)   │
│user_id(FK)   │ │user_id(FK)   │ │              │
│member_name   │ │trainer_name  │ │              │
│status        │ │availability  │ │              │
│trainer_id(FK)├─┤specialization│ │              │
└──────┬───────┘ └──────┬───────┘ └──────────────┘
       │                │
       │                ├──────────┬──────────┐
       │                │          │          │
       ▼                ▼          ▼          ▼
┌──────────────┐ ┌──────────────┐┌────────┐┌──────────┐
│   payments   │ │   sessions   ││classes ││workplans │
├──────────────┤ ├──────────────┤├────────┤├──────────┤
│payment_id(PK)│ │session_id(PK)││class_id││plan_id   │
│member_id(FK) │ │member_id(FK) ││trainer_│├──────────┤
│amount        │ │trainer_id(FK)││max_cap │
│status        │ │session_date  ││status  │
└──────────────┘ └──────────────┘└───┬────┘└──────────┘
                                     │
                                     ▼
                            ┌──────────────────┐
                            │ class_attendance │
                            ├──────────────────┤
                            │ attendance_id(PK)│
                            │ class_id(FK)     │
                            │ member_id(FK)    │
                            │ status           │
                            └──────────────────┘
```

---

## 🔗 Integration Points

### 1. Email Service Integration

**Service**: Mailtrap / SMTP  
**Trigger Events**:
- User registration (verification email)
- Member creation (admin creates member)
- Password reset (resend verification)
- Payment confirmation
- Session booking confirmation
- Class enrollment confirmation
- General notifications

**Configuration Files**:
- `config/mailtrap.php` - Mailtrap API credentials
- `config/SMTPMailService.php` - SMTP configuration
- `includes/email-notifications.php` - Email templates and functions

### 2. Session Management

**Type**: Server-side session storage  
**Session Variables**:
- `user_id` - Authenticated user's ID
- `email` - User's email address
- `user_type` - Role (admin, member, trainer)
- `name` - User's display name
- `last_activity` - Timestamp for timeout detection

**Timeout**: 30 minutes of inactivity

### 3. API Endpoints

**Base URL**: `/api/`

**Endpoints**:
- `POST /api/notifications.php?action=mark_read`
- `POST /api/notifications.php?action=mark_all_read`
- `POST /api/notifications.php?action=delete`
- `POST /api/notifications.php?action=get_unread_count`
- `POST /api/notifications.php?action=get_unread`

**Response Format**: JSON

### 4. Activity Logging

**Function**: `logAction($user_id, $action, $module, $description)`

**Logged Events**:
- User login/logout
- Record creation/update/deletion
- Payments processed
- Member status changes
- Session bookings

**Storage**: `activity_log` table

### 5. File Upload Handling

**Directories**:
- `backend/storage/` - File storage
- `uploads/` - User uploads

**Security**: 
- File type validation
- Size limits
- Sanitized filenames

---

## 🔐 Security Architecture

### Authentication
- **Method**: Session-based with bcrypt password hashing
- **Token Generation**: Random token for email verification
- **Token Validation**: Token checked against stored hash
- **Session Timeout**: 30 minutes inactivity

### Authorization
- **Method**: Role-based access control (RBAC)
- **Roles**: Admin, Member, Trainer
- **Enforcement**: Server-side validation on every request

### Input Validation
- **Sanitization**: `sanitize()` function for user input
- **Validation**: Type and format checking
- **Parameterized Queries**: PDO prepared statements

### Output Encoding
- **HTML Encoding**: `htmlspecialchars()` with ENT_QUOTES
- **JSON Encoding**: `json_encode()` for API responses
- **URL Encoding**: For redirect URLs

---

## 📞 Support & Maintenance

For questions or issues, refer to:
1. `/docs/00_START_HERE.md` - Quick start guide
2. `/docs/IMPLEMENTATION_GUIDE.md` - Development guide
3. Activity logs for debugging user issues
4. Error logs in PHP error_log

---

**Last Updated**: May 11, 2026  
**Next Review**: June 2026
