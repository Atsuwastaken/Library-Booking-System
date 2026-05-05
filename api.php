<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Vary: Origin');

ini_set('display_errors', '0');

set_error_handler(function ($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(function ($e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error while processing API request.',
        'error' => $e->getMessage()
    ]);
    exit;
});

require_once __DIR__ . '/packages/core/BookingService.php';

$action = $_GET['action'] ?? '';
$service = new BookingService();

function requireAuthenticatedUserId()
{
    $userId = $_SESSION['user_id'] ?? null;
    if (empty($userId)) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Authentication required.'
        ]);
        exit;
    }
    return (int) $userId;
}

function isAdmin()
{
    $role = strtolower((string) ($_SESSION['user_role'] ?? ''));
    return $role === 'admin';
}

function requireAdminSession()
{
    $role = strtolower((string) ($_SESSION['user_role'] ?? ''));
    if ($role !== 'admin') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Admin access required.'
        ]);
        exit;
    }
}

if ($action === 'export_session_logs_csv') {
    requireAuthenticatedUserId();
    requireAdminSession();

    $fromDateTime = date('Y-m-d H:i:s', strtotime('-3 years'));
    $logs = $service->getSessionLogsSince($fromDateTime);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="session_logs_last_3_years_' . date('Ymd_His') . '.csv"');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');

    $out = fopen('php://output', 'w');
    fputcsv($out, ['Log ID', 'Session ID', 'Facilitator', 'User', 'Requester Email', 'Action', 'Log Date', 'Session Status']);

    foreach ($logs as $row) {
        $fac = trim((string) ($row['facilitator'] ?? ''));
        $usr = trim((string) ($row['user'] ?? ''));
        $eml = trim((string) ($row['requester_email'] ?? ''));

        fputcsv($out, [
            $row['id'] ?? '',
            $row['session_id'] ?? '',
            ($fac !== '' ? $fac : 'TBA'),
            ($usr !== '' ? $usr : 'TBA'),
            ($eml !== '' ? $eml : 'TBA'),
            $row['action'] ?? '',
            $row['log_date'] ?? '',
            $row['session_status'] ?? ''
        ]);
    }

    fclose($out);
    exit;
}

if ($action === 'export_sessions_csv') {
    requireAuthenticatedUserId();
    requireAdminSession();
    $sessions = $service->exportSessionsWithDetails();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="sessions_export_' . date('Ymd_His') . '.csv"');

    $out = fopen('php://output', 'w');
    fputcsv($out, [
        'Session ID',
        'Appointment Type',
        'Topic',
        'Date/Time',
        'End Time',
        'Mode',
        'Venue',
        'Status',
        'Requester Name',
        'Requester Email',
        'Student Number',
        'Year Level',
        'Program',
        'Department',
        'Enrollment Status',
        'Facilitator',
        'Special Requests',
        'Cancellation Reason'
    ]);
    $fmt = function ($val, $default = 'N/A') {
        $v = trim((string) ($val ?? ''));
        return $v !== '' ? $v : $default;
    };

    foreach ($sessions as $row) {
        fputcsv($out, [
            $row['session_id'] ?? '',
            $fmt($row['appointment_type'] ?? ''),
            $fmt($row['topic'] ?? ''),
            $fmt($row['date_time'] ?? ''),
            $fmt($row['end_time'] ?? ''),
            $fmt($row['mode'] ?? ''),
            $fmt($row['venue'] ?? '', 'TBA'),
            $fmt($row['booking_status'] ?? ''),
            $fmt($row['student_name'] ?? ''),
            $fmt($row['student_email'] ?? ''),
            $fmt($row['student_number'] ?? ''),
            $fmt($row['year_level'] ?? ''),
            $fmt($row['program_name'] ?? ''),
            $fmt($row['student_department'] ?? ''),
            $fmt($row['enrollment_status'] ?? ''),
            $fmt($row['facilitator_name'] ?? '', 'TBA'),
            $fmt($row['special_requests'] ?? '', 'None'),
            $fmt($row['cancellation_reason'] ?? '', 'N/A')
        ]);
    }
    fclose($out);
    exit;
}

if ($action === 'export_users_csv') {
    requireAuthenticatedUserId();
    requireAdminSession();
    $users = $service->getUsersForAdmin();
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d_H-i-s') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Name',
        'Email',
        'Role',
        'User Type',
        'Student Number',
        'Department Name',
        'Program Name',
        'Year Level',
        'Enrollment Status'
    ]);
    foreach ($users as $u) {
        fputcsv($output, [
            $u['name'],
            $u['email'],
            $u['role'],
            $u['user_type'] ?? 'non-student',
            $u['student_number'] ?? '',
            $u['department_name'] ?? '',
            $u['program_name'] ?? '',
            $u['year_level'] ?? '',
            $u['enrollment_status'] ?? ''
        ]);
    }
    fclose($output);
    exit;
}

if ($action === 'import_users_csv') {
    requireAuthenticatedUserId();
    requireAdminSession();
    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
        exit;
    }
    try {
        $results = $service->importUsersFromCSV($_FILES['csv_file']['tmp_name']);
        echo json_encode(['success' => true, 'results' => $results]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'submit_registration') {
    $data = json_decode(file_get_contents('php://input'), true);
    $studentNumber = trim((string) ($data['student_number'] ?? ''));
    $name = trim((string) ($data['name'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $password = (string) ($data['password'] ?? '');
    $departmentId = (int) ($data['department_id'] ?? 0);
    $userType = $data['user_type'] ?? 'non-student';
    $yearLevel = $data['year_level'] ?? null;
    $courseProgram = $data['course_program'] ?? null;
    $enrollmentStatus = $data['enrollment_status'] ?? null;

    if ($name === '' || $email === '' || $password === '' || $departmentId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Name, email, password, and department are required.']);
        exit;
    }

    try {
        $success = $service->submitRegistrationRequest($studentNumber, $name, $email, $password, $departmentId, 'general', $userType, $yearLevel, $courseProgram, $enrollmentStatus);
        echo json_encode([
            'success' => $success,
            'message' => $success
                ? 'Registration successful. Request sent to admin for approval.'
                : 'Unable to register.'
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'update_profile') {
    $userId = requireAuthenticatedUserId();
    $data = json_decode(file_get_contents('php://input'), true);

    try {
        $success = $service->updateUserProfile($userId, $data);
        echo json_encode(['success' => $success, 'message' => 'Profile updated successfully.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_registration_requests') {
    requireAuthenticatedUserId();
    requireAdminSession();

    $status = trim((string) ($_GET['status'] ?? ''));
    $requests = $service->getRegistrationRequests($status !== '' ? $status : null);
    echo json_encode(['success' => true, 'requests' => $requests]);
    exit;
}

if ($action === 'approve_registration_request') {
    $adminUserId = requireAuthenticatedUserId();
    requireAdminSession();

    $data = json_decode(file_get_contents('php://input'), true);
    $requestId = (int) ($data['request_id'] ?? 0);
    $role = trim((string) ($data['role'] ?? 'general'));
    $departmentId = (int) ($data['department_id'] ?? 0);
    $facilitatorEnabled = !empty($data['facilitator_enabled']);
    $userType = $data['user_type'] ?? 'non-student';
    $yearLevel = $data['year_level'] ?? null;
    $courseProgram = $data['course_program'] ?? null;
    $enrollmentStatus = $data['enrollment_status'] ?? null;

    if ($requestId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid request id.']);
        exit;
    }

    try {
        $success = $service->approveRegistrationRequest(
            $requestId,
            $adminUserId,
            $role,
            $departmentId > 0 ? $departmentId : null,
            $facilitatorEnabled,
            $userType,
            $yearLevel,
            $courseProgram,
            $enrollmentStatus
        );
        echo json_encode(['success' => $success]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'reject_registration_request') {
    $adminUserId = requireAuthenticatedUserId();
    requireAdminSession();

    $data = json_decode(file_get_contents('php://input'), true);
    $requestId = (int) ($data['request_id'] ?? 0);
    $reason = trim((string) ($data['reason'] ?? ''));

    if ($requestId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid request id.']);
        exit;
    }

    $success = $service->rejectRegistrationRequest($requestId, $adminUserId, $reason);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'update_user_admin') {
    requireAuthenticatedUserId();
    requireAdminSession();

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data.']);
        exit;
    }

    try {
        $success = $service->updateUserAdmin($data);
        echo json_encode(['success' => $success]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}


if ($action === 'get_users_admin') {
    requireAuthenticatedUserId();
    requireAdminSession();

    $users = $service->getUsersForAdmin();
    echo json_encode(['success' => true, 'users' => $users]);
    exit;
}

if ($action === 'add_user_admin') {
    requireAuthenticatedUserId();
    requireAdminSession();

    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim((string) ($data['name'] ?? ''));
    $email = trim((string) ($data['email'] ?? ''));
    $password = (string) ($data['password'] ?? '');
    $studentNumber = trim((string) ($data['student_number'] ?? ''));
    $role = trim((string) ($data['role'] ?? 'staff'));
    $departmentId = (int) ($data['department_id'] ?? 0);
    $facilitatorId = !empty($data['facilitator_id']) ? (int) $data['facilitator_id'] : null;
    $facilitatorEnabled = !empty($data['facilitator_enabled']); // legacy fallback
    $userType = $data['user_type'] ?? 'non-student';
    $yearLevel = $data['year_level'] ?? null;
    $courseProgram = $data['course_program'] ?? null;
    $enrollmentStatus = $data['enrollment_status'] ?? null;

    if ($name === '' || $email === '' || $password === '') {
        echo json_encode(['success' => false, 'message' => 'Name, email, and password are required.']);
        exit;
    }

    try {
        $userId = $service->addUserByAdmin(
            $name,
            $email,
            $password,
            $role,
            $studentNumber,
            $departmentId > 0 ? $departmentId : null,
            $facilitatorEnabled,
            $userType,
            $yearLevel,
            $courseProgram,
            $enrollmentStatus,
            $facilitatorId
        );

        echo json_encode(['success' => true, 'user_id' => $userId]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete_user_admin') {
    requireAuthenticatedUserId();
    requireAdminSession();

    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int) ($data['id'] ?? 0);

    if ($id === (int) ($_SESSION['user_id'] ?? 0)) {
        echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']);
        exit;
    }

    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid user id.']);
        exit;
    }

    try {
        $success = $service->deleteUserByAdmin($id);
        echo json_encode(['success' => $success]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'delete_all_users_admin') {
    requireAuthenticatedUserId();
    requireAdminSession();
    $data = json_decode(file_get_contents('php://input'), true);
    $currentUserId = (int) ($_SESSION['user_id'] ?? 0);
    $ids = $data['ids'] ?? null;
    try {
        $service->deleteAllUsersByAdmin($currentUserId, $ids);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

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
    $userId = requireAuthenticatedUserId();

    $success = $service->confirmBooking($sessionId, $userId, $specialRequests);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'get_topics') {
    $deptId = $_GET['department_id'] ?? null;
    $topics = $service->getTopics($deptId);
    echo json_encode(['success' => true, 'topics' => $topics]);
    exit;
}

if ($action === 'get_topic_catalog') {
    $topics = $service->getTopicCatalog();
    echo json_encode(['success' => true, 'topics' => $topics]);
    exit;
}

if ($action === 'add_topic') {
    $data = json_decode(file_get_contents('php://input'), true);
    $name = trim((string) ($data['name'] ?? ''));
    $departmentIds = $data['department_ids'] ?? [];
    $facilitatorIds = $data['facilitator_ids'] ?? [];

    if ($name === '') {
        echo json_encode(['success' => false, 'message' => 'Topic name is required.']);
        exit;
    }

    $success = $service->addTopic($name, $departmentIds, $facilitatorIds);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'update_topic') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int) ($data['id'] ?? 0);
    $name = trim((string) ($data['name'] ?? ''));
    $departmentIds = $data['department_ids'] ?? [];
    $facilitatorIds = $data['facilitator_ids'] ?? [];

    if ($id <= 0 || $name === '') {
        echo json_encode(['success' => false, 'message' => 'Topic id and name are required.']);
        exit;
    }

    $success = $service->updateTopic($id, $name, $departmentIds, $facilitatorIds);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'delete_topic') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = (int) ($data['id'] ?? 0);
    $success = $service->deleteTopic($id);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'get_user_info') {
    $userId = requireAuthenticatedUserId();
    $user = $service->getUserInfo($userId);
    echo json_encode(['success' => true, 'user' => $user]);
    exit;
}

if ($action === 'get_topic_details') {
    $topicId = $_GET['topic_id'] ?? null;
    $depts = $service->getTopicDepartments($topicId);
    echo json_encode(['success' => true, 'departments' => $depts]);
    exit;
}

if ($action === 'get_facilitators') {
    $topicId = $_GET['topic_id'] ?? null;
    $facilitators = $service->getFacilitators($topicId);
    echo json_encode(['success' => true, 'facilitators' => $facilitators]);
    exit;
}

if ($action === 'get_departments') {
    $departments = $service->getDepartments();
    echo json_encode(['success' => true, 'departments' => $departments]);
    exit;
}

if ($action === 'get_programs') {
    $deptId = $_GET['department_id'] ?? null;
    $programs = $service->getPrograms($deptId);
    echo json_encode(['success' => true, 'programs' => $programs]);
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
    $position = $data['position'] ?? '';
    $topicIds = $data['topic_ids'] ?? [];
    $departmentIds = $data['department_ids'] ?? [];
    $success = $service->addFacilitator($name, $position, $topicIds, $departmentIds);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'update_facilitator') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $name = $data['name'] ?? '';
    $position = $data['position'] ?? '';
    $topicIds = $data['topic_ids'] ?? [];
    $departmentIds = $data['department_ids'] ?? [];
    $success = $service->updateFacilitator($id, $name, $position, $topicIds, $departmentIds);
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
    if (!is_array($data)) {
        $data = [];
    }
    $type = $data['type'] ?? 'Consultation';
    $fid = $data['facilitator_id'] ?? 0;
    $dt = $data['date_time'] ?? '';
    $et = $data['end_time'] ?? '';
    $mode = $data['mode'] ?? 'Onsite';
    $name = $data['name'] ?? '';
    $email = $data['email'] ?? '';
    $phone = $data['phone'] ?? '';
    $notes = $data['notes'] ?? '';
    $requesterDepartment = $data['department'] ?? '';
    $topic = $data['topic'] ?? 'General Consultation';
    $customRequestor = $data['custom_requestor'] ?? null;

    $userId = requireAuthenticatedUserId();

    $requestDetails = [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'department' => $requesterDepartment,
        'notes' => $notes
    ];

    try {
        $success = $service->createAdvancedBooking($type, $fid, $topic, $dt, $et, $mode, $userId, $requestDetails, $customRequestor);
        echo json_encode(['success' => $success]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'get_appointments') {
    $userId = requireAuthenticatedUserId();
    $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    $user = $service->getUserInfo($userId);
    $facilitatorId = $user['facilitator_id'] ?? null;

    // If my_appointments=true, always scope to the requesting user (even for admins)
    $myAppointments = ($_GET['my_appointments'] ?? '') === 'true';
    if ($myAppointments) {
        $isAdmin = false;
    }

    $filters = [
        'requestor' => $_GET['requestor'] ?? 'all',
        'department' => $_GET['department'] ?? 'all',
        'facilitator' => $_GET['facilitator'] ?? 'all',
        'status' => $_GET['status'] ?? 'all',
        'datetime' => $_GET['datetime'] ?? 'newest',
        'date' => $_GET['date'] ?? '',
        'include_archived' => ($_GET['include_archived'] ?? '') === 'true'
    ];

    $apps = $service->getAppointments($userId, $isAdmin, $facilitatorId, $filters);
    echo json_encode(['success' => true, 'appointments' => $apps]);
    exit;
}

if ($action === 'update_appointment') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $status = strtoupper(trim((string) ($data['status'] ?? 'PENDING')));
    $venue = $data['venue'] ?? 'TBA';
    $facId = $data['facilitator_id'] ?? null;
    $cancellationReason = $data['cancellation_reason'] ?? null;
    $cancelledBy = $data['cancelled_by'] ?? null;
    $evaluationNotes = $data['evaluation_notes'] ?? null;
    $outsideFacilitator = $data['outside_facilitator'] ?? null;

    if ($status === 'DECLINED') {
        requireAuthenticatedUserId();
        requireAdminSession();
    }

    $result = $service->updateAppointment($id, $status, $venue, $facId, $cancellationReason, $cancelledBy, $evaluationNotes, $outsideFacilitator);
    if ($result === true) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => $result]);
    }
    exit;
}

if ($action === 'cancel_appointment') {
    requireAuthenticatedUserId();
    requireAdminSession();
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $cancellationReason = $data['cancellation_reason'] ?? null;
    $cancelledBy = $data['cancelled_by'] ?? null;
    $success = $service->cancelAppointment($id, $cancellationReason, $cancelledBy);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'change_instructor') {
    $userId = requireAuthenticatedUserId();
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $facilitatorId = $data['facilitator_id'] ?? null;

    // Optional: check ownership if not admin
    if (!isAdmin()) {
        $session = $service->getSessionById($id);
        if (!$session || $session['user_id'] != $userId) {
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
    }

    try {
        $success = $service->changeInstructor($id, $facilitatorId);
        echo json_encode(['success' => $success]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

if ($action === 'archive_session') {
    requireAuthenticatedUserId();
    requireAdminSession();
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $success = $service->archiveSession($id);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'unarchive_session') {
    requireAuthenticatedUserId();
    requireAdminSession();
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    $success = $service->unarchiveSession($id);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'bulk_archive_sessions') {
    requireAuthenticatedUserId();
    requireAdminSession();
    $data = json_decode(file_get_contents('php://input'), true);
    $ids = $data['ids'] ?? [];
    $success = $service->bulkArchiveSessions($ids);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'bulk_unarchive_sessions') {
    requireAuthenticatedUserId();
    requireAdminSession();
    $data = json_decode(file_get_contents('php://input'), true);
    $ids = $data['ids'] ?? [];
    $success = $service->bulkUnarchiveSessions($ids);
    echo json_encode(['success' => $success]);
    exit;
}



if ($action === 'get_off_days') {
    $offDays = $service->getOffDays();
    echo json_encode(['success' => true, 'off_days' => $offDays]);
    exit;
}

if ($action === 'save_off_day') {
    $userId = requireAuthenticatedUserId();
    requireAdminSession();

    $data = json_decode(file_get_contents('php://input'), true);
    $date = trim((string) ($data['date'] ?? ''));
    $description = trim((string) ($data['description'] ?? ''));

    if ($date === '') {
        echo json_encode(['success' => false, 'message' => 'Off-day date is required.']);
        exit;
    }

    $success = $service->saveOffDay($date, $description, $userId);
    echo json_encode(['success' => $success]);
    exit;
}

if ($action === 'delete_off_day') {
    $userId = requireAuthenticatedUserId();
    requireAdminSession();

    $data = json_decode(file_get_contents('php://input'), true);
    $date = trim((string) ($data['date'] ?? ''));

    if ($date === '') {
        echo json_encode(['success' => false, 'message' => 'Off-day date is required.']);
        exit;
    }

    $success = $service->deleteOffDay($date);
    echo json_encode(['success' => $success]);
    exit;
}



if ($action === 'get_facilitator_sessions') {
    $facId = $_GET['facilitator_id'] ?? 0;
    $sessions = $service->getFacilitatorSessions($facId);
    echo json_encode(['success' => true, 'sessions' => $sessions]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'API Endpoint Unrecognized']);
