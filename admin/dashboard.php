<?php
session_start(); // ADD THIS - CRITICAL!

// Check if user is logged in and is admin
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["user_type"] !== "admin") {
    header("location: ../login.php");
    exit;
}

// Include database connection
require_once "../config.php";

// Initialize variables
$page_title = "Dashboard";
$header_title = "Welcome to Success Driving School";
$notification_count = 3; // Example - this should come from database

// Generate content
ob_start();
?>

<!-- Main Grid Layout -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
    <!-- New Appointments -->
    <div class="card">
        <div class="card-header">
            <h3><i class="far fa-calendar-check"></i> New Appointments</h3>
            <span class="badge badge-primary">2 New</span>
        </div>
        
        <!-- Appointment Item 1 -->
        <div style="display: flex; margin-bottom: 15px;">
            <div style="margin-right: 15px; flex: 0 0 20px;">
                <i class="far fa-calendar" style="color: #8b8d93;"></i>
            </div>
            <div style="flex-grow: 1;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 15px; font-weight: 500;">Driving Test Appointment</div>
                    <span class="badge badge-warning">Pending</span>
                </div>
                <div style="font-size: 12px; color: #8b8d93; margin-top: 3px;">
                    <i class="far fa-clock"></i> 10 minutes ago
                </div>
            </div>
        </div>
        
        <!-- Appointment Item 2 -->
        <div style="display: flex; margin-bottom: 15px;">
            <div style="margin-right: 15px; flex: 0 0 20px;">
                <i class="far fa-calendar" style="color: #8b8d93;"></i>
            </div>
            <div style="flex-grow: 1;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 15px; font-weight: 500;">Lesson Scheduling Request</div>
                    <span class="badge badge-warning">Pending</span>
                </div>
                <div style="font-size: 12px; color: #8b8d93; margin-top: 3px;">
                    <i class="far fa-clock"></i> 1 hour ago
                </div>
            </div>
        </div>
        
        <!-- Appointment Item 3 -->
        <div style="display: flex;">
            <div style="margin-right: 15px; flex: 0 0 20px;">
                <i class="far fa-calendar" style="color: #8b8d93;"></i>
            </div>
            <div style="flex-grow: 1;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="font-size: 15px; font-weight: 500;">Practice Session Booked</div>
                    <span class="badge badge-success">Confirmed</span>
                </div>
                <div style="font-size: 12px; color: #8b8d93; margin-top: 3px;">
                    <i class="far fa-clock"></i> 2 hours ago
                </div>
            </div>
        </div>
    </div>
    
    <!-- New Applicants -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-user-plus"></i> New Applicants</h3>
            <span class="badge badge-primary">3 New</span>
        </div>
        
        <!-- Applicant Item 1 -->
        <div style="display: flex; margin-bottom: 15px;">
            <div style="margin-right: 15px; flex: 0 0 20px;">
                <i class="far fa-user" style="color: #8b8d93;"></i>
            </div>
            <div style="flex-grow: 1;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 15px; font-weight: 500;">John Smith - Class B License</div>
                        <div style="font-size: 12px; color: #8b8d93; margin-top: 3px;">
                            <i class="far fa-clock"></i> 30 minutes ago
                        </div>
                    </div>
                    <span class="badge badge-primary">New</span>
                </div>
            </div>
        </div>
        
        <!-- Applicant Item 2 -->
        <div style="display: flex; margin-bottom: 15px;">
            <div style="margin-right: 15px; flex: 0 0 20px;">
                <i class="far fa-user" style="color: #8b8d93;"></i>
            </div>
            <div style="flex-grow: 1;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 15px; font-weight: 500;">Sarah Wilson - Class A License</div>
                        <div style="font-size: 12px; color: #8b8d93; margin-top: 3px;">
                            <i class="far fa-clock"></i> 3 hours ago
                        </div>
                    </div>
                    <span class="badge badge-primary">New</span>
                </div>
            </div>
        </div>
        
        <!-- Applicant Item 3 -->
        <div style="display: flex;">
            <div style="margin-right: 15px; flex: 0 0 20px;">
                <i class="far fa-user" style="color: #8b8d93;"></i>
            </div>
            <div style="flex-grow: 1;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 15px; font-weight: 500;">Mike Johnson - Class C License</div>
                        <div style="font-size: 12px; color: #8b8d93; margin-top: 3px;">
                            <i class="far fa-clock"></i> 5 hours ago
                        </div>
                    </div>
                    <span class="badge" style="background-color: #8b8d93; color: white;">Reviewed</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Appointment Schedule -->
<div class="card">
    <div class="card-header">
        <h3><i class="far fa-calendar-alt"></i> Appointment Schedule</h3>
    </div>
    
    <!-- Schedule Table -->
    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="border-bottom: 1px solid #3a3f48; text-align: left;">
                <th style="padding: 10px 15px; font-weight: 500; color: #8b8d93;">Date/Time</th>
                <th style="padding: 10px 15px; font-weight: 500; color: #8b8d93;">Transmission</th>
                <th style="padding: 10px 15px; font-weight: 500; color: #8b8d93;">Type</th>
                <th style="padding: 10px 15px; font-weight: 500; color: #8b8d93;">Status</th>
            </tr>
        </thead>
        <tbody>
            <tr style="border-bottom: 1px solid #3a3f48;">
                <td style="padding: 15px; font-size: 14px;">Jul 15, 2023 - 10:00 AM</td>
                <td style="padding: 15px; font-size: 14px;">Manual</td>
                <td style="padding: 15px; font-size: 14px;">Automated</td>
                <td style="padding: 15px; font-size: 14px;">
                    <span class="badge badge-success">Confirmed</span>
                </td>
            </tr>
            <tr>
                <td style="padding: 15px; font-size: 14px;">Jul 15, 2023 - 2:00 PM</td>
                <td style="padding: 15px; font-size: 14px;">Automatic</td>
                <td style="padding: 15px; font-size: 14px;">Manual</td>
                <td style="padding: 15px; font-size: 14px;">
                    <span class="badge badge-warning">Pending</span>
                </td>
            </tr>
        </tbody>
    </table>
</div>
<?php
$content = ob_get_clean();

// Add any additional styles
$extra_styles = <<<EOT
<style>
    /* Additional styles specific to admin dashboard */
    table th, table td {
        white-space: nowrap;
    }
</style>
EOT;

// Add any additional scripts
$extra_scripts = <<<EOT
<script>
    // Additional scripts specific to admin dashboard
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Admin dashboard loaded');
    });
</script>
EOT;

// Include the main layout template
include "../layouts/main_layout.php";
?>