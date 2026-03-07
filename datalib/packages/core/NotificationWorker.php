<?php
require_once __DIR__ . '/Database.php';

class NotificationWorker {
    public static function sendConfirmation($userId, $sessionId, $mode) {
        // Retrieve student email to dispatch message
        $db = (new Database())->getPdo();
        $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $studentEmail = $user ? $user['email'] : 'unknown@example.com';
        
        // Core asynchronous email triggering simulation handler
        // Differentiates online/onsite link logistics
        
        $location = ($mode === 'Online') 
            ? "Meeting Link: https://zoom.us/j/mockmeeting" . rand(1000, 9999) 
            : "Location: Main Library, Conference Room A";
            
        $logMessage = "[" . date('Y-m-d H:i:s') . "] ✉️ ASYNC EMAIL CONFIRMATION DISPATCHED\n";
        $logMessage .= "     To: $studentEmail (User ID: $userId)\n";
        $logMessage .= "     Session ID: $sessionId\n";
        $logMessage .= "     Modality: $mode\n";
        $logMessage .= "     Data: $location\n";
        $logMessage .= "---------------------------------------------------------\n";
        
        $logFile = __DIR__ . '/notification-dispatch.log';
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}
