<?php
session_start(); // ADD THIS LINE - CRITICAL!

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Include database connection
require_once "../config.php";

// Initialize variables
$page_title = "Dashboard";
$header_title = "Welcome back, " . (isset($_SESSION['full_name']) ? explode(' ', $_SESSION['full_name'])[0] : "Student");
$notification_count = 2; // Example - this should come from database

// Generate content
ob_start();
?>

<!-- Stats Overview -->
<div class="stats-container">
    <div class="stat-card">
        <div class="icon" style="background-color: rgba(75, 192, 192, 0.2); color: #4bc0c0;">
            <i class="fas fa-tasks"></i>
        </div>
        <div class="value">65%</div>
        <div class="label">Course Progress</div>
    </div>
    
    <div class="stat-card">
        <div class="icon" style="background-color: rgba(255, 205, 86, 0.2); color: #ffcc00;">
            <i class="fas fa-car"></i>
        </div>
        <div class="value">85%</div>
        <div class="label">Driving Performance</div>
    </div>
    
    <div class="stat-card">
        <div class="icon" style="background-color: rgba(54, 162, 235, 0.2); color: #36a2eb;">
            <i class="fas fa-check-square"></i>
        </div>
        <div class="value">12/20</div>
        <div class="label">Completed Tasks</div>
    </div>
    
    <div class="stat-card">
        <div class="icon" style="background-color: rgba(153, 102, 255, 0.2); color: #9966ff;">
            <i class="far fa-calendar-check"></i>
        </div>
        <div class="value">24.5h</div>
        <div class="label">Completed Hours</div>
    </div>
</div>

<!-- Main content grid -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <!-- Left column -->
    <div>
        <!-- Scheduled Training Card -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-calendar-alt"></i> Scheduled Training</h3>
            </div>
            <div style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <div>
                        <div style="font-size: 16px; font-weight: 500;">Freeway Driving</div>
                        <div style="font-size: 13px; color: #8b8d93;">10:00 AM, June 28, 2025</div>
                    </div>
                    <div>
                        <span class="badge badge-warning">Upcoming</span>
                    </div>
                </div>
                
                <div class="progress" style="height: 8px; background-color: #3a3f48; border-radius: 4px; margin-top: 10px;">
                    <div style="height: 100%; width: 75%; background-color: #ffcc00; border-radius: 4px;"></div>
                </div>
            </div>
            
            <hr style="border: none; border-top: 1px solid #3a3f48; margin: 15px 0;">
            
            <div style="display: flex; justify-content: space-between;">
                <div>
                    <div style="font-size: 16px; font-weight: 500;">Highway Overtaking Class</div>
                    <div style="font-size: 13px; color: #8b8d93;">2:30 PM, June 30, 2025</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Right column -->
    <div>
        <!-- Upcoming Module Card -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-book-open"></i> Upcoming Modules</h3>
            </div>
            <div style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <div>
                        <div style="font-size: 16px; font-weight: 500;">Night Driving</div>
                        <div style="font-size: 13px; color: #8b8d93;">Chapter 5, Module 3</div>
                    </div>
                    <span class="badge badge-success">Started</span>
                </div>
                <div class="progress" style="height: 8px; background-color: #3a3f48; border-radius: 4px; margin-top: 10px;">
                    <div style="height: 100%; width: 30%; background-color: #4caf50; border-radius: 4px;"></div>
                </div>
            </div>
            
            <hr style="border: none; border-top: 1px solid #3a3f48; margin: 15px 0;">
            
            <div style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <div>
                        <div style="font-size: 16px; font-weight: 500;">Road Safety</div>
                        <div style="font-size: 13px; color: #8b8d93;">Chapter 6, Module 1</div>
                    </div>
                    <span class="badge badge-success">Available</span>
                </div>
                <div class="progress" style="height: 8px; background-color: #3a3f48; border-radius: 4px; margin-top: 10px;">
                    <div style="height: 100%; width: 0%; background-color: #4caf50; border-radius: 4px;"></div>
                </div>
            </div>
            
            <hr style="border: none; border-top: 1px solid #3a3f48; margin: 15px 0;">
            
            <div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                    <div>
                        <div style="font-size: 16px; font-weight: 500;">Parking Basics</div>
                        <div style="font-size: 13px; color: #8b8d93;">Chapter 6, Module 2</div>
                    </div>
                    <span class="badge" style="background-color: #8b8d93; color: white;">Locked</span>
                </div>
            </div>
        </div>
        
        <!-- Recent Assessments Card -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-clipboard-check"></i> Recent Assessments</h3>
            </div>
            <div style="margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <div style="font-size: 16px; font-weight: 500;">Traffic Signs</div>
                    <span class="badge badge-success">95%</span>
                </div>
                <div style="font-size: 13px; color: #8b8d93;">June 15, 2025</div>
            </div>
            
            <hr style="border: none; border-top: 1px solid #3a3f48; margin: 15px 0;">
            
            <div>
                <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                    <div style="font-size: 16px; font-weight: 500;">Road Markings</div>
                    <span class="badge badge-success">88%</span>
                </div>
                <div style="font-size: 13px; color: #8b8d93;">June 10, 2025</div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();

// Add any additional styles
$extra_styles = <<<EOT
<style>
    /* Additional styles specific to this dashboard */
    .progress {
        overflow: hidden;
    }
</style>
EOT;

// Add any additional scripts
$extra_scripts = <<<EOT
<script>
    // Additional scripts specific to this dashboard
    document.addEventListener('DOMContentLoaded', function() {
        console.log('User dashboard loaded');
    });
</script>
EOT;

// Include the main layout template
include "../layouts/main_layout.php";
?>