<?php
session_start();
require_once "config.php";

$message = "";
$message_type = "";
$token_valid = false;
$email = "";

// Check if token is provided
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // Verify token
    $sql = "SELECT email, expires_at, used FROM password_resets WHERE token = ?";
    
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "s", $token);
        
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) == 1) {
                mysqli_stmt_bind_result($stmt, $email, $expires_at, $used);
                mysqli_stmt_fetch($stmt);
                
                // Check if token is expired or already used
                if ($used == 1) {
                    $message = "This reset link has already been used.";
                    $message_type = "error";
                } elseif (strtotime($expires_at) < time()) {
                    $message = "This reset link has expired. Please request a new one.";
                    $message_type = "error";
                } else {
                    $token_valid = true;
                }
            } else {
                $message = "Invalid reset link.";
                $message_type = "error";
            }
        }
        
        mysqli_stmt_close($stmt);
    }
} else {
    $message = "No reset token provided.";
    $message_type = "error";
}

// Handle password reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && $token_valid) {
    $new_password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $token = $_POST["token"];
    
    if (empty($new_password) || empty($confirm_password)) {
        $message = "Please fill in all fields.";
        $message_type = "error";
    } elseif (strlen($new_password) < 6) {
        $message = "Password must be at least 6 characters long.";
        $message_type = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = "Passwords do not match.";
        $message_type = "error";
    } else {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update user password
        $update_sql = "UPDATE users SET password = ? WHERE email = ?";
        
        if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
            mysqli_stmt_bind_param($update_stmt, "ss", $hashed_password, $email);
            
            if (mysqli_stmt_execute($update_stmt)) {
                // Mark token as used
                $mark_used_sql = "UPDATE password_resets SET used = 1 WHERE token = ?";
                
                if ($mark_stmt = mysqli_prepare($conn, $mark_used_sql)) {
                    mysqli_stmt_bind_param($mark_stmt, "s", $token);
                    mysqli_stmt_execute($mark_stmt);
                    mysqli_stmt_close($mark_stmt);
                }
                
                $message = "Password has been reset successfully! You can now log in with your new password.";
                $message_type = "success";
                $token_valid = false; // Hide the form after successful reset
            } else {
                $message = "Something went wrong. Please try again.";
                $message_type = "error";
            }
            
            mysqli_stmt_close($update_stmt);
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Success Driving School</title>
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
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #1e2129;
            color: white;
            padding: 20px;
        }
        
        .container {
            background-color: #282c34;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
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
        
        .password-field {
            position: relative;
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
        
        .toggle-password {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #9a9a9a;
            cursor: pointer;
        }
        
        .password-requirements {
            font-size: 12px;
            color: #9a9a9a;
            margin-top: 5px;
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
        
        .submit-btn:disabled {
            background-color: #666;
            cursor: not-allowed;
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
        
        .success-icon {
            color: #4caf50 !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">
            <img src="images/logo.png" alt="Success Driving Logo">
        </div>
        
        <?php if ($message_type == "success" && !$token_valid): ?>
            <div class="icon-wrapper">
                <i class="fas fa-check-circle success-icon"></i>
            </div>
        <?php else: ?>
            <div class="icon-wrapper">
                <i class="fas fa-key"></i>
            </div>
        <?php endif; ?>
        
        <h2 class="title">Reset Password</h2>
        
        <?php if(!empty($message)): ?>
            <div class="message <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($token_valid): ?>
            <p class="subtitle">
                Please enter your new password below.
            </p>
            
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]) . '?token=' . urlencode($token); ?>" method="post">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="form-group">
                    <label for="password">New Password</label>
                    <div class="password-field">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Enter new password" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('password', this)"></i>
                    </div>
                    <div class="password-requirements">
                        Password must be at least 6 characters long
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <div class="password-field">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm new password" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password', this)"></i>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-check"></i> Reset Password
                </button>
            </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="login.php"><i class="fas fa-arrow-left"></i> Back to Login</a>
        </div>
    </div>
    
    <script>
        function togglePassword(inputId, icon) {
            const passwordField = document.getElementById(inputId);
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
