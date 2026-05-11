<?php
/**
 * Maya Payment Service
 * External Payment Processing Module - Sandbox/Testing Environment
 * Level Up Fitness - Gym Management System
 */

class MayaPaymentService {
    
    // Maya API Configuration
    private $apiKey;
    private $apiSecret;
    private $apiUrl;
    private $environment;
    private $merchantId;
    private $merchantName;
    private $callbackUrl;
    private $webhookSecret;
    
    // Payment Gateway Configuration
    private $config;
    
    /**
     * Constructor
     * Initialize Maya Payment Service with configuration
     */
    public function __construct($environment = 'sandbox') {
        $this->environment = $environment;
        $this->loadConfiguration();
    }
    
    /**
     * Load Configuration from payment gateway config file
     */
    private function loadConfiguration() {
        $configFile = dirname(__FILE__) . '/payment-gateway.php';
        
        if (!file_exists($configFile)) {
            throw new Exception('Payment gateway configuration file not found');
        }
        
        $this->config = require $configFile;
        
        // Set environment-specific configuration
        if (!isset($this->config['maya'][$this->environment])) {
            throw new Exception("Environment '{$this->environment}' not configured");
        }
        
        $mayaConfig = $this->config['maya'][$this->environment];
        
        $this->apiKey = $mayaConfig['api_key'];
        $this->apiSecret = $mayaConfig['api_secret'];
        $this->apiUrl = $mayaConfig['api_url'];
        $this->merchantId = $mayaConfig['merchant_id'];
        $this->merchantName = $mayaConfig['merchant_name'];
        $this->callbackUrl = $mayaConfig['callback_url'];
        $this->webhookSecret = $mayaConfig['webhook_secret'];
    }
    
    /**
     * Create Payment Request
     * Initialize a new payment transaction with Maya
     * 
     * @param array $paymentData - Payment details
     * @return array - Payment checkout link and transaction ID
     */
    public function createPaymentRequest($paymentData) {
        try {
            // Validate payment data
            $this->validatePaymentData($paymentData);
            
            // Generate transaction ID
            $transactionId = $this->generateTransactionId();
            
            // Prepare payment payload
            $payload = $this->buildPaymentPayload($paymentData, $transactionId);
            
            // Send request to Maya API
            $response = $this->sendRequest('POST', '/api/v1/checkout/create', $payload);
            
            // Handle response
            if ($response['success'] === true) {
                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'checkout_url' => $response['checkout_url'],
                    'reference_number' => $response['reference_number'],
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s'),
                    'response' => $response
                ];
            } else {
                throw new Exception('Maya API Error: ' . ($response['error'] ?? 'Unknown error'));
            }
            
        } catch (Exception $e) {
            error_log('Maya Payment Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status' => 'error'
            ];
        }
    }
    
    /**
     * Validate Payment Data
     */
    private function validatePaymentData($data) {
        if (empty($data['member_id'])) {
            throw new Exception('Member ID is required');
        }
        
        if (empty($data['amount']) || $data['amount'] <= 0) {
            throw new Exception('Valid amount is required');
        }
        
        if (empty($data['description'])) {
            throw new Exception('Payment description is required');
        }
    }
    
    /**
     * Generate Transaction ID
     * Format: MAYA-TIMESTAMP-RANDOM
     */
    private function generateTransactionId() {
        return 'MAYA-' . time() . '-' . substr(uniqid(mt_rand(), true), 0, 8);
    }
    
    /**
     * Build Payment Payload for Maya API
     */
    private function buildPaymentPayload($paymentData, $transactionId) {
        $amount = floatval($paymentData['amount']);
        
        // Format amount for Maya (in centavos if applicable)
        $amountInCentavos = intval($amount * 100);
        
        return [
            // Request Details
            'request_id' => uniqid(),
            'reference_number' => $transactionId,
            'timestamp' => date('c'), // ISO 8601 format
            
            // Merchant Info
            'merchant_id' => $this->merchantId,
            'merchant_name' => $this->merchantName,
            
            // Amount & Currency
            'total_amount' => $amount,
            'amount_in_centavos' => $amountInCentavos,
            'currency' => 'PHP',
            
            // Payment Details
            'description' => $paymentData['description'] ?? 'Gym Membership Payment',
            'payment_type' => $paymentData['payment_type'] ?? 'E_WALLET',
            'intended_payment_method' => $paymentData['intended_payment_method'] ?? 'MAYA',
            
            // Customer Info
            'buyer' => [
                'first_name' => $paymentData['first_name'] ?? 'Member',
                'middle_name' => $paymentData['middle_name'] ?? '',
                'last_name' => $paymentData['last_name'] ?? '',
                'email' => $paymentData['email'] ?? '',
                'phone' => $paymentData['phone'] ?? '',
                'billing_address' => [
                    'line1' => $paymentData['address'] ?? '',
                    'city' => $paymentData['city'] ?? '',
                    'state' => $paymentData['state'] ?? '',
                    'postal_code' => $paymentData['postal_code'] ?? '',
                    'country_code' => 'PH'
                ]
            ],
            
            // Item Details
            'items' => [
                [
                    'name' => 'Gym Membership',
                    'code' => 'MEMBERSHIP-' . ($paymentData['member_id'] ?? ''),
                    'description' => $paymentData['description'] ?? '',
                    'amount' => $amount,
                    'quantity' => 1,
                    'total_amount' => $amount,
                    'tax_amount' => 0,
                    'tax_rate' => 0
                ]
            ],
            
            // Redirect URLs
            'redirect_url' => [
                'success' => $this->callbackUrl . '?status=success&ref=' . urlencode($transactionId),
                'failure' => $this->callbackUrl . '?status=failed&ref=' . urlencode($transactionId),
                'cancel' => $this->callbackUrl . '?status=cancelled&ref=' . urlencode($transactionId)
            ],
            
            // Webhook Configuration
            'webhook_url' => str_replace('/payment/callback', '/api/payments/webhook.php', $this->callbackUrl),
            'webhook_secret' => $this->webhookSecret,
            
            // Additional Metadata
            'metadata' => [
                'member_id' => $paymentData['member_id'],
                'session_id' => $paymentData['session_id'] ?? null,
                'payment_id' => $paymentData['payment_id'] ?? null,
                'environment' => $this->environment
            ],
            
            // Request Security
            'signature' => $this->generateSignature($transactionId, $amount)
        ];
    }
    
    /**
     * Generate Signature for Request Security
     */
    private function generateSignature($transactionId, $amount) {
        $signatureString = "{$this->merchantId}|{$transactionId}|{$amount}|{$this->apiSecret}";
        return hash('sha256', $signatureString);
    }
    
    /**
     * Verify Webhook Signature
     * Verify that webhook came from Maya
     */
    public function verifyWebhookSignature($webhookData, $signature) {
        // Reconstruct the data string used for signature
        $dataString = json_encode($webhookData, JSON_UNESCAPED_SLASHES);
        $expectedSignature = hash_hmac('sha256', $dataString, $this->webhookSecret);
        
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Process Webhook Callback
     * Handle payment status updates from Maya
     */
    public function processWebhookCallback($webhookData) {
        try {
            // Verify webhook authenticity
            if (!$this->verifyWebhookSignature($webhookData, $webhookData['signature'] ?? '')) {
                throw new Exception('Invalid webhook signature');
            }
            
            // Extract transaction details
            $status = $webhookData['status'] ?? null;
            $transactionId = $webhookData['reference_number'] ?? null;
            $amount = $webhookData['total_amount'] ?? 0;
            
            // Determine payment status
            $paymentStatus = $this->mapMayaStatusToPaymentStatus($status);
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'status' => $paymentStatus,
                'amount' => $amount,
                'data' => $webhookData
            ];
            
        } catch (Exception $e) {
            error_log('Webhook Processing Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Map Maya Payment Status to System Status
     */
    private function mapMayaStatusToPaymentStatus($mayaStatus) {
        $statusMap = [
            'COMPLETED' => 'Paid',
            'SUCCESS' => 'Paid',
            'PAID' => 'Paid',
            'PENDING' => 'Pending',
            'AUTHORIZED' => 'Pending',
            'FAILED' => 'Pending',
            'DECLINED' => 'Pending',
            'CANCELLED' => 'Pending',
            'EXPIRED' => 'Overdue',
            'REVERSED' => 'Pending'
        ];
        
        return $statusMap[strtoupper($mayaStatus)] ?? 'Pending';
    }
    
    /**
     * Check Transaction Status
     * Query Maya API for current transaction status
     */
    public function checkTransactionStatus($transactionId) {
        try {
            $payload = [
                'merchant_id' => $this->merchantId,
                'reference_number' => $transactionId,
                'timestamp' => date('c')
            ];
            
            $response = $this->sendRequest('GET', '/api/v1/checkout/status', $payload);
            
            if ($response['success'] === true) {
                return [
                    'success' => true,
                    'transaction_id' => $transactionId,
                    'status' => $this->mapMayaStatusToPaymentStatus($response['status']),
                    'amount' => $response['total_amount'] ?? null,
                    'response' => $response
                ];
            } else {
                throw new Exception('Failed to retrieve transaction status');
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Refund Payment
     * Process refund for a paid transaction
     */
    public function refundPayment($transactionId, $amount = null) {
        try {
            // Get transaction details first
            $statusResponse = $this->checkTransactionStatus($transactionId);
            
            if (!$statusResponse['success'] || $statusResponse['status'] !== 'Paid') {
                throw new Exception('Transaction not found or not in paid status');
            }
            
            $refundAmount = $amount ?? $statusResponse['amount'];
            
            $payload = [
                'merchant_id' => $this->merchantId,
                'reference_number' => $transactionId,
                'refund_amount' => $refundAmount,
                'reason' => 'Customer request',
                'timestamp' => date('c')
            ];
            
            $response = $this->sendRequest('POST', '/api/v1/refund/create', $payload);
            
            if ($response['success'] === true) {
                return [
                    'success' => true,
                    'refund_id' => $response['refund_id'] ?? null,
                    'transaction_id' => $transactionId,
                    'amount' => $refundAmount,
                    'status' => 'refunded',
                    'response' => $response
                ];
            } else {
                throw new Exception('Refund failed: ' . ($response['error'] ?? 'Unknown error'));
            }
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Send HTTP Request to Maya API
     * Handles authentication and error handling
     */
    private function sendRequest($method, $endpoint, $payload) {
        try {
            $url = $this->apiUrl . $endpoint;
            
            // Initialize cURL
            $curl = curl_init();
            
            curl_setopt_array($curl, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey,
                    'X-API-Key: ' . $this->apiKey,
                    'X-Request-Id: ' . uniqid()
                ]
            ]);
            
            // Add payload for POST requests
            if ($method === 'POST' || $method === 'PUT') {
                curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
            }
            
            // Execute request
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $curlError = curl_error($curl);
            curl_close($curl);
            
            // Handle cURL errors
            if ($curlError) {
                throw new Exception("cURL Error: $curlError");
            }
            
            // Parse response
            $responseData = json_decode($response, true);
            
            // Handle HTTP errors
            if ($httpCode >= 400) {
                $errorMsg = $responseData['error_message'] ?? $responseData['message'] ?? "HTTP $httpCode";
                throw new Exception("Maya API Error: $errorMsg");
            }
            
            return $responseData ?? ['success' => false, 'response' => $response];
            
        } catch (Exception $e) {
            error_log('cURL Request Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Test Connection
     * Verify Maya API connectivity
     */
    public function testConnection() {
        try {
            $payload = [
                'merchant_id' => $this->merchantId,
                'timestamp' => date('c')
            ];
            
            $response = $this->sendRequest('GET', '/api/v1/health', $payload);
            
            return [
                'success' => true,
                'message' => 'Maya API connection successful',
                'environment' => $this->environment,
                'merchant_id' => $this->merchantId,
                'response' => $response
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'environment' => $this->environment
            ];
        }
    }
    
    /**
     * Get Configuration Details (for debugging)
     */
    public function getConfigDetails() {
        return [
            'environment' => $this->environment,
            'merchant_id' => $this->merchantId,
            'merchant_name' => $this->merchantName,
            'api_url' => $this->apiUrl,
            'callback_url' => $this->callbackUrl
        ];
    }
}
?>
