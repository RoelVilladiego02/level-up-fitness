<?php
require 'config/database.php';

$r = $pdo->query('SELECT member_id FROM members LIMIT 1')->fetch();
echo 'Sample member_id: ' . $r['member_id'] . "\n";
?>
