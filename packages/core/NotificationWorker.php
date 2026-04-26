<?php
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/../phpmailer/Exception.php';
require_once __DIR__ . '/../phpmailer/PHPMailer.php';
require_once __DIR__ . '/../phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class NotificationWorker
{
    private static $mailConfig = null;

    private static function getMailConfig()
    {
        if (self::$mailConfig === null) {
            self::$mailConfig = require __DIR__ . '/email-config.php';
        }
        return self::$mailConfig;
    }

    private static function logNotification($message)
    {
        $logFile = __DIR__ . '/notification-dispatch.log';
        file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
    }

    private static function sendEmail($toEmail, $subject, $body)
    {
        if (!$toEmail || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            self::logNotification('Invalid recipient email: ' . (string) $toEmail);
            return false;
        }

        $config = self::getMailConfig();

        $mail = new PHPMailer(true); // true = enable exceptions

        try {
            // SMTP server settings
            $mail->isSMTP();
            $mail->Host       = $config['MAIL_HOST'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $config['MAIL_USERNAME'];
            $mail->Password   = $config['MAIL_PASSWORD'];
            $mail->SMTPSecure = $config['MAIL_ENCRYPTION'] === 'ssl'
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = (int) $config['MAIL_PORT'];
            $mail->SMTPDebug  = (int) ($config['MAIL_DEBUG'] ?? 0);
            $mail->Debugoutput = 'error_log';
            $mail->CharSet    = PHPMailer::CHARSET_UTF8;

            // Sender
            $mail->setFrom(
                $config['MAIL_FROM_EMAIL'],
                $config['MAIL_FROM_NAME']
            );

            // Reply-To
            if (!empty($config['MAIL_REPLY_TO'])) {
                $mail->addReplyTo($config['MAIL_REPLY_TO'], $config['MAIL_FROM_NAME']);
            }

            // Recipient
            $mail->addAddress($toEmail);

            // Content
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body    = $body;

            $mail->send();

            self::logNotification('Email sent to ' . $toEmail . ' | Subject: ' . $subject);
            return true;

        } catch (PHPMailerException $e) {
            self::logNotification('Email FAILED for ' . $toEmail . ' | Subject: ' . $subject . ' | Error: ' . $mail->ErrorInfo);
            error_log('PHPMailer error: ' . $mail->ErrorInfo);
            return false;
        } catch (\Exception $e) {
            self::logNotification('Email FAILED for ' . $toEmail . ' | Subject: ' . $subject . ' | Error: ' . $e->getMessage());
            error_log('Email error: ' . $e->getMessage());
            return false;
        }
    }

    public static function sendConfirmation($userId, $sessionId, $mode)
    {
        $db = (new Database())->getPdo();
        $stmt = $db->prepare('SELECT email, name FROM users WHERE id = ?');
        $stmt->execute([(int) $userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || empty($user['email'])) {
            self::logNotification('Confirmation email skipped. User/email not found for user_id=' . (int) $userId);
            return false;
        }

        $location = strtoupper((string) $mode) === 'ONLINE'
            ? 'Meeting setup will be sent by facilitator before your schedule.'
            : 'Venue: Main Library, Conference Room A';

        $subject = 'Library Appointment Confirmation';
        $body = "Hello " . ($user['name'] ?? 'Student') . ",\n\n";
        $body .= "Your appointment has been confirmed.\n\n";
        $body .= "Session ID: " . (int) $sessionId . "\n";
        $body .= "Mode: " . (string) $mode . "\n";
        $body .= $location . "\n\n";
        $body .= "If you have concerns, reply to this email or contact AUF Library.\n\n";
        $body .= "AUF Library Booking System";

        return self::sendEmail($user['email'], $subject, $body);
    }

    public static function sendAppointmentUpdate($sessionId, $status, $cancelledBy = null, $cancellationReason = null, $adminNote = null)
    {
        $db = (new Database())->getPdo();
        $stmt = $db->prepare("SELECT
                s.id,
                s.topic,
                s.date_time,
                s.end_time,
                s.mode,
                s.venue,
                s.status,
                COALESCE(u.name, s.requester_name, 'Student') AS student_name,
                COALESCE(u.email, s.requester_email, '') AS student_email,
                f.name AS facilitator_name
            FROM sessions s
            LEFT JOIN users u ON s.user_id = u.id
            LEFT JOIN facilitators f ON s.facilitator_id = f.id
            WHERE s.id = ?");
        $stmt->execute([(int) $sessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || empty($row['student_email'])) {
            self::logNotification('Appointment update email skipped. Session/email not found for session_id=' . (int) $sessionId);
            return false;
        }

        $normalizedStatus = strtoupper((string) $status);
        $subject = 'Appointment Update: ' . $normalizedStatus;
        $body = "Hello " . ($row['student_name'] ?? 'Student') . ",\n\n";
        $body .= "There is an update regarding your appointment.\n\n";
        $body .= "Appointment Details:\n";
        $body .= "- Session ID: " . (int) ($row['id'] ?? 0) . "\n";
        $body .= "- Topic: " . (string) ($row['topic'] ?? 'N/A') . "\n";
        $body .= "- Date/Time: " . (string) ($row['date_time'] ?? 'N/A') . "\n";
        $body .= "- End Time: " . (string) ($row['end_time'] ?? 'N/A') . "\n";
        $body .= "- Mode: " . (string) ($row['mode'] ?? 'N/A') . "\n";
        $body .= "- Venue/Link: " . (string) ($row['venue'] ?? 'TBA') . "\n";
        $body .= "- Facilitator: " . (string) ($row['facilitator_name'] ?? 'To Be Assigned') . "\n";
        $body .= "- Status: " . $normalizedStatus . "\n\n";

        if ($normalizedStatus === 'CANCELLED' || $normalizedStatus === 'DECLINED') {
            $body .= "This appointment was " . strtolower($normalizedStatus) . " by " . (string) ($cancelledBy ?: 'Admin') . ".\n";
            if (!empty($cancellationReason)) {
                $body .= "Reason: " . trim((string) $cancellationReason) . "\n";
            }
            $body .= "\n";
        }

        if ($normalizedStatus === 'CONFIRMED' && !empty($adminNote)) {
            $body .= "Confirmed Note from Admin:\n" . trim((string) $adminNote) . "\n\n";
        }

        if ($normalizedStatus === 'COMPLETED' && !empty($adminNote)) {
            $body .= "Completion Message:\n" . trim((string) $adminNote) . "\n\n";
        }

        if ($normalizedStatus !== 'CONFIRMED' && $normalizedStatus !== 'COMPLETED' && !empty($adminNote)) {
            $body .= "Admin Note:\n" . trim((string) $adminNote) . "\n\n";
        }

        $body .= "If you have questions, please contact AUF Library.\n";
        $body .= "Email: library@auf.edu.ph\nPhone: (045) 625-2888 local 712\n\n";
        $body .= "AUF Library Booking System";

        return self::sendEmail($row['student_email'], $subject, $body);
    }
}
