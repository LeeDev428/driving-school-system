<?php
session_start();
require_once "config.php";

$error = "";
$success = "";
$full_name = $email = $password = $confirm_password = $license_type = $contact_number = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate name
    if (empty(trim($_POST["full_name"]))) {
        $error = "Please enter your full name.";
    } else {
        $full_name = trim($_POST["full_name"]);
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $error = "Please enter your email.";
    } else {
        // Prepare a select statement
        $sql = "SELECT id FROM users WHERE email = ?";
        
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_email);
            
            // Set parameters
            $param_email = trim($_POST["email"]);
            
            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                /* store result */
                mysqli_stmt_store_result($stmt);
                
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $error = "This email is already taken.";
                } else {
                    $email = trim($_POST["email"]);
                }
            } else {
                $error = "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $error = "Please enter a password.";     
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $error = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $error = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($error) && ($password != $confirm_password)) {
            $error = "Password did not match.";
        }
    }
    
    // Get license type
    $license_type = !empty($_POST["license_type"]) ? trim($_POST["license_type"]) : '';
    
    // Get contact number
    $contact_number = !empty($_POST["contact_number"]) ? trim($_POST["contact_number"]) : '';
    
    // Check input errors before inserting in database
    if (empty($error)) {
        
        // Prepare an insert statement
        $sql = "INSERT INTO users (full_name, email, password, license_type, contact_number, user_type) VALUES (?, ?, ?, ?, ?, 'student')";
         
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "sssss", $param_full_name, $param_email, $param_password, $param_license_type, $param_contact_number);
            
            // Set parameters
            $param_full_name = $full_name;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_license_type = $license_type;
            $param_contact_number = $contact_number;
            
            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Redirect to login page
                $success = "Registration successful! Please log in.";
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
    <title>Register - Success Driving School</title>
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
            padding: 40px 20px;
            overflow-y: auto;
        }
        
        .register-container {
            background-color: #282c34;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            text-align: center;
            border: 1px solid #3a3f48;
            margin: auto;
        }
        
        .logo {
            margin-bottom: 20px;
        }
        
        .logo img {
            height: 60px;
            max-width: 200px;
            object-fit: contain;
        }
        
        h2 {
            margin-bottom: 20px;
            font-weight: 600;
            color: #ffc107;
        }
        
        .form-group {
            margin-bottom: 15px;
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
            margin-top: 5px;
        }
        
        .login-link {
            margin-top: 20px;
            color: #9a9a9a;
        }
        
        .login-link a {
            color: #ffc107;
            text-decoration: none;
        }
        
        .copyright {
            margin-top: 20px;
            color: #9a9a9a;
            font-size: 14px;
        }
        
        .error-message {
            color: #ff3333;
            margin-bottom: 15px;
            font-size: 14px;
            text-align: center;
        }
        
        .success-message {
            color: #4CAF50;
            margin-bottom: 15px;
            font-size: 14px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="logo">
            <img src="assets/images/dss_logo.png" alt="Success Driving Logo">
        </div>
        
        <h2>Student Registration</h2>
        
        <?php if(!empty($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo $full_name; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo $email; ?>" required>
            </div>
            
            <div class="form-group">
                <label for="contact_number">Contact Number</label>
                <input type="tel" class="form-control" id="contact_number" name="contact_number" value="<?php echo $contact_number; ?>">
            </div>
            
            <div class="form-group">
                <label for="license_type">License Type</label>
                <select class="form-control" id="license_type" name="license_type">
                    <option value="">Select License Type</option>
                    <option value="Class A" <?php if($license_type == "Class A") echo "selected"; ?>>Class A</option>
                    <option value="Class B" <?php if($license_type == "Class B") echo "selected"; ?>>Class B</option>
                    <option value="Class C" <?php if($license_type == "Class C") echo "selected"; ?>>Class C</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" class="form-control" id="password" name="password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('password')"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-field">
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    <i class="fas fa-eye toggle-password" onclick="togglePassword('confirm_password')"></i>
                </div>
            </div>
            
            <button type="submit" class="submit-btn">Register</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="login.php">Login here</a>
        </div>
        
        <div class="copyright">
            © 2024 Success Driving School. All rights reserved.
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordField = document.getElementById(fieldId);
            const toggleIcon = passwordField.nextElementSibling;
            
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