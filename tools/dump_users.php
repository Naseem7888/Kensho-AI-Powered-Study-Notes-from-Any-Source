<?php
$db = __DIR__ . '/../database/database.sqlite';
$pdo = new PDO('sqlite:' . $db);
$stmt = $pdo->query('SELECT id, name, email, password FROM users LIMIT 20');
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$rows) { echo "NO_USERS\n"; exit; }
foreach ($rows as $r) {
    echo json_encode($r) . PHP_EOL;
}
