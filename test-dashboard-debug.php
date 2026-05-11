<?php
/**
 * Debug Dashboard Data Issue
 */

require_once dirname(__FILE__) . '/config/database.php';

echo "=== Dashboard Data Debug ===\n\n";

$tables = ['members', 'trainers', 'payments', 'sessions', 'workouts', 'classes', 'reservations', 'class_attendance'];

foreach ($tables as $table) {
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM `$table`");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo $table . ": " . ($result['count'] ?? 0) . " records\n";
    } catch (Exception $e) {
        echo $table . ": ERROR - " . $e->getMessage() . "\n";
    }
}

echo "\n=== Testing Admin Dashboard Queries ===\n\n";

// Test specific queries from admin dashboard
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM members");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total Members: " . ($result['count'] ?? 0) . "\n";
} catch (Exception $e) {
    echo "Members query error: " . $e->getMessage() . "\n";
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM members WHERE status = 'Active'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Active Members: " . ($result['count'] ?? 0) . "\n";
} catch (Exception $e) {
    echo "Active members query error: " . $e->getMessage() . "\n";
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM payments");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total Payments: " . ($result['count'] ?? 0) . "\n";
} catch (Exception $e) {
    echo "Payments query error: " . $e->getMessage() . "\n";
}

try {
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE payment_status = 'Paid'");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total Revenue (Paid): " . ($result['total'] ?? 0) . "\n";
} catch (Exception $e) {
    echo "Revenue query error: " . $e->getMessage() . "\n";
}

echo "\n=== Sample Data ===\n\n";

try {
    $stmt = $pdo->prepare("SELECT * FROM members LIMIT 3");
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Sample members:\n";
    foreach ($results as $row) {
        echo "  - " . ($row['member_name'] ?? $row['name'] ?? 'N/A') . " (ID: " . $row['member_id'] . ")\n";
    }
    if (empty($results)) {
        echo "  No records found\n";
    }
} catch (Exception $e) {
    echo "Members sample error: " . $e->getMessage() . "\n";
}

?>
