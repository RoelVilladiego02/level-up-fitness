<?php
/**
 * Generate Sample Training Sessions with Member Data & Workout Plans
 * Level Up Fitness - Gym Management System
 * 
 * Features:
 * - Generate realistic member samples (50-100 members)
 * - Create workout plans for members based on templates
 * - Align training sessions with workout templates
 * - Enroll members in training sessions
 * - Link trainers to members
 */

require_once dirname(__FILE__) . '/config/config.php';
require_once dirname(__FILE__) . '/config/database.php';

echo "\n" . str_repeat("=", 70) . "\n";
echo "  GENERATE TRAINING SESSIONS WITH MEMBERS & WORKOUT PLANS\n";
echo str_repeat("=", 70) . "\n\n";

function generateID($prefix) {
    $timestamp = time();
    $random = rand(100, 999);
    return $prefix . $timestamp . $random;
}

function generatePhoneNumber() {
    // Generate Philippine phone number format
    return '0917' . rand(1000000, 9999999);
}

try {
    // ========================================
    // 1. VERIFY EXISTING DATA
    // ========================================
    echo "📋 STEP 1: Verifying existing data...\n";
    echo str_repeat("-", 70) . "\n";
    
    $trainers = $pdo->query("SELECT trainer_id, trainer_name, specialization FROM trainers WHERE status = 'Active'")->fetchAll();
    if (empty($trainers)) {
        echo "❌ No active trainers found. Please create trainers first.\n";
        exit(1);
    }
    echo "✓ Found " . count($trainers) . " active trainers\n";

    $gyms = $pdo->query("SELECT gym_id, gym_name FROM gyms")->fetchAll();
    if (empty($gyms)) {
        echo "❌ No gyms found. Please create gyms first.\n";
        exit(1);
    }
    echo "✓ Found " . count($gyms) . " gyms\n";

    $templates = $pdo->query("SELECT template_id, template_name, template_type, difficulty_level, goal FROM workout_templates WHERE is_active = 1")->fetchAll();
    if (empty($templates)) {
        echo "❌ No active workout templates found. Please run populate-templates.php first.\n";
        exit(1);
    }
    echo "✓ Found " . count($templates) . " active workout templates\n\n";

    // ========================================
    // 2. GENERATE MEMBER SAMPLES
    // ========================================
    echo "👥 STEP 2: Generating sample members...\n";
    echo str_repeat("-", 70) . "\n";

    $firstNames = ['John', 'Sarah', 'Michael', 'Emily', 'David', 'Jessica', 'Robert', 'Lisa', 'James', 'Maria', 
                   'Christopher', 'Anna', 'Daniel', 'Jennifer', 'Matthew', 'Amanda', 'Anthony', 'Michelle', 'Mark', 'Elizabeth',
                   'Donald', 'Patricia', 'Steven', 'Linda', 'Paul', 'Barbara', 'Andrew', 'Debra', 'Joshua', 'Nancy',
                   'Carlos', 'Sofia', 'Luis', 'Isabella', 'Miguel', 'Sophia', 'Jorge', 'Julia', 'Francisco', 'Angela'];
    
    $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Martinez', 'Rodriguez', 'Santos', 'Reyes',
                  'Gonzalez', 'Cruz', 'Fernandez', 'Perez', 'Castro', 'Lopez', 'Rivera', 'Morales', 'Vega', 'Romero'];

    $membershipTypes = ['Monthly', 'Quarterly', 'Annual'];
    $memberCount = 75; // Generate 75 members
    $insertedMembers = 0;

    $userInsertStmt = $pdo->prepare("INSERT INTO users (email, password, user_type) VALUES (?, ?, ?)");
    $memberInsertStmt = $pdo->prepare("
        INSERT INTO members (member_id, user_id, member_name, contact_number, email, membership_type, join_date, trainer_id, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $memberIds = [];
    $membersByTrainer = array_fill_keys(array_column($trainers, 'trainer_id'), []);

    for ($i = 0; $i < $memberCount; $i++) {
        $firstName = $firstNames[array_rand($firstNames)];
        $lastName = $lastNames[array_rand($lastNames)];
        $memberName = "$firstName $lastName";
        $email = strtolower(str_replace(' ', '.', $memberName)) . $i . '@member.levelupfitness.com';
        $phone = generatePhoneNumber();
        $membershipType = $membershipTypes[array_rand($membershipTypes)];
        $joinDate = date('Y-m-d', strtotime('-' . rand(1, 180) . ' days'));
        
        // Assign to a random trainer (80% chance) or null (20%)
        $trainer = rand(1, 100) <= 80 ? $trainers[array_rand($trainers)] : null;
        $trainerId = $trainer ? $trainer['trainer_id'] : null;

        try {
            // Create user account
            $hashedPassword = password_hash('password123', PASSWORD_BCRYPT);
            $userInsertStmt->execute([$email, $hashedPassword, 'member']);
            $userId = $pdo->lastInsertId();

            // Create member
            $memberId = generateID('MEM');
            $memberInsertStmt->execute([$memberId, $userId, $memberName, $phone, $email, $membershipType, $joinDate, $trainerId, 'Active']);
            $insertedMembers++;
            $memberIds[] = $memberId;
            
            if ($trainerId) {
                $membersByTrainer[$trainerId][] = $memberId;
            }
        } catch (Exception $e) {
            // Skip duplicates
        }
    }

    echo "✓ Created $insertedMembers member accounts\n";
    echo "✓ Assigned " . array_sum(array_map('count', $membersByTrainer)) . " members to trainers\n\n";

    // ========================================
    // 3. CREATE WORKOUT PLANS FOR MEMBERS
    // ========================================
    echo "📝 STEP 3: Creating workout plans for members...\n";
    echo str_repeat("-", 70) . "\n";

    $workoutPlanInsertStmt = $pdo->prepare("
        INSERT INTO workout_plans 
        (workout_plan_id, template_id, member_id, trainer_id, plan_name, weekly_schedule, plan_details, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $plansCreated = 0;
    $memberWeeklySchedules = [
        'Monday, Wednesday, Friday',
        'Tuesday, Thursday, Saturday',
        'Monday, Tuesday, Wednesday, Thursday',
        'Monday through Friday',
        'Monday, Wednesday, Friday, Sunday',
        'Tuesday, Thursday',
        'Daily',
        'Monday, Wednesday, Friday, Saturday'
    ];

    foreach ($memberIds as $memberId) {
        // Find trainer for this member
        $memberTrainer = null;
        foreach ($membersByTrainer as $trainerId => $members) {
            if (in_array($memberId, $members)) {
                $memberTrainer = $trainerId;
                break;
            }
        }

        // Assign 1-2 workout templates per member
        $templateCount = rand(1, 2);
        $selectedTemplates = array_slice($templates, 0, $templateCount);

        foreach ($selectedTemplates as $template) {
            try {
                $workoutPlanId = generateID('WKP');
                $planName = $memberName = "Custom " . $template['template_name'] . " Plan";
                $weeklySchedule = $memberWeeklySchedules[array_rand($memberWeeklySchedules)];
                
                // Create detailed plan
                $planDetails = json_encode([
                    'template_type' => $template['template_type'],
                    'difficulty_level' => $template['difficulty_level'],
                    'goal' => $template['goal'],
                    'weekly_schedule' => $weeklySchedule,
                    'notes' => 'Personal plan created on ' . date('Y-m-d'),
                    'progress_tracking' => 'In Progress'
                ]);

                $workoutPlanInsertStmt->execute([
                    $workoutPlanId,
                    $template['template_id'],
                    $memberId,
                    $memberTrainer,
                    $planName,
                    $weeklySchedule,
                    $planDetails
                ]);
                $plansCreated++;
            } catch (Exception $e) {
                // Skip on error
            }
        }
    }

    echo "✓ Created $plansCreated workout plans\n\n";

    // ========================================
    // 4. CREATE ALIGNED TRAINING SESSIONS
    // ========================================
    echo "🏋️  STEP 4: Creating training sessions aligned with templates...\n";
    echo str_repeat("-", 70) . "\n";

    $sessionInsertStmt = $pdo->prepare("
        INSERT INTO training_sessions 
        (session_name, trainer_id, gym_id, session_date, session_time, duration, max_capacity, description, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    $sessionTemplates = [
        ['tpl_id' => 'tpl_001', 'name' => 'Beginner Full Body', 'time' => '07:00', 'capacity' => 15, 'duration' => 60],
        ['tpl_id' => 'tpl_002', 'name' => 'Push Pull Legs', 'time' => '18:00', 'capacity' => 12, 'duration' => 75],
        ['tpl_id' => 'tpl_003', 'name' => 'HIIT Cardio Blast', 'time' => '06:00', 'capacity' => 20, 'duration' => 45],
        ['tpl_id' => 'tpl_004', 'name' => 'Upper Lower Split', 'time' => '19:00', 'capacity' => 14, 'duration' => 60],
        ['tpl_id' => 'tpl_005', 'name' => 'Advanced Hypertrophy', 'time' => '17:00', 'capacity' => 10, 'duration' => 90],
        ['tpl_id' => 'tpl_006', 'name' => 'Strength Foundation', 'time' => '08:00', 'capacity' => 12, 'duration' => 60],
        ['tpl_id' => 'tpl_007', 'name' => 'Core & Stability', 'time' => '09:00', 'capacity' => 25, 'duration' => 45],
        ['tpl_id' => 'tpl_009', 'name' => 'Flexibility & Mobility', 'time' => '10:00', 'capacity' => 18, 'duration' => 45],
    ];

    $timeSlots = ['06:00', '07:00', '08:00', '09:00', '10:00', '17:00', '18:00', '19:00', '20:00'];
    $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $sessionsCreated = 0;
    $sessionIds = [];

    // Generate sessions for next 30 days
    for ($day = 1; $day <= 30; $day++) {
        $sessionDate = date('Y-m-d', strtotime("+$day days"));
        $dayNum = date('N', strtotime($sessionDate));

        // Skip Sunday (dayNum = 7)
        if ($dayNum == 7) continue;

        // Generate 3-4 sessions per day
        $sessionCountPerDay = rand(3, 4);

        for ($s = 0; $s < $sessionCountPerDay; $s++) {
            $trainer = $trainers[array_rand($trainers)];
            $gym = $gyms[array_rand($gyms)];
            $sessionTemplate = $sessionTemplates[array_rand($sessionTemplates)];
            $time = $timeSlots[array_rand($timeSlots)];
            $status = rand(1, 100) <= 70 ? 'Scheduled' : 'Ongoing';

            try {
                $sessionInsertStmt->execute([
                    $sessionTemplate['name'],
                    $trainer['trainer_id'],
                    $gym['gym_id'],
                    $sessionDate,
                    $time,
                    $sessionTemplate['duration'],
                    $sessionTemplate['capacity'],
                    'Professional ' . $sessionTemplate['name'] . ' session with ' . $trainer['trainer_name'],
                    $status
                ]);
                $sessionsCreated++;
                $sessionIds[] = $pdo->lastInsertId();
            } catch (Exception $e) {
                // Skip on error
            }
        }
    }

    echo "✓ Created $sessionsCreated training sessions\n\n";

    // ========================================
    // 5. ENROLL MEMBERS IN TRAINING SESSIONS
    // ========================================
    echo "📍 STEP 5: Enrolling members in training sessions...\n";
    echo str_repeat("-", 70) . "\n";

    $attendeeInsertStmt = $pdo->prepare("
        INSERT INTO training_session_attendees 
        (session_id, member_id, attendance_status, created_at)
        VALUES (?, ?, ?, NOW())
    ");

    $enrollmentInsertStmt = $pdo->prepare("
        INSERT INTO training_session_attendees 
        (session_id, member_id, attendance_status, created_at)
        VALUES (?, ?, 'Present', NOW())
    ");

    $enrollmentsCreated = 0;
    $duplicatesSkipped = 0;

    // Get future sessions (next 7 days)
    $futureDate = date('Y-m-d', strtotime('+7 days'));
    $startDate = date('Y-m-d', strtotime('+1 day'));

    $futureSessionsStmt = $pdo->prepare("
        SELECT session_id FROM training_sessions 
        WHERE session_date >= ? AND session_date <= ?
        ORDER BY session_date, session_time
        LIMIT 50
    ");
    $futureSessionsStmt->execute([$startDate, $futureDate]);
    $sessionsForEnrollment = $futureSessionsStmt->fetchAll(PDO::FETCH_COLUMN);

    foreach ($sessionsForEnrollment as $sessionId) {
        // Enroll 3-8 random members per session
        $membersPerSession = rand(3, 8);
        $membersCopy = $memberIds;
        shuffle($membersCopy);
        $shuffledMembers = array_slice($membersCopy, 0, $membersPerSession);

        foreach ($shuffledMembers as $memberId) {
            try {
                // Check if already enrolled
                $checkStmt = $pdo->prepare("
                    SELECT COUNT(*) as count FROM training_session_attendees 
                    WHERE session_id = ? AND member_id = ?
                ");
                $checkStmt->execute([$sessionId, $memberId]);
                $exists = $checkStmt->fetch()['count'];

                if (!$exists) {
                    $enrollmentInsertStmt->execute([$sessionId, $memberId]);
                    $enrollmentsCreated++;
                } else {
                    $duplicatesSkipped++;
                }
            } catch (Exception $e) {
                // Skip duplicate enrollments
            }
        }
    }

    echo "✓ Enrolled $enrollmentsCreated members in training sessions\n";
    if ($duplicatesSkipped > 0) {
        echo "⊘ Skipped $duplicatesSkipped duplicate enrollments\n";
    }
    echo "\n";

    // ========================================
    // 6. DISPLAY SUMMARY STATISTICS
    // ========================================
    echo str_repeat("=", 70) . "\n";
    echo "  📊 DATA GENERATION SUMMARY\n";
    echo str_repeat("=", 70) . "\n";

    $totalMembersDb = $pdo->query("SELECT COUNT(*) as count FROM members")->fetch()['count'];
    $totalPlansDb = $pdo->query("SELECT COUNT(*) as count FROM workout_plans")->fetch()['count'];
    $totalSessionsDb = $pdo->query("SELECT COUNT(*) as count FROM training_sessions")->fetch()['count'];
    $totalEnrollmentsDb = $pdo->query("SELECT COUNT(*) as count FROM training_session_attendees")->fetch()['count'];

    echo "\n📋 STATISTICS:\n";
    echo "├─ Total Members: $totalMembersDb\n";
    echo "├─ Members Generated: $insertedMembers\n";
    echo "├─ Workout Plans Created: $plansCreated\n";
    echo "├─ Total Workout Plans in DB: $totalPlansDb\n";
    echo "├─ Training Sessions Created: $sessionsCreated\n";
    echo "├─ Total Sessions in DB: $totalSessionsDb\n";
    echo "├─ Session Enrollments: $enrollmentsCreated\n";
    echo "└─ Total Enrollments in DB: $totalEnrollmentsDb\n";

    echo "\n👥 MEMBER ASSIGNMENTS:\n";
    $assignedCount = array_sum(array_map('count', $membersByTrainer));
    echo "├─ Members Assigned to Trainers: $assignedCount\n";
    echo "├─ Unassigned Members: " . ($insertedMembers - $assignedCount) . "\n";
    echo "└─ Trainer Assignment Coverage: " . round(($assignedCount / $insertedMembers) * 100) . "%\n";

    echo "\n" . str_repeat("=", 70) . "\n";
    echo "  ✅ SAMPLE DATA GENERATION COMPLETE!\n";
    echo str_repeat("=", 70) . "\n\n";

    echo "📍 NEXT STEPS:\n";
    echo "├─ View Members: http://localhost/level-up-fitness/modules/members/\n";
    echo "├─ View Trainers: http://localhost/level-up-fitness/modules/trainers/\n";
    echo "├─ View Sessions: http://localhost/level-up-fitness/modules/sessions/\n";
    echo "├─ View Workouts: http://localhost/level-up-fitness/modules/workouts/\n";
    echo "└─ View Templates: http://localhost/level-up-fitness/modules/templates/\n\n";

} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "📍 Location: " . $e->getFile() . " (Line " . $e->getLine() . ")\n\n";
    exit(1);
}
?>
