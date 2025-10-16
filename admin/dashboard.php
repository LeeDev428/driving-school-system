<?php
session_start();

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

// Fetch real data from database
try {
    // Get recent appointments
    $appointments_query = "SELECT a.*, u.full_name as student_name, i.full_name as instructor_name, at.name as type_name
                          FROM appointments a 
                          LEFT JOIN users u ON a.student_id = u.id 
                          LEFT JOIN instructors inst ON a.instructor_id = inst.id
                          LEFT JOIN users i ON inst.user_id = i.id
                          LEFT JOIN appointment_types at ON a.appointment_type_id = at.id
                          ORDER BY a.created_at DESC LIMIT 10";
    $appointments_result = $conn->query($appointments_query);
    $recent_appointments = $appointments_result ? $appointments_result->fetch_all(MYSQLI_ASSOC) : [];

    // Get new applicants (recent students)
    $applicants_query = "SELECT * FROM users WHERE user_type = 'student' 
                        ORDER BY created_at DESC LIMIT 10";
    $applicants_result = $conn->query($applicants_query);
    $new_applicants = $applicants_result ? $applicants_result->fetch_all(MYSQLI_ASSOC) : [];

    // Get today's schedule
    $today_schedule_query = "SELECT a.*, u.full_name as student_name, i.full_name as instructor_name, 
                            v.make, v.model, v.transmission_type, at.name as type_name
                            FROM appointments a 
                            LEFT JOIN users u ON a.student_id = u.id 
                            LEFT JOIN instructors inst ON a.instructor_id = inst.id
                            LEFT JOIN users i ON inst.user_id = i.id
                            LEFT JOIN vehicles v ON a.vehicle_id = v.id 
                            LEFT JOIN appointment_types at ON a.appointment_type_id = at.id
                            WHERE DATE(a.appointment_date) = CURDATE() 
                            ORDER BY a.start_time";
    $schedule_result = $conn->query($today_schedule_query);
    $today_schedule = $schedule_result ? $schedule_result->fetch_all(MYSQLI_ASSOC) : [];

    // Get notification count (pending appointments)
    $notification_query = "SELECT COUNT(*) as count FROM appointments WHERE status = 'pending'";
    $notification_result = $conn->query($notification_query);
    $notification_count = $notification_result ? $notification_result->fetch_assoc()['count'] : 0;

    // Get stats for dashboard
    $stats_query = "SELECT 
                      (SELECT COUNT(*) FROM appointments WHERE status = 'pending') as pending_appointments,
                      (SELECT COUNT(*) FROM appointments WHERE status = 'confirmed') as confirmed_appointments,
                      (SELECT COUNT(*) FROM users WHERE user_type = 'student' AND DATE(created_at) = CURDATE()) as new_students_today,
                      (SELECT COUNT(*) FROM users WHERE user_type = 'student') as total_students,
                      (SELECT COUNT(DISTINCT session_id) FROM quiz_responses) as total_simulation_sessions,
                      (SELECT COUNT(DISTINCT user_id) FROM user_assessment_sessions WHERE status = 'completed' AND passed = 1) as assessment_passes,
                      (SELECT COUNT(DISTINCT user_id) FROM user_quiz_sessions WHERE status = 'completed' AND passed = 1) as quiz_passes,
                      (SELECT COUNT(*) FROM appointments WHERE DATE(appointment_date) = CURDATE()) as appointments_today";
    $stats_result = $conn->query($stats_query);
    $stats = $stats_result ? $stats_result->fetch_assoc() : [];
    
    // Get recent instructors (5 most recently added)
    $instructors_query = "SELECT inst.id, inst.license_number, inst.years_experience, inst.is_active, 
                         inst.created_at, u.full_name, u.email, u.contact_number
                         FROM instructors inst
                         LEFT JOIN users u ON inst.user_id = u.id
                         ORDER BY inst.created_at DESC LIMIT 5";
    $instructors_result = $conn->query($instructors_query);
    if (!$instructors_result) {
        error_log("Instructors query error: " . $conn->error);
        $recent_instructors = [];
    } else {
        $recent_instructors = $instructors_result->fetch_all(MYSQLI_ASSOC);
    }
    
    // Get recent vehicles (5 most recently added)
    $vehicles_query = "SELECT id, make, model, year, license_plate, transmission_type, 
                      vehicle_type, color, is_available, created_at
                      FROM vehicles 
                      ORDER BY created_at DESC LIMIT 5";
    $vehicles_result = $conn->query($vehicles_query);
    if (!$vehicles_result) {
        error_log("Vehicles query error: " . $conn->error);
        $recent_vehicles = [];
    } else {
        $recent_vehicles = $vehicles_result->fetch_all(MYSQLI_ASSOC);
    }
    
} catch (Exception $e) {
    // Log the error
    error_log("Dashboard error: " . $e->getMessage());
    
    $recent_appointments = [];
    $new_applicants = [];
    $today_schedule = [];
    $notification_count = 0;
    $recent_instructors = [];
    $recent_vehicles = [];
    $stats = ['pending_appointments' => 0, 'confirmed_appointments' => 0, 'new_students_today' => 0, 'total_students' => 0, 'total_simulation_sessions' => 0, 'assessment_passes' => 0, 'quiz_passes' => 0, 'appointments_today' => 0];
    
    // Display error for debugging
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 20px; border-radius: 5px; border: 1px solid #f5c6cb;'>";
    echo "<strong>Error loading dashboard data:</strong> " . htmlspecialchars($e->getMessage());
    echo "<br><strong>SQL Error:</strong> " . htmlspecialchars($conn->error);
    echo "</div>";
}

// Generate content
ob_start();
?>

<!-- Main Grid Layout -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
    <!-- New Appointments -->
    <div class="card">
        <div class="card-header">
            <h3><i class="far fa-calendar-check"></i> Recent Appointments</h3>
            <span class="badge badge-primary"><?php echo count($recent_appointments); ?> Total</span>
        </div>
        
        <?php if (!empty($recent_appointments)): ?>
            <?php foreach (array_slice($recent_appointments, 0, 3) as $index => $appointment): ?>
                <div style="display: flex; <?php echo $index < 2 ? 'margin-bottom: 15px;' : ''; ?>">
                    <div style="margin-right: 15px; flex: 0 0 20px;">
                        <i class="far fa-calendar" style="color: #8b8d93;"></i>
                    </div>
                    <div style="flex-grow: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div style="font-size: 15px; font-weight: 500;">
                                <?php echo htmlspecialchars($appointment['student_name'] ?? 'Unknown Student'); ?> - 
                                <?php echo htmlspecialchars($appointment['type_name'] ?? 'Appointment'); ?>
                                <?php if (!empty($appointment['course_type'])): ?>
                                    <span style="font-size: 10px; background: rgba(255, 204, 0, 0.2); color: #ffcc00; padding: 2px 6px; border-radius: 3px; margin-left: 5px;">
                                        <?php echo strtoupper($appointment['course_type']); ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <span class="badge badge-<?php 
                                echo $appointment['status'] == 'confirmed' ? 'success' : 
                                    ($appointment['status'] == 'pending' ? 'warning' : 'secondary'); 
                            ?>">
                                <?php echo ucfirst($appointment['status']); ?>
                            </span>
                        </div>
                        <div style="font-size: 12px; color: #8b8d93; margin-top: 3px;">
                            <i class="far fa-clock"></i> 
                            <?php 
                            if ($appointment['appointment_date'] && $appointment['start_time']) {
                                echo date('M j, Y g:i A', strtotime($appointment['appointment_date'] . ' ' . $appointment['start_time']));
                            } else {
                                echo 'Date/Time not set';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (count($recent_appointments) > 3): ?>
                <div style="text-align: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #3a3f48;">
                    <a href="appointments.php" style="color: #ffcc00; text-decoration: none; font-size: 12px;">
                        View All Appointments (<?php echo count($recent_appointments); ?>)
                    </a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div style="padding: 20px; text-align: center; color: #8b8d93;">
                <i class="far fa-calendar-times" style="font-size: 24px; margin-bottom: 10px; opacity: 0.5;"></i>
                <div>No recent appointments found.</div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- New Applicants -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-user-plus"></i> Recent Students</h3>
            <span class="badge badge-primary"><?php echo count($new_applicants); ?> Total</span>
        </div>
        
        <?php if (!empty($new_applicants)): ?>
            <?php foreach (array_slice($new_applicants, 0, 3) as $index => $applicant): ?>
                <div style="display: flex; <?php echo $index < 2 ? 'margin-bottom: 15px;' : ''; ?>">
                    <div style="margin-right: 15px; flex: 0 0 20px;">
                        <i class="far fa-user" style="color: #8b8d93;"></i>
                    </div>
                    <div style="flex-grow: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <div style="font-size: 15px; font-weight: 500;">
                                    <?php echo htmlspecialchars($applicant['full_name']); ?>
                                </div>
                                <div style="font-size: 12px; color: #8b8d93; margin-top: 3px;">
                                    <i class="far fa-clock"></i> 
                                    <?php 
                                    $created_time = strtotime($applicant['created_at']);
                                    $now = time();
                                    $diff = $now - $created_time;
                                    
                                    if ($diff < 3600) {
                                        echo floor($diff / 60) . ' minutes ago';
                                    } elseif ($diff < 86400) {
                                        echo floor($diff / 3600) . ' hours ago';
                                    } else {
                                        echo date('M j, Y', $created_time);
                                    }
                                    ?>
                                </div>
                            </div>
                            <span class="badge badge-<?php echo (strtotime($applicant['created_at']) > strtotime('-24 hours')) ? 'primary' : 'secondary'; ?>">
                                <?php echo (strtotime($applicant['created_at']) > strtotime('-24 hours')) ? 'New' : 'Registered'; ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (count($new_applicants) > 3): ?>
                <div style="text-align: center; margin-top: 15px; padding-top: 15px; border-top: 1px solid #3a3f48;">
                    <a href="students.php" style="color: #ffcc00; text-decoration: none; font-size: 12px;">
                        View All Students (<?php echo count($new_applicants); ?>)
                    </a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div style="padding: 20px; text-align: center; color: #8b8d93;">
                <i class="fas fa-user-times" style="font-size: 24px; margin-bottom: 10px; opacity: 0.5;"></i>
                <div>No recent students found.</div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Stats Cards -->
<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">
    <div class="card" style="text-align: center; padding: 15px;">
        <div style="font-size: 24px; font-weight: 600; color: #2196F3; margin-bottom: 5px;">
            <?php echo $stats['appointments_today'] ?? 0; ?>
        </div>
        <div style="font-size: 12px; color: #8b8d93; text-transform: uppercase;">
            Appointments Today
        </div>
    </div>
    
    <div class="card" style="text-align: center; padding: 15px;">
        <div style="font-size: 24px; font-weight: 600; color: #ffcc00; margin-bottom: 5px;">
            <?php echo $stats['pending_appointments'] ?? 0; ?>
        </div>
        <div style="font-size: 12px; color: #8b8d93; text-transform: uppercase;">
            Pending Appointments
        </div>
    </div>
    
    <div class="card" style="text-align: center; padding: 15px;">
        <div style="font-size: 24px; font-weight: 600; color: #9C27B0; margin-bottom: 5px;">
            <?php echo $stats['total_students'] ?? 0; ?>
        </div>
        <div style="font-size: 12px; color: #8b8d93; text-transform: uppercase;">
            Total Students
        </div>
    </div>
    
    <div class="card" style="text-align: center; padding: 15px;">
        <div style="font-size: 24px; font-weight: 600; color: #4CAF50; margin-bottom: 5px;">
            <?php echo $stats['new_students_today'] ?? 0; ?>
        </div>
        <div style="font-size: 12px; color: #8b8d93; text-transform: uppercase;">
            New Students Today
        </div>
    </div>
</div>

<!-- E-Learning Stats Cards -->
<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">
    <div class="card" style="text-align: center; padding: 20px; cursor: pointer;" onclick="window.location.href='e-learning.php'">
        <div style="font-size: 32px; margin-bottom: 10px;">📝</div>
        <div style="font-size: 24px; font-weight: 600; color: #667eea; margin-bottom: 5px;">
            <?php echo $stats['assessment_passes'] ?? 0; ?>
        </div>
        <div style="font-size: 12px; color: #8b8d93; text-transform: uppercase;">
            Assessment Passes
        </div>
        <div style="font-size: 10px; color: #667eea; margin-top: 5px;">
            <i class="fas fa-eye"></i> View Details
        </div>
    </div>
    
    <div class="card" style="text-align: center; padding: 20px; cursor: pointer;" onclick="window.location.href='e-learning.php'">
        <div style="font-size: 32px; margin-bottom: 10px;">📚</div>
        <div style="font-size: 24px; font-weight: 600; color: #f5576c; margin-bottom: 5px;">
            <?php echo $stats['quiz_passes'] ?? 0; ?>
        </div>
        <div style="font-size: 12px; color: #8b8d93; text-transform: uppercase;">
            Quiz Passes
        </div>
        <div style="font-size: 10px; color: #f5576c; margin-top: 5px;">
            <i class="fas fa-eye"></i> View Details
        </div>
    </div>
    
    <div class="card" style="text-align: center; padding: 20px; cursor: pointer;" onclick="window.location.href='simulation_result.php'">
        <div style="font-size: 32px; margin-bottom: 10px;">🎮</div>
        <div style="font-size: 24px; font-weight: 600; color: #FF5722; margin-bottom: 5px;">
            <?php echo $stats['total_simulation_sessions'] ?? 0; ?>
        </div>
        <div style="font-size: 12px; color: #8b8d93; text-transform: uppercase;">
            Simulation Sessions
        </div>
        <div style="font-size: 10px; color: #FF5722; margin-top: 5px;">
            <i class="fas fa-chart-bar"></i> View Results
        </div>
    </div>
</div>

<!-- Today's Schedule -->
<div class="card">
    <div class="card-header">
        <h3><i class="far fa-calendar-alt"></i> Today's Schedule</h3>
        <span class="badge badge-info"><?php echo count($today_schedule); ?> Appointments</span>
    </div>
    
    <?php if (!empty($today_schedule)): ?>
        <!-- Schedule Table -->
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid #3a3f48; text-align: left;">
                    <th style="padding: 10px 15px; font-weight: 500; color: #8b8d93;">Time</th>
                    <th style="padding: 10px 15px; font-weight: 500; color: #8b8d93;">Student</th>
                    <th style="padding: 10px 15px; font-weight: 500; color: #8b8d93;">Instructor</th>
                    <th style="padding: 10px 15px; font-weight: 500; color: #8b8d93;">Vehicle</th>
                    <th style="padding: 10px 15px; font-weight: 500; color: #8b8d93;">Type</th>
                    <th style="padding: 10px 15px; font-weight: 500; color: #8b8d93;">Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($today_schedule as $index => $schedule): ?>
                    <tr style="<?php echo $index < count($today_schedule) - 1 ? 'border-bottom: 1px solid #3a3f48;' : ''; ?>">
                        <td style="padding: 15px; font-size: 14px;">
                            <?php echo date('g:i A', strtotime($schedule['start_time'])); ?>
                            <?php if ($schedule['end_time']): ?>
                                - <?php echo date('g:i A', strtotime($schedule['end_time'])); ?>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px; font-size: 14px;">
                            <?php echo htmlspecialchars($schedule['student_name'] ?? 'Unknown'); ?>
                        </td>
                        <td style="padding: 15px; font-size: 14px;">
                            <?php echo htmlspecialchars($schedule['instructor_name'] ?? 'Not Assigned'); ?>
                        </td>
                        <td style="padding: 15px; font-size: 14px;">
                            <?php 
                            if ($schedule['make'] && $schedule['model']) {
                                echo htmlspecialchars($schedule['make'] . ' ' . $schedule['model']);
                                if ($schedule['transmission_type']) {
                                    echo ' (' . ucfirst($schedule['transmission_type']) . ')';
                                }
                            } else {
                                echo 'Not Assigned';
                            }
                            ?>
                        </td>
                        <td style="padding: 15px; font-size: 14px;">
                            <?php echo htmlspecialchars($schedule['type_name'] ?? 'Appointment'); ?>
                            <?php if (!empty($schedule['course_type'])): ?>
                                <br><span style="font-size: 10px; background: rgba(255, 204, 0, 0.2); color: #ffcc00; padding: 2px 6px; border-radius: 3px;">
                                    <?php echo strtoupper($schedule['course_type']); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 15px; font-size: 14px;">
                            <span class="badge badge-<?php 
                                echo $schedule['status'] == 'confirmed' ? 'success' : 
                                    ($schedule['status'] == 'pending' ? 'warning' : 
                                    ($schedule['status'] == 'completed' ? 'info' : 'secondary')); 
                            ?>">
                                <?php echo ucfirst($schedule['status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="padding: 40px; text-align: center; color: #8b8d93;">
            <i class="far fa-calendar-times" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
            <div style="font-size: 16px; margin-bottom: 5px;">No appointments scheduled for today</div>
            <div style="font-size: 12px;">The schedule is clear!</div>
        </div>
    <?php endif; ?>
</div>

<!-- Grid for Instructors and Vehicles -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
    <!-- Recent Instructors -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chalkboard-teacher"></i> Recent Instructors</h3>
            <a href="instructors.php" style="color: #ffcc00; text-decoration: none; font-size: 14px;">View All →</a>
        </div>
        
        <?php if (!empty($recent_instructors)): ?>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid #3a3f48; text-align: left;">
                        <th style="padding: 10px 15px; font-weight: 500; color: #8b8d93;">Name</th>
                        <th style="padding: 10px 15px; font-weight: 500; color: #8b8d93;">License</th>
                        <th style="padding: 10px 15px; font-weight: 500; color: #8b8d93;">Experience</th>
                        <th style="padding: 10px 15px; font-weight: 500; color: #8b8d93;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_instructors as $index => $instructor): ?>
                        <tr style="<?php echo $index < count($recent_instructors) - 1 ? 'border-bottom: 1px solid #3a3f48;' : ''; ?>">
                            <td style="padding: 15px; font-size: 14px;">
                                <div style="font-weight: 500;"><?php echo htmlspecialchars($instructor['full_name'] ?? 'Unknown'); ?></div>
                                <div style="font-size: 12px; color: #8b8d93;"><?php echo htmlspecialchars($instructor['email'] ?? ''); ?></div>
                            </td>
                            <td style="padding: 15px; font-size: 14px;">
                                <span style="font-family: monospace; color: #ffcc00;">
                                    <?php echo htmlspecialchars($instructor['license_number'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td style="padding: 15px; font-size: 14px;">
                                <?php echo $instructor['years_experience'] ?? 0; ?> years
                            </td>
                            <td style="padding: 15px; font-size: 14px;">
                                <span class="badge badge-<?php echo $instructor['is_active'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $instructor['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="padding: 40px; text-align: center; color: #8b8d93;">
                <i class="fas fa-user-slash" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                <div style="font-size: 16px; margin-bottom: 5px;">No instructors found</div>
                <div style="font-size: 12px;">
                    <a href="instructors.php" style="color: #ffcc00;">Add an instructor</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Recent Vehicles -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-car"></i> Recent Vehicles</h3>
            <a href="vehicles.php" style="color: #ffcc00; text-decoration: none; font-size: 14px;">View All →</a>
        </div>
        
        <?php if (!empty($recent_vehicles)): ?>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid #3a3f48; text-align: left;">
                        <th style="padding: 10px 15px; font-weight: 500; color: #8b8d93;">Vehicle</th>
                        <th style="padding: 10px 15px; font-weight: 500; color: #8b8d93;">Plate</th>
                        <th style="padding: 10px 15px; font-weight: 500; color: #8b8d93;">Type</th>
                        <th style="padding: 10px 15px; font-weight: 500; color: #8b8d93;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_vehicles as $index => $vehicle): ?>
                        <tr style="<?php echo $index < count($recent_vehicles) - 1 ? 'border-bottom: 1px solid #3a3f48;' : ''; ?>">
                            <td style="padding: 15px; font-size: 14px;">
                                <div style="font-weight: 500;">
                                    <?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?>
                                </div>
                                <div style="font-size: 12px; color: #8b8d93;">
                                    <?php echo htmlspecialchars($vehicle['year']); ?>
                                    <?php if (!empty($vehicle['color'])): ?>
                                        • <?php echo htmlspecialchars($vehicle['color']); ?>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="padding: 15px; font-size: 14px;">
                                <span style="font-family: monospace; background: rgba(255, 204, 0, 0.2); color: #ffcc00; padding: 4px 8px; border-radius: 4px; font-size: 12px;">
                                    <?php echo htmlspecialchars($vehicle['license_plate'] ?? 'N/A'); ?>
                                </span>
                            </td>
                            <td style="padding: 15px; font-size: 14px;">
                                <div><?php echo ucfirst($vehicle['transmission_type'] ?? 'N/A'); ?></div>
                                <div style="font-size: 11px; color: #8b8d93;">
                                    <?php echo ucfirst($vehicle['vehicle_type'] ?? 'N/A'); ?>
                                </div>
                            </td>
                            <td style="padding: 15px; font-size: 14px;">
                                <span class="badge badge-<?php echo $vehicle['is_available'] ? 'success' : 'secondary'; ?>">
                                    <?php echo $vehicle['is_available'] ? 'Available' : 'In Use'; ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div style="padding: 40px; text-align: center; color: #8b8d93;">
                <i class="fas fa-car-side" style="font-size: 48px; margin-bottom: 15px; opacity: 0.5;"></i>
                <div style="font-size: 16px; margin-bottom: 5px;">No vehicles found</div>
                <div style="font-size: 12px;">
                    <a href="vehicles.php" style="color: #ffcc00;">Add a vehicle</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
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
    
    .card {
        background: #282c34;
        border: 1px solid #3a3f48;
        border-radius: 8px;
        margin-bottom: 20px;
        overflow: hidden;
    }
    
    .card-header {
        background: #1e2129;
        padding: 15px 20px;
        border-bottom: 1px solid #3a3f48;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .card-header h3 {
        margin: 0;
        color: #ffcc00;
        font-size: 16px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .card > div:not(.card-header) {
        padding: 20px;
    }
    
    .badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .badge-primary {
        background: rgba(33, 150, 243, 0.2);
        color: #2196F3;
        border: 1px solid rgba(33, 150, 243, 0.3);
    }
    
    .badge-success {
        background: rgba(76, 175, 80, 0.2);
        color: #4CAF50;
        border: 1px solid rgba(76, 175, 80, 0.3);
    }
    
    .badge-warning {
        background: rgba(255, 152, 0, 0.2);
        color: #ff9800;
        border: 1px solid rgba(255, 152, 0, 0.3);
    }
    
    .badge-info {
        background: rgba(0, 188, 212, 0.2);
        color: #00bcd4;
        border: 1px solid rgba(0, 188, 212, 0.3);
    }
    
    .badge-secondary {
        background: rgba(139, 141, 147, 0.2);
        color: #8b8d93;
        border: 1px solid rgba(139, 141, 147, 0.3);
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        div[style*="grid-template-columns: 1fr 1fr"] {
            grid-template-columns: 1fr !important;
        }
        
        div[style*="grid-template-columns: repeat(4, 1fr)"] {
            grid-template-columns: repeat(2, 1fr) !important;
        }
        
        table {
            font-size: 12px;
        }
        
        table th, table td {
            padding: 8px 10px !important;
        }
    }
</style>
EOT;

// Add any additional scripts
$extra_scripts = <<<EOT
<script>
    // Additional scripts specific to admin dashboard
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Admin dashboard loaded');
        
        // Auto-refresh dashboard every 5 minutes
        setInterval(function() {
            // You can add AJAX refresh logic here if needed
            console.log('Dashboard auto-refresh check');
        }, 300000); // 5 minutes
        
        // Add click handlers for quick actions
        document.querySelectorAll('.badge-warning').forEach(function(badge) {
            if (badge.textContent.toLowerCase() === 'pending') {
                badge.style.cursor = 'pointer';
                badge.title = 'Click to view details';
            }
        });
    });
</script>
EOT;

// Include the main layout template
include "../layouts/main_layout.php";
?>