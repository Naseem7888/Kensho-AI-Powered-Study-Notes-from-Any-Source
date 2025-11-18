<?php
$db = __DIR__ . '/../database/database.sqlite';
if (!file_exists($db)) {
    echo "NO_DB\n";
    exit(1);
}
try {
    $pdo = new PDO('sqlite:' . $db);
    $res = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name");
    foreach ($res as $r) {
        echo $r['name'] . PHP_EOL;
    }
} catch (Exception $e) {
    echo 'ERR: ' . $e->getMessage() . PHP_EOL;
    exit(2);
}
