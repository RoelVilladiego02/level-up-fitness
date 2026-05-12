Access denied: You do not have permission to access this page.<?php
/**
 * Migration: Create Invoices Table
 * Level Up Fitness - Gym Management System
 * 
 * This migration creates the invoices table to track what members owe,
 * separating invoices (what's due) from payments (what's been paid).
 * 
 * Run: php migrations/create-invoices-table.php
 */

require_once dirname(dirname(__FILE__)) . '/config/config.php';
require_once dirname(dirname(__FILE__)) . '/config/database.php';

try {
    echo "Creating invoices table...\n";
    
    // Create invoices table
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS invoices (
        invoice_id VARCHAR(50) PRIMARY KEY,
        member_id VARCHAR(50) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        description VARCHAR(255) NOT NULL,
        invoice_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        due_date DATETIME NOT NULL,
        invoice_status ENUM('Draft', 'Pending', 'Partially Paid', 'Paid', 'Overdue', 'Cancelled') NOT NULL DEFAULT 'Pending',
        payment_method VARCHAR(50),
        notes TEXT,
        created_by VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
        INDEX idx_member_id (member_id),
        INDEX idx_status (invoice_status),
        INDEX idx_due_date (due_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($createTableSQL);
    echo "✓ Invoices table created successfully\n";
    
    // Create invoice_payments junction table to link payments to invoices
    $createPaymentsTableSQL = "
    CREATE TABLE IF NOT EXISTS invoice_payments (
        payment_id VARCHAR(50) PRIMARY KEY,
        invoice_id VARCHAR(50) NOT NULL,
        member_id VARCHAR(50) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        payment_method ENUM('Maya', 'Manual', 'Bank Transfer', 'GCash', 'Other') NOT NULL,
        payment_status ENUM('Pending', 'Processing', 'Paid', 'Failed', 'Cancelled') NOT NULL DEFAULT 'Pending',
        transaction_id VARCHAR(255),
        payment_date DATETIME,
        payment_proof_url VARCHAR(255),
        notes TEXT,
        created_by VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id) ON DELETE CASCADE,
        FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
        INDEX idx_invoice_id (invoice_id),
        INDEX idx_member_id (member_id),
        INDEX idx_status (payment_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    $pdo->exec($createPaymentsTableSQL);
    echo "✓ Invoice payments table created successfully\n";
    
    // Migrate existing payments to invoices (if any)
    $checkPaymentsSQL = "SELECT COUNT(*) as count FROM payments";
    $stmt = $pdo->query($checkPaymentsSQL);
    $paymentCount = $stmt->fetch()['count'];
    
    if ($paymentCount > 0) {
        echo "\nMigrating existing payments...\n";
        
        // For each existing payment, create a matching invoice and payment record
        $existingPayments = $pdo->query("SELECT * FROM payments ORDER BY payment_date DESC")->fetchAll();
        
        foreach ($existingPayments as $payment) {
            // Only migrate paid payments to maintain history
            if ($payment['payment_status'] === 'Paid') {
                $invoiceId = generateUniqueID(INVOICE_ID_PREFIX, 'invoices');
                $paymentId = $payment['payment_id'];
                
                // Create invoice record
                $insertInvoiceSQL = "
                INSERT INTO invoices 
                (invoice_id, member_id, amount, description, invoice_date, due_date, invoice_status, payment_method, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";
                
                $invoiceStmt = $pdo->prepare($insertInvoiceSQL);
                $invoiceStmt->execute([
                    $invoiceId,
                    $payment['member_id'],
                    $payment['amount'],
                    'Migrated from legacy payment system',
                    $payment['payment_date'],
                    date('Y-m-d', strtotime($payment['payment_date'] . ' +30 days')),
                    'Paid',
                    $payment['payment_method'],
                    $payment['created_by'] ?? 'system',
                    $payment['payment_date']
                ]);
                
                // Create payment record
                $insertPaymentSQL = "
                INSERT INTO invoice_payments
                (payment_id, invoice_id, member_id, amount, payment_method, payment_status, payment_date, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";
                
                $paymentStmt = $pdo->prepare($insertPaymentSQL);
                $paymentStmt->execute([
                    $paymentId,
                    $invoiceId,
                    $payment['member_id'],
                    $payment['amount'],
                    $payment['payment_method'],
                    'Paid',
                    $payment['payment_date'],
                    $payment['created_by'] ?? 'system',
                    $payment['payment_date']
                ]);
            }
        }
        
        echo "✓ Migrated {$paymentCount} existing payments\n";
    }
    
    // Create view for outstanding invoices (for easy querying)
    $createViewSQL = "
    CREATE OR REPLACE VIEW member_outstanding_invoices AS
    SELECT 
        i.invoice_id,
        i.member_id,
        i.amount,
        COALESCE(SUM(ip.amount), 0) as paid_amount,
        (i.amount - COALESCE(SUM(ip.amount), 0)) as outstanding_amount,
        i.due_date,
        CASE 
            WHEN i.invoice_status = 'Paid' THEN 'Paid'
            WHEN (i.amount - COALESCE(SUM(ip.amount), 0)) > 0 AND i.due_date < NOW() THEN 'Overdue'
            WHEN (i.amount - COALESCE(SUM(ip.amount), 0)) > 0 THEN 'Pending'
            ELSE i.invoice_status
        END as current_status,
        i.description,
        i.invoice_date
    FROM invoices i
    LEFT JOIN invoice_payments ip ON i.invoice_id = ip.invoice_id AND ip.payment_status = 'Paid'
    WHERE i.invoice_status != 'Cancelled'
    GROUP BY i.invoice_id;
    ";
    
    $pdo->exec($createViewSQL);
    echo "✓ Created member_outstanding_invoices view\n";
    
    echo "\n✓✓✓ Migration completed successfully! ✓✓✓\n";
    echo "\nNew tables/views created:\n";
    echo "  - invoices (tracks what members owe)\n";
    echo "  - invoice_payments (tracks payments made)\n";
    echo "  - member_outstanding_invoices (view for outstanding amounts)\n";
    
} catch (Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
