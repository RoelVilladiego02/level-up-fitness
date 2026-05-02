<?php
require_once 'config/config.php';
require_once 'config/database.php';

// Get the latest token
$stmt = $pdo->prepare("SELECT * FROM verification_tokens ORDER BY created_at DESC LIMIT 1");
$stmt->execute();
$token = $stmt->fetch(PDO::FETCH_ASSOC);

if ($token) {
    echo "Latest token in DB:\n";
    var_dump($token);
    
    echo "\n\nNow testing validateVerificationToken function:\n";
    require_once 'includes/functions.php';
    
    $result = validateVerificationToken($token['token']);
    echo "Result: " . var_export($result, true) . "\n";
    
    // Try manual query to debug
    echo "\n\nManual query test:\n";
    $sql = "
        SELECT user_id FROM verification_tokens
        WHERE token = ? 
        AND token_type = 'email_verification'
        AND used_at IS NULL
        AND expires_at > NOW()
        LIMIT 1
    ";
    echo "Query: $sql\n";
    echo "Token to search: " . $token['token'] . "\n";
    echo "Token type in DB: " . $token['token_type'] . "\n";
    echo "Expires at: " . $token['expires_at'] . "\n";
    echo "Used at: " . ($token['used_at'] ?? 'NULL') . "\n";
    
    $checkStmt = $pdo->prepare($sql);
    if ($checkStmt->execute([$token['token']])) {
        $result = $checkStmt->fetch();
        echo "Manual query result: " . var_export($result, true) . "\n";
    }
} else {
    echo "No tokens found in database\n";
}
?>