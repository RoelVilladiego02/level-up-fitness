<?php
/**
 * PDF Generator Service - Using DOMPDF
 * Level Up Fitness - Gym Management System
 * 
 * Generates professional PDF files from HTML content using DOMPDF library
 * Properly handles invoice PDFs for email attachments
 */

require_once dirname(__FILE__) . '/../vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

class PDFGenerator {
    
    /**
     * Generate PDF from HTML content using DOMPDF
     * Creates a proper PDF file that can be attached to emails
     * 
     * @param string $htmlContent HTML content to convert
     * @param string $filename Desired filename (without extension)
     * @return array Result ['success' => bool, 'file_path' => string, 'error' => string]
     */
    public static function generateFromHtml($htmlContent, $filename = 'document') {
        try {
            // Create temporary directory if it doesn't exist
            $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'level-up-fitness-invoices';
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            // Generate unique filename
            $uniqueName = $filename . '_' . time() . '_' . uniqid() . '.pdf';
            $filePath = $tempDir . DIRECTORY_SEPARATOR . $uniqueName;
            
            // Initialize DOMPDF
            $options = new Options();
            $options->set([
                'isRemoteEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
                'isHtml5ParserEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'isPhpEnabled' => false, // For security
            ]);
            
            $dompdf = new Dompdf($options);
            
            // Load HTML content
            $dompdf->loadHtml($htmlContent, 'UTF-8');
            
            // Set paper size and orientation
            $dompdf->setPaper('A4', 'portrait');
            
            // Render PDF
            $dompdf->render();
            
            // Get PDF content
            $pdfContent = $dompdf->output();
            
            // Save PDF to file
            if (file_put_contents($filePath, $pdfContent)) {
                return [
                    'success' => true,
                    'file_path' => $filePath,
                    'file_type' => 'application/pdf'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Failed to write PDF file to temporary directory'
                ];
            }
            
        } catch (Exception $e) {
            error_log('PDF Generation Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'PDF Generation failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate Invoice PDF specifically
     * Creates a professional PDF from payment details
     * 
     * @param array $payment Payment record from database
     * @return array Result ['success' => bool, 'file_path' => string, ...]
     */
    public static function generateInvoicePdf($payment) {
        try {
            // Create HTML content for invoice
            $htmlContent = self::createInvoiceHtml($payment);
            
            // Use the main PDF generation method
            $filename = 'invoice_' . $payment['payment_id'];
            return self::generateFromHtml($htmlContent, $filename);
            
        } catch (Exception $e) {
            error_log('Invoice PDF Generation Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to generate invoice PDF: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create HTML content for invoice
     * Generates professional HTML markup for PDF conversion
     * 
     * @param array $payment Payment details from database
     * @return string HTML content with styling
     */
    private static function createInvoiceHtml($payment) {
        $invoiceDate = isset($payment['payment_date']) ? date('Y-m-d', strtotime($payment['payment_date'])) : date('Y-m-d');
        
        // Determine status badge
        $status = htmlspecialchars($payment['payment_status']);
        $statusBadge = '';
        if ($status === 'Completed' || $status === 'completed') {
            $statusBadge = "<span style='background-color: #28a745; color: white; padding: 4px 10px; border-radius: 3px; font-size: 12px; font-weight: bold;'>COMPLETED</span>";
        } elseif ($status === 'Pending' || $status === 'pending') {
            $statusBadge = "<span style='background-color: #ffc107; color: black; padding: 4px 10px; border-radius: 3px; font-size: 12px; font-weight: bold;'>PENDING</span>";
        } else {
            $statusBadge = "<span style='background-color: #dc3545; color: white; padding: 4px 10px; border-radius: 3px; font-size: 12px; font-weight: bold;'>" . strtoupper($status) . "</span>";
        }
        
        // Format amount as currency
        $amount = isset($payment['amount']) ? floatval($payment['amount']) : 0;
        $formattedAmount = "PHP " . number_format($amount, 2);
        
        // Get payment method with fallback
        $paymentMethod = !empty($payment['payment_method']) ? htmlspecialchars($payment['payment_method']) : 'N/A';
        
        // Build complete HTML string
        $html = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
            color: #333;
        }
        .invoice { 
            max-width: 800px;
            background: white;
            padding: 40px;
            margin: 0 auto;
            border: 1px solid #ddd;
        }
        .header { 
            border-bottom: 3px solid #4A90E2;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #4A90E2;
            margin: 0;
            font-size: 28px;
            letter-spacing: -0.5px;
        }
        .header p {
            color: #666;
            margin: 5px 0 0 0;
            font-size: 14px;
        }
        .invoice-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        .invoice-info-col {
            flex: 1;
            margin-right: 20px;
        }
        .invoice-info-col:last-child {
            margin-right: 0;
        }
        .invoice-info-col h3 {
            color: #999;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 10px 0;
            letter-spacing: 0.5px;
        }
        .invoice-info-col p {
            margin: 5px 0;
            font-size: 14px;
            font-weight: bold;
        }
        .bill-to {
            background-color: #f9f9f9;
            padding: 15px;
            margin-bottom: 30px;
            border-left: 4px solid #4A90E2;
        }
        .bill-to h3 {
            color: #999;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 15px 0;
            letter-spacing: 0.5px;
        }
        .bill-to p {
            margin: 8px 0;
            font-size: 14px;
        }
        .bill-to strong {
            color: #333;
            display: block;
            margin-bottom: 10px;
            font-size: 15px;
        }
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
        }
        .invoice-table thead {
            background-color: #f0f0f0;
        }
        .invoice-table th {
            padding: 12px;
            text-align: left;
            font-weight: bold;
            color: #333;
            font-size: 12px;
            text-transform: uppercase;
            border-bottom: 2px solid #4A90E2;
        }
        .invoice-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }
        .invoice-table tbody tr:last-child td {
            border-bottom: 2px solid #4A90E2;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            background-color: #f9f9f9;
            font-weight: bold;
            font-size: 16px;
        }
        .payment-details {
            margin: 30px 0;
            background-color: #f9f9f9;
            padding: 20px;
            border-left: 4px solid #4A90E2;
        }
        .payment-details h3 {
            color: #999;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            margin: 0 0 15px 0;
            letter-spacing: 0.5px;
        }
        .payment-details p {
            margin: 8px 0;
            font-size: 14px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #999;
            font-size: 12px;
        }
        .footer p {
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <div class='invoice'>
        <!-- Header -->
        <div class='header'>
            <h1>INVOICE</h1>
            <p>Level Up Fitness - Gym Management System</p>
        </div>

        <!-- Invoice Info -->
        <div class='invoice-info'>
            <div class='invoice-info-col'>
                <h3>Invoice Number</h3>
                <p>" . htmlspecialchars($payment['payment_id']) . "</p>
            </div>
            <div class='invoice-info-col'>
                <h3>Invoice Date</h3>
                <p>" . $invoiceDate . "</p>
            </div>
            <div class='invoice-info-col'>
                <h3>Payment Status</h3>
                <p>" . $statusBadge . "</p>
            </div>
        </div>

        <!-- Bill To -->
        <div class='bill-to'>
            <h3>Bill To</h3>
            <strong>" . htmlspecialchars($payment['member_name']) . "</strong>
            <p>Email: " . htmlspecialchars($payment['email']) . "</p>
            <p>Phone: " . htmlspecialchars($payment['contact_number'] ?? 'N/A') . "</p>
            <p>Membership: " . htmlspecialchars($payment['membership_type'] ?? 'Standard') . "</p>
        </div>

        <!-- Invoice Table -->
        <table class='invoice-table'>
            <thead>
                <tr>
                    <th>Description</th>
                    <th style='text-align: right;'>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>" . $paymentMethod . " Payment - " . htmlspecialchars($payment['membership_type'] ?? 'Membership') . "</td>
                    <td class='text-right'>" . $formattedAmount . "</td>
                </tr>
                <tr class='total-row'>
                    <td style='text-align: right;'>TOTAL:</td>
                    <td class='text-right'>" . $formattedAmount . "</td>
                </tr>
            </tbody>
        </table>

        <!-- Payment Details -->
        <div class='payment-details'>
            <h3>Payment Information</h3>
            <p><strong>Payment Method:</strong> " . $paymentMethod . "</p>
            <p><strong>Payment Reference:</strong> " . htmlspecialchars($payment['payment_reference'] ?? 'N/A') . "</p>
            <p><strong>Payment Status:</strong> " . htmlspecialchars($payment['payment_status']) . "</p>
        </div>

        <!-- Footer -->
        <div class='footer'>
            <p>Thank you for your payment!</p>
            <p>For any questions, please contact us at support@levelupfitness.com</p>
            <p style='margin-top: 15px; border-top: 1px solid #ddd; padding-top: 10px;'>
                Level Up Fitness - Gym Management System<br>
                © 2024 All rights reserved
            </p>
        </div>
    </div>
</body>
</html>";
        
        return $html;
    }
    
    /**
     * Clean up temporary PDF files
     * Removes files older than 1 hour to prevent disk space issues
     * 
     * @return int Number of files deleted
     */
    public static function cleanupOldFiles() {
        $tempDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'level-up-fitness-invoices';
        $deleted = 0;
        
        if (!is_dir($tempDir)) {
            return 0;
        }
        
        $files = scandir($tempDir);
        $oneHourAgo = time() - 3600; // 1 hour
        
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $filePath = $tempDir . DIRECTORY_SEPARATOR . $file;
            if (is_file($filePath) && filemtime($filePath) < $oneHourAgo) {
                if (unlink($filePath)) {
                    $deleted++;
                }
            }
        }
        
        return $deleted;
    }
}
?>
