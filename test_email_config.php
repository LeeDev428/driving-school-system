<?php
/**
 * Test Email Configuration
 * This script tests if PHPMailer is properly installed and configured
 * Run this file in your browser to test email sending
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Email Configuration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #ffc107;
            padding-bottom: 10px;
        }
        .status {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            font-weight: bold;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .test-section {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-left: 4px solid #ffc107;
        }
        button {
            background-color: #ffc107;
            color: #000;
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        button:hover {
            background-color: #e0a800;
        }
        pre {
            background-color: #282c34;
            color: #ffffff;
            padding: 15px;
            border-radius: 5px;
            overflow-x: auto;
        }
        .file-check {
            font-family: monospace;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Email Configuration Test</h1>
        
        <div class="test-section">
            <h2>Step 1: Check PHPMailer Files</h2>
            <?php
            $phpmailerFiles = [
                'phpmailer/PHPMailer.php',
                'phpmailer/SMTP.php',
                'phpmailer/Exception.php'
            ];
            
            $allFilesExist = true;
            foreach ($phpmailerFiles as $file) {
                if (file_exists($file)) {
                    echo "<div class='status success file-check'>✓ Found: $file</div>";
                } else {
                    echo "<div class='status error file-check'>✗ Missing: $file</div>";
                    $allFilesExist = false;
                }
            }
            
            if (!$allFilesExist) {
                echo "<div class='status warning'>";
                echo "<strong>⚠️ PHPMailer files are missing!</strong><br>";
                echo "Please run the PowerShell script: <code>install_phpmailer.ps1</code><br>";
                echo "Or download PHPMailer manually from GitHub.";
                echo "</div>";
            }
            ?>
        </div>
        
        <div class="test-section">
            <h2>Step 2: Check Database Table</h2>
            <?php
            require_once 'config.php';
            
            $tableExists = false;
            $result = mysqli_query($conn, "SHOW TABLES LIKE 'password_resets'");
            
            if (mysqli_num_rows($result) > 0) {
                echo "<div class='status success'>✓ Table 'password_resets' exists</div>";
                $tableExists = true;
                
                // Check table structure
                $columns = mysqli_query($conn, "DESCRIBE password_resets");
                echo "<div class='status info'>";
                echo "<strong>Table Structure:</strong><br>";
                echo "<ul style='margin: 10px 0;'>";
                while ($col = mysqli_fetch_assoc($columns)) {
                    echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
                }
                echo "</ul>";
                echo "</div>";
            } else {
                echo "<div class='status error'>✗ Table 'password_resets' does not exist</div>";
                echo "<div class='status warning'>";
                echo "<strong>⚠️ Database table is missing!</strong><br>";
                echo "Please run the SQL script: <code>add_password_resets_table.sql</code>";
                echo "</div>";
            }
            ?>
        </div>
        
        <div class="test-section">
            <h2>Step 3: Check Required Files</h2>
            <?php
            $requiredFiles = [
                'forgot_password.php',
                'reset_password.php',
                'send_reset_email.php'
            ];
            
            $allRequiredExist = true;
            foreach ($requiredFiles as $file) {
                if (file_exists($file)) {
                    echo "<div class='status success file-check'>✓ Found: $file</div>";
                } else {
                    echo "<div class='status error file-check'>✗ Missing: $file</div>";
                    $allRequiredExist = false;
                }
            }
            ?>
        </div>
        
        <div class="test-section">
            <h2>Step 4: Gmail Configuration</h2>
            <div class='status info'>
                <strong>📧 Email Settings:</strong><br>
                <strong>Email:</strong> deduyoroy02@gmail.com<br>
                <strong>App Password:</strong> ntue ydcf abel nqnm<br>
                <strong>SMTP Server:</strong> smtp.gmail.com<br>
                <strong>Port:</strong> 587 (TLS)
            </div>
        </div>
        
        <?php if ($allFilesExist && $tableExists && $allRequiredExist): ?>
        <div class="test-section">
            <h2>Step 5: Send Test Email</h2>
            
            <?php
            if (isset($_POST['test_email'])) {
                $testEmail = $_POST['email'];
                
                if (!empty($testEmail) && filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
                    require_once 'send_reset_email.php';
                    
                    $testLink = "http://" . $_SERVER['HTTP_HOST'] . "/test-reset-link";
                    
                    echo "<div class='status info'>📤 Sending test email to: <strong>$testEmail</strong></div>";
                    
                    if (sendPasswordResetEmail($testEmail, "Test User", $testLink)) {
                        echo "<div class='status success'>";
                        echo "✅ <strong>Success!</strong> Test email sent successfully!<br>";
                        echo "Please check the inbox (and spam folder) of: <strong>$testEmail</strong>";
                        echo "</div>";
                    } else {
                        echo "<div class='status error'>";
                        echo "❌ <strong>Failed!</strong> Could not send email.<br>";
                        echo "Possible issues:<br>";
                        echo "<ul>";
                        echo "<li>Check if PHPMailer files are correctly installed</li>";
                        echo "<li>Verify Gmail app password is correct</li>";
                        echo "<li>Make sure 2-Step Verification is enabled on Gmail</li>";
                        echo "<li>Check PHP error logs for details</li>";
                        echo "</ul>";
                        echo "</div>";
                    }
                } else {
                    echo "<div class='status error'>❌ Please enter a valid email address</div>";
                }
            }
            ?>
            
            <form method="post" style="margin-top: 20px;">
                <p>Enter an email address to receive a test password reset email:</p>
                <input type="email" name="email" placeholder="your.email@example.com" 
                       style="padding: 10px; width: 300px; border: 1px solid #ddd; border-radius: 5px;" required>
                <button type="submit" name="test_email">📧 Send Test Email</button>
            </form>
        </div>
        <?php else: ?>
        <div class="test-section">
            <div class='status error'>
                <strong>❌ Cannot test email sending</strong><br>
                Please fix the issues above first.
            </div>
        </div>
        <?php endif; ?>
        
        <div class="test-section">
            <h2>📖 Documentation</h2>
            <p>For complete installation instructions, see:</p>
            <ul>
                <li><strong>FORGOT_PASSWORD_INSTALLATION.md</strong> - Complete guide</li>
                <li><strong>install_phpmailer.ps1</strong> - Automatic PHPMailer installer</li>
            </ul>
        </div>
        
        <div class="test-section" style="border-left-color: #28a745;">
            <h2>✅ System Status</h2>
            <?php
            $readyCount = 0;
            $totalChecks = 3;
            
            if ($allFilesExist) $readyCount++;
            if ($tableExists) $readyCount++;
            if ($allRequiredExist) $readyCount++;
            
            $percentage = ($readyCount / $totalChecks) * 100;
            
            if ($percentage == 100) {
                echo "<div class='status success'>";
                echo "🎉 <strong>System Ready!</strong> All components are installed correctly.";
                echo "</div>";
            } else {
                echo "<div class='status warning'>";
                echo "⚠️ <strong>Setup Incomplete:</strong> $readyCount out of $totalChecks checks passed.";
                echo "</div>";
            }
            ?>
        </div>
        
        <p style="text-align: center; margin-top: 30px; color: #666;">
            <a href="login.php" style="color: #ffc107; text-decoration: none;">← Back to Login</a>
        </p>
    </div>
</body>
</html>
