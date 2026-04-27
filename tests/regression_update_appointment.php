<?php
require_once __DIR__ . '/../packages/core/BookingService.php';

$sourceDb = __DIR__ . '/../packages/core/data/sched.sqlite';
$tempDb = sys_get_temp_dir() . '/library-booking-update-appointment-test.sqlite';

if (!copy($sourceDb, $tempDb)) {
    throw new RuntimeException('Failed to create temp database copy.');
}

register_shutdown_function(static function () use ($tempDb) {
    if (is_file($tempDb)) {
        @unlink($tempDb);
    }
});

$pdo = new PDO('sqlite:' . $tempDb);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("DELETE FROM decision_logs");
$pdo->exec("DELETE FROM sessions WHERE id >= 900000");

$insertUser = $pdo->prepare("
    INSERT OR REPLACE INTO users (id, student_number, name, email, role, password, department_id, facilitator_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$insertUser->execute([900000, 'TEST-900000', 'Regression Student', 'invalid-email', 'student', 'password', 1, null]);

$insertSession = $pdo->prepare("
    INSERT INTO sessions (
        id, user_id, type, topic, date_time, end_time, mode, venue, facilitator_id, status,
        special_requests, requester_name, requester_email, requester_department_id,
        notification_minutes, cancellation_reason, cancelled_date_time, cancelled_by, evaluation_notes, archived_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$insertSession->execute([
    900000,
    900000,
    'Consultation',
    'Regression Topic',
    '2026-05-01 10:00:00',
    '2026-05-01 11:00:00',
    'Online',
    'Original Venue',
    null,
    'PENDING',
    null,
    'Regression Student',
    'invalid-email',
    1,
    30,
    null,
    null,
    null,
    null,
    null,
]);

$service = new BookingService();
$dbProp = new ReflectionProperty(BookingService::class, 'db');
$dbProp->setAccessible(true);
$dbProp->setValue($service, $pdo);

$updated = $service->updateAppointment(900000, 'CONFIRMED', 'Updated Venue', null, null, null, 'Admin confirmation note');
if ($updated !== true) {
    throw new RuntimeException('Expected confirmed appointment update to succeed.');
}

$session = $pdo->query("SELECT status, venue, evaluation_notes FROM sessions WHERE id = 900000")->fetch(PDO::FETCH_ASSOC);
if (!$session) {
    throw new RuntimeException('Updated session not found.');
}

if ($session['status'] !== 'CONFIRMED') {
    throw new RuntimeException('Expected session status to be CONFIRMED, got ' . var_export($session['status'], true));
}

if ($session['venue'] !== 'Updated Venue') {
    throw new RuntimeException('Expected venue to be updated.');
}

if ($session['evaluation_notes'] !== 'Admin confirmation note') {
    throw new RuntimeException('Expected confirmation note to be persisted.');
}

$decisionLog = $pdo->query("SELECT decision, appointment_type, evaluation_notes FROM decision_logs WHERE session_id = 900000 ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if (!$decisionLog) {
    throw new RuntimeException('Expected a decision log row for the confirmed update.');
}

if ($decisionLog['decision'] !== 'CONFIRMED') {
    throw new RuntimeException('Expected decision log decision to be CONFIRMED.');
}

if ($decisionLog['appointment_type'] !== 'Consultation') {
    throw new RuntimeException('Expected decision log appointment type to match the session type.');
}

if ($decisionLog['evaluation_notes'] !== 'Admin confirmation note') {
    throw new RuntimeException('Expected decision log to store the confirmation note.');
}

echo "PASS\n";
