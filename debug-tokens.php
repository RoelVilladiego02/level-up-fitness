<?php
require_once 'config/config.php';
require_once 'config/database.php';

echo "Latest tokens in database:\n";
$stmt = $pdo->prepare('SELECT * FROM verification_tokens ORDER BY created_at DESC LIMIT 3');
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($results as $row) {
    echo "Token: " . substr($row['token'], 0, 10) . "...\n";
    echo "  User ID: " . $row['user_id'] . "\n";
    echo "  Type: " . $row['token_type'] . "\n";
    echo "  Expires: " . $row['expires_at'] . "\n";
    echo "  Used: " . ($row['used_at'] ?? 'NULL') . "\n";
    echo "---\n";
}

// Test validation directly
echo "\nTesting validateVerificationToken function:\n";
require_once 'includes/functions.php';

$latestToken = $results[0]['token'] ?? null;
if ($latestToken) {
    echo "Testing token: " . substr($latestToken, 0, 10) . "...\n";
    $result = validateVerificationToken($latestToken);
    echo "Validation result: " . var_export($result, true) . "\n";
}
?>