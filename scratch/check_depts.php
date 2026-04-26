<?php
$pdo = new PDO('sqlite:packages/core/data/sched.sqlite');
$stmt = $pdo->query('SELECT * FROM department');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
