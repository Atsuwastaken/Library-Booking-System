<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/packages/core/BookingService.php';

$action = $_GET['action'] ?? '';
$service = new BookingService();

if ($action === 'get_sessions') {
    $sessions = $service->getAvailableSessions();
    echo json_encode(['success' => true, 'sessions' => $sessions]);
    exit;
}

if ($action === 'lock_session') {
    $data = json_decode(file_get_contents('php://input'), true);
    $sessionId = $data['session_id'] ?? 0;
    $success = $service->lockSession($sessionId);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'unlock_session') {
    $data = json_decode(file_get_contents('php://input'), true);
    $sessionId = $data['session_id'] ?? 0;
    $success = $service->unlockSession($sessionId);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'confirm_booking') {
    $data = json_decode(file_get_contents('php://input'), true);
    $sessionId = $data['session_id'] ?? 0;
    $specialRequests = $data['special_requests'] ?? '';
    // Dynamically retrieve authenticated User ID Context
    $userId = $_SESSION['user_id'] ?? 1; 
    
    $success = $service->confirmBooking($sessionId, $userId, $specialRequests);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'get_facilitators') {
    $facilitators = $service->getFacilitators();
    echo json_encode(['success' => true, 'facilitators' => $facilitators]);
    exit;
}

if ($action === 'add_session') {
    $data = json_decode(file_get_contents('php://input'), true);
    $fid = $data['facilitator_id'] ?? 0;
    $topic = $data['topic'] ?? '';
    $dt = $data['date_time'] ?? '';
    $mode = $data['mode'] ?? 'Onsite';
    $success = $service->addSession($fid, $topic, $dt, $mode);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'remove_session') {
    $data = json_decode(file_get_contents('php://input'), true);
    $sid = $data['session_id'] ?? 0;
    $success = $service->removeSession($sid);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'add_facilitator') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = $data['name'] ?? '';
    $expertise = $data['expertise'] ?? '';
    $success = $service->addFacilitator($name, $expertise);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'update_facilitator') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $name = $data['name'] ?? '';
    $expertise = $data['expertise'] ?? '';
    $success = $service->updateFacilitator($id, $name, $expertise);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'delete_facilitator') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $success = $service->deleteFacilitator($id);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'advanced_booking') {
    $data = json_decode(file_get_contents('php://input'), true);
    $fid = $data['facilitator_id'] ?? 0;
    $dt = $data['date_time'] ?? '';
    $mode = $data['mode'] ?? 'Onsite';
    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    $phone = $data['phone'] ?? '';
    $reminder = $data['reminder'] ?? '30';
    $topic = $data['topic'] ?? 'General Consultation';
    
    // Auth context (simulated or session based)
    $userId = $_SESSION['user_id'] ?? 1;
    
    $specialRequests = "Name: $name | Email: $email | Phone: $phone | Reminder: $reminder minutes";
    
    $success = $service->createAdvancedBooking($fid, $topic, $dt, $mode, $userId, $specialRequests);
    echo json_encode(['success' => $success]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'API Endpoint Unrecognized']);
