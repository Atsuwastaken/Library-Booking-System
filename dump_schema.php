<?php
$pdo = new PDO('sqlite:packages/core/data/sched.sqlite');
$stmt = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type='table'");
foreach ($stmt as $row) {
    echo "-- Table: " . $row['name'] . "\n";
    echo $row['sql'] . "\n\n";
}
