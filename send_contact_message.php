<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/Exception.php';
require 'phpmailer/PHPMailer.php';
require 'phpmailer/SMTP.php';

header('Content-Type: application/json');

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
        $mail = new PHPMailer(true);
        
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'deduyoroy02@gmail.com';
        $mail->Password   = 'ntue ydcf abel nqnm';  // App password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Email settings
        $mail->setFrom('deduyoroy02@gmail.com', 'Success Driving School - Contact Form');
        $mail->addAddress('successdrivingschool21@gmail.com', 'Success Driving School');
        $mail->addReplyTo($email, $name);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Contact Form: ' . $subject;
        
        $mail->Body = "
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
        
        $mail->AltBody = "New Contact Form Message\n\n" .
                        "From: $name\n" .
                        "Email: $email\n" .
                        "Subject: $subject\n\n" .
                        "Message:\n$message\n\n" .
                        "---\n" .
                        "This email was sent from the Contact Form on Success Driving School website";
        
        $mail->send();
        
        echo json_encode([
            'success' => true,
            'message' => 'Thank you! Your message has been sent successfully. We will get back to you soon.'
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Sorry, there was an error sending your message. Please try again later.',
            'error' => $mail->ErrorInfo
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}
?>
