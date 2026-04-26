<?php
require_once __DIR__ . '/../core/Database.php';

$db = (new Database())->getPdo();

echo "=========================================================\n";
echo "📦 LIBRARIAN CLI - SESSION & BOOKING MANAGEMENT\n";
echo "=========================================================\n\n";

echo "--- CORE SESSIONS ---\n";
$stmt = $db->query("SELECT s.id, s.topic, s.date_time, s.status, f.name as facilitator FROM sessions s JOIN facilitators f ON s.facilitator_id = f.id ORDER BY s.date_time ASC");
$sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach($sessions as $s) {
    echo sprintf("[%02d] %-32s | %-12s | %-18s | System Lock: %s\n", 
        $s['id'], $s['topic'], str_replace('2026-', '', $s['date_time']), $s['facilitator'], $s['status']);
}

echo "\n--- RECORDED RESERVATIONS (CONFIRMED) ---\n";
$stmt = $db->query("SELECT s.id, COALESCE(u.name, s.requester_name, 'External Requestor') as name, s.topic, s.status FROM sessions s LEFT JOIN users u ON s.user_id = u.id WHERE s.status = 'CONFIRMED' ORDER BY s.date_time ASC");
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($bookings) === 0) {
    echo "  > No bookings found at this time.\n";
} else {
    foreach($bookings as $b) {
        echo sprintf("Session #%04d | Requestor: %-16s | Session: %-32s | Status: %s\n", 
            $b['id'], $b['name'], $b['topic'], $b['status']);
    }
}
echo "=========================================================\n";
