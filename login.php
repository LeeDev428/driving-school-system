<?php
session_start();
require_once "config.php";

$error = "";
$email = $password = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get email and password
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    
    // Validate email and password are not empty
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Search by email only - unified login for both admin and student
        $sql = "SELECT id, email, password, user_type, full_name FROM users WHERE email = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $email);
            
            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Store result
                mysqli_stmt_store_result($stmt);
                
                // Check if email exists
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    // Bind result variables
                    mysqli_stmt_bind_result($stmt, $id, $email, $hashed_password, $user_type, $full_name);
                    
                    if (mysqli_stmt_fetch($stmt)) {
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, store data in session variables
                            $_SESSION["loggedin"] = true;
                            $_SESSION["id"] = $id;
                            $_SESSION["email"] = $email;
                            $_SESSION["user_type"] = $user_type;
                            $_SESSION["full_name"] = $full_name;
                            
                            // Redirect based on user type
                            if ($user_type == "admin") {
                                header("location: admin/dashboard.php");
                                exit;
                            } else {
                                header("location: user/dashboard.php");
                                exit;
                            }
                        } else {
                            // Password is not valid
                            $error = "Invalid email or password.";
                        }
                    }
                } else {
                    // Email doesn't exist
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Close connection
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Success Driving School</title>
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
        
        .login-container {
            background-color: #282c34;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 400px;
            text-align: center;
            border: 1px solid #3a3f48;
        }
        
        .logo {
            margin-bottom: 30px;
        }
        
        .logo img {
            height: 80px;
        }
        
        .login-title {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 30px;
            color: #ffc107;
        }
        
        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
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
        }
        
        .password-field {
            position: relative;
        }
        
        .toggle-password {
            position: absolute;
            right: 10px;
            top: 12px;
            color: #9a9a9a;
            cursor: pointer;
        }
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
        }
        
        .checkbox-container input {
            margin-right: 5px;
        }
        
        .forgot-link {
            color: #ffc107;
            text-decoration: none;
            font-size: 14px;
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
        }
        
        .copyright {
            margin-top: 30px;
            color: #9a9a9a;
            font-size: 14px;
        }
        
        .error-message {
            color: #ff3333;
            margin-bottom: 15px;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <img src="images/logo.png" alt="Success Driving Logo">
        </div>
        
        <h2 class="login-title">Sign In to Your Account</h2>
        
        <?php if(!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            
            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" class="form-control" id="password" name="password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                </div>
            </div>
            
            <div class="remember-forgot">
                <div class="checkbox-container">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
                <a href="#" class="forgot-link">Forgot your password?</a>
            </div>
            
            <button type="submit" class="submit-btn">Login</button>
        </form>
        
        <div class="copyright">
            © 2024 Success Driving School. All rights reserved.
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleIcon = document.querySelector('.toggle-password');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>