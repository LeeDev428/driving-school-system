<?php
session_start();
require_once "config.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    
    if (empty($email)) {
        $message = "Please enter your email address.";
        $message_type = "error";
    } else {
        // Check if email exists in database
        $sql = "SELECT id, email, full_name FROM users WHERE email = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $email);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    mysqli_stmt_bind_result($stmt, $user_id, $user_email, $full_name);
                    mysqli_stmt_fetch($stmt);
                    
                    // Generate unique token
                    $token = bin2hex(random_bytes(32));
                    $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour'));
                    
                    // Store token in database
                    $insert_sql = "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)";
                    
                    if ($insert_stmt = mysqli_prepare($conn, $insert_sql)) {
                        mysqli_stmt_bind_param($insert_stmt, "sss", $email, $token, $expires_at);
                        
                        if (mysqli_stmt_execute($insert_stmt)) {
                            // Send email with reset link
                            require_once 'send_reset_email.php';
                            
                            $reset_link = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token=" . $token;
                            
                            if (sendPasswordResetEmail($user_email, $full_name, $reset_link)) {
                                $message = "Password reset link has been sent to your email address. Please check your inbox.";
                                $message_type = "success";
                            } else {
                                $message = "Failed to send email. Please try again later.";
                                $message_type = "error";
                            }
                        } else {
                            $message = "Something went wrong. Please try again later.";
                            $message_type = "error";
                        }
                        
                        mysqli_stmt_close($insert_stmt);
                    }
                } else {
                    // Don't reveal if email exists or not for security
                    $message = "If an account exists with this email, you will receive a password reset link shortly.";
                    $message_type = "success";
                }
            }
            
            mysqli_stmt_close($stmt);
        }
    }
    
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Success Driving School</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #1e2129;
            color: white;
        }
        
        .container {
            background-color: #282c34;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 450px;
            border: 1px solid #3a3f48;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo img {
            height: 80px;
        }
        
        .title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #ffc107;
            text-align: center;
        }
        
        .subtitle {
            font-size: 14px;
            color: #9a9a9a;
            margin-bottom: 30px;
            text-align: center;
            line-height: 1.6;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #9a9a9a;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 12px;
            border-radius: 5px;
            border: none;
            background-color: #1e2129;
            color: white;
            font-size: 14px;
        }
        
        .form-control:focus {
            outline: 2px solid #ffc107;
        }
        
        .submit-btn {
            width: 100%;
            padding: 12px;
            background-color: #ffc107;
            color: #282c34;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        
        .submit-btn:hover {
            background-color: #e0a800;
        }
        
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
        
        .back-link a {
            color: #ffc107;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link a:hover {
            text-decoration: underline;
        }
        
        .message {
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
        }
        
        .message.success {
            background-color: rgba(76, 175, 80, 0.2);
            color: #4caf50;
            border: 1px solid #4caf50;
        }
        
        .message.error {
            background-color: rgba(244, 67, 54, 0.2);
            color: #f44336;
            border: 1px solid #f44336;
        }
        
        .icon-wrapper {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .icon-wrapper i {
            font-size: 60px;
            color: #ffc107;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="assets/images/dss_logo.png" alt="Success Driving Logo">
        </div>
        
        <div class="icon-wrapper">
            <i class="fas fa-lock"></i>
        </div>
        
        <h2 class="title">Forgot Password?</h2>
        <p class="subtitle">
            Enter your email address and we'll send you a link to reset your password.
        </p>
        
        <?php if(!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required>
            </div>
            
            <button type="submit" class="submit-btn">
                <i class="fas fa-paper-plane"></i> Send Reset Link
            </button>
        </form>
        
        <div class="back-link">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
</body>
</html>
