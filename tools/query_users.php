<?php
require __DIR__ . '/../vendor/autoload.php';

try {
    $pdo = new PDO('sqlite:' . __DIR__ . '/../database/database.sqlite');
    $stmt = $pdo->query("SELECT id, name, email, created_at FROM users ORDER BY id DESC LIMIT 10");
    if (!$stmt) {
        echo "Query failed or table missing\n";
        exit(0);
    }
    foreach ($stmt as $row) {
        echo json_encode($row, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
