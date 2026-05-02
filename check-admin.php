<?php
require_once 'config/config.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "Checking admin user status...\n";

$stmt = $pdo->prepare("SELECT id, email, user_type, is_verified FROM users WHERE email = ?");
$stmt->execute(['admin@levelupfitness.com']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "Admin user found:\n";
    var_dump($user);
    
    echo "\nPassword test:\n";
    $testPassword = 'password';
    $storedPassword = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $storedPassword->execute([$user['id']]);
    $pwdRow = $storedPassword->fetch();
    $hashMatch = verifyPassword($testPassword, $pwdRow['password']);
    echo "Password 'password' matches hash: " . ($hashMatch ? 'YES' : 'NO') . "\n";
} else {
    echo "Admin user NOT found!\n";
}
?>