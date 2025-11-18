<?php
$db = __DIR__ . '/../database/database.sqlite';
$pdo = new PDO('sqlite:' . $db);
$email = 'dev+test@localhost';
$name = 'Dev Tester';
$plain = 'password';
$hash = password_hash($plain, PASSWORD_BCRYPT, ['cost' => 12]);
// check exists
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo "user exists\n";
    exit(0);
}
$stmt = $pdo->prepare('INSERT INTO users (name, email, password, created_at, updated_at) VALUES (?, ?, ?, datetime("now"), datetime("now"))');
$stmt->execute([$name, $email, $hash]);
echo "created user: $email with password: $plain\n";
