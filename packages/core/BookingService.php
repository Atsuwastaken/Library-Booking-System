<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/NotificationWorker.php';

class BookingService {
    private $db;
    
    public function __construct() {
        $this->db = (new Database())->getPdo();
    }
    

    public function getTopics($departmentId = null)
    {
        if ($departmentId) {
            $stmt = $this->db->prepare("SELECT DISTINCT t.* FROM topics t 
                                       JOIN topic_departments td ON t.id = td.topic_id 
                                       WHERE td.department_id = ?
                                       ORDER BY t.name ASC");
            $stmt->execute([$departmentId]);
        } else {
            $stmt = $this->db->query("SELECT * FROM topics ORDER BY name ASC");
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopicDepartments($topicId)
    {
        $stmt = $this->db->prepare("SELECT DISTINCT d.id, d.name FROM department d 
                                   JOIN topic_departments td ON d.id = td.department_id 
                                   WHERE td.topic_id = ?
                                   ORDER BY d.name ASC");
        $stmt->execute([$topicId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopicCatalog()
    {
        $stmt = $this->db->query("SELECT t.id, t.name,
                                  GROUP_CONCAT(DISTINCT d.name) as departments,
                                  GROUP_CONCAT(DISTINCT td.department_id) as department_ids,
                                  GROUP_CONCAT(DISTINCT f.name) as facilitators,
                                  GROUP_CONCAT(DISTINCT tf.facilitator_id) as facilitator_ids
                                  FROM topics t
                                  LEFT JOIN topic_departments td ON t.id = td.topic_id
                                  LEFT JOIN department d ON td.department_id = d.id
                                  LEFT JOIN topic_facilitators tf ON t.id = tf.topic_id
                                  LEFT JOIN facilitators f ON tf.facilitator_id = f.id
                                  GROUP BY t.id
                                  ORDER BY t.name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addTopic($name, $departmentIds = [], $facilitatorIds = [])
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO topics (name) VALUES (?)");
            $stmt->execute([$name]);
            $topicId = (int) $this->db->lastInsertId();

            if (!empty($departmentIds)) {
                $mapStmt = $this->db->prepare("INSERT INTO topic_departments (topic_id, department_id) VALUES (?, ?)");
                foreach ($departmentIds as $deptId) {
                    $mapStmt->execute([$topicId, $deptId]);
                }
            }

            if (!empty($facilitatorIds)) {
                $facStmt = $this->db->prepare("INSERT INTO topic_facilitators (topic_id, facilitator_id, department_id) VALUES (?, ?, ?)");
                foreach ($facilitatorIds as $facilitatorId) {
                    if (!empty($departmentIds)) {
                        foreach ($departmentIds as $deptId) {
                            $facStmt->execute([$topicId, $facilitatorId, $deptId]);
                        }
                    } else {
                        $facStmt->execute([$topicId, $facilitatorId, null]);
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function updateTopic($id, $name, $departmentIds = [], $facilitatorIds = [])
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE topics SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);

            $delStmt = $this->db->prepare("DELETE FROM topic_departments WHERE topic_id = ?");
            $delStmt->execute([$id]);

            $delFacStmt = $this->db->prepare("DELETE FROM topic_facilitators WHERE topic_id = ?");
            $delFacStmt->execute([$id]);

            if (!empty($departmentIds)) {
                $mapStmt = $this->db->prepare("INSERT INTO topic_departments (topic_id, department_id) VALUES (?, ?)");
                foreach ($departmentIds as $deptId) {
                    $mapStmt->execute([$id, $deptId]);
                }
            }

            if (!empty($facilitatorIds)) {
                $facStmt = $this->db->prepare("INSERT INTO topic_facilitators (topic_id, facilitator_id, department_id) VALUES (?, ?, ?)");
                foreach ($facilitatorIds as $facilitatorId) {
                    if (!empty($departmentIds)) {
                        foreach ($departmentIds as $deptId) {
                            $facStmt->execute([$id, $facilitatorId, $deptId]);
                        }
                    } else {
                        $facStmt->execute([$id, $facilitatorId, null]);
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function deleteTopic($id)
    {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("DELETE FROM topic_departments WHERE topic_id = ?");
            $stmt->execute([$id]);

            $stmt = $this->db->prepare("DELETE FROM topic_facilitators WHERE topic_id = ?");
            $stmt->execute([$id]);

            $stmt = $this->db->prepare("DELETE FROM topics WHERE id = ?");
            $stmt->execute([$id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getDepartments() {
        $stmt = $this->db->query("SELECT * FROM department ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFacilitators($topicId = null) {
        $sql = "
            SELECT f.*, 
                   GROUP_CONCAT(DISTINCT t.name) as expertise,
                   GROUP_CONCAT(DISTINCT tf.topic_id) as topic_ids,
                   GROUP_CONCAT(DISTINCT d.name) as departments,
                   GROUP_CONCAT(DISTINCT df.department_id) as department_ids
            FROM facilitators f
            LEFT JOIN topic_facilitators tf ON f.id = tf.facilitator_id
            LEFT JOIN topics t ON tf.topic_id = t.id
            LEFT JOIN department_facilitators df ON f.id = df.facilitator_id
            LEFT JOIN department d ON df.department_id = d.id
        ";
        
        $params = [];
        if ($topicId) {
            $sql .= " WHERE f.id IN (SELECT facilitator_id FROM topic_facilitators WHERE topic_id = ?) ";
            $params[] = $topicId;
        }
        
        $sql .= " GROUP BY f.id ORDER BY f.name ASC ";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addSession($facilitatorId, $topic, $dateTime, $mode) {
        $stmt = $this->db->prepare("INSERT INTO sessions (facilitator_id, topic, date_time, mode, status) VALUES (?, ?, ?, ?, 'AVAILABLE')");
        $success = $stmt->execute([$facilitatorId, $topic, $dateTime, $mode]);
        if ($success) {
            $this->logSessionEvent((int) $this->db->lastInsertId(), 'created');
        }
        return $success;
    }

    public function getAvailableSessions()
    {
        $stmt = $this->db->query("SELECT * FROM sessions WHERE status = 'AVAILABLE' ORDER BY date_time ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function lockSession($sessionId)
    {
        $stmt = $this->db->prepare("UPDATE sessions SET status = 'LOCKED' WHERE id = ? AND status = 'AVAILABLE'");
        return $stmt->execute([$sessionId]);
    }

    public function unlockSession($sessionId)
    {
        $stmt = $this->db->prepare("UPDATE sessions SET status = 'AVAILABLE' WHERE id = ? AND status = 'LOCKED'");
        return $stmt->execute([$sessionId]);
    }

    public function confirmBooking($sessionId, $userId, $specialRequests = '')
    {
        $this->db->beginTransaction();
        try {
            $parsed = $this->parseLegacySpecialRequests($specialRequests);

            $requesterDepartmentId = null;
            if (!empty($parsed['department'])) {
                $deptStmt = $this->db->prepare('SELECT id FROM department WHERE LOWER(name) = LOWER(?) LIMIT 1');
                $deptStmt->execute([trim((string) $parsed['department'])]);
                $deptRow = $deptStmt->fetch(PDO::FETCH_ASSOC);
                $requesterDepartmentId = $deptRow['id'] ?? null;
            }

            $stmt = $this->db->prepare("UPDATE sessions
                SET status = 'CONFIRMED', user_id = ?, special_requests = ?,
                    requester_name = ?, requester_email = ?, requester_department_id = ?,
                    notification_minutes = ?
                WHERE id = ?");
            $stmt->execute([
                $userId,
                $specialRequests,
                $parsed['name'],
                $parsed['email'],
                $requesterDepartmentId,
                $parsed['reminder'],
                $sessionId
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('confirmBooking failed: ' . $e->getMessage());
            return false;
        }
    }

    private function normalizeReminderMinutes($reminder)
    {
        if ($reminder === null || $reminder === '') {
            return 30;
        }

        if (is_numeric($reminder)) {
            $value = (int) $reminder;
            return $value >= 0 ? $value : 30;
        }

        $text = strtolower((string) $reminder);
        if (preg_match('/(\d+)\s*(day|days)/', $text, $m)) {
            return (int) $m[1] * 1440;
        }
        if (preg_match('/(\d+)\s*(hour|hours|hr|hrs)/', $text, $m)) {
            return (int) $m[1] * 60;
        }
        if (preg_match('/(\d+)/', $text, $m)) {
            return (int) $m[1];
        }

        return 30;
    }

    private function parseLegacySpecialRequests($specialRequests)
    {
        $result = [
            'name' => '',
            'email' => '',
            'department' => '',
            'reminder' => 30
        ];

        if (!is_string($specialRequests) || trim($specialRequests) === '') {
            return $result;
        }

        $parts = explode(' | ', $specialRequests);
        foreach ($parts as $part) {
            $kv = explode(': ', $part, 2);
            if (count($kv) !== 2) {
                continue;
            }

            $key = strtolower(trim($kv[0]));
            $value = trim($kv[1]);

            if ($key === 'name') {
                $result['name'] = $value;
            } elseif ($key === 'email') {
                $result['email'] = $value;
            } elseif ($key === 'dept' || $key === 'department') {
                $result['department'] = $value;
            } elseif ($key === 'reminder') {
                $result['reminder'] = $this->normalizeReminderMinutes($value);
            }
        }

        return $result;
    }

    public function findUserByEmail($email)
    {
        $normalized = strtolower(trim((string) $email));
        if ($normalized === '') {
            return null;
        }

        $stmt = $this->db->prepare("SELECT u.id, u.name, u.email, d.name as department_name
                                   FROM users u
                                   LEFT JOIN department d ON u.department_id = d.id
                                   WHERE LOWER(u.email) = ?
                                   LIMIT 1");
        $stmt->execute([$normalized]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function removeSession($sessionId) {
        // Only allow removing available sessions
        $details = $this->getSessionLogDetails($sessionId);
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE id = ? AND status = 'AVAILABLE'");
        $success = $stmt->execute([$sessionId]);
        if ($success && $stmt->rowCount() > 0 && $details) {
            $this->insertSessionLogFromDetails($details, 'deleted');
        }
        return $success;
    }

    public function addFacilitator($name, $position = '', $topicIds = [], $departmentIds = []) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO facilitators (name, position) VALUES (?, ?)");
            $stmt->execute([$name, $position]);
            $facilitatorId = $this->db->lastInsertId();
            
            if (!empty($topicIds)) {
                $stmt = $this->db->prepare("INSERT INTO topic_facilitators (topic_id, facilitator_id, department_id) VALUES (?, ?, ?)");
                foreach ($topicIds as $tid) {
                    if (!empty($departmentIds)) {
                        foreach ($departmentIds as $did) {
                            $stmt->execute([$tid, $facilitatorId, $did]);
                        }
                    } else {
                        $stmt->execute([$tid, $facilitatorId, null]);
                    }
                }
            }

            if (!empty($departmentIds)) {
                $stmt = $this->db->prepare("INSERT INTO department_facilitators (department_id, facilitator_id) VALUES (?, ?)");
                foreach ($departmentIds as $did) {
                    $stmt->execute([$did, $facilitatorId]);
                }
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function updateFacilitator($id, $name, $position = '', $topicIds = [], $departmentIds = []) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("UPDATE facilitators SET name = ?, position = ? WHERE id = ?");
            $stmt->execute([$name, $position, $id]);
            
            $stmt = $this->db->prepare("DELETE FROM topic_facilitators WHERE facilitator_id = ?");
            $stmt->execute([$id]);

            $stmt = $this->db->prepare("DELETE FROM department_facilitators WHERE facilitator_id = ?");
            $stmt->execute([$id]);
            
            if (!empty($topicIds)) {
                $stmt = $this->db->prepare("INSERT INTO topic_facilitators (topic_id, facilitator_id, department_id) VALUES (?, ?, ?)");
                foreach ($topicIds as $tid) {
                    if (!empty($departmentIds)) {
                        foreach ($departmentIds as $did) {
                            $stmt->execute([$tid, $id, $did]);
                        }
                    } else {
                        $stmt->execute([$tid, $id, null]);
                    }
                }
            }

            if (!empty($departmentIds)) {
                $stmt = $this->db->prepare("INSERT INTO department_facilitators (department_id, facilitator_id) VALUES (?, ?)");
                foreach ($departmentIds as $did) {
                    $stmt->execute([$did, $id]);
                }
            }
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function deleteFacilitator($id)
    {
        $this->db->beginTransaction();
        try {
            // Remove sessions first
            $stmt = $this->db->prepare("DELETE FROM sessions WHERE facilitator_id = ?");
            $stmt->execute([$id]);

            $stmt = $this->db->prepare("DELETE FROM topic_facilitators WHERE facilitator_id = ?");
            $stmt->execute([$id]);

            $stmt = $this->db->prepare("DELETE FROM department_facilitators WHERE facilitator_id = ?");
            $stmt->execute([$id]);

            $stmt = $this->db->prepare("DELETE FROM facilitators WHERE id = ?");
            $stmt->execute([$id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function createAdvancedBooking($type, $facilitatorId, $topic, $dateTime, $endTime, $mode, $userId, $requestDetails = [], $customRequestor = null)
    {
        $this->db->beginTransaction();
        $effectiveUserId = null;
        $sessionId = null;
        try {
            $startTimestamp = strtotime((string) $dateTime);
            if ($startTimestamp === false) {
                throw new Exception('Invalid booking date/time.');
            }

            if ((int) date('w', $startTimestamp) === 0) {
                throw new Exception('Library bookings are closed on Sundays.');
            }

            $offDay = $this->getOffDayByDate(date('Y-m-d', $startTimestamp));
            $creator = $this->getUserInfo($userId);
            $creatorRole = strtolower((string) ($creator['role'] ?? ''));
            if ($offDay && $creatorRole === 'student') {
                $offDayReason = trim((string) ($offDay['description'] ?? ''));
                throw new Exception($offDayReason !== '' ? $offDayReason : 'This day is unavailable for booking.');
            }

            // Nullify facilitator for Seminar or Orientation
            if (in_array(strtolower((string) $type), ['seminar', 'orientation'])) {
                $facilitatorId = null;
            }

            $requesterName = trim((string) ($requestDetails['name'] ?? ''));
            $requesterEmail = trim((string) ($requestDetails['email'] ?? ''));
            $requesterDepartmentId = !empty($requestDetails['department']) ? (int) $requestDetails['department'] : null;
            $notes = trim((string) ($requestDetails['notes'] ?? '')); // Stored in special_requests
            $notificationMinutes = $this->normalizeReminderMinutes($requestDetails['reminder'] ?? 30);
            $isFacilitatorBooking = !empty($creator['facilitator_id']);

            // Staff bookings may override requestor context.
            if (is_array($customRequestor) && !empty($customRequestor)) {
                $requesterName = trim((string) ($customRequestor['name'] ?? $requesterName));
                $requesterEmail = trim((string) ($customRequestor['email'] ?? $requesterEmail));
                $requesterDepartmentId = !empty($customRequestor['dept_id']) ? (int) $customRequestor['dept_id'] : $requesterDepartmentId;
            }

            if ($isFacilitatorBooking) {
                // Facilitators must explicitly provide requester details; never default to facilitator profile.
                if ($requesterName === '' || $requesterEmail === '' || empty($requesterDepartmentId)) {
                    throw new Exception('Requestor name, email, and department are required for facilitator bookings.');
                }
            } else {
                // If requestor email belongs to an existing user, bind by user_id only.
                $resolvedUser = $this->findUserByEmail($requesterEmail);
                $effectiveUserId = $resolvedUser['id'] ?? null;

                if ($effectiveUserId) {
                    $requesterName = '';
                    $requesterEmail = '';
                    $requesterDepartmentId = null;
                } else {
                    // Non-existing requestors must provide identity fields.
                    if ($requesterName === '' || $requesterEmail === '' || empty($requesterDepartmentId)) {
                        throw new Exception('Requestor name, email, and department are required when requestor has no account.');
                    }
                }
            }

            $specialRequests = $notes;

            $stmt = $this->db->prepare("INSERT INTO sessions (
                user_id, type, facilitator_id, topic, date_time, end_time, mode, status,
                special_requests, requester_name, requester_email, requester_department_id, notification_minutes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING', ?, ?, ?, ?, ?)");

            $stmt->execute([
                $effectiveUserId,
                $type,
                $facilitatorId,
                $topic,
                $dateTime,
                $endTime,
                $mode,
                $specialRequests,
                $requesterName,
                $requesterEmail,
                $requesterDepartmentId,
                $notificationMinutes
            ]);

            $sessionId = $this->db->lastInsertId();
            $this->logSessionEvent((int) $sessionId, 'created');
            $this->db->commit();
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('createAdvancedBooking failed: ' . $e->getMessage());
            return false;
        }

        // Notification should not break booking success after commit.
        if ($effectiveUserId) {
            try {
                NotificationWorker::sendConfirmation($effectiveUserId, $sessionId, $mode);
            } catch (Throwable $notifyError) {
                error_log('createAdvancedBooking notification failed: ' . $notifyError->getMessage());
            }
        }

        return true;
    }

    public function getAppointments($userId, $isAdmin = false, $facilitatorId = null) {
        // Piggyback auto-archive and auto-purge on admin loads
        if ($isAdmin) {
            $this->autoArchiveStaleAppointments();
            $this->autoPurgeOldArchived();
        }

        $sql = "
                 SELECT s.id as session_id, s.type as appointment_type, s.topic, s.date_time, s.end_time, s.mode, s.venue,
                   s.status as booking_status, s.special_requests,
                     s.requester_name, s.requester_email, s.requester_department_id,
                     rd.name as requester_department,
                   s.notification_minutes, s.cancellation_reason, s.cancelled_date_time, s.cancelled_by, s.evaluation_notes,
                   f.name as facilitator_name, f.id as facilitator_id,
                                     COALESCE(u.name, s.requester_name, 'External Requestor') as student_name,
                                     COALESCE(u.email, s.requester_email, '') as student_email,
                                     COALESCE(ud.name, rd.name, '') as student_department
            FROM sessions s
            LEFT JOIN facilitators f ON s.facilitator_id = f.id
            LEFT JOIN users u ON s.user_id = u.id
                        LEFT JOIN department rd ON s.requester_department_id = rd.id
                        LEFT JOIN department ud ON u.department_id = ud.id
            WHERE s.archived_at IS NULL
        ";
        
        $params = [];
        if (!$isAdmin) {
            $sql .= " AND (s.user_id = ?";
            $params[] = $userId;

            if (!empty($facilitatorId)) {
                $sql .= " OR s.facilitator_id = ?";
                $params[] = $facilitatorId;
            }

            $sql .= ")";
        }
        
        $sql .= " ORDER BY s.date_time ASC ";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateAppointment($sessionId, $status, $venue, $facilitatorId, $cancellationReason = null, $cancelledBy = null, $evaluationNotes = null) {
        $this->db->beginTransaction();
        try {
            $facId = ($facilitatorId && $facilitatorId !== 'null' && $facilitatorId !== '0') ? $facilitatorId : null;
            $normalizedStatus = strtoupper((string) $status);
            $isClosedStatus = in_array($normalizedStatus, ['CANCELLED', 'DECLINED'], true);
            $isCompletedStatus = $normalizedStatus === 'COMPLETED';
            $isConfirmedStatus = $normalizedStatus === 'CONFIRMED';
            $reasonToSave = $isClosedStatus ? trim((string) ($cancellationReason ?? '')) : null;
            $cancelledAt = $isClosedStatus ? date('Y-m-d H:i:s') : null;
            $cancelledByValue = $isClosedStatus ? $cancelledBy : null;
            $notesToSave = ($isCompletedStatus || $isConfirmedStatus) ? trim((string) ($evaluationNotes ?? '')) : null;

            $stmt = $this->db->prepare("UPDATE sessions SET status = ?, venue = ?, facilitator_id = ?, cancellation_reason = ?, cancelled_date_time = ?, cancelled_by = ?, evaluation_notes = ? WHERE id = ?");
            $stmt->execute([$normalizedStatus, $venue, $facId, ($reasonToSave !== '' ? $reasonToSave : null), $cancelledAt, $cancelledByValue, ($notesToSave !== '' ? $notesToSave : null), $sessionId]);
            $hasChanges = $stmt->rowCount() > 0;
            if ($hasChanges) {
                $this->logSessionEvent((int) $sessionId, 'modified');
                // Log decision for CONFIRMED / DECLINED
                if (in_array($normalizedStatus, ['CONFIRMED', 'DECLINED'], true)) {
                    $this->logDecision((int) $sessionId, $normalizedStatus, $reasonToSave, $notesToSave);
                }
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }

        if ($hasChanges) {
            try {
                NotificationWorker::sendAppointmentUpdate(
                    (int) $sessionId,
                    $normalizedStatus,
                    $cancelledByValue,
                    ($reasonToSave !== '' ? $reasonToSave : null),
                    ($notesToSave !== '' ? $notesToSave : null)
                );
            } catch (Throwable $notifyError) {
                error_log('updateAppointment notification failed: ' . $notifyError->getMessage());
            }
        }

        return true;
    }

    public function cancelAppointment($sessionId, $cancellationReason = null, $cancelledBy = null) {
        $this->db->beginTransaction();
        try {
            $reason = trim((string) ($cancellationReason ?? ''));
            $cancelledAt = date('Y-m-d H:i:s');
            $stmt = $this->db->prepare("UPDATE sessions SET status = 'CANCELLED', cancellation_reason = ?, cancelled_date_time = ?, cancelled_by = ? WHERE id = ?");
            $success = $stmt->execute([($reason !== '' ? $reason : null), $cancelledAt, $cancelledBy, $sessionId]);
            $hasChanges = $success && $stmt->rowCount() > 0;
            if ($hasChanges) {
                $this->logSessionEvent((int) $sessionId, 'modified');
            }
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }

        if ($hasChanges) {
            try {
                NotificationWorker::sendAppointmentUpdate(
                    (int) $sessionId,
                    'CANCELLED',
                    $cancelledBy,
                    ($reason !== '' ? $reason : null),
                    null
                );
            } catch (Throwable $notifyError) {
                error_log('cancelAppointment notification failed: ' . $notifyError->getMessage());
            }
        }

        return $success;
    }

    public function changeInstructorToTba($sessionId, $facilitatorId = null) {
        $facId = ($facilitatorId && $facilitatorId !== 'null' && $facilitatorId !== '0') ? $facilitatorId : null;
        $stmt = $this->db->prepare("UPDATE sessions SET status = 'PENDING', facilitator_id = ? WHERE id = ?");
        $success = $stmt->execute([$facId, $sessionId]);
        if ($success && $stmt->rowCount() > 0) {
            $this->logSessionEvent((int) $sessionId, 'modified');
        }
        return $success;
    }

    private function getSessionLogDetails($sessionId)
    {
        $stmt = $this->db->prepare("SELECT s.id AS session_id,
                                   s.topic,
                                   s.status,
                                   f.name AS facilitator_name,
                                   COALESCE(u.name, s.requester_name, '') AS user_name,
                                   COALESCE(u.email, s.requester_email, '') AS requester_email,
                                   COALESCE(d.name, rd.name, '') AS college_name
                                   FROM sessions s
                                   LEFT JOIN facilitators f ON s.facilitator_id = f.id
                                   LEFT JOIN users u ON s.user_id = u.id
                                   LEFT JOIN department d ON u.department_id = d.id
                                   LEFT JOIN department rd ON s.requester_department_id = rd.id
                                   WHERE s.id = ?");
        $stmt->execute([(int) $sessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function insertSessionLogFromDetails($details, $action)
    {
        if (!is_array($details) || empty($details)) {
            return;
        }

        $stmt = $this->db->prepare('INSERT INTO session_logs (session_id, facilitator, user, requester_email, college, topic, action, log_date, session_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $details['session_id'] ?? null,
            $details['facilitator_name'] ?? '',
            $details['user_name'] ?? '',
            $details['requester_email'] ?? '',
            $details['college_name'] ?? '',
            $details['topic'] ?? '',
            $action,
            date('Y-m-d H:i:s'),
            $details['status'] ?? ''
        ]);
    }

    private function logSessionEvent($sessionId, $action)
    {
        $details = $this->getSessionLogDetails((int) $sessionId);
        if (!$details) {
            return;
        }
        $this->insertSessionLogFromDetails($details, $action);
    }

    public function getSessionLogsSince($fromDateTime)
    {
        $stmt = $this->db->prepare("SELECT id, session_id, facilitator, user, requester_email, college, topic, action, log_date, session_status
                                   FROM session_logs
                                   WHERE datetime(log_date) >= datetime(?)
                                   ORDER BY datetime(log_date) DESC, id DESC");
        $stmt->execute([$fromDateTime]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* Decision Logs — only CONFIRMED / DECLINED */

    private function logDecision($sessionId, $decision, $reason = null, $notes = null)
    {
        $stmt = $this->db->prepare("SELECT s.id,
                                   s.type AS appointment_type, s.topic, s.venue, s.mode,
                                   s.date_time, s.end_time,
                                   COALESCE(f.name, '') AS facilitator_name,
                                   COALESCE(u.name, s.requester_name, '') AS requester_name,
                                   COALESCE(u.email, s.requester_email, '') AS requester_email,
                                   COALESCE(d.name, rd.name, '') AS college
                                   FROM sessions s
                                   LEFT JOIN facilitators f ON s.facilitator_id = f.id
                                   LEFT JOIN users u ON s.user_id = u.id
                                   LEFT JOIN department d ON u.department_id = d.id
                                   LEFT JOIN department rd ON s.requester_department_id = rd.id
                                   WHERE s.id = ?");
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) return;

        $ins = $this->db->prepare("INSERT INTO decision_logs
            (session_id, decision, decided_at, appointment_type, topic, facilitator_name, requester_name, requester_email, college, venue, appointment_date, appointment_end, mode, cancellation_reason, evaluation_notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([
            $sessionId,
            $decision,
            date('Y-m-d H:i:s'),
            $row['appointment_type'] ?? '',
            $row['topic'] ?? '',
            $row['facilitator_name'] ?? '',
            $row['requester_name'] ?? '',
            $row['requester_email'] ?? '',
            $row['college'] ?? '',
            $row['venue'] ?? '',
            $row['date_time'] ?? '',
            $row['end_time'] ?? '',
            $row['mode'] ?? '',
            ($reason !== null && $reason !== '') ? $reason : null,
            ($notes !== null && $notes !== '') ? $notes : null
        ]);
    }

    public function getDecisionLogs($decision = null)
    {
        $sql = "SELECT * FROM decision_logs";
        $params = [];
        if ($decision !== null && $decision !== '' && $decision !== 'all') {
            $sql .= " WHERE UPPER(decision) = ?";
            $params[] = strtoupper($decision);
        }
        $sql .= " ORDER BY datetime(decided_at) DESC, id DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* Seminar Management */
    public function getSeminars() {
        $stmt = $this->db->query("SELECT * FROM seminars ORDER BY date_time ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOffDays()
    {
        $this->ensureOffDaysTable();
        $stmt = $this->db->query("SELECT * FROM off_days ORDER BY date ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOffDayByDate($date)
    {
        $this->ensureOffDaysTable();
        $stmt = $this->db->prepare("SELECT * FROM off_days WHERE date = ? LIMIT 1");
        $stmt->execute([$date]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function saveOffDay($date, $description, $createdBy = null)
    {
        $this->ensureOffDaysTable();

        if ($this->offDaysHasColumn('created_by')) {
            $updateStmt = $this->db->prepare("UPDATE off_days SET description = ?, created_by = ?, created_at = CURRENT_TIMESTAMP WHERE date = ?");
            $updateStmt->execute([$description, $createdBy, $date]);

            if ($updateStmt->rowCount() > 0) {
                return true;
            }

            $insertStmt = $this->db->prepare("INSERT INTO off_days (date, description, created_by) VALUES (?, ?, ?)");
            return $insertStmt->execute([$date, $description, $createdBy]);
        }

        $updateStmt = $this->db->prepare("UPDATE off_days SET description = ?, created_at = CURRENT_TIMESTAMP WHERE date = ?");
        $updateStmt->execute([$description, $date]);

        if ($updateStmt->rowCount() > 0) {
            return true;
        }

        $insertStmt = $this->db->prepare("INSERT INTO off_days (date, description) VALUES (?, ?)");
        return $insertStmt->execute([$date, $description]);
    }

    public function deleteOffDay($date)
    {
        $this->ensureOffDaysTable();
        $stmt = $this->db->prepare("DELETE FROM off_days WHERE date = ?");
        return $stmt->execute([$date]);
    }

    private function ensureOffDaysTable()
    {
        $this->db->exec("CREATE TABLE IF NOT EXISTS off_days (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            date TEXT UNIQUE,
            description TEXT,
            created_by INTEGER,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(created_by) REFERENCES users(id)
        )");

        if (!$this->offDaysHasColumn('created_by')) {
            $this->db->exec("ALTER TABLE off_days ADD COLUMN created_by INTEGER");
        }

        if (!$this->offDaysHasColumn('created_at')) {
            $this->db->exec("ALTER TABLE off_days ADD COLUMN created_at TEXT DEFAULT CURRENT_TIMESTAMP");
        }
    }

    private function offDaysHasColumn($columnName)
    {
        $stmt = $this->db->query("PRAGMA table_info(off_days)");
        $columns = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        foreach ($columns as $col) {
            if (($col['name'] ?? '') === $columnName) {
                return true;
            }
        }

        return false;
    }

    public function addSeminar($title, $desc, $dt, $speaker, $venue) {
        $stmt = $this->db->prepare("INSERT INTO seminars (title, description, date_time, speaker, venue) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$title, $desc, $dt, $speaker, $venue]);
    }

    public function deleteSeminar($id) {
        $stmt = $this->db->prepare("DELETE FROM seminars WHERE id = ?");
        return $stmt->execute([$id]);
    }
    public function getUserInfo($userId)
    {
        $stmt = $this->db->prepare("SELECT u.id, u.name, u.email, u.student_number, u.role, u.department_id, u.facilitator_id, d.name as department_name 
                                   FROM users u 
                                   LEFT JOIN department d ON u.department_id = d.id 
                                   WHERE u.id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function authenticateUser($email, $password)
    {
        $stmt = $this->db->prepare("SELECT id, name, email, role, facilitator_id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return null;
        }

        $passwordStmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
        $passwordStmt->execute([$user['id']]);
        $storedPassword = $passwordStmt->fetchColumn();

        if ($storedPassword !== $password) {
            return null;
        }

        return $user;
    }

    private function normalizeRole($role)
    {
        $normalized = strtolower(trim((string) $role));
        if (!in_array($normalized, ['student', 'staff', 'admin'], true)) {
            return 'student';
        }
        return $normalized;
    }

    public function submitRegistrationRequest($studentNumber, $name, $email, $password, $departmentId, $requestedRole = 'student', $requestedFacilitatorId = null, $yearLevel = null, $course = null, $program = null, $section = null)
    {
        $normalizedEmail = strtolower(trim((string) $email));
        if ($normalizedEmail === '') {
            throw new Exception('Email is required.');
        }

        $existsUserStmt = $this->db->prepare("SELECT 1 FROM users WHERE LOWER(email) = ? LIMIT 1");
        $existsUserStmt->execute([$normalizedEmail]);
        if ($existsUserStmt->fetchColumn()) {
            throw new Exception('An account with this email already exists.');
        }

        $existsPendingStmt = $this->db->prepare("SELECT 1 FROM registration_requests WHERE LOWER(email) = ? AND UPPER(status) = 'PENDING' LIMIT 1");
        $existsPendingStmt->execute([$normalizedEmail]);
        if ($existsPendingStmt->fetchColumn()) {
            throw new Exception('A pending registration request already exists for this email.');
        }

        $roleToStore = $this->normalizeRole($requestedRole);
        $facilitatorId = !empty($requestedFacilitatorId) ? (int) $requestedFacilitatorId : null;
        $deptId = !empty($departmentId) ? (int) $departmentId : null;

        $stmt = $this->db->prepare("INSERT INTO registration_requests (student_number, name, email, password, department_id, requested_role, requested_facilitator_id, year_level, course, program, section, status, created_at)
                                   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', CURRENT_TIMESTAMP)");
        return $stmt->execute([
            trim((string) $studentNumber),
            trim((string) $name),
            $normalizedEmail,
            (string) $password,
            $deptId,
            $roleToStore,
            $facilitatorId,
            $yearLevel !== null ? trim((string) $yearLevel) : null,
            $course !== null ? trim((string) $course) : null,
            $program !== null ? trim((string) $program) : null,
            $section !== null ? trim((string) $section) : null
        ]);
    }

    public function getRegistrationRequests($status = null)
    {
        $sql = "SELECT rr.*, d.name AS department_name, f.name AS facilitator_name, reviewer.name AS reviewed_by_name
                FROM registration_requests rr
                LEFT JOIN department d ON rr.department_id = d.id
                LEFT JOIN facilitators f ON rr.requested_facilitator_id = f.id
                LEFT JOIN users reviewer ON rr.reviewed_by = reviewer.id";

        $params = [];
        if ($status !== null && $status !== '') {
            $sql .= " WHERE UPPER(rr.status) = UPPER(?)";
            $params[] = $status;
        }

        $sql .= " ORDER BY datetime(rr.created_at) DESC, rr.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function syncFacilitatorFromUser($userId, $name, $departmentId = null, $existingFacilitatorId = null)
    {
        $facilitatorName = trim((string) $name);
        if ($facilitatorName === '') {
            return null;
        }

        $position = 'Facilitator';
        $facilitatorId = !empty($existingFacilitatorId) ? (int) $existingFacilitatorId : null;

        if ($facilitatorId) {
            $updateStmt = $this->db->prepare("UPDATE facilitators SET name = ?, position = ? WHERE id = ?");
            $updateStmt->execute([$facilitatorName, $position, $facilitatorId]);
        } else {
            $insertStmt = $this->db->prepare("INSERT INTO facilitators (name, position) VALUES (?, ?)");
            $insertStmt->execute([$facilitatorName, $position]);
            $facilitatorId = (int) $this->db->lastInsertId();
        }

        $deleteDeptStmt = $this->db->prepare("DELETE FROM department_facilitators WHERE facilitator_id = ?");
        $deleteDeptStmt->execute([$facilitatorId]);

        if (!empty($departmentId)) {
            $insertDeptStmt = $this->db->prepare("INSERT INTO department_facilitators (department_id, facilitator_id) VALUES (?, ?)");
            $insertDeptStmt->execute([(int) $departmentId, $facilitatorId]);
        }

        $updateUserStmt = $this->db->prepare("UPDATE users SET facilitator_id = ? WHERE id = ?");
        $updateUserStmt->execute([$facilitatorId, (int) $userId]);

        return $facilitatorId;
    }

    private function removeFacilitatorLink($facilitatorId)
    {
        if (empty($facilitatorId)) {
            return;
        }

        $facilitatorId = (int) $facilitatorId;

        $stmt = $this->db->prepare("UPDATE sessions SET facilitator_id = NULL WHERE facilitator_id = ?");
        $stmt->execute([$facilitatorId]);

        $stmt = $this->db->prepare("DELETE FROM topic_facilitators WHERE facilitator_id = ?");
        $stmt->execute([$facilitatorId]);

        $stmt = $this->db->prepare("DELETE FROM department_facilitators WHERE facilitator_id = ?");
        $stmt->execute([$facilitatorId]);

        $stmt = $this->db->prepare("DELETE FROM facilitators WHERE id = ?");
        $stmt->execute([$facilitatorId]);
    }

    public function approveRegistrationRequest($requestId, $approvedByUserId, $role = 'student', $departmentId = null, $facilitatorEnabled = false)
    {
        $this->db->beginTransaction();
        try {
            $reqStmt = $this->db->prepare("SELECT * FROM registration_requests WHERE id = ? AND UPPER(status) = 'PENDING' LIMIT 1");
            $reqStmt->execute([(int) $requestId]);
            $request = $reqStmt->fetch(PDO::FETCH_ASSOC);
            if (!$request) {
                throw new Exception('Registration request not found or already processed.');
            }

            $normalizedEmail = strtolower(trim((string) ($request['email'] ?? '')));
            $existsUserStmt = $this->db->prepare("SELECT 1 FROM users WHERE LOWER(email) = ? LIMIT 1");
            $existsUserStmt->execute([$normalizedEmail]);
            if ($existsUserStmt->fetchColumn()) {
                throw new Exception('A user with this email already exists.');
            }

            $roleToSave = $this->normalizeRole($role ?: ($request['requested_role'] ?? 'student'));
            $deptToSave = !empty($departmentId) ? (int) $departmentId : (!empty($request['department_id']) ? (int) $request['department_id'] : null);

            $createStmt = $this->db->prepare("INSERT INTO users (student_number, name, email, role, password, department_id, facilitator_id, year_level, course, program, section)
                                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $createStmt->execute([
                trim((string) ($request['student_number'] ?? '')),
                trim((string) ($request['name'] ?? '')),
                $normalizedEmail,
                $roleToSave,
                (string) ($request['password'] ?? ''),
                $deptToSave,
                null,
                $request['year_level'] ?? null,
                $request['course'] ?? null,
                $request['program'] ?? null,
                $request['section'] ?? null
            ]);

            $newUserId = (int) $this->db->lastInsertId();
            $shouldBeFacilitator = filter_var($facilitatorEnabled, FILTER_VALIDATE_BOOL);
            if ($shouldBeFacilitator) {
                $facilitatorId = $this->syncFacilitatorFromUser(
                    $newUserId,
                    $request['name'] ?? '',
                    $deptToSave,
                    !empty($request['requested_facilitator_id']) ? (int) $request['requested_facilitator_id'] : null
                );

                if ($facilitatorId) {
                    $linkStmt = $this->db->prepare("UPDATE users SET facilitator_id = ? WHERE id = ?");
                    $linkStmt->execute([$facilitatorId, $newUserId]);
                }
            }

            $updateReqStmt = $this->db->prepare("UPDATE registration_requests
                                                 SET status = 'APPROVED', review_note = 'Approved', reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP
                                                 WHERE id = ?");
            $updateReqStmt->execute([(int) $approvedByUserId, (int) $requestId]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function rejectRegistrationRequest($requestId, $reviewedByUserId, $reason = null)
    {
        $note = trim((string) ($reason ?? ''));
        if ($note === '') {
            $note = 'Rejected';
        }

        $stmt = $this->db->prepare("UPDATE registration_requests
                                   SET status = 'REJECTED', review_note = ?, reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP
                                   WHERE id = ? AND UPPER(status) = 'PENDING'");
        $stmt->execute([$note, (int) $reviewedByUserId, (int) $requestId]);
        return true;
    }

    public function getUsersForAdmin()
    {
        $stmt = $this->db->query("SELECT u.id, u.student_number, u.name, u.email, u.role, u.department_id, u.facilitator_id,
                                 CASE WHEN u.facilitator_id IS NOT NULL THEN 1 ELSE 0 END AS is_facilitator,
                                 d.name AS department_name, f.name AS facilitator_name
                                 FROM users u
                                 LEFT JOIN department d ON u.department_id = d.id
                                 LEFT JOIN facilitators f ON u.facilitator_id = f.id
                                 ORDER BY u.name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addUserByAdmin($name, $email, $password, $role = 'staff', $studentNumber = '', $departmentId = null, $facilitatorEnabled = false)
    {
        $normalizedName = trim((string) $name);
        $normalizedEmail = strtolower(trim((string) $email));
        $rawPassword = (string) $password;

        if ($normalizedName === '' || $normalizedEmail === '' || $rawPassword === '') {
            throw new Exception('Name, email, and password are required.');
        }

        $existsUserStmt = $this->db->prepare("SELECT 1 FROM users WHERE LOWER(email) = ? LIMIT 1");
        $existsUserStmt->execute([$normalizedEmail]);
        if ($existsUserStmt->fetchColumn()) {
            throw new Exception('A user with this email already exists.');
        }

        $normalizedRole = $this->normalizeRole($role);
        $shouldBeFacilitator = filter_var($facilitatorEnabled, FILTER_VALIDATE_BOOL);

        $finalStudentNumber = trim((string) $studentNumber);
        $finalDepartmentId = !empty($departmentId) ? (int) $departmentId : null;

        if ($normalizedRole !== 'student') {
            $finalStudentNumber = '';
            $finalDepartmentId = null;
        }

        if ($shouldBeFacilitator) {
            $finalDepartmentId = null;
        }

        $this->db->beginTransaction();
        try {
            $insertStmt = $this->db->prepare("INSERT INTO users (student_number, name, email, role, password, department_id, facilitator_id)
                                             VALUES (?, ?, ?, ?, ?, ?, NULL)");
            $insertStmt->execute([
                $finalStudentNumber,
                $normalizedName,
                $normalizedEmail,
                $normalizedRole,
                $rawPassword,
                $finalDepartmentId
            ]);

            $userId = (int) $this->db->lastInsertId();

            if ($shouldBeFacilitator) {
                $facilitatorId = $this->syncFacilitatorFromUser($userId, $normalizedName, null, null);
                if ($facilitatorId) {
                    $linkStmt = $this->db->prepare("UPDATE users SET facilitator_id = ? WHERE id = ?");
                    $linkStmt->execute([$facilitatorId, $userId]);
                }
            }

            $this->db->commit();
            return $userId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateUserByAdmin($id, $name, $email, $studentNumber, $role, $departmentId = null, $facilitatorEnabled = false)
    {
        $normalizedRole = $this->normalizeRole($role);
        $deptId = !empty($departmentId) ? (int) $departmentId : null;
        $shouldBeFacilitator = filter_var($facilitatorEnabled, FILTER_VALIDATE_BOOL);

        $currentStmt = $this->db->prepare("SELECT facilitator_id FROM users WHERE id = ? LIMIT 1");
        $currentStmt->execute([(int) $id]);
        $currentFacilitatorId = $currentStmt->fetchColumn();

        $stmt = $this->db->prepare("UPDATE users
                                   SET name = ?, email = ?, student_number = ?, role = ?, department_id = ?
                                   WHERE id = ?");
        $stmt->execute([
            trim((string) $name),
            strtolower(trim((string) $email)),
            trim((string) $studentNumber),
            $normalizedRole,
            $deptId,
            (int) $id
        ]);

        if ($shouldBeFacilitator) {
            $facilitatorId = $this->syncFacilitatorFromUser((int) $id, $name, $deptId, $currentFacilitatorId ?: null);
            if ($facilitatorId) {
                $linkStmt = $this->db->prepare("UPDATE users SET facilitator_id = ? WHERE id = ?");
                $linkStmt->execute([$facilitatorId, (int) $id]);
            }
        } else {
            $clearStmt = $this->db->prepare("UPDATE users SET facilitator_id = NULL WHERE id = ?");
            $clearStmt->execute([(int) $id]);
            if (!empty($currentFacilitatorId)) {
                $this->removeFacilitatorLink($currentFacilitatorId);
            }
        }

        return $stmt->rowCount() > 0;
    }

    public function deleteUserByAdmin($id)
    {
        $this->db->beginTransaction();
        try {
            $userStmt = $this->db->prepare("SELECT facilitator_id FROM users WHERE id = ? LIMIT 1");
            $userStmt->execute([(int) $id]);
            $facilitatorId = $userStmt->fetchColumn();

            $stmt = $this->db->prepare("UPDATE sessions SET user_id = NULL WHERE user_id = ?");
            $stmt->execute([(int) $id]);

            if (!empty($facilitatorId)) {
                $this->removeFacilitatorLink($facilitatorId);
            }

            $deleteRequestsStmt = $this->db->prepare("DELETE FROM registration_requests WHERE email = (SELECT email FROM users WHERE id = ? LIMIT 1)");
            $deleteRequestsStmt->execute([(int) $id]);

            $deleteUserStmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $deleteUserStmt->execute([(int) $id]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getFacilitatorSessions($facilitatorId)
    {
        $stmt = $this->db->prepare("SELECT s.*,
                                   COALESCE(u.name, s.requester_name, 'External Requestor') as requestor_name,
                                   COALESCE(u.email, s.requester_email, '') as requestor_email,
                                   u.student_number as requestor_id,
                                   COALESCE(d.name, rd.name, '') as department_name
                                   FROM sessions s
                                   LEFT JOIN users u ON s.user_id = u.id
                                   LEFT JOIN department d ON u.department_id = d.id
                                   LEFT JOIN department rd ON s.requester_department_id = rd.id
                                   WHERE s.facilitator_id = ? AND s.status = 'CONFIRMED'
                                   ORDER BY s.date_time ASC");
        $stmt->execute([$facilitatorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ========== Appointment Archiving ========== */

    public function archiveAppointments($sessionIds)
    {
        if (empty($sessionIds) || !is_array($sessionIds)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($sessionIds), '?'));
        $now = date('Y-m-d H:i:s');
        $stmt = $this->db->prepare("UPDATE sessions SET archived_at = ? WHERE id IN ($placeholders) AND archived_at IS NULL");
        $params = array_merge([$now], array_map('intval', $sessionIds));
        $stmt->execute($params);

        // Log the archive event for each session
        foreach ($sessionIds as $sid) {
            $this->logSessionEvent((int) $sid, 'archived');
        }

        return $stmt->rowCount() > 0;
    }

    public function unarchiveAppointments($sessionIds)
    {
        if (empty($sessionIds) || !is_array($sessionIds)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($sessionIds), '?'));
        $stmt = $this->db->prepare("UPDATE sessions SET archived_at = NULL WHERE id IN ($placeholders) AND archived_at IS NOT NULL");
        $params = array_map('intval', $sessionIds);
        $stmt->execute($params);

        foreach ($sessionIds as $sid) {
            $this->logSessionEvent((int) $sid, 'unarchived');
        }

        return $stmt->rowCount() > 0;
    }

    public function getArchivedAppointments()
    {
        $this->autoArchiveStaleAppointments();
        $this->autoPurgeOldArchived();

        $sql = "
            SELECT s.id as session_id, s.type as appointment_type, s.topic, s.date_time, s.end_time, s.mode, s.venue,
                   s.status as booking_status, s.special_requests,
                   s.requester_name, s.requester_email, s.requester_department_id,
                   rd.name as requester_department,
                   s.notification_minutes, s.cancellation_reason, s.cancelled_date_time, s.cancelled_by, s.evaluation_notes,
                   s.archived_at,
                   f.name as facilitator_name, f.id as facilitator_id,
                   COALESCE(u.name, s.requester_name, 'External Requestor') as student_name,
                   COALESCE(u.email, s.requester_email, '') as student_email,
                   COALESCE(ud.name, rd.name, '') as student_department
            FROM sessions s
            LEFT JOIN facilitators f ON s.facilitator_id = f.id
            LEFT JOIN users u ON s.user_id = u.id
            LEFT JOIN department rd ON s.requester_department_id = rd.id
            LEFT JOIN department ud ON u.department_id = ud.id
            WHERE s.archived_at IS NOT NULL
            ORDER BY s.archived_at DESC
        ";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteArchivedAppointment($sessionId)
    {
        $sessionId = (int) $sessionId;

        // Log the permanent deletion before removing
        $this->logSessionEvent($sessionId, 'permanently_deleted');

        $stmt = $this->db->prepare("DELETE FROM sessions WHERE id = ? AND archived_at IS NOT NULL");
        $stmt->execute([$sessionId]);
        return $stmt->rowCount() > 0;
    }

    public function autoArchiveStaleAppointments()
    {
        $cutoff = date('Y-m-d H:i:s', strtotime('-1 month'));
        $now = date('Y-m-d H:i:s');

        // Auto-archive PENDING appointments older than 1 month
        $stmt = $this->db->prepare("UPDATE sessions
            SET archived_at = ?
            WHERE UPPER(status) = 'PENDING'
              AND archived_at IS NULL
              AND datetime(date_time) < datetime(?)");
        $stmt->execute([$now, $cutoff]);

        // Also auto-archive CANCELLED/DECLINED/COMPLETED older than 1 month
        $stmt2 = $this->db->prepare("UPDATE sessions
            SET archived_at = ?
            WHERE UPPER(status) IN ('CANCELLED', 'DECLINED', 'COMPLETED')
              AND archived_at IS NULL
              AND datetime(date_time) < datetime(?)");
        $stmt2->execute([$now, $cutoff]);
    }

    public function autoPurgeOldArchived()
    {
        $cutoff = date('Y-m-d H:i:s', strtotime('-4 months'));

        // Find sessions to purge so we can log them first
        $findStmt = $this->db->prepare("SELECT id FROM sessions
            WHERE archived_at IS NOT NULL
              AND datetime(archived_at) < datetime(?)");
        $findStmt->execute([$cutoff]);
        $toPurge = $findStmt->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($toPurge)) {
            foreach ($toPurge as $sid) {
                $this->logSessionEvent((int) $sid, 'auto_purged');
            }

            $placeholders = implode(',', array_fill(0, count($toPurge), '?'));
            $delStmt = $this->db->prepare("DELETE FROM sessions WHERE id IN ($placeholders)");
            $delStmt->execute(array_map('intval', $toPurge));
        }
    }
}
