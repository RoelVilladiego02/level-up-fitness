# Session Request System - Complete Implementation

## Overview
The **Attendance Module** has been replaced with a **Session Request System** where members can request training sessions from their assigned trainers, and trainers can approve or reject those requests.

## Database Changes
- **Table Created**: `session_requests`
  - `request_id` (Primary Key, Auto-increment)
  - `member_id` (Foreign Key → members)
  - `trainer_id` (Foreign Key → trainers)
  - `requested_date` (DATE)
  - `requested_time` (TIME)
  - `duration` (INT - minutes)
  - `purpose` (VARCHAR - session purpose)
  - `status` (ENUM: Pending, Approved, Rejected, Cancelled)
  - `trainer_notes` (LONGTEXT - trainer feedback)
  - `created_at`, `updated_at` (timestamps)

## Automatic Creation
- The `session_requests` table is **automatically created** when `php reset-database.php` is run
- No manual migration needed - all schema changes are integrated into the reset script

## File Structure

### For Members
- **`request.php`** - Request a new training session
  - Form with date, time, duration, and purpose
  - Auto-populated trainer from member's trainer assignment
  - Validation for past dates

- **`my-requests.php`** - View all member's requests
  - Filter by status (All, Pending, Approved, Rejected)
  - View request details
  - Cancel pending requests

### For Trainers & Admins
- **`index.php`** - List all session requests
  - Search by member name, email, or purpose
  - Filter by status
  - Quick approve/reject buttons for pending requests
  - Pagination support

### Shared
- **`view.php`** - View request details
  - Display all request information
  - Show trainer notes (if any)
  - Members can cancel pending requests
  - Access control enforced (members see only own, trainers see only their members)

- **`approve.php`** - Approve a session request (Trainers/Admins only)
  - Optional notes field
  - Status changes to "Approved"

- **`reject.php`** - Reject a session request (Trainers/Admins only)
  - Required reason field
  - Status changes to "Rejected"

## Access Control

### Members
- Can view: Own session requests only
- Actions: Request session, cancel pending requests
- Navigation: "Session Requests" → `my-requests.php`

### Trainers
- Can view: Only requests for their assigned members
- Actions: View, approve, or reject requests
- Cannot see requests from members of other trainers
- Navigation: "Session Requests" → `index.php`

### Admins
- Can view: All session requests in the system
- Actions: View, approve, or reject any request
- Full oversight of all requests
- Navigation: "Session Requests" → `index.php`

## Request Workflow

```
1. Member creates request
   ↓
2. Request stored with status = "Pending"
   ↓
3. Trainer reviews request
   ├─ Approve → Status = "Approved" + trainer notes
   ├─ Reject → Status = "Rejected" + rejection reason
   └─ No action → Remains "Pending"
   
4. Member can cancel anytime (if Pending)
   └─ Status = "Cancelled"
```

## Key Features

✅ **Proper Access Control**
- Members see only their own requests
- Trainers see only their members' requests
- Admins see all requests
- Enforced at database query level

✅ **Validation**
- Cannot request past dates
- Duration: 15-480 minutes
- Purpose is required
- Member must have assigned trainer

✅ **Status Tracking**
- Pending (awaiting trainer review)
- Approved (trainer accepted)
- Rejected (trainer declined with reason)
- Cancelled (member withdrew)

✅ **Search & Filter**
- Search by member name, email, or purpose
- Filter by status
- Pagination for large datasets

✅ **Automatic Database Setup**
- Table created on `reset-database.php` run
- No manual migrations needed
- Integrated schema checks

## Testing Credentials

```
Member: john@email.com / member123
  - Assigned trainer: Jane Smith
  - Can: Request sessions, view requests, cancel pending

Trainer: trainer@levelupfitness.com / trainer123
  - Can: View all John's requests, approve/reject

Admin: admin@levelupfitness.com / admin123
  - Can: View all requests, approve/reject any
```

## Usage Example

### As Member:
1. Login as john@email.com
2. Click "Session Requests" in sidebar
3. Click "Request New Session"
4. Fill in date, time, duration, purpose
5. Submit - request sent to trainer

### As Trainer:
1. Login as trainer@levelupfitness.com
2. Click "Session Requests" in sidebar
3. View pending requests from John
4. Click approve/reject buttons
5. Add notes/reasons

### As Admin:
1. Login as admin@levelupfitness.com
2. Click "Session Requests" in sidebar
3. See all requests from all members
4. Manage as needed
