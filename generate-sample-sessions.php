<?php
/**
 * Generate Sample Training Sessions
 * Level Up Fitness - Gym Management System
 * 
 * Creates realistic training sessions linked to existing trainers and gyms
 */

require_once dirname(__FILE__) . '/config/config.php';
require_once dirname(__FILE__) . '/config/database.php';

echo "Starting sample training sessions generation...\n\n";

try {
    // Get all active trainers
    $trainersStmt = $pdo->prepare("SELECT trainer_id, trainer_name FROM trainers WHERE status = 'Active'");
    $trainersStmt->execute();
    $trainers = $trainersStmt->fetchAll();

    if (empty($trainers)) {
        echo "⚠ No active trainers found. Please create trainers first.\n";
        exit(1);
    }

    echo "✓ Found " . count($trainers) . " active trainers\n";

    // Get all gyms
    $gymsStmt = $pdo->prepare("SELECT gym_id, gym_name FROM gyms");
    $gymsStmt->execute();
    $gyms = $gymsStmt->fetchAll();

    if (empty($gyms)) {
        echo "⚠ No gyms found. Please create gyms first.\n";
        exit(1);
    }

    echo "✓ Found " . count($gyms) . " gyms\n\n";

    // Sample session templates
    $sessionTemplates = [
        ['name' => 'Morning HIIT Bootcamp', 'duration' => 45, 'capacity' => 15, 'description' => 'High-intensity interval training session for all fitness levels'],
        ['name' => 'Power Yoga Flow', 'duration' => 60, 'capacity' => 12, 'description' => 'Dynamic yoga session combining strength and flexibility'],
        ['name' => 'Strength Training 101', 'duration' => 60, 'capacity' => 10, 'description' => 'Introduction to weight training and proper form'],
        ['name' => 'Cardio Blast', 'duration' => 45, 'capacity' => 20, 'description' => 'High-energy cardiovascular workout session'],
        ['name' => 'Core & Abs', 'duration' => 30, 'capacity' => 25, 'description' => 'Focused abdominal and core strength training'],
        ['name' => 'Pilates Reformer Class', 'duration' => 50, 'capacity' => 8, 'description' => 'Pilates using reformer machines for toning'],
        ['name' => 'Spinning Cycles', 'duration' => 45, 'capacity' => 30, 'description' => 'Indoor cycling class with energetic music'],
        ['name' => 'CrossFit WOD', 'duration' => 60, 'capacity' => 12, 'description' => 'Workout of the day with functional movements'],
        ['name' => 'Boxing Fitness', 'duration' => 50, 'capacity' => 16, 'description' => 'Boxing techniques combined with cardio'],
        ['name' => 'Zumba Dance Party', 'duration' => 55, 'capacity' => 35, 'description' => 'Fun Latin-inspired dance fitness class'],
        ['name' => 'Stretching & Mobility', 'duration' => 45, 'capacity' => 20, 'description' => 'Improve flexibility and joint mobility'],
        ['name' => 'Personal Training Session', 'duration' => 60, 'capacity' => 1, 'description' => 'One-on-one personalized training'],
        ['name' => 'TRX Suspension Training', 'duration' => 50, 'capacity' => 12, 'description' => 'Full-body workout using suspension trainers'],
        ['name' => 'Kettlebell Conditioning', 'duration' => 45, 'capacity' => 14, 'description' => 'Dynamic kettlebell movements for conditioning'],
        ['name' => 'Aqua Aerobics', 'duration' => 45, 'capacity' => 18, 'description' => 'Low-impact aerobics in the pool'],
    ];

    $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
    $times = ['06:00', '07:00', '08:00', '09:00', '17:00', '18:00', '19:00', '20:00'];
    
    $sessionsCreated = 0;
    $sessionIds = [];

    // Generate sessions for next 60 days
    $startDate = date('Y-m-d', strtotime('+1 day'));
    $endDate = date('Y-m-d', strtotime('+60 days'));
    
    echo "Generating sessions from $startDate to $endDate...\n";
    echo "This will create realistic recurring sessions.\n\n";

    foreach ($trainers as $trainerIndex => $trainer) {
        // Each trainer gets 3-5 different session types
        $sessionCount = rand(3, 5);
        $assignedSessions = array_slice($sessionTemplates, $trainerIndex * 3, $sessionCount);
        
        foreach ($assignedSessions as $sessionIndex => $template) {
            // Assign specific days and times per trainer
            $dayOfWeek = $daysOfWeek[$sessionIndex % 7];
            $timeIndex = ($trainerIndex + $sessionIndex) % count($times);
            $time = $times[$timeIndex];
            
            // Get gym for this trainer (rotate through gyms)
            $gym = $gyms[$trainerIndex % count($gyms)];
            
            // Generate recurring sessions for this day
            $currentDate = new DateTime($startDate);
            $targetDayNum = array_search($dayOfWeek, $daysOfWeek);
            
            // Move to first occurrence of target day
            while ($currentDate->format('N') - 1 != $targetDayNum) {
                $currentDate->add(new DateInterval('P1D'));
            }
            
            // Create multiple sessions on this day of week
            while ($currentDate <= new DateTime($endDate)) {
                $sessionDate = $currentDate->format('Y-m-d');
                $sessionId = generateID('TRS');
                
                try {
                    $insertStmt = $pdo->prepare("
                        INSERT INTO training_sessions 
                        (session_id, session_name, trainer_id, gym_id, session_date, session_time, 
                         duration, max_capacity, description, status, created_at, updated_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                    ");
                    
                    $insertStmt->execute([
                        $sessionId,
                        $template['name'],
                        $trainer['trainer_id'],
                        $gym['gym_id'],
                        $sessionDate,
                        $time,
                        $template['duration'],
                        $template['capacity'],
                        $template['description'],
                        'Scheduled'
                    ]);
                    
                    $sessionsCreated++;
                    $sessionIds[] = $sessionId;
                    
                    echo "  ✓ Created: {$template['name']} - {$trainer['trainer_name']} - {$dayOfWeek} {$time} ({$sessionDate})\n";
                    
                } catch (Exception $e) {
                    echo "  ✗ Failed to create session: " . $e->getMessage() . "\n";
                }
                
                // Move to next week
                $currentDate->add(new DateInterval('P7D'));
            }
        }
    }

    echo "\n" . str_repeat("=", 60) . "\n";
    echo "✓ Successfully generated $sessionsCreated training sessions!\n";
    echo str_repeat("=", 60) . "\n\n";

    // Generate some sample attendees for past/completed sessions
    echo "Generating sample session attendees...\n";

    // Get members
    $membersStmt = $pdo->prepare("SELECT member_id FROM members WHERE status = 'Active' LIMIT 20");
    $membersStmt->execute();
    $members = $membersStmt->fetchAll(PDO::FETCH_COLUMN);

    if (!empty($members)) {
        $attendeesCreated = 0;
        
        // Get some scheduled sessions from the next 7 days
        $futureDate = date('Y-m-d', strtotime('+7 days'));
        $pastDate = date('Y-m-d', strtotime('+1 day'));
        
        $futureSessionsStmt = $pdo->prepare("
            SELECT session_id FROM training_sessions 
            WHERE session_date >= ? AND session_date <= ?
            LIMIT 30
        ");
        $futureSessionsStmt->execute([$pastDate, $futureDate]);
        $sessionsForAttendees = $futureSessionsStmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($sessionsForAttendees as $sessionId) {
            // Add 3-8 random members to each session
            $membersToAdd = array_slice($members, 0, rand(3, 8));
            
            foreach ($membersToAdd as $memberId) {
                try {
                    // Check if already enrolled
                    $checkStmt = $pdo->prepare("
                        SELECT COUNT(*) as count FROM training_session_attendees 
                        WHERE session_id = ? AND member_id = ?
                    ");
                    $checkStmt->execute([$sessionId, $memberId]);
                    $exists = $checkStmt->fetch()['count'];

                    if (!$exists) {
                        $attendeeId = generateID('ATND');
                        $attendeeStmt = $pdo->prepare("
                            INSERT INTO training_session_attendees 
                            (attendee_id, session_id, member_id, attendance_status, created_at)
                            VALUES (?, ?, ?, 'Present', NOW())
                        ");
                        $attendeeStmt->execute([$attendeeId, $sessionId, $memberId]);
                        $attendeesCreated++;
                    }
                } catch (Exception $e) {
                    // Silently fail on duplicate
                }
            }
        }

        echo "✓ Added $attendeesCreated attendees to sample sessions\n\n";
    }

    // Statistics
    echo "📊 SAMPLE DATA STATISTICS\n";
    echo str_repeat("-", 60) . "\n";
    
    $sessionCountStmt = $pdo->query("SELECT COUNT(*) as count FROM training_sessions");
    $totalSessions = $sessionCountStmt->fetch()['count'];
    
    $attendeeCountStmt = $pdo->query("SELECT COUNT(*) as count FROM training_session_attendees");
    $totalAttendees = $attendeeCountStmt->fetch()['count'];
    
    $trainerSessionsStmt = $pdo->query("
        SELECT trainer_id, COUNT(*) as count FROM training_sessions 
        GROUP BY trainer_id ORDER BY count DESC LIMIT 5
    ");
    $topTrainers = $trainerSessionsStmt->fetchAll();
    
    echo "Total Training Sessions: $totalSessions\n";
    echo "Total Attendees Registered: $totalAttendees\n";
    echo "\nTop Trainers by Session Count:\n";
    foreach ($topTrainers as $idx => $trainerData) {
        $trainerStmt = $pdo->prepare("SELECT trainer_name FROM trainers WHERE trainer_id = ?");
        $trainerStmt->execute([$trainerData['trainer_id']]);
        $trainerName = $trainerStmt->fetch()['trainer_name'];
        echo "  " . ($idx + 1) . ". $trainerName: " . $trainerData['count'] . " sessions\n";
    }

    echo "\n✓ Sample data generation completed successfully!\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}

/**
 * Generate unique ID with prefix
 */
function generateID($prefix) {
    $timestamp = time();
    $random = rand(100, 999);
    return $prefix . $timestamp . $random;
}

?>
