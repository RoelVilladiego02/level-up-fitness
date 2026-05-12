<?php
/**
 * Generate Sample Invoices for Testing
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║   Generating Sample Invoices for Testing               ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

try {
    // Get all active members
    $stmt = $pdo->query("SELECT member_id, member_name FROM members WHERE status = 'Active' ORDER BY member_id");
    $members = $stmt->fetchAll();

    if (empty($members)) {
        echo "❌ No active members found.\n\n";
        exit(1);
    }

    echo "Creating invoices for " . count($members) . " members...\n\n";

    $invoiceCount = 0;

    foreach ($members as $member) {
        // Create 2-3 invoices per member
        $numInvoices = rand(2, 3);
        
        for ($i = 0; $i < $numInvoices; $i++) {
            $amount = 2500 + rand(0, 5000);
            $daysOffset = 30 + ($i * 30);
            $dueDate = date('Y-m-d', strtotime("+$daysOffset days"));
            
            $descriptions = [
                'Monthly Membership',
                'Personal Training Session',
                'Group Class Package',
                'Annual Membership',
                'Equipment Access',
                'Nutrition Consultation'
            ];
            $description = $descriptions[array_rand($descriptions)];
            
            $invoiceId = generateID('INV');
            
            $stmt = $pdo->prepare("
                INSERT INTO invoices 
                (invoice_id, member_id, amount, description, due_date, invoice_status, payment_method, created_by, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $invoiceId,
                $member['member_id'],
                $amount,
                $description,
                $dueDate,
                'Pending',
                'Maya',
                'admin-1'
            ]);
            
            $invoiceCount++;
            echo "  ✓ Created $invoiceId for {$member['member_name']} - ₱$amount - Due: $dueDate\n";
        }
    }

    echo "\n✅ Generated $invoiceCount sample invoices successfully!\n\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
