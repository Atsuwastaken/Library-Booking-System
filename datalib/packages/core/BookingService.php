<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/NotificationWorker.php';

class BookingService {
    private $db;
    
    public function __construct() {
        $this->db = (new Database())->getPdo();
    }
    
    public function getAvailableSessions() {
        $now = time();
        $stmt = $this->db->prepare("
            SELECT s.*, f.name as facilitator_name 
            FROM sessions s
            JOIN facilitators f ON s.facilitator_id = f.id
            WHERE s.status IN ('AVAILABLE', 'BOOKED')
               OR (s.status = 'PENDING' AND s.locked_until < ?)
            ORDER BY s.date_time ASC
        ");
        $stmt->execute([$now]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function lockSession($sessionId) {
        $now = time();
        $lockUntil = $now + 300; // 5-minute checkout lock queue
        $stmt = $this->db->prepare("
            UPDATE sessions 
            SET status = 'PENDING', locked_until = ?
            WHERE id = ? AND (status = 'AVAILABLE' OR (status = 'PENDING' AND locked_until < ?))
        ");
        $stmt->execute([$lockUntil, $sessionId, $now]);
        return $stmt->rowCount() > 0;
    }
    
    public function unlockSession($sessionId) {
        // Releases the temporary lock if cart abandoned or user presses cancel
        $stmt = $this->db->prepare("UPDATE sessions SET status = 'AVAILABLE', locked_until = 0 WHERE id = ? AND status = 'PENDING'");
        $stmt->execute([$sessionId]);
        return $stmt->rowCount() > 0;
    }
    
    public function confirmBooking($sessionId, $userId, $specialRequests) {
        $this->db->beginTransaction();
        
        // Grab to read mode logic
        $stmt = $this->db->prepare("SELECT mode FROM sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$session) {
            $this->db->rollBack();
            return false;
        }
        
        $stmt = $this->db->prepare("UPDATE sessions SET status = 'BOOKED' WHERE id = ? AND status = 'PENDING'");
        $stmt->execute([$sessionId]);
        
        if ($stmt->rowCount() === 0) {
            // Lock expired or tampered
            $this->db->rollBack();
            return false;
        }
        
        // Store unstructured tracking and transaction mapping
        $stmt = $this->db->prepare("INSERT INTO bookings (user_id, session_id, special_requests, status) VALUES (?, ?, ?, 'CONFIRMED')");
        $stmt->execute([$userId, $sessionId, $specialRequests]);
        
        $this->db->commit();
        
        // Trigger generic asynchronous process map
        NotificationWorker::sendConfirmation($userId, $sessionId, $session['mode']);
        return true;
    }
        
    public function getFacilitators() {
        $stmt = $this->db->query("SELECT * FROM facilitators ORDER BY name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function addSession($facilitatorId, $topic, $dateTime, $mode) {
        $stmt = $this->db->prepare("INSERT INTO sessions (facilitator_id, topic, date_time, mode, status) VALUES (?, ?, ?, ?, 'AVAILABLE')");
        return $stmt->execute([$facilitatorId, $topic, $dateTime, $mode]);
    }

    public function removeSession($sessionId) {
        // Only allow removing available sessions
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE id = ? AND status = 'AVAILABLE'");
        return $stmt->execute([$sessionId]);
    }

    public function addFacilitator($name, $expertise) {
        $stmt = $this->db->prepare("INSERT INTO facilitators (name, expertise) VALUES (?, ?)");
        return $stmt->execute([$name, $expertise]);
    }

    public function updateFacilitator($id, $name, $expertise) {
        $stmt = $this->db->prepare("UPDATE facilitators SET name = ?, expertise = ? WHERE id = ?");
        return $stmt->execute([$name, $expertise, $id]);
    }

    public function deleteFacilitator($id) {
        $this->db->beginTransaction();
        try {
            // Remove sessions first
            $stmt = $this->db->prepare("DELETE FROM sessions WHERE facilitator_id = ?");
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

    public function createAdvancedBooking($facilitatorId, $topic, $dateTime, $mode, $userId, $specialRequests) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO sessions (facilitator_id, topic, date_time, mode, status) VALUES (?, ?, ?, ?, 'BOOKED')");
            $stmt->execute([$facilitatorId, $topic, $dateTime, $mode]);
            $sessionId = $this->db->lastInsertId();

            $stmt = $this->db->prepare("INSERT INTO bookings (user_id, session_id, special_requests, status) VALUES (?, ?, ?, 'CONFIRMED')");
            $stmt->execute([$userId, $sessionId, $specialRequests]);

            $this->db->commit();
            NotificationWorker::sendConfirmation($userId, $sessionId, $mode);
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
}
