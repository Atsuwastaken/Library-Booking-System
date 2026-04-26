<?php
$dbFile = 'packages/core/data/sched.sqlite';
$pdo = new PDO('sqlite:' . $dbFile);
$tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    echo "Table: $table\n";
    $schema = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='$table'")->fetchColumn();
    echo $schema . ";\n";
    
    echo "Data Sample (first 5 rows):\n";
    try {
        $data = $pdo->query("SELECT * FROM $table LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        print_r($data);
    } catch (Exception $e) {
        echo "Error reading data: " . $e->getMessage() . "\n";
    }
    echo "---------------------------------\n\n";
}
