<?php
require_once 'config/database.php';

try {
    $result = $pdo->query('SHOW COLUMNS FROM trainers');
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    echo "Trainers table columns:\n";
    foreach ($columns as $col) {
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
