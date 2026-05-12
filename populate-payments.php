<?php
/**
 * Simulate Payments for Unpaid Invoices
 * Level Up Fitness - Gym Management System
 * 
 * This script creates payment records for existing unpaid invoices
 * using different payment methods and statuses.
 */

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "\n╔════════════════════════════════════════════════════════╗\n";
echo "║   Simulating Payments for Unpaid Invoices             ║\n";
echo "╚════════════════════════════════════════════════════════╝\n\n";

try {
    // Get all unpaid invoices
    $stmt = $pdo->query("
        SELECT invoice_id, member_id, amount, description, due_date, invoice_status
        FROM invoices 
        WHERE invoice_status IN ('Pending', 'Overdue', 'Partially Paid')
        ORDER BY invoice_date ASC
    ");
    $invoices = $stmt->fetchAll();

    if (empty($invoices)) {
        echo "❌ No unpaid invoices found.\n\n";
        exit(1);
    }

    echo "Found " . count($invoices) . " unpaid invoices. Processing payments...\n\n";

    // Available payment methods
    $paymentMethods = [
        'Cash',
        'Card',
        'GCash',
        'Bank Transfer',
        'Cheque',
        'Online - Maya',
        'Online - GCash',
        'Online - Credit Card'
    ];

    $paymentCount = 0;
    $totalProcessed = 0;
    $processedAmount = 0;

    foreach ($invoices as $invoice) {
        // Decide payment type: 70% full payment, 20% partial payment, 10% no payment (skip)
        $paymentTypeRand = rand(1, 100);
        
        if ($paymentTypeRand <= 10) {
            // Skip this invoice - no payment yet
            continue;
        }
        
        if ($paymentTypeRand <= 30) {
            // Partial payment (60-90% of invoice amount)
            $paymentPercentage = rand(60, 90) / 100;
            $paymentAmount = $invoice['amount'] * $paymentPercentage;
            $newInvoiceStatus = 'Partially Paid';
        } else {
            // Full payment
            $paymentAmount = $invoice['amount'];
            $newInvoiceStatus = 'Paid';
        }

        // Randomize payment method
        $paymentMethod = $paymentMethods[array_rand($paymentMethods)];
        
        // Determine payment status: 85% Paid, 10% Pending, 5% Overdue
        $statusRand = rand(1, 100);
        if ($statusRand <= 85) {
            $paymentStatus = 'Paid';
        } elseif ($statusRand <= 95) {
            $paymentStatus = 'Pending';
        } else {
            $paymentStatus = 'Overdue';
        }
        
        // Random payment date (before or on due date for realistic data)
        $dueDate = new DateTime($invoice['due_date']);
        $paymentDate = (clone $dueDate)->modify('-' . rand(0, 30) . ' days')->format('Y-m-d');

        // Create payment record in invoice_payments table
        $paymentId = generateID('PAY');
        
        $stmt = $pdo->prepare("
            INSERT INTO invoice_payments 
            (payment_id, invoice_id, member_id, amount, payment_method, payment_status, payment_date, notes, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $notes = "Payment for invoice {$invoice['invoice_id']}: {$invoice['description']}";
        if ($newInvoiceStatus === 'Partially Paid') {
            $notes .= " (Partial Payment)";
        }
        
        $stmt->execute([
            $paymentId,
            $invoice['invoice_id'],
            $invoice['member_id'],
            $paymentAmount,
            $paymentMethod,
            $paymentStatus,
            $paymentDate,
            $notes
        ]);

        // Update invoice status
        $updateStmt = $pdo->prepare("
            UPDATE invoices 
            SET invoice_status = ?, payment_method = ?, updated_at = NOW()
            WHERE invoice_id = ?
        ");
        $updateStmt->execute([$newInvoiceStatus, $paymentMethod, $invoice['invoice_id']]);

        $paymentCount++;
        $totalProcessed += $paymentAmount;
        
        echo "  ✓ " . str_pad($paymentId, 18) . " | {$invoice['invoice_id']} | {$paymentMethod}" . 
             " | ₱" . number_format($paymentAmount, 2) . " | {$paymentStatus} | {$newInvoiceStatus}\n";
    }

    echo "\n✅ Successfully created $paymentCount payment records!\n\n";
    
    // Show summary statistics
    echo "Summary Statistics:\n";
    echo str_repeat("─", 70) . "\n";
    echo "  Total Payments Created: $paymentCount\n";
    echo "  Total Amount Processed: ₱" . number_format($totalProcessed, 2) . "\n\n";
    
    // Show payment status breakdown
    $stmt = $pdo->query("SELECT 
        payment_status, 
        COUNT(*) as count, 
        SUM(amount) as total
    FROM invoice_payments 
    GROUP BY payment_status");
    
    $summary = $stmt->fetchAll();
    
    echo "Payment Status Breakdown:\n";
    echo str_repeat("─", 70) . "\n";
    foreach ($summary as $row) {
        echo "  {$row['payment_status']}: {$row['count']} payments - ₱" . number_format($row['total'], 2) . "\n";
    }
    
    // Show invoice status breakdown
    echo "\n\nInvoice Status Breakdown:\n";
    echo str_repeat("─", 70) . "\n";
    $stmt = $pdo->query("SELECT 
        invoice_status, 
        COUNT(*) as count, 
        SUM(amount) as total
    FROM invoices 
    GROUP BY invoice_status");
    
    $invoiceSummary = $stmt->fetchAll();
    foreach ($invoiceSummary as $row) {
        echo "  {$row['invoice_status']}: {$row['count']} invoices - ₱" . number_format($row['total'], 2) . "\n";
    }
    
    echo "\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    exit(1);
}
