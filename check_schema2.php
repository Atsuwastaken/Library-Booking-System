$pdo = new PDO('sqlite:C:\xampp\htdocs\Library-Booking-System\packages\core\data\sched.sqlite');
$result = $pdo->query('PRAGMA table_info(sessions)');
echo "Columns in sessions table:\n";
foreach ($result as $row) {
    echo $row['name'] . ' - ' . $row['type'] . "\n";
}
