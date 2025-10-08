<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader or require PHPMailer files
require_once 'phpmailer/PHPMailer.php';
require_once 'phpmailer/SMTP.php';
require_once 'phpmailer/Exception.php';

function sendPasswordResetEmail($to_email, $user_name, $reset_link) {
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
        $mail->addAddress($to_email, $user_name);
        $mail->addReplyTo('deduyoroy02@gmail.com', 'Success Driving School');
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request - Success Driving School';
        
        $mail->Body = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 0;
                }
                .email-container {
                    max-width: 600px;
                    margin: 20px auto;
                    background-color: #ffffff;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                }
                .header {
                    background-color: #282c34;
                    color: #ffffff;
                    padding: 30px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0;
                    color: #ffc107;
                    font-size: 28px;
                }
                .content {
                    padding: 40px 30px;
                    color: #333333;
                    line-height: 1.6;
                }
                .content h2 {
                    color: #282c34;
                    margin-top: 0;
                }
                .reset-button {
                    display: inline-block;
                    padding: 15px 30px;
                    background-color: #ffc107;
                    color: #282c34;
                    text-decoration: none;
                    border-radius: 5px;
                    font-weight: bold;
                    margin: 20px 0;
                }
                .reset-button:hover {
                    background-color: #e0a800;
                }
                .link-text {
                    background-color: #f4f4f4;
                    padding: 15px;
                    border-radius: 5px;
                    word-break: break-all;
                    font-size: 12px;
                    color: #666;
                    margin: 20px 0;
                }
                .footer {
                    background-color: #f4f4f4;
                    padding: 20px 30px;
                    text-align: center;
                    font-size: 12px;
                    color: #666666;
                }
                .warning {
                    background-color: #fff3cd;
                    border-left: 4px solid #ffc107;
                    padding: 15px;
                    margin: 20px 0;
                }
            </style>
        </head>
        <body>
            <div class="email-container">
                <div class="header">
                    <h1>🚗 Success Driving School</h1>
                </div>
                <div class="content">
                    <h2>Hello, ' . htmlspecialchars($user_name) . '!</h2>
                    <p>We received a request to reset your password. If you didn\'t make this request, you can safely ignore this email.</p>
                    
                    <p>To reset your password, click the button below:</p>
                    
                    <div style="text-align: center;">
                        <a href="' . $reset_link . '" class="reset-button">Reset My Password</a>
                    </div>
                    
                    <div class="warning">
                        <strong>⚠️ Important:</strong> This link will expire in 1 hour for security reasons.
                    </div>
                    
                    <p>If the button doesn\'t work, copy and paste this link into your browser:</p>
                    <div class="link-text">' . $reset_link . '</div>
                    
                    <p>If you have any questions or need assistance, please contact our support team.</p>
                    
                    <p>Best regards,<br><strong>Success Driving School Team</strong></p>
                </div>
                <div class="footer">
                    <p>© 2024 Success Driving School. All rights reserved.</p>
                    <p>This is an automated email. Please do not reply to this message.</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        // Plain text version for email clients that don't support HTML
        $mail->AltBody = "Hello $user_name,\n\n";
        $mail->AltBody .= "We received a request to reset your password.\n\n";
        $mail->AltBody .= "To reset your password, visit this link:\n";
        $mail->AltBody .= "$reset_link\n\n";
        $mail->AltBody .= "This link will expire in 1 hour.\n\n";
        $mail->AltBody .= "If you didn't request this, please ignore this email.\n\n";
        $mail->AltBody .= "Best regards,\nSuccess Driving School Team";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending failed: {$mail->ErrorInfo}");
        return false;
    }
}
?>
