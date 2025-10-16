<?php
/**
 * APPOINTMENT REMINDER EMAIL SYSTEM
 * 
 * This script sends email reminders to students 1 day before their appointments.
 * 
 * SETUP INSTRUCTIONS:
 * 1. Run this script as a scheduled task (cron job on Linux, Task Scheduler on Windows)
 * 2. Schedule it to run once daily at 8:00 AM
 * 
 * WINDOWS TASK SCHEDULER SETUP:
 * - Action: Start a program
 * - Program: C:\laragon\bin\php\php-8.4.3-Win32-vs16-x64\php.exe
 * - Arguments: "D:\laragon\www\driving-school-system\send_appointment_reminder.php"
 * - Trigger: Daily at 8:00 AM
 * 
 * LINUX CRON JOB SETUP:
 * - Run: crontab -e
 * - Add: 0 8 * * * /usr/bin/php /path/to/send_appointment_reminder.php
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once 'config.php';
require_once 'phpmailer/PHPMailer.php';
require_once 'phpmailer/SMTP.php';
require_once 'phpmailer/Exception.php';

// Log file for tracking email sends
$logFile = __DIR__ . '/logs/appointment_reminders.log';
if (!file_exists(dirname($logFile))) {
    mkdir(dirname($logFile), 0777, true);
}

function logMessage($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

function sendReminderEmail($to_email, $student_name, $course_type, $appointment_date, $appointment_time) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'deduyoroy02@gmail.com';
        $mail->Password   = 'ntue ydcf abel nqnm';  // App password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('deduyoroy02@gmail.com', 'Success Driving School');
        $mail->addAddress($to_email, $student_name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = '⏰ Reminder: Your Appointment is Tomorrow!';
        
        // Format date nicely
        $formatted_date = date('l, F j, Y', strtotime($appointment_date));
        $formatted_time = $appointment_time ? date('g:i A', strtotime($appointment_time)) : 'All Day';
        
        // Determine course name
        $course_name = ($course_type == 'TDC') ? 'Theoretical Driving Course' : 'Practical Driving Course';
        
        $mail->Body = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f9f9f9; }
        .header { background-color: #ffc107; color: #000; padding: 20px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { background-color: #ffffff; padding: 30px; border-radius: 0 0 5px 5px; }
        .appointment-details { background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
        .appointment-details h3 { margin-top: 0; color: #856404; }
        .detail-row { margin: 10px 0; }
        .detail-label { font-weight: bold; color: #555; }
        .footer { text-align: center; margin-top: 20px; color: #777; font-size: 12px; }
        .button { display: inline-block; background-color: #ffc107; color: #000; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🚗 Success Driving School</h1>
            <p>Appointment Reminder</p>
        </div>
        <div class="content">
            <h2>Hi {$student_name}!</h2>
            <p>This is a friendly reminder that your appointment is scheduled for <strong>TOMORROW</strong>.</p>
            
            <div class="appointment-details">
                <h3>📋 Appointment Details</h3>
                <div class="detail-row">
                    <span class="detail-label">Course:</span> {$course_name} ({$course_type})
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date:</span> {$formatted_date}
                </div>
                <div class="detail-row">
                    <span class="detail-label">Time:</span> {$formatted_time}
                </div>
            </div>
            
            <h3>⚠️ Important Reminders:</h3>
            <ul>
                <li>Please arrive <strong>15 minutes early</strong></li>
                <li>Bring a valid ID</li>
                <li>Bring your payment receipt (if not yet submitted)</li>
                <li>For PDC: Wear comfortable clothing and closed-toe shoes</li>
            </ul>
            
            <p>If you need to reschedule or have any questions, please contact us as soon as possible.</p>
            
            <center>
                <a href="http://localhost/driving-school-system/user/appointments.php" class="button">View My Appointments</a>
            </center>
            
            <p>We look forward to seeing you!</p>
            <p><strong>Success Driving School Team</strong></p>
        </div>
        <div class="footer">
            <p>This is an automated reminder. Please do not reply to this email.</p>
            <p>&copy; 2025 Success Driving School. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;
        
        $mail->AltBody = "Hi {$student_name}!\n\n"
                       . "Reminder: Your appointment is tomorrow!\n\n"
                       . "Course: {$course_name} ({$course_type})\n"
                       . "Date: {$formatted_date}\n"
                       . "Time: {$formatted_time}\n\n"
                       . "Please arrive 15 minutes early and bring a valid ID.\n\n"
                       . "Success Driving School";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        logMessage("Email send failed to {$to_email}: {$mail->ErrorInfo}");
        return false;
    }
}

// Start script
logMessage("========================================");
logMessage("Starting appointment reminder check...");

try {
    // Calculate tomorrow's date
    $tomorrow = date('Y-m-d', strtotime('+1 day'));
    logMessage("Checking for appointments on: {$tomorrow}");
    
    // Query to get appointments scheduled for tomorrow
    $sql = "SELECT 
                a.id,
                a.appointment_date,
                a.preferred_time,
                a.course_selection,
                u.id as student_id,
                u.full_name as student_name,
                u.email as student_email,
                a.status,
                a.reminder_sent
            FROM appointments a
            INNER JOIN users u ON a.student_id = u.id
            WHERE DATE(a.appointment_date) = ?
            AND a.status != 'cancelled'
            AND (a.reminder_sent IS NULL OR a.reminder_sent = 0)
            ORDER BY a.appointment_date, a.preferred_time";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $tomorrow);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $appointments = $result->fetch_all(MYSQLI_ASSOC);
    $totalAppointments = count($appointments);
    
    logMessage("Found {$totalAppointments} appointment(s) requiring reminders");
    
    $successCount = 0;
    $failCount = 0;
    
    foreach ($appointments as $appointment) {
        $studentName = $appointment['student_name'];
        $studentEmail = $appointment['student_email'];
        $courseType = $appointment['course_selection'];
        $appointmentDate = $appointment['appointment_date'];
        $appointmentTime = $appointment['preferred_time'];
        $appointmentId = $appointment['id'];
        
        logMessage("Processing: {$studentName} ({$studentEmail}) - {$courseType} on {$appointmentDate}");
        
        // Send email
        if (sendReminderEmail($studentEmail, $studentName, $courseType, $appointmentDate, $appointmentTime)) {
            // Mark as sent in database
            $updateSql = "UPDATE appointments SET reminder_sent = 1, reminder_sent_at = NOW() WHERE id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param('i', $appointmentId);
            $updateStmt->execute();
            
            $successCount++;
            logMessage("✓ Email sent successfully to {$studentEmail}");
        } else {
            $failCount++;
            logMessage("✗ Failed to send email to {$studentEmail}");
        }
    }
    
    logMessage("========================================");
    logMessage("Reminder check completed!");
    logMessage("Total: {$totalAppointments} | Success: {$successCount} | Failed: {$failCount}");
    logMessage("========================================");
    
} catch (Exception $e) {
    logMessage("ERROR: " . $e->getMessage());
    logMessage("========================================");
}

$conn->close();
?>
