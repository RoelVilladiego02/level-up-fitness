# Workout Templates Implementation Guide

## Overview
This guide provides instructions to set up and use the new **Popular Workout Templates** feature in Level Up Fitness. The feature allows members and trainers to browse pre-built workout plans and customize them for personal use.

## What Was Implemented

### 1. **Database Migration** (`migration_add_workout_templates.sql`)
- Created `workout_templates` table with 25 pre-populated popular workout plans
- Added `template_id` column to `workout_plans` table to track which template was used as a base
- Includes templates across multiple categories:
  - **Strength Training**: StrongLifts 5x5, Starting Strength, PPL, Upper/Lower Split, PHUL
  - **Cardio & HIIT**: Couch to 5K, HIIT Fat Burner, HIIT Full Body, Steady State Cardio, CrossFit WOD
  - **Flexibility**: Yoga, Dynamic Stretching, Pilates Core
  - **Bodybuilding**: Arnold Split, Bro Split, Hypertrophy Focus
  - **Home Programs**: Beachbody P90X, Bodyweight Strength, Daily Movement, Office Worker Wellness
  - **Sport-Specific**: Runner Training, Boxer Training, Basketball Athlete

### 2. **New Modules** (`/modules/templates/`)

#### **index.php** - Template Listing Page
- Browse all 25 popular workout templates
- Filter by:
  - Type (Strength, Cardio, HIIT, Flexibility, etc.)
  - Difficulty Level (Beginner, Intermediate, Advanced)
  - Search by name, goal, or description
- View template cards with:
  - Badges showing type and difficulty
  - Popularity score (times used)
  - Goal summary
  - Duration and exercise count
  - Equipment requirements

#### **view.php** - Template Details Page
- Comprehensive view of a single template
- Shows:
  - Full description
  - Equipment requirements
  - Weekly schedule breakdown
  - Quick stats sidebar
  - Popularity metrics

#### **customize.php** - Create Plan from Template
- Create personalized workout plans from templates
- Features:
  - **For Trainers/Admins**: Select member and optionally assign trainer
  - **For Members**: Automatically creates plan for themselves
  - Customize:
    - Plan name
    - Duration (in weeks)
    - Add custom notes and modifications
  - Preview template schedule
  - Auto-increments template popularity counter

### 3. **Navigation Updates**
- Added "Workout Templates" link in sidebar
- Available in both MEMBER and TRAINER OPERATIONS sections
- Icon: `<i class="fas fa-heart"></i>`

## Installation Instructions

### Step 1: Apply Database Migration

**Option A: Using phpMyAdmin**
1. Open phpMyAdmin at `http://localhost/phpmyadmin`
2. Select the `level_up_fitness` database
3. Go to **SQL** tab
4. Open file: `/sql/migration_add_workout_templates.sql`
5. Copy entire content
6. Paste into SQL query box
7. Click **Go** to execute

**Option B: Using Command Line**
```bash
cd c:\xampp\htdocs\level-up-fitness
mysql -u root -p level_up_fitness < sql/migration_add_workout_templates.sql
```

### Step 2: Verify Installation

1. Check if `workout_templates` table exists:
   ```sql
   SELECT COUNT(*) FROM workout_templates;
   ```
   Should return: **25 templates**

2. Verify `template_id` column in `workout_plans`:
   ```sql
   DESCRIBE workout_plans;
   ```
   Should show `template_id` column

## Usage Guide

### For Members
1. Navigate to **Workout Templates** from sidebar
2. Browse available templates by:
   - Scrolling through all templates
   - Filtering by type or difficulty
   - Searching by name/goal
3. Click **View Details** to see complete information
4. Click **Use This Template** to create a personalized plan
5. Fill in:
   - Custom name for your plan
   - Duration (weeks)
   - Any custom modifications
6. Click **Create Workout Plan**

### For Trainers
1. Navigate to **Workout Templates** from sidebar
2. Browse templates
3. Click **Use & Customize** on a template
4. Select member from dropdown
5. Optionally customize plan details
6. Click **Create Workout Plan**
7. Plan will be assigned to selected member

### For Admins
1. Navigate to **Workout Templates** from sidebar
2. All features available like trainers
3. Can select any member and assign any trainer
4. Browse and customize templates for your gym members

## Features

### Template Features
- **25 Pre-loaded Templates** covering major workout categories
- **Difficulty Levels**: Beginner, Intermediate, Advanced
- **Equipment Info**: Required equipment for each template
- **Schedule Details**: Daily/weekly exercise breakdowns
- **Popularity Tracking**: Shows how many members used each
- **Search & Filter**: Find templates by name, type, difficulty

### Customization Features
- **Flexible Duration**: Modify program length (1-52 weeks)
- **Custom Names**: Give plans personalized names
- **Modification Notes**: Add custom instructions or variations
- **Member Assignment**: Trainers can assign to specific members
- **Template Tracking**: Plans track which template they originated from

## Database Structure

### workout_templates Table
```sql
CREATE TABLE `workout_templates` (
  `template_id` varchar(50) PRIMARY KEY,
  `template_name` varchar(255),
  `template_type` varchar(100),
  `difficulty_level` enum('Beginner','Intermediate','Advanced'),
  `description` longtext,
  `goal` varchar(255),
  `duration_weeks` int,
  `weekly_schedule` longtext (JSON),
  `exercises_count` int,
  `equipment_required` varchar(500),
  `image_url` varchar(500),
  `popularity_score` int,
  `is_active` tinyint(1),
  `created_at` timestamp,
  `updated_at` timestamp
);
```

### Updated workout_plans Table
Added column:
```sql
`template_id` varchar(50) (nullable, references workout_templates)
```

## API Endpoints / Routes

| Route | Method | Access | Description |
|-------|--------|--------|-------------|
| `/modules/templates/` | GET | Member, Trainer, Admin | List all templates |
| `/modules/templates/view.php?id=TEMPLATE_ID` | GET | Member, Trainer, Admin | View template details |
| `/modules/templates/customize.php?id=TEMPLATE_ID` | GET/POST | Member, Trainer, Admin | Create plan from template |

## Testing Checklist

### Member Account Testing
- [ ] Login as member
- [ ] Navigate to Workout Templates
- [ ] View all 25 templates
- [ ] Filter by type (e.g., "Strength")
- [ ] Filter by difficulty (e.g., "Beginner")
- [ ] Search for a template (e.g., "5x5")
- [ ] Click "View Details" on a template
- [ ] Click "Use This Template"
- [ ] Create a custom plan from template
- [ ] Verify plan appears in Workout Plans list

### Trainer Account Testing
- [ ] Login as trainer
- [ ] Navigate to Workout Templates
- [ ] View all templates
- [ ] Click "Use & Customize"
- [ ] Select a member from dropdown
- [ ] Customize plan and create
- [ ] Verify plan assigned to selected member

### Admin Account Testing
- [ ] Login as admin
- [ ] Access all template features
- [ ] Test assigning templates to different members
- [ ] Assign trainers to plans
- [ ] Verify everything functions correctly

## SQL Queries for Verification

### Check Templates Loaded
```sql
SELECT template_name, template_type, difficulty_level, exercises_count 
FROM workout_templates 
ORDER BY template_type, difficulty_level;
```

### Check Plans from Templates
```sql
SELECT wp.plan_name, wt.template_name, wp.created_at
FROM workout_plans wp
LEFT JOIN workout_templates wt ON wp.template_id = wt.template_id
ORDER BY wp.created_at DESC;
```

### Check Template Popularity
```sql
SELECT template_name, template_type, popularity_score 
FROM workout_templates 
ORDER BY popularity_score DESC 
LIMIT 10;
```

## Troubleshooting

### Migration Won't Execute
- **Issue**: Permission denied or syntax error
- **Solution**: 
  1. Check that `level_up_fitness` database exists
  2. Ensure user has appropriate privileges
  3. Try executing individual CREATE TABLE statements first

### Templates Not Showing
- **Issue**: Empty template list
- **Solution**:
  1. Verify migration executed successfully
  2. Check: `SELECT COUNT(*) FROM workout_templates;`
  3. Ensure `is_active = 1` for templates

### Customize Button Not Working
- **Issue**: Button doesn't appear or link is broken
- **Solution**:
  1. Clear browser cache
  2. Check that user is properly authenticated
  3. Verify templates exist in database
  4. Check error logs for PHP errors

### Layout Issues
- **Issue**: Cards don't display properly
- **Solution**:
  1. Ensure Bootstrap CSS is loaded
  2. Check browser console for JavaScript errors
  3. Try in different browser
  4. Clear browser cache and refresh

## Future Enhancements

Potential features to add:
1. **Template Ratings**: Allow members to rate templates
2. **Custom Templates**: Let admins create custom templates
3. **Before/After Analytics**: Track member progress with templates
4. **Template Collections**: Group related templates (e.g., "Beginner Pack")
5. **Progress Tracking**: Monitor completion rates
6. **Social Features**: Share templates between members
7. **AI Recommendations**: Suggest templates based on goals
8. **Mobile App Integration**: Support for mobile devices

## Support

For issues or questions:
1. Check this guide's Troubleshooting section
2. Review database structure for accuracy
3. Check PHP error logs
4. Verify all files are in correct directories
5. Ensure proper file permissions

---

**Last Updated**: February 22, 2026
**Status**: ✅ Complete and Ready for Testing
