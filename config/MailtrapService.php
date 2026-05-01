<?php
/**
 * Mailtrap Email Service
 * Level Up Fitness - Gym Management System
 * 
 * Handles all email sending through Mailtrap API
 */

require_once dirname(__FILE__) . '/mailtrap.php';

class MailtrapService {
    
    /**
     * Send email via Mailtrap API
     * 
     * @param array $to Recipient(s) - ['email' => 'name', 'email2' => 'name2']
     * @param string $subject Email subject
     * @param string $htmlBody HTML body content
     * @param string $textBody Plain text body (optional)
     * @param array $options Additional options (cc, bcc, attachments, etc.)
     * @return array Result ['success' => bool, 'message' => string, 'message_id' => string]
     */
    public static function send($to, $subject, $htmlBody, $textBody = '', $options = []) {
        
        // Validate inputs
        if (!MAILTRAP_ENABLED) {
            error_log('Mailtrap is disabled - email not sent');
            return ['success' => false, 'message' => 'Email service disabled'];
        }
        
        if (!self::validateCredentials()) {
            error_log('Mailtrap credentials not properly configured');
            return ['success' => false, 'message' => 'Email service not configured'];
        }
        
        // Prepare recipient array
        if (is_string($to)) {
            $toArray = [['email' => $to]];
        } else {
            $toArray = self::formatRecipients($to);
        }
        
        // Build email payload
        $payload = [
            'from' => [
                'email' => MAILTRAP_FROM_EMAIL,
                'name' => MAILTRAP_FROM_NAME
            ],
            'to' => $toArray,
            'subject' => $subject,
            'html' => $htmlBody,
        ];
        
        // Add plain text if provided
        if (!empty($textBody)) {
            $payload['text'] = $textBody;
        }
        
        // Add reply-to
        if (!empty(MAILTRAP_REPLY_TO_EMAIL)) {
            $payload['reply_to'] = [
                'email' => MAILTRAP_REPLY_TO_EMAIL,
                'name' => MAILTRAP_REPLY_TO_NAME
            ];
        }
        
        // Add CC if provided
        if (!empty($options['cc'])) {
            $payload['cc'] = self::formatRecipients($options['cc']);
        }
        
        // Add BCC if provided
        if (!empty($options['bcc'])) {
            $payload['bcc'] = self::formatRecipients($options['bcc']);
        }
        
        // Add custom headers if provided
        if (!empty($options['headers'])) {
            $payload['headers'] = $options['headers'];
        }
        
        // Add template variables for dynamic content (if using Mailtrap templates)
        if (!empty($options['template_variables'])) {
            $payload['template_variables'] = $options['template_variables'];
        }
        
        // Send email with retry logic
        $result = self::sendWithRetry($payload, MAILTRAP_RETRY_COUNT);
        
        // Log the result
        if ($result['success']) {
            error_log("Email sent successfully to: " . json_encode($to) . " | Message ID: " . $result['message_id']);
        } else {
            error_log("Email failed for: " . json_encode($to) . " | Error: " . $result['message']);
        }
        
        return $result;
    }
    
    /**
     * Send email with retry logic
     * 
     * @param array $payload Email payload
     * @param int $retries Number of retries
     * @return array Result
     */
    private static function sendWithRetry($payload, $retries = 3) {
        
        for ($attempt = 1; $attempt <= $retries; $attempt++) {
            $result = self::makeApiCall($payload);
            
            if ($result['success']) {
                return $result;
            }
            
            // If this is not the last attempt, wait before retrying
            if ($attempt < $retries) {
                error_log("Email send failed (attempt $attempt/$retries), retrying...");
                sleep(MAILTRAP_RETRY_DELAY);
            }
        }
        
        return $result; // Return last failed attempt
    }
    
    /**
     * Make API call to Mailtrap
     * 
     * @param array $payload Email payload
     * @return array Result
     */
    private static function makeApiCall($payload) {
        
        $url = MAILTRAP_API_BASE_URL . '/api/send';
        
        $headers = [
            'Authorization: Bearer ' . MAILTRAP_API_TOKEN,
            'Content-Type: application/json',
            'Accept: application/json'
        ];
        
        // Initialize cURL
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode(['emails' => [$payload]]),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        
        // Execute request
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);
        
        // Parse response
        if ($error) {
            return [
                'success' => false,
                'message' => 'cURL error: ' . $error,
                'message_id' => null
            ];
        }
        
        $decoded = json_decode($response, true);
        
        // Check for successful response
        if ($httpCode === 200 && !empty($decoded['success'])) {
            return [
                'success' => true,
                'message' => 'Email sent successfully',
                'message_id' => $decoded['message_ids'][0] ?? null
            ];
        }
        
        // Handle error response
        $errorMsg = $decoded['message'] ?? $decoded['error'] ?? 'Unknown error';
        return [
            'success' => false,
            'message' => 'API Error: ' . $errorMsg,
            'message_id' => null,
            'http_code' => $httpCode
        ];
    }
    
    /**
     * Validate Mailtrap credentials
     * 
     * @return bool
     */
    private static function validateCredentials() {
        return !empty(MAILTRAP_API_TOKEN) && 
               MAILTRAP_API_TOKEN !== 'YOUR_MAILTRAP_API_TOKEN' &&
               !empty(MAILTRAP_INBOX_ID) &&
               MAILTRAP_INBOX_ID !== 'YOUR_INBOX_ID';
    }
    
    /**
     * Format recipients array
     * Converts various input formats to Mailtrap format
     * 
     * @param mixed $recipients Email(s) to format
     * @return array Formatted recipients
     */
    private static function formatRecipients($recipients) {
        
        if (is_string($recipients)) {
            return [['email' => $recipients]];
        }
        
        if (is_array($recipients)) {
            $formatted = [];
            
            foreach ($recipients as $key => $value) {
                // Handle array of email strings
                if (is_numeric($key)) {
                    if (is_array($value) && !empty($value['email'])) {
                        $formatted[] = $value;
                    } else {
                        $formatted[] = ['email' => $value];
                    }
                } else {
                    // Handle associative array [email => name]
                    $formatted[] = [
                        'email' => $key,
                        'name' => $value
                    ];
                }
            }
            
            return $formatted;
        }
        
        return [];
    }
    
    /**
     * Send bulk emails
     * 
     * @param array $emails Array of email configurations
     * @return array Results for each email
     */
    public static function sendBulk($emails) {
        $results = [];
        
        foreach ($emails as $index => $email) {
            $results[$index] = self::send(
                $email['to'],
                $email['subject'],
                $email['html'],
                $email['text'] ?? '',
                $email['options'] ?? []
            );
        }
        
        return $results;
    }
    
    /**
     * Test email sending
     * Useful for verifying Mailtrap configuration
     * 
     * @param string $testEmail Email to send test to
     * @return array Result
     */
    public static function test($testEmail = '') {
        
        if (empty($testEmail)) {
            $testEmail = MAILTRAP_TEST_EMAIL ?? 'test@mailinator.com';
        }
        
        $htmlBody = '<html><body>';
        $htmlBody .= '<h2>Mailtrap Configuration Test</h2>';
        $htmlBody .= '<p><strong>Status:</strong> Email service is working correctly!</p>';
        $htmlBody .= '<p><strong>Timestamp:</strong> ' . date('Y-m-d H:i:s') . '</p>';
        $htmlBody .= '<p><strong>Application:</strong> Level Up Fitness</p>';
        $htmlBody .= '</body></html>';
        
        return self::send(
            $testEmail,
            'Mailtrap Configuration Test - Level Up Fitness',
            $htmlBody,
            'Mailtrap Configuration Test'
        );
    }
}

?>
