<?php
$pdo = new PDO("sqlite:C:\xampp\htdocs\Library-Booking-System\packages\core\data\sched.sqlite");
echo "=== Sessions Table Columns ===\n";
$result = $pdo->query("PRAGMA table_info(sessions)");
foreach ($result as $row) {
    echo $row["name"] . " - " . $row["type"] . "\n";
}
echo "\n=== Sample Session Records ===\n";
$result = $pdo->query("SELECT * FROM sessions LIMIT 3");
foreach ($result as $row) {
    echo "ID: " . $row["id"] . ", date_time: " . $row["date_time"] . ", status: " . $row["status"] . "\n";
}
echo "\n=== Session_Logs Table Columns ===\n";
$result = $pdo->query("PRAGMA table_info(session_logs)");
foreach ($result as $row) {
    echo $row["name"] . " - " . $row["type"] . "\n";
}
?>
