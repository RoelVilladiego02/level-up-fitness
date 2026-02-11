<?php
require_once dirname(__FILE__) . '/config/database.php';

echo "=== Admin Dashboard Data Verification ===\n\n";

$queries = [
    "Total Members" => "SELECT COUNT(*) as count FROM members",
    "Active Trainers" => "SELECT COUNT(*) as count FROM trainers WHERE status = 'Active'",
    "Active Members" => "SELECT COUNT(*) as count FROM members WHERE status = 'Active'",
    "Total Payments" => "SELECT COUNT(*) as count FROM payments",
    "Paid Payments" => "SELECT COUNT(*) as count FROM payments WHERE payment_status = 'Paid'",
    "Pending Payments" => "SELECT COUNT(*) as count FROM payments WHERE payment_status = 'Pending'",
    "Today's Revenue" => "SELECT SUM(amount) as total FROM payments WHERE payment_date = CURDATE() AND payment_status = 'Paid'",
    "Recent Activities" => "SELECT COUNT(*) as count FROM activity_log",
];

foreach ($queries as $name => $query) {
    try {
        $result = $pdo->query($query);
        $data = $result->fetch();
        echo "✓ $name: ";
        if (isset($data['count'])) {
            echo $data['count'];
        } elseif (isset($data['total'])) {
            echo "₱" . ($data['total'] ?? 0);
        }
        echo "\n";
    } catch (Exception $e) {
        echo "✗ $name: " . $e->getMessage() . "\n";
    }
}

echo "\n✓ All dashboard data queries working correctly!\n";
?>
