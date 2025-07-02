<?php
// No need to start session here - already started in dashboards
// Check if $_SESSION variables are available
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Get current page for sidebar highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Success Driving</title>
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
            display: flex;
            background-color: #1e2129;
            color: white;
            height: 100vh;
            overflow: hidden;
        }

        /* Sidebar */
        .sidebar {
            width: 220px;
            background-color: #1a1d24;
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding-top: 20px;
            display: flex;
            flex-direction: column;
        }

        .sidebar-brand {
            display: flex;
            align-items: center;
            padding: 0 20px;
            margin-bottom: 30px;
        }

        .sidebar-brand img {
            width: 35px;
            height: 35px;
            background-color: #ffcc00;
            border-radius: 50%;
            padding: 5px;
        }

        .sidebar-brand h2 {
            color: #fff;
            margin-left: 10px;
            font-size: 18px;
            font-weight: 600;
        }

        .sidebar-menu {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            color: #8b8d93;
            text-decoration: none;
            padding: 12px 20px;
            transition: all 0.3s;
            font-size: 15px;
        }

        .sidebar-menu a.active, 
        .sidebar-menu a:hover {
            background-color: #282c34;
            color: #fff;
            border-left: 4px solid #ffcc00;
        }

        .sidebar-menu a i {
            margin-right: 15px;
            font-size: 18px;
        }

        .bottom-menu {
            margin-top: auto;
            margin-bottom: 20px;
        }

        /* Main Content */
        .main-content {
            margin-left: 220px;
            width: calc(100% - 220px);
            padding: 20px;
            overflow-y: auto;
            height: 100vh;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 20px;
            border-bottom: 1px solid #2e323a;
            margin-bottom: 20px;
        }

        .header h2 {
            font-size: 24px;
            font-weight: 600;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .notifications {
            position: relative;
            cursor: pointer;
        }

        .notifications i {
            font-size: 22px;
            color: #8b8d93;
        }

        .notifications .badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #ffcc00;
            color: #1a1d24;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            font-size: 11px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-profile img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            background-color: #ffcc00;
        }

        /* Card Styles */
        .card {
            background-color: #282c34;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #3a3f48;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .card-header h3 {
            font-size: 18px;
            font-weight: 500;
        }

        .badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }

        .badge-primary {
            background-color: #ffcc00;
            color: #1a1d24;
        }

        .badge-success {
            background-color: #4caf50;
            color: white;
        }

        .badge-warning {
            background-color: #ff9800;
            color: #1a1d24;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-card {
            background-color: #282c34;
            border-radius: 10px;
            padding: 15px;
            border: 1px solid #3a3f48;
            display: flex;
            flex-direction: column;
        }

        .stat-card .icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }

        .stat-card .icon i {
            font-size: 20px;
        }

        .stat-card .value {
            font-size: 24px;
            font-weight: 600;
            margin: 5px 0;
        }

        .stat-card .label {
            font-size: 14px;
            color: #8b8d93;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                padding-top: 15px;
            }
            
            .sidebar-brand h2,
            .sidebar-menu a span {
                display: none;
            }
            
            .sidebar-menu a {
                justify-content: center;
                padding: 15px;
            }
            
            .sidebar-menu a i {
                margin-right: 0;
            }
            
            .main-content {
                margin-left: 70px;
                width: calc(100% - 70px);
            }
            
            .stats-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <?php if (isset($extra_styles)) echo $extra_styles; ?>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <!-- Fallback logo if image doesn't exist -->
            <div style="width: 35px; height: 35px; background-color: #ffcc00; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold;">S</div>
            <h2>Success Driving</h2>
        </div>
        
        <div class="sidebar-menu">
            <?php include(__DIR__ . '/sidebar_menu.php'); ?>
        </div>
        
        <div class="bottom-menu">
            <a href="../settings.php" <?php if($current_page == "settings.php") echo 'class="active"'; ?>>
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            <a href="../logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <div class="header">
            <h2><?php echo isset($header_title) ? $header_title : $page_title; ?></h2>
            
            <div class="user-info">
                <div class="notifications">
                    <i class="far fa-bell"></i>
                    <?php if (isset($notification_count) && $notification_count > 0): ?>
                    <div class="badge"><?php echo $notification_count; ?></div>
                    <?php endif; ?>
                </div>
                
                <div class="user-profile">
                    <!-- Fallback for user avatar image -->
                    <div style="width: 35px; height: 35px; background-color: #ffcc00; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #1a1d24; font-weight: bold;">
                        <?php echo substr($_SESSION['full_name'], 0, 1); ?>
                    </div>
                    <span><?php echo $_SESSION['user_type'] == 'admin' ? 'Admin User' : $_SESSION['full_name']; ?></span>
                </div>
            </div>
        </div>
        
        <!-- Content inserted by specific page -->
        <?php echo $content; ?>
    </div>

    <script>
        // Base JavaScript functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Dashboard layout loaded');
        });
    </script>
    <?php if (isset($extra_scripts)) echo $extra_scripts; ?>
</body>
</html>