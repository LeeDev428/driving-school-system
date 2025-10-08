<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Unified Login System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
        }
        h2 {
            color: #666;
            border-bottom: 2px solid #ffc107;
            padding-bottom: 10px;
        }
        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
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
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        code {
            background-color: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        ul {
            line-height: 1.8;
        }
        .test-button {
            background-color: #ffc107;
            color: #000;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 5px;
            text-decoration: none;
            display: inline-block;
        }
        .test-button:hover {
            background-color: #e0a800;
        }
    </style>
</head>
<body>
    <h1>🎓 Unified Login System Test Page</h1>
    
    <div class="test-section">
        <h2>📋 System Status</h2>
        <?php
        session_start();
        require_once "config.php";
        
        // Test database connection
        if ($conn) {
            echo '<div class="status success">✅ Database connection successful</div>';
        } else {
            echo '<div class="status error">❌ Database connection failed</div>';
        }
        
        // Check if users table exists
        $result = mysqli_query($conn, "SHOW TABLES LIKE 'users'");
        if (mysqli_num_rows($result) > 0) {
            echo '<div class="status success">✅ Users table exists</div>';
            
            // Check for user_type column
            $result = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'user_type'");
            if (mysqli_num_rows($result) > 0) {
                echo '<div class="status success">✅ user_type column exists</div>';
            } else {
                echo '<div class="status error">❌ user_type column not found</div>';
            }
        } else {
            echo '<div class="status error">❌ Users table not found</div>';
        }
        
        // Count users by type
        $result = mysqli_query($conn, "SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type");
        echo '<div class="status info">';
        echo '<strong>User Statistics:</strong><br>';
        if (mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                echo "• " . ucfirst($row['user_type']) . " users: " . $row['count'] . "<br>";
            }
        } else {
            echo "No users found in database.";
        }
        echo '</div>';
        ?>
    </div>
    
    <div class="test-section">
        <h2>🧪 Test Instructions</h2>
        <div class="status info">
            <strong>To test the unified login system:</strong>
            <ol>
                <li>Make sure you have at least one student user (register via <code>register.php</code>)</li>
                <li>Create an admin user using one of the methods in <code>CREATE_ADMIN_USER.sql</code></li>
                <li>Try logging in with each account type</li>
                <li>Verify correct redirection:
                    <ul>
                        <li>Student → <code>user/dashboard.php</code></li>
                        <li>Admin → <code>admin/dashboard.php</code></li>
                    </ul>
                </li>
            </ol>
        </div>
    </div>
    
    <div class="test-section">
        <h2>🔗 Quick Actions</h2>
        <a href="login.php" class="test-button">Go to Login Page</a>
        <a href="register.php" class="test-button">Go to Registration</a>
        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
            <a href="logout.php" class="test-button">Logout</a>
            <?php if ($_SESSION['user_type'] === 'admin'): ?>
                <a href="admin/dashboard.php" class="test-button">Admin Dashboard</a>
            <?php else: ?>
                <a href="user/dashboard.php" class="test-button">Student Dashboard</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <div class="test-section">
        <h2>📝 Expected Behavior</h2>
        <ul>
            <li>✅ No more tab buttons on login page</li>
            <li>✅ Single "Sign In to Your Account" heading</li>
            <li>✅ One "Login" button (not "Admin Login" or "Student Login")</li>
            <li>✅ System automatically detects user type from database</li>
            <li>✅ Redirects to correct dashboard based on user_type</li>
            <li>✅ Generic error messages for security</li>
        </ul>
    </div>
    
    <div class="test-section">
        <h2>⚠️ Common Issues & Solutions</h2>
        <div class="status info">
            <strong>If login doesn't work:</strong>
            <ul>
                <li>Check database connection in <code>config.php</code></li>
                <li>Verify user exists in database with correct email</li>
                <li>Ensure password is properly hashed in database</li>
                <li>Check <code>user_type</code> field is set correctly ('admin' or 'student')</li>
                <li>Verify session is starting correctly</li>
            </ul>
        </div>
    </div>
    
    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
    <div class="test-section">
        <h2>👤 Current Session Info</h2>
        <div class="status success">
            <strong>You are logged in!</strong><br>
            • User ID: <?php echo $_SESSION['id']; ?><br>
            • Email: <?php echo $_SESSION['email']; ?><br>
            • User Type: <?php echo strtoupper($_SESSION['user_type']); ?><br>
            • Name: <?php echo $_SESSION['full_name']; ?>
        </div>
    </div>
    <?php endif; ?>
    
</body>
</html>
