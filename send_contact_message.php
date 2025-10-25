<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

header('Content-Type: application/json');

// ====== EMAIL CONFIGURATION ======
// Choose your email provider: 'php_mail', 'sendgrid', or 'gmail'
define('EMAIL_PROVIDER', 'php_mail'); // Using PHP mail() - works immediately

// SendGrid Configuration (currently has credit limit issue)
define('SENDGRID_API_KEY', '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');
define('SENDGRID_FROM_EMAIL', 'deduyoroy02@gmail.com');
define('SENDGRID_FROM_NAME', 'Success Driving School');

// Gmail Configuration (app password invalid)
define('GMAIL_USERNAME', 'deduyoroy02@gmail.com');
define('GMAIL_PASSWORD', 'ntue ydcf abel nqnm');

// Recipient
define('RECIPIENT_EMAIL', 'successdrivingschool21@gmail.com');
define('RECIPIENT_NAME', 'Success Driving School');
define('FROM_EMAIL', 'noreply@successdrivingschool.local');
define('FROM_NAME', 'Success Driving School - Contact Form');
// =================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    // Validation
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        echo json_encode([
            'success' => false,
            'message' => 'All fields are required.'
        ]);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email address.'
        ]);
        exit;
    }
    
    try {
        if (EMAIL_PROVIDER === 'php_mail') {
            // ========== PHP MAIL() METHOD ==========
            $to = RECIPIENT_EMAIL;
            $emailSubject = 'Contact Form: ' . $subject;
            $emailHtml = generateEmailHTML($name, $email, $subject, $message);
            $emailText = generateEmailText($name, $email, $subject, $message);
            
            // Headers
            $headers = "From: " . FROM_NAME . " <" . FROM_EMAIL . ">\r\n";
            $headers .= "Reply-To: " . $name . " <" . $email . ">\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "X-Mailer: PHP/" . phpversion();
            
            // Send email
            $sent = mail($to, $emailSubject, $emailHtml, $headers);
            
            if ($sent) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Thank you! Your message has been sent successfully. We will get back to you soon.'
                ]);
            } else {
                throw new Exception('PHP mail() function failed');
            }
            
        } elseif (EMAIL_PROVIDER === 'sendgrid') {
            // ========== SENDGRID WEB API METHOD ==========
            $emailHtml = generateEmailHTML($name, $email, $subject, $message);
            $emailText = generateEmailText($name, $email, $subject, $message);
            
            $data = [
                'personalizations' => [[
                    'to' => [[
                        'email' => RECIPIENT_EMAIL,
                        'name' => RECIPIENT_NAME
                    ]],
                    'subject' => 'Contact Form: ' . $subject
                ]],
                'from' => [
                    'email' => SENDGRID_FROM_EMAIL,
                    'name' => SENDGRID_FROM_NAME
                ],
                'reply_to' => [
                    'email' => $email,
                    'name' => $name
                ],
                'content' => [
                    [
                        'type' => 'text/plain',
                        'value' => $emailText
                    ],
                    [
                        'type' => 'text/html',
                        'value' => $emailHtml
                    ]
                ]
            ];
            
            $ch = curl_init('https://api.sendgrid.com/v3/mail/send');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . SENDGRID_API_KEY,
                'Content-Type: application/json'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode >= 200 && $httpCode < 300) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Thank you! Your message has been sent successfully. We will get back to you soon.'
                ]);
            } else {
                error_log("SendGrid Error: HTTP $httpCode - $response");
                throw new Exception('SendGrid API request failed: ' . $response);
            }
            
        } else {
            // ========== GMAIL METHOD (SMTP) ==========
            $mail = new PHPMailer(true);
            
            // SMTP Configuration
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = GMAIL_USERNAME;
            $mail->Password   = GMAIL_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            
            // Email settings
            $mail->setFrom(GMAIL_USERNAME, 'Success Driving School - Contact Form');
            $mail->addAddress(RECIPIENT_EMAIL, RECIPIENT_NAME);
            $mail->addReplyTo($email, $name);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Contact Form: ' . $subject;
            $mail->Body = generateEmailHTML($name, $email, $subject, $message);
            $mail->AltBody = generateEmailText($name, $email, $subject, $message);
            
            $mail->send();
            
            echo json_encode([
                'success' => true,
                'message' => 'Thank you! Your message has been sent successfully. We will get back to you soon.'
            ]);
        }
        
    } catch (Exception $e) {
        error_log("Contact Form Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Sorry, there was an error sending your message. Please try again later.',
            'debug' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}

// Helper Functions
function generateEmailHTML($name, $email, $subject, $message) {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #FFC107 0%, #FFD54F 100%); padding: 20px; text-align: center; }
            .header h2 { margin: 0; color: #000; }
            .content { background: #f9f9f9; padding: 20px; margin-top: 20px; border-radius: 5px; }
            .field { margin-bottom: 15px; }
            .field strong { color: #000; display: inline-block; min-width: 100px; }
            .message-box { background: #fff; padding: 15px; border-left: 4px solid #FFC107; margin-top: 10px; }
            .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h2>New Contact Form Message</h2>
            </div>
            <div class='content'>
                <div class='field'>
                    <strong>From:</strong> " . htmlspecialchars($name) . "
                </div>
                <div class='field'>
                    <strong>Email:</strong> " . htmlspecialchars($email) . "
                </div>
                <div class='field'>
                    <strong>Subject:</strong> " . htmlspecialchars($subject) . "
                </div>
                <div class='message-box'>
                    <strong>Message:</strong><br><br>
                    " . nl2br(htmlspecialchars($message)) . "
                </div>
            </div>
            <div class='footer'>
                <p>This email was sent from the Contact Form on Success Driving School website</p>
                <p>&copy; 2017-2025 Success Technical & Vocational School, Inc.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

function generateEmailText($name, $email, $subject, $message) {
    return "New Contact Form Message\n\n" .
           "From: $name\n" .
           "Email: $email\n" .
           "Subject: $subject\n\n" .
           "Message:\n$message\n\n" .
           "---\n" .
           "This email was sent from the Contact Form on Success Driving School website";
}
?>
