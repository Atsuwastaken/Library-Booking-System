<?php

$databasePath = dirname(__DIR__, 2) . '/packages/core/data/sched.sqlite';
$pdo = new PDO('sqlite:' . $databasePath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type = 'table' ORDER BY name");
foreach ($stmt as $row) {
    echo '-- Table: ' . $row['name'] . "\n";
    echo $row['sql'] . "\n\n";
}
