<?php
/**
 * Generate Sample Training Sessions - Simple Version
 * Level Up Fitness - Gym Management System
 */

require_once dirname(__FILE__) . '/config/config.php';
require_once dirname(__FILE__) . '/config/database.php';

echo "=== Generating Sample Training Sessions ===\n\n";

try {
    // Get existing trainers
    $trainers = $pdo->query("SELECT trainer_id, trainer_name, specialization FROM trainers WHERE status = 'Active' LIMIT 5")->fetchAll();
    
    if (empty($trainers)) {
        echo "❌ No active trainers found.\n";
        exit(1);
    }

    // Get existing gyms
    $gyms = $pdo->query("SELECT gym_id, gym_name FROM gyms LIMIT 3")->fetchAll();
    
    if (empty($gyms)) {
        echo "❌ No gyms found.\n";
        exit(1);
    }

    echo "✓ Found " . count($trainers) . " trainers\n";
    echo "✓ Found " . count($gyms) . " gyms\n";

    // Sample session templates
    $sessionTemplates = [
        ['name' => 'Morning Strength Training', 'desc' => 'Build strength with compound movements', 'duration' => 60],
        ['name' => 'HIIT Cardio Blast', 'desc' => 'High-intensity interval training for maximum calorie burn', 'duration' => 45],
        ['name' => 'Yoga Flow', 'desc' => 'Relaxing and flexible yoga session', 'duration' => 60],
        ['name' => 'Core Strengthening', 'desc' => 'Focus on core stability and strength', 'duration' => 45],
        ['name' => 'Spinning Class', 'desc' => 'Indoor cycling workout set to music', 'duration' => 50],
        ['name' => 'Pilates Reformer', 'desc' => 'Advanced pilates using reformer equipment', 'duration' => 50],
        ['name' => 'Boxing Training', 'desc' => 'Learn boxing techniques and cardio', 'duration' => 60],
        ['name' => 'Full Body Bootcamp', 'desc' => 'Complete full body workout', 'duration' => 60],
        ['name' => 'Flexibility & Stretching', 'desc' => 'Improve flexibility and reduce soreness', 'duration' => 45],
        ['name' => 'Power Lifting', 'desc' => 'Advanced powerlifting techniques', 'duration' => 90],
    ];

    $timeSlots = ['06:00', '07:00', '08:00', '09:00', '10:00', '16:00', '17:00', '18:00', '19:00', '20:00'];
    $capacities = [15, 20, 25, 30];
    $statuses = ['Scheduled', 'Scheduled', 'Scheduled', 'Ongoing'];

    $inserted = 0;
    $insertStmt = $pdo->prepare("
        INSERT INTO training_sessions 
        (session_name, trainer_id, gym_id, session_date, session_time, duration, max_capacity, description, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");

    // Generate sessions for next 14 days
    for ($day = 0; $day <= 14; $day++) {
        $sessionDate = date('Y-m-d', strtotime("+$day days"));
        $dayOfWeek = date('N', strtotime($sessionDate));

        // Skip Sundays
        if ($dayOfWeek == 7) continue;

        // 3-5 sessions per day
        $count = rand(3, 5);
        
        for ($i = 0; $i < $count; $i++) {
            $trainer = $trainers[array_rand($trainers)];
            $gym = $gyms[array_rand($gyms)];
            $template = $sessionTemplates[array_rand($sessionTemplates)];
            $time = $timeSlots[array_rand($timeSlots)];
            $capacity = $capacities[array_rand($capacities)];
            $status = $statuses[array_rand($statuses)];

            try {
                $insertStmt->execute([
                    $template['name'],
                    $trainer['trainer_id'],
                    $gym['gym_id'],
                    $sessionDate,
                    $time,
                    $template['duration'],
                    $capacity,
                    $template['desc'],
                    $status
                ]);
                $inserted++;
            } catch (Exception $e) {
                // Skip duplicates
            }
        }
    }

    echo "\n✓ Inserted: $inserted training sessions\n";
    echo "\nSession details:\n";
    echo "- Date Range: Today to +14 days\n";
    echo "- Trainers: " . count($trainers) . "\n";
    echo "- Gyms: " . count($gyms) . "\n";
    echo "- Sessions per day: 3-5\n";
    echo "\n✅ Sample data generated successfully!\n";
    echo "View at: http://localhost/level-up-fitness/modules/sessions/\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
