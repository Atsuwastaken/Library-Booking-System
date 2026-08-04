<?php

$databasePath = dirname(__DIR__, 2) . '/packages/core/data/sched.sqlite';
$pdo = new PDO('sqlite:' . $databasePath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "=== Sessions Table Columns ===\n";
$result = $pdo->query('PRAGMA table_info(sessions)');
foreach ($result as $row) {
    echo $row['name'] . ' - ' . $row['type'] . "\n";
}

echo "\n=== Sample Session Records ===\n";
$result = $pdo->query('SELECT * FROM sessions LIMIT 3');
foreach ($result as $row) {
    echo 'ID: ' . $row['id'] . ', date_time: ' . $row['date_time'] . ', status: ' . $row['status'] . "\n";
}

echo "\n=== Session Logs Table Columns ===\n";
$result = $pdo->query('PRAGMA table_info(session_logs)');
foreach ($result as $row) {
    echo $row['name'] . ' - ' . $row['type'] . "\n";
}
