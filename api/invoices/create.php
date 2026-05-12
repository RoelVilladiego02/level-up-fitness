<?php
/**
 * API: Create Invoice
 * Endpoint: POST /api/invoices/create.php
 */

// Use clean API header instead of full header.php
require_once dirname(dirname(dirname(__FILE__))) . '/includes/api_header.php';

// ====================== ADMIN CHECK ======================
if (!isset($_SESSION['user_id'])) {
    ob_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$isAdmin = false;
if (isset($_SESSION['role']) && in_array(strtolower($_SESSION['role']), ['admin', 'administrator', 'superadmin'])) {
    $isAdmin = true;
} elseif (isset($_SESSION['user_role']) && in_array(strtolower($_SESSION['user_role']), ['admin', 'administrator', 'superadmin'])) {
    $isAdmin = true;
} elseif (function_exists('hasRole') && hasRole('admin')) {
    $isAdmin = true;
} elseif (function_exists('isAdmin') && isAdmin()) {
    $isAdmin = true;
}

if (!$isAdmin) {
    ob_clean();
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
// ========================================================

$memberId    = sanitize($_POST['member_id'] ?? '');
$description = sanitize($_POST['description'] ?? '');
$amount      = floatval($_POST['amount'] ?? 0);
$dueDate     = sanitize($_POST['due_date'] ?? '');
$notes       = sanitize($_POST['notes'] ?? '');

$errors = [];
if (empty($memberId))     $errors[] = 'Member required';
if (empty($description))  $errors[] = 'Description required';
if ($amount <= 0)         $errors[] = 'Amount must be greater than 0';
if (empty($dueDate))      $errors[] = 'Due date required';

if (!empty($errors)) {
    ob_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

try {
    $invoiceId = createInvoice($memberId, $amount, $description, $dueDate, 'Manual', $_SESSION['user_id']);
    
    if (!$invoiceId) {
        throw new Exception('Failed to create invoice');
    }
    
    // Add notes
    if (!empty($notes)) {
        $stmt = $pdo->prepare("UPDATE invoices SET notes = ? WHERE invoice_id = ?");
        $stmt->execute([$notes, $invoiceId]);
    }
    
    logAction($_SESSION['user_id'], 'INVOICE_CREATED', 'Invoices', 
              "Created invoice #$invoiceId for member $memberId");

    // Background email
    try {
        $stmt = $pdo->prepare("INSERT INTO background_tasks 
            (task_type, task_data, status, created_at) 
            VALUES ('send_invoice_email', ?, 'pending', NOW())");
        
        $taskData = json_encode([
            'invoice_id' => $invoiceId,
            'member_id'  => $memberId,
            'description'=> $description,
            'amount'     => $amount,
            'due_date'   => $dueDate
        ]);
        $stmt->execute([$taskData]);

        sendInvoiceEmailBackground($invoiceId, $memberId, $description, $amount, $dueDate);
        
    } catch (Exception $e) {
        error_log('Email queue failed: ' . $e->getMessage());
    }
    
    ob_clean();
    echo json_encode([
        'success'    => true,
        'invoice_id' => $invoiceId,
        'message'    => 'Invoice created successfully!'
    ]);
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}

// Background email function
function sendInvoiceEmailBackground($invoiceId, $memberId, $description, $amount, $dueDate) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT email, member_name FROM members WHERE member_id = ?");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$member || empty($member['email'])) return;

        $subject = 'New Invoice - Level Up Fitness';
        $message = "Hello " . htmlspecialchars($member['member_name']) . ",\n\n" .
                   "You have a new invoice:\n\n" .
                   "Invoice ID: " . $invoiceId . "\n" .
                   "Description: " . htmlspecialchars($description) . "\n" .
                   "Amount: " . formatCurrency($amount) . "\n" .
                   "Due Date: " . formatDate($dueDate) . "\n\n" .
                   "Please log in to view and pay.\n\nBest regards,\nLevel Up Fitness";

        sendEmailNotification($member['email'], $subject, $message, 'text');
    } catch (Exception $e) {
        error_log('Send invoice email failed: ' . $e->getMessage());
    }
}
?>