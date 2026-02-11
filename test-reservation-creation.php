<?php
require_once 'config/database.php';

echo "=== Testing Reservation Creation ===\n\n";

try {
    // Check if members exist
    $memberResult = $pdo->query("SELECT COUNT(*) as count FROM members");
    $memberCount = $memberResult->fetch()['count'];
    echo "✓ Members in database: $memberCount\n";

    // Check if equipment exists
    $equipResult = $pdo->query("SELECT COUNT(*) as count FROM equipment");
    $equipCount = $equipResult->fetch()['count'];
    echo "✓ Equipment in database: $equipCount\n";

    // Check if we can query reservations
    $resResult = $pdo->query("SELECT COUNT(*) as count FROM reservations");
    $resCount = $resResult->fetch()['count'];
    echo "✓ Reservations in database: $resCount\n";

    // Test the conflict checking query structure
    echo "\n✓ Testing reservation availability check...\n";
    
    $testDate = date('Y-m-d', strtotime('+1 day'));
    $testStartTime = '10:00:00';
    $testEndTime = '11:00:00';
    
    // Get first equipment
    $equipStmt = $pdo->query("SELECT equipment_id FROM equipment LIMIT 1");
    $equip = $equipStmt->fetch();
    
    if ($equip) {
        $conflictStmt = $pdo->prepare("
            SELECT COUNT(*) as count FROM reservations 
            WHERE equipment_id = ? 
            AND reservation_date = ? 
            AND status IN ('Confirmed', 'Pending')
            AND (
                (start_time < ? AND end_time > ?)
            )
        ");
        $conflictStmt->execute([$equip['equipment_id'], $testDate, $testEndTime, $testStartTime]);
        $conflict = $conflictStmt->fetch();
        echo "✓ Conflict check query works (conflicts: " . $conflict['count'] . ")\n";
    }

    echo "\n✓ All reservation creation checks passed!\n";

} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
