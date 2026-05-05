<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/NotificationWorker.php';

class BookingService
{
    private $db;

    public function __construct()
    {
        $this->db = (new Database())->getPdo();
    }

    public function getSessionById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM sessions WHERE id = ? LIMIT 1");
        $stmt->execute([(int) $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
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

    public function getPrograms($departmentId = null)
    {
        if ($departmentId) {
            $stmt = $this->db->prepare("SELECT * FROM programs WHERE department_id = ? ORDER BY name ASC");
            $stmt->execute([$departmentId]);
        } else {
            $stmt = $this->db->query("SELECT * FROM programs ORDER BY name ASC");
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

    public function getDepartments()
    {
        $stmt = $this->db->query("SELECT * FROM department ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFacilitators($topicId = null)
    {
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

    public function addSession($facilitatorId, $topic, $dateTime, $mode)
    {
        $stmt = $this->db->prepare("INSERT INTO sessions (facilitator_id, topic, date_time, mode, status, created_date) VALUES (?, ?, ?, ?, 'AVAILABLE', datetime('now', 'localtime'))");
        $success = $stmt->execute([$facilitatorId, $topic, $dateTime, $mode]);
        if ($success) {
            $this->logSessionEvent((int) $this->db->lastInsertId(), 'created');
        }
        return $success;
    }

    public function getAvailableSessions()
    {
        $stmt = $this->db->query("SELECT id, facilitator_id, type, topic, date_time, end_time, mode, venue, status FROM sessions WHERE status IN ('AVAILABLE', 'CONFIRMED') ORDER BY created_date ASC");
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
                    requester_name = ?, requester_email = ?, requester_department_id = ?
                WHERE id = ?");
            $stmt->execute([
                $userId,
                $specialRequests,
                $parsed['name'],
                $parsed['email'],
                $requesterDepartmentId,
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



    private function parseLegacySpecialRequests($specialRequests)
    {
        $result = [
            'name' => '',
            'email' => '',
            'department' => ''
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

    public function removeSession($sessionId)
    {
        // Only allow removing available sessions
        $details = $this->getSessionLogDetails($sessionId);
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE id = ? AND status = 'AVAILABLE'");
        $success = $stmt->execute([$sessionId]);
        if ($success && $stmt->rowCount() > 0 && $details) {
            $this->insertSessionLogFromDetails($details, 'deleted');
        }
        return $success;
    }

    public function addFacilitator($name, $position = '', $topicIds = [], $departmentIds = [])
    {
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

    public function updateFacilitator($id, $name, $position = '', $topicIds = [], $departmentIds = [])
    {
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

    public function findUserById($userId)
    {
        $stmt = $this->db->prepare("SELECT id, name, email, department_id FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([(int) $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function createAdvancedBooking($type, $facilitatorId, $topic, $dateTime, $endTime, $mode, $userId, $requestDetails = [], $customRequestor = null)
    {
        // 1. Facilitator availability check (only for Instructional Programs where facilitator is selected)
        if (!empty($facilitatorId) && !in_array(strtolower((string) $type), ['seminar', 'orientation'])) {
            if ($this->hasFacilitatorConflict($facilitatorId, $dateTime, $endTime)) {
                throw new Exception('The selected facilitator is not available at that time.');
            }
        }

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
            if ($offDay && $creatorRole === 'general') {
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
                // If requestor email belongs to an existing user, bind by user_id.
                $resolvedUser = $this->findUserByEmail($requesterEmail);
                $effectiveUserId = $resolvedUser['id'] ?? null;
                if ($effectiveUserId) {
                    // Always store requester info - preserves session data if user is deleted/re-imported
                    $userInfo = $this->findUserById($effectiveUserId);
                    if ($userInfo) {
                        $requesterName = $userInfo['name'];
                        $requesterEmail = $userInfo['email'];
                        $requesterDepartmentId = $userInfo['department_id'];
                    }
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
                special_requests, requester_name, requester_email, requester_department_id, created_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'PENDING', ?, ?, ?, ?, datetime('now', 'localtime'))");

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
                $requesterDepartmentId
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

    /**
     * Checks if a facilitator has any CONFIRMED sessions that overlap with the requested time range.
     */
    private function hasFacilitatorConflict($facilitatorId, $startTime, $endTime, $excludeSessionId = null)
    {
        $sql = "
            SELECT COUNT(*) FROM sessions 
            WHERE facilitator_id = ? 
            AND status = 'CONFIRMED'
            AND (
                (date_time < ? AND COALESCE(end_time, datetime(date_time, '+1 hour')) > ?)
            )
        ";

        $params = [$facilitatorId, $endTime, $startTime];

        if ($excludeSessionId) {
            $sql .= " AND id != ?";
            $params[] = $excludeSessionId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function getAppointments($userId, $isAdmin = false, $facilitatorId = null, $filters = [])
    {
        $sql = "
                 SELECT s.id as session_id, s.type as appointment_type, s.topic, s.date_time, s.end_time, s.mode, s.venue,
                   s.status as booking_status, s.special_requests, s.outside_facilitator,
                     s.requester_name, s.requester_email, s.requester_department_id,
                     rd.name as requester_department,
                   s.cancellation_reason, s.cancelled_date_time, s.cancelled_by, s.evaluation_notes, s.archived_at,
                   f.name as facilitator_name, f.id as facilitator_id,
                                     COALESCE(u.name, s.requester_name, 'External Requestor') as student_name,
                                     COALESCE(u.email, s.requester_email, '') as student_email,
                                     COALESCE(ud.name, rd.name, '') as student_department
            FROM sessions s
            LEFT JOIN facilitators f ON s.facilitator_id = f.id
            LEFT JOIN users u ON s.user_id = u.id
                        LEFT JOIN department rd ON s.requester_department_id = rd.id
                        LEFT JOIN department ud ON u.department_id = ud.id
        ";

        $where = [];
        $params = [];

        if (!$isAdmin) {
            $where[] = "(s.user_id = ?" . (!empty($facilitatorId) ? " OR s.facilitator_id = ?" : "") . ")";
            $params[] = $userId;
            if (!empty($facilitatorId))
                $params[] = $facilitatorId;
        }

        if ($isAdmin) {
            if (!empty($filters['requestor']) && $filters['requestor'] !== 'all') {
                $where[] = "(s.user_id = ? OR s.requester_name LIKE ?)";
                $params[] = $filters['requestor'];
                $params[] = '%' . $filters['requestor'] . '%';
            }
            if (!empty($filters['department']) && $filters['department'] !== 'all') {
                $where[] = "(u.department_id = ? OR s.requester_department_id = ?)";
                $params[] = $filters['department'];
                $params[] = $filters['department'];
            }
            if (!empty($filters['facilitator']) && $filters['facilitator'] !== 'all') {
                $where[] = "s.facilitator_id = ?";
                $params[] = $filters['facilitator'];
            }
            if (!empty($filters['date'])) {
                $where[] = "DATE(s.date_time) = ?";
                $params[] = $filters['date'];
            }
        }

        // Handle archiving filters
        $includeArchived = !empty($filters['include_archived']) && $filters['include_archived'] !== false;
        if ($includeArchived) {
            $where[] = "s.archived_at IS NOT NULL";
        } else {
            $where[] = "s.archived_at IS NULL";
        }
        $status = $filters['status'] ?? 'all';
        if ($status !== 'all') {
            $where[] = "s.status = ?";
            $params[] = $status;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(" AND ", $where);
        }

        $sortOrder = (isset($filters['datetime']) && $filters['datetime'] === 'oldest') ? 'ASC' : 'DESC';
        $sql .= " ORDER BY s.created_date $sortOrder ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateAppointment($sessionId, $status, $venue, $facilitatorId, $cancellationReason = null, $cancelledBy = null, $evaluationNotes = null, $outsideFacilitator = null)
    {
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

            // Check for facilitator conflict if confirming and facilitator is set
            if ($isConfirmedStatus && $facId) {
                $sessionStmt = $this->db->prepare("SELECT date_time, end_time FROM sessions WHERE id = ?");
                $sessionStmt->execute([$sessionId]);
                $session = $sessionStmt->fetch(PDO::FETCH_ASSOC);

                if ($session && !empty($session['date_time'])) {
                    $startTime = $session['date_time'];
                    $endTime = $session['end_time'] ?? $session['date_time'];

                    if ($this->hasFacilitatorConflict($facId, $startTime, $endTime, $sessionId)) {
                        throw new Exception('The selected facilitator is already booked for this time slot.');
                    }
                }
            }

            $stmt = $this->db->prepare("UPDATE sessions SET status = ?, venue = ?, facilitator_id = ?, outside_facilitator = ?, cancellation_reason = ?, cancelled_date_time = ?, cancelled_by = ?, evaluation_notes = ? WHERE id = ?");
            $stmt->execute([$normalizedStatus, $venue, $facId, $outsideFacilitator, ($reasonToSave !== '' ? $reasonToSave : null), $cancelledAt, $cancelledByValue, ($notesToSave !== '' ? $notesToSave : null), $sessionId]);
            $hasChanges = $stmt->rowCount() > 0;
            if ($hasChanges) {
                $this->logSessionEvent((int) $sessionId, 'modified');
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log('updateAppointment failed: ' . $e->getMessage());
            return $e->getMessage();
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

    public function cancelAppointment($sessionId, $cancellationReason = null, $cancelledBy = null)
    {
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

    public function archiveSession($id)
    {
        $stmt = $this->db->prepare("UPDATE sessions SET archived_at = CURRENT_TIMESTAMP WHERE id = ?");
        $success = $stmt->execute([$id]);
        if ($success) {
            $this->logSessionEvent((int) $id, 'archived');
        }
        return $success;
    }

    public function unarchiveSession($id)
    {
        $stmt = $this->db->prepare("UPDATE sessions SET archived_at = NULL WHERE id = ?");
        $success = $stmt->execute([$id]);
        if ($success) {
            $this->logSessionEvent((int) $id, 'unarchived');
        }
        return $success;
    }

    public function bulkArchiveSessions(array $ids): bool
    {
        if (empty($ids))
            return true;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("UPDATE sessions SET archived_at = CURRENT_TIMESTAMP WHERE id IN ($placeholders)");
        $success = $stmt->execute(array_values($ids));
        if ($success) {
            foreach ($ids as $id) {
                $this->logSessionEvent((int) $id, 'archived');
            }
        }
        return $success;
    }

    public function bulkUnarchiveSessions(array $ids): bool
    {
        if (empty($ids))
            return true;
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("UPDATE sessions SET archived_at = NULL WHERE id IN ($placeholders)");
        $success = $stmt->execute(array_values($ids));
        if ($success) {
            foreach ($ids as $id) {
                $this->logSessionEvent((int) $id, 'unarchived');
            }
        }
        return $success;
    }

    public function changeInstructor($sessionId, $facilitatorId = null)
    {
        $facId = ($facilitatorId && $facilitatorId !== 'null' && $facilitatorId !== '0') ? (int) $facilitatorId : null;

        if ($facId) {
            // Check for conflicts
            $session = $this->getSessionById($sessionId);
            if (!$session)
                throw new Exception("Session not found.");

            $stmt = $this->db->prepare("SELECT id FROM sessions 
                                       WHERE facilitator_id = ? 
                                       AND status = 'CONFIRMED' 
                                       AND id != ? 
                                       AND SUBSTR(date_time, 1, 10) = SUBSTR(?, 1, 10)
                                       AND (
                                           (date_time < ? AND end_time > ?)
                                       )");
            $stmt->execute([$facId, $sessionId, $session['date_time'], $session['end_time'], $session['date_time']]);
            if ($stmt->fetch()) {
                throw new Exception("The selected instructor is already booked for this time slot.");
            }
        }

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
                                   COALESCE(s.outside_facilitator, f.name, '') AS facilitator_name,
                                   COALESCE(u.name, s.requester_name, '') AS user_name,
                                   COALESCE(u.email, s.requester_email, '') AS requester_email,
                                   COALESCE(d.name, rd.name, '') AS department_name
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

        $fmt = function ($val, $default = 'N/A') {
            $v = trim((string) ($val ?? ''));
            return $v !== '' ? $v : $default;
        };

        $stmt = $this->db->prepare('INSERT INTO session_logs (session_id, facilitator, user, requester_email, department, topic, action, log_date, session_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $details['session_id'] ?? null,
            $fmt($details['facilitator_name'] ?? '', 'TBA'),
            $fmt($details['user_name'] ?? ''),
            $fmt($details['requester_email'] ?? ''),
            $fmt($details['department_name'] ?? ''),
            $fmt($details['topic'] ?? ''),
            $action,
            date('Y-m-d H:i:s'),
            $fmt($details['status'] ?? '')
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
        $sql = "SELECT * FROM session_logs
                WHERE log_date >= ?
                ORDER BY log_date DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$fromDateTime]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function exportSessionsWithDetails()
    {
        $sql = "
            SELECT 
                s.id as session_id,
                s.type as appointment_type,
                s.topic,
                s.date_time,
                s.end_time,
                s.mode,
                s.venue,
                s.status as booking_status,
                s.special_requests,
                s.cancellation_reason,
                s.evaluation_notes,
                COALESCE(u.name, s.requester_name, 'External Requestor') as student_name,
                COALESCE(u.email, s.requester_email, '') as student_email,
                u.student_number,
                u.year_level,
                u.enrollment_status,
                p.name as program_name,
                COALESCE(ud.name, rd.name, '') as student_department,
                f.name as facilitator_name
            FROM sessions s
            LEFT JOIN users u ON s.user_id = u.id
            LEFT JOIN facilitators f ON s.facilitator_id = f.id
            LEFT JOIN department rd ON s.requester_department_id = rd.id
            LEFT JOIN department ud ON u.department_id = ud.id
            LEFT JOIN programs p ON u.course_program = p.id
            ORDER BY s.created_date DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
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


    public function getUserInfo($userId)
    {
        $stmt = $this->db->prepare("SELECT u.id, u.name, u.email, u.student_number, u.role, u.department_id, u.facilitator_id, d.name as department_name,
                                          u.user_type, u.year_level, u.course_program, u.enrollment_status, p.name as program_name
                                   FROM users u 
                                   LEFT JOIN department d ON u.department_id = d.id 
                                   LEFT JOIN programs p ON u.course_program = p.id
                                   WHERE u.id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function authenticateUser($email, $password)
    {
        $normalizedEmail = strtolower(trim((string) $email));
        $stmt = $this->db->prepare("SELECT id, name, email, password, role, facilitator_id FROM users WHERE LOWER(email) = ? LIMIT 1");
        $stmt->execute([$normalizedEmail]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return null;
        }

        if (!password_verify($password, $user['password'])) {
            return null;
        }

        unset($user['password']); // Don't return hashed password
        return $user;
    }

    private function normalizeRole($role)
    {
        $normalized = strtolower(trim((string) $role));
        if (!in_array($normalized, ['general', 'staff', 'admin'], true)) {
            return 'general';
        }
        return $normalized;
    }

    public function submitRegistrationRequest($studentNumber, $name, $email, $password, $departmentId, $requestedRole = 'general', $userType = 'non-student', $yearLevel = null, $courseProgram = null, $enrollmentStatus = null, $enrollmentType = null)
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

        $roleToStore = $this->normalizeRole($requestedRole);
        $deptId = !empty($departmentId) ? (int) $departmentId : null;

        $stmt = $this->db->prepare("INSERT INTO registration_requests (student_number, name, email, password, department_id, requested_role, user_type, year_level, course_program, enrollment_status)
                                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            trim((string) $studentNumber),
            trim((string) $name),
            $normalizedEmail,
            password_hash((string) $password, PASSWORD_DEFAULT),
            $deptId,
            $roleToStore,
            $userType,
            $yearLevel,
            $courseProgram,
            $enrollmentStatus
        ]);
    }

    public function getRegistrationRequests($status = null)
    {
        $sql = "SELECT rr.*, d.name AS department_name, reviewer.name AS reviewed_by_name, p.name AS course_program_name
                FROM registration_requests rr
                LEFT JOIN department d ON rr.department_id = d.id
                LEFT JOIN users reviewer ON rr.reviewed_by = reviewer.id
                LEFT JOIN programs p ON rr.course_program = p.id";

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

    public function approveRegistrationRequest($requestId, $approvedByUserId, $role = 'general', $departmentId = null, $facilitatorEnabled = false, $userType = 'non-student', $yearLevel = null, $courseProgram = null, $enrollmentStatus = null)
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

            $roleToSave = $this->normalizeRole($role ?: ($request['requested_role'] ?? 'general'));
            $deptToSave = !empty($departmentId) ? (int) $departmentId : (!empty($request['department_id']) ? (int) $request['department_id'] : null);

            $createStmt = $this->db->prepare("INSERT INTO users (student_number, name, email, role, password, department_id, facilitator_id, user_type, year_level, course_program, enrollment_status)
                                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $createStmt->execute([
                trim((string) ($request['student_number'] ?? '')),
                trim((string) ($request['name'] ?? '')),
                $normalizedEmail,
                $roleToSave,
                (string) ($request['password'] ?? ''),
                $deptToSave,
                null,
                $userType ?: ($request['user_type'] ?? 'non-student'),
                $yearLevel ?: ($request['year_level'] ?? null),
                $courseProgram ?: ($request['course_program'] ?? null),
                $enrollmentStatus ?: ($request['enrollment_status'] ?? null)
            ]);

            $newUserId = (int) $this->db->lastInsertId();
            $shouldBeFacilitator = filter_var($facilitatorEnabled, FILTER_VALIDATE_BOOL);
            if ($shouldBeFacilitator) {
                $facilitatorId = $this->syncFacilitatorFromUser(
                    $newUserId,
                    $request['name'] ?? '',
                    $deptToSave,
                    null
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
                                 u.user_type, u.year_level, u.course_program, u.enrollment_status, p.name AS program_name, p.id AS program_id,
                                 CASE WHEN u.facilitator_id IS NOT NULL THEN 1 ELSE 0 END AS is_facilitator,
                                 d.name AS department_name, f.name AS facilitator_name
                                 FROM users u
                                 LEFT JOIN department d ON u.department_id = d.id
                                 LEFT JOIN facilitators f ON u.facilitator_id = f.id
                                 LEFT JOIN programs p ON u.course_program = p.id
                                 ORDER BY u.name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateUserProfile($userId, $data)
    {
        $userId = (int) $userId;
        $name = trim((string) ($data['name'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $deptId = !empty($data['department_id']) ? (int) $data['department_id'] : null;
        $studentNumber = trim((string) ($data['student_number'] ?? ''));
        $yearLevel = $data['year_level'] ?? null;
        $courseProgram = $data['course_program'] ?? null;
        $enrollmentStatus = $data['enrollment_status'] ?? null;
        $currentPassword = $data['current_password'] ?? '';
        $newPassword = $data['new_password'] ?? '';

        if ($name === '' || $email === '') {
            throw new Exception('Name and email are required.');
        }

        $emailCheck = $this->db->prepare("SELECT id FROM users WHERE LOWER(email) = ? AND id != ? LIMIT 1");
        $emailCheck->execute([$email, $userId]);
        if ($emailCheck->fetchColumn()) {
            throw new Exception('This email is already in use by another account.');
        }

        if ($newPassword !== '') {
            $userStmt = $this->db->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
            $userStmt->execute([$userId]);
            $currentSavedPassword = $userStmt->fetchColumn();
            if (!password_verify($currentPassword, $currentSavedPassword)) {
                throw new Exception('The current password you entered is incorrect.');
            }
        }

        $this->db->beginTransaction();
        try {
            $sql = "UPDATE users SET name = ?, email = ?, department_id = ?, student_number = ?, year_level = ?, course_program = ?, enrollment_status = ?";
            $params = [$name, $email, $deptId, $studentNumber, $yearLevel, $courseProgram, $enrollmentStatus];

            if ($newPassword !== '') {
                $sql .= ", password = ?";
                $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = ?";
            $params[] = $userId;

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);

            $userStmt = $this->db->prepare("SELECT facilitator_id FROM users WHERE id = ? LIMIT 1");
            $userStmt->execute([$userId]);
            $facilitatorId = $userStmt->fetchColumn();
            if ($facilitatorId) {
                $this->syncFacilitatorFromUser($userId, $name, $deptId, $facilitatorId);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function addUserByAdmin($name, $email, $password, $role = 'staff', $studentNumber = '', $departmentId = null, $facilitatorEnabled = false, $userType = 'non-student', $yearLevel = null, $courseProgram = null, $enrollmentStatus = null, $facilitatorId = null)
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

        if ($normalizedRole !== 'general') {
            $finalStudentNumber = '';
            $finalDepartmentId = null;
        }

        if ($shouldBeFacilitator || $facilitatorId) {
            $finalDepartmentId = null;
        }

        $this->db->beginTransaction();
        try {
            $insertStmt = $this->db->prepare("INSERT INTO users (student_number, name, email, role, password, department_id, facilitator_id, user_type, year_level, course_program, enrollment_status)
                                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $insertStmt->execute([
                $finalStudentNumber,
                $normalizedName,
                $normalizedEmail,
                $normalizedRole,
                password_hash($rawPassword, PASSWORD_DEFAULT),
                $finalDepartmentId,
                $facilitatorId,
                $userType,
                $yearLevel,
                $courseProgram,
                $enrollmentStatus
            ]);

            $userId = (int) $this->db->lastInsertId();

            if ($shouldBeFacilitator && !$facilitatorId) {
                $newFacId = $this->syncFacilitatorFromUser($userId, $normalizedName, null, null);
                if ($newFacId) {
                    $linkStmt = $this->db->prepare("UPDATE users SET facilitator_id = ? WHERE id = ?");
                    $linkStmt->execute([$newFacId, $userId]);
                }
            }

            $this->db->commit();
            return $userId;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function updateUserAdmin($data)
    {
        $id = $data['id'] ?? null;
        if (!$id)
            throw new Exception("User ID is required.");

        $updateFields = [];
        $params = [];

        $fields = [
            'name',
            'email',
            'role',
            'student_number',
            'user_type',
            'department_id',
            'course_program',
            'year_level',
            'enrollment_status',
            'facilitator_id'
        ];

        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                // Convert empty strings to null for IDs/levels
                $val = $data[$field];
                if ($val === '' && in_array($field, ['department_id', 'course_program', 'facilitator_id', 'year_level', 'enrollment_status'])) {
                    $val = null;
                }
                $params[] = $val;
            }
        }

        if (isset($data['password']) && !empty($data['password'])) {
            $updateFields[] = "password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        if (empty($updateFields))
            return true;

        $params[] = $id;
        $stmt = $this->db->prepare("UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?");
        return $stmt->execute($params);
    }

    public function deleteUserByAdmin($id)
    {
        $this->db->beginTransaction();
        try {
            $userStmt = $this->db->prepare("SELECT id, name, email, department_id, facilitator_id FROM users WHERE id = ? LIMIT 1");
            $userStmt->execute([(int) $id]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            // Preserve requester info in sessions before unlinking
            if ($user) {
                $stmt = $this->db->prepare("UPDATE sessions SET 
                    requester_name = COALESCE(NULLIF(requester_name, ''), ?),
                    requester_email = COALESCE(NULLIF(requester_email, ''), ?),
                    requester_department_id = COALESCE(requester_department_id, ?)
                    WHERE user_id = ?");
                $stmt->execute([$user['name'], $user['email'], $user['department_id'], (int) $id]);
            }
            $stmt = $this->db->prepare("UPDATE sessions SET user_id = NULL WHERE user_id = ?");
            $stmt->execute([(int) $id]);
            $facilitatorId = $user['facilitator_id'] ?? null;
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
                                   ORDER BY s.created_date ASC");
        $stmt->execute([$facilitatorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function deleteAllUsersByAdmin(int $currentUserId, array $ids = null): bool
    {
        $this->db->beginTransaction();
        try {
            if ($ids !== null) {
                // Filter out current user
                $ids = array_filter($ids, fn($id) => (int) $id !== $currentUserId);
                if (empty($ids)) {
                    $this->db->commit();
                    return true;
                }
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $this->db->prepare("SELECT id, email, name, department_id, facilitator_id FROM users WHERE id IN ($placeholders)");
                $stmt->execute(array_values($ids));
            } else {
                $stmt = $this->db->prepare("SELECT id, email, name, department_id, facilitator_id FROM users WHERE id != ?");
                $stmt->execute([$currentUserId]);
            }
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($users)) {
                $this->db->commit();
                return true;
            }
            // Preserve requester info before unlinking
            foreach ($users as $user) {
                $stmt = $this->db->prepare("UPDATE sessions SET 
                    requester_name = COALESCE(NULLIF(requester_name, ''), ?),
                    requester_email = COALESCE(NULLIF(requester_email, ''), ?),
                    requester_department_id = COALESCE(requester_department_id, ?)
                    WHERE user_id = ?");
                $stmt->execute([$user['name'], $user['email'], $user['department_id'], $user['id']]);
            }
            $userIds = array_column($users, 'id');
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));

            // Unlink user from sessions
            $stmt = $this->db->prepare("UPDATE sessions SET user_id = NULL WHERE user_id IN ($placeholders)");
            $stmt->execute($userIds);

            // Handle facilitator cleanup
            foreach ($users as $user) {
                if (!empty($user['facilitator_id'])) {
                    // Unlink facilitator from sessions
                    $stmt = $this->db->prepare("UPDATE sessions SET facilitator_id = NULL WHERE facilitator_id = ?");
                    $stmt->execute([$user['facilitator_id']]);
                    // Delete facilitator mappings
                    $stmt = $this->db->prepare("DELETE FROM topic_facilitators WHERE facilitator_id = ?");
                    $stmt->execute([$user['facilitator_id']]);
                    $stmt = $this->db->prepare("DELETE FROM department_facilitators WHERE facilitator_id = ?");
                    $stmt->execute([$user['facilitator_id']]);
                    // Delete facilitator record
                    $stmt = $this->db->prepare("DELETE FROM facilitators WHERE id = ?");
                    $stmt->execute([$user['facilitator_id']]);
                }
            }

            // Delete registration requests for these users' emails
            $emails = array_filter(array_column($users, 'email'));
            if (!empty($emails)) {
                $emailPlaceholders = implode(',', array_fill(0, count($emails), '?'));
                $stmt = $this->db->prepare("DELETE FROM registration_requests WHERE email IN ($emailPlaceholders)");
                $stmt->execute(array_values($emails));
            }

            // Delete all users except current
            $stmt = $this->db->prepare("DELETE FROM users WHERE id IN ($placeholders)");
            $stmt->execute($userIds);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }



    public function importUsersFromCSV($filePath)
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new Exception("Failed to read file.");
        }

        // Detect delimiter
        $firstLine = fgets($handle);
        rewind($handle);
        $separator = ',';
        if ($firstLine) {
            $separators = [',', ';', "\t", '|'];
            $counts = [];
            foreach ($separators as $sep) {
                $counts[$sep] = substr_count($firstLine, $sep);
            }
            arsort($counts);
            $separator = (max($counts) > 0) ? key($counts) : ',';
        }

        $results = [];
        $rowNum = 0;

        // Read header row and create mapping
        $headers = fgetcsv($handle, 0, $separator);
        if (!$headers) {
            fclose($handle);
            throw new Exception("CSV file is empty.");
        }

        // Strip BOM if present
        if (isset($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        }

        // Clean headers
        $headerMap = [];
        foreach ($headers as $index => $label) {
            $clean = strtolower(trim($label));
            $clean = str_replace([' ', '.', '_', '(', ')', '-'], '', $clean);
            $headerMap[$clean] = $index;
        }

        $getVal = function ($data, $keys) use ($headerMap) {
            foreach ($keys as $key) {
                if (isset($headerMap[$key])) {
                    return trim($data[$headerMap[$key]] ?? '');
                }
            }
            return null;
        };

        // Cache departments and programs for mapping
        $deptStmt = $this->db->query("SELECT id, LOWER(name) as name FROM department");
        $allDepts = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

        $progStmt = $this->db->query("SELECT id, LOWER(name) as name, department_id FROM programs");
        $allProgs = $progStmt->fetchAll(PDO::FETCH_ASSOC);

        $this->db->beginTransaction();
        try {
            while (($data = fgetcsv($handle, 0, $separator)) !== false) {
                $rowNum++;

                $name = $getVal($data, ['name', 'fullname', 'username']);
                $email = strtolower($getVal($data, ['email', 'emailaddress', 'useremail']) ?? '');
                $password = $getVal($data, ['password', 'pass', 'pw', 'userpassword', 'pwd']);
                $role = strtolower($getVal($data, ['role', 'userrole', 'privilege']) ?? 'general');
                $studentNumber = $getVal($data, ['studentnumber', 'studentno', 'idnumber', 'id']);
                $userType = strtolower($getVal($data, ['usertype', 'type', 'category']) ?? 'non-student');
                $yearLevel = $getVal($data, ['yearlevel', 'year', 'level', 'yr']);
                $programName = strtolower($getVal($data, ['programname', 'program', 'courseprogram']) ?? '');
                $departmentName = strtolower($getVal($data, ['departmentname', 'department', 'college']) ?? '');
                $enrollmentStatus = $getVal($data, ['enrollmentstatus', 'status', 'standing', 'enrollstatus']);

                if (!$name || !$email) {
                    $results[] = ['row' => $rowNum, 'success' => false, 'message' => 'Missing Name or Email'];
                    continue;
                }

                // Map department name to ID
                $deptId = null;
                if ($departmentName) {
                    foreach ($allDepts as $d) {
                        if ($d['name'] === $departmentName) {
                            $deptId = $d['id'];
                            break;
                        }
                    }
                }

                // Map program name to ID
                $courseProgramId = null;
                if ($programName) {
                    foreach ($allProgs as $p) {
                        if ($p['name'] === $programName) {
                            // If deptId is known, try to match it specifically
                            if ($deptId && $p['department_id'] == $deptId) {
                                $courseProgramId = $p['id'];
                                break;
                            }
                            // Fallback to first match if no deptId or no specific match yet
                            if (!$courseProgramId)
                                $courseProgramId = $p['id'];
                        }
                    }
                }

                $stmt = $this->db->prepare("SELECT id FROM users WHERE LOWER(email) = ?");
                $stmt->execute([$email]);
                $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!in_array($role, ['general', 'staff', 'admin']))
                    $role = 'general';

                if ($existingUser) {
                    $updateFields = [];
                    $params = [];
                    if ($studentNumber !== null) {
                        $updateFields[] = "student_number = ?";
                        $params[] = ($role === 'general' ? $studentNumber : null);
                    }
                    if ($name !== null) {
                        $updateFields[] = "name = ?";
                        $params[] = $name;
                    }
                    if ($role !== null) {
                        $updateFields[] = "role = ?";
                        $params[] = $role;
                    }
                    if ($deptId !== null) {
                        $updateFields[] = "department_id = ?";
                        $params[] = ($role === 'general' ? $deptId : null);
                    }
                    if ($userType !== null) {
                        $updateFields[] = "user_type = ?";
                        $params[] = ($role === 'general' ? $userType : 'non-student');
                    }
                    if ($yearLevel !== null) {
                        $updateFields[] = "year_level = ?";
                        $params[] = ($role === 'general' ? $yearLevel : null);
                    }
                    if ($courseProgramId !== null) {
                        $updateFields[] = "course_program = ?";
                        $params[] = ($role === 'general' ? $courseProgramId : null);
                    }
                    if ($enrollmentStatus !== null) {
                        $updateFields[] = "enrollment_status = ?";
                        $params[] = ($role === 'general' ? $enrollmentStatus : null);
                    }

                    if ($password) {
                        $updateFields[] = "password = ?";
                        $params[] = password_hash($password, PASSWORD_DEFAULT);
                    }

                    if (!empty($updateFields)) {
                        $params[] = $existingUser['id'];
                        $stmt = $this->db->prepare("UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?");
                        $stmt->execute($params);
                        $results[] = ['row' => $rowNum, 'success' => true, 'message' => 'Updated'];
                    } else {
                        $results[] = ['row' => $rowNum, 'success' => true, 'message' => 'No changes'];
                    }
                } else {
                    if (!$password)
                        $password = $studentNumber ?: explode('@', $email)[0];
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                    $stmt = $this->db->prepare("
                        INSERT INTO users (student_number, name, email, role, password, department_id, user_type, year_level, course_program, enrollment_status)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $role === 'general' ? $studentNumber : null,
                        $name,
                        $email,
                        $role,
                        $hashedPassword,
                        $role === 'general' ? $deptId : null,
                        $role === 'general' ? $userType : 'non-student',
                        $role === 'general' ? $yearLevel : null,
                        $role === 'general' ? $courseProgramId : null,
                        $role === 'general' ? $enrollmentStatus : null
                    ]);
                    $results[] = ['row' => $rowNum, 'success' => true, 'user_id' => $this->db->lastInsertId()];
                }
            }
            fclose($handle);
            $this->db->commit();
            return $results;
        } catch (Exception $e) {
            if ($this->db->inTransaction())
                $this->db->rollBack();
            if ($handle)
                fclose($handle);
            throw $e;
        }
    }
}
