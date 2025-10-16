<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// Include database connection
require_once "../config.php";

// Include access control
require_once "access_control.php";

// CHECK ACCESS - Redirect to appointments if user hasn't paid or not approved
$access_check = checkUserAccess($_SESSION["id"], $conn);
if (!$access_check['has_access']) {
    redirectToAppointments($access_check['message']);
}

// Initialize variables
$page_title = "Dashboard";
$header_title = "Welcome back, " . (isset($_SESSION['full_name']) ? explode(' ', $_SESSION['full_name'])[0] : "Student");
$notification_count = 2; // Example - this should come from database

// Fetch user stats
$user_id = $_SESSION['id'];
$stats = [];

try {
    // Get user's appointment statistics
    $appointments_query = "SELECT 
        COUNT(*) as total_appointments,
        SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_appointments,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_appointments,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments
        FROM appointments 
        WHERE student_id = ?";
    
    $stmt = $conn->prepare($appointments_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $appointments_stats = $stmt->get_result()->fetch_assoc();
    
    // Get user's assessment status
    $assessment_query = "SELECT 
        COUNT(*) as total_attempts,
        MAX(score) as best_score,
        MAX(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as has_passed
        FROM user_assessment_sessions 
        WHERE user_id = ? AND status = 'completed'";
    
    $stmt = $conn->prepare($assessment_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $assessment_stats = $stmt->get_result()->fetch_assoc();
    
    // Get user's quiz status
    $quiz_query = "SELECT 
        COUNT(*) as total_attempts,
        MAX(score) as best_score,
        MAX(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as has_passed
        FROM user_quiz_sessions 
        WHERE user_id = ? AND status = 'completed'";
    
    $stmt = $conn->prepare($quiz_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $quiz_stats = $stmt->get_result()->fetch_assoc();
    
    // Calculate course progress (assessment + quiz completion)
    $course_progress = 0;
    if ($assessment_stats['has_passed']) {
        $course_progress += 50; // Assessment is 50% of e-learning
    }
    if ($quiz_stats['has_passed']) {
        $course_progress += 50; // Quiz is 50% of e-learning
    }
    
    $stats = array_merge($appointments_stats, [
        'assessment_attempts' => $assessment_stats['total_attempts'] ?? 0,
        'assessment_best_score' => $assessment_stats['best_score'] ?? 0,
        'assessment_passed' => $assessment_stats['has_passed'] ?? 0,
        'quiz_attempts' => $quiz_stats['total_attempts'] ?? 0,
        'quiz_best_score' => $quiz_stats['best_score'] ?? 0,
        'quiz_passed' => $quiz_stats['has_passed'] ?? 0,
        'course_progress' => $course_progress
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching user stats: " . $e->getMessage());
    $stats = [
        'total_appointments' => 0,
        'confirmed_appointments' => 0,
        'pending_appointments' => 0,
        'completed_appointments' => 0,
        'assessment_attempts' => 0,
        'assessment_best_score' => 0,
        'assessment_passed' => 0,
        'quiz_attempts' => 0,
        'quiz_best_score' => 0,
        'quiz_passed' => 0,
        'course_progress' => 0
    ];
}

// Generate content
ob_start();
?>

<!-- Stats Overview -->
<div class="stats-container">
    <div class="stat-card">
        <div class="icon" style="background-color: rgba(75, 192, 192, 0.2); color: #4bc0c0;">
            <i class="fas fa-tasks"></i>
        </div>
        <div class="value"><?php echo $stats['course_progress']; ?>%</div>
        <div class="label">E-Learning Progress</div>
    </div>
    
    <div class="stat-card">
        <div class="icon" style="background-color: rgba(255, 205, 86, 0.2); color: #ffcc00;">
            <i class="fas fa-calendar"></i>
        </div>
        <div class="value"><?php echo $stats['total_appointments']; ?></div>
        <div class="label">Total Appointments</div>
    </div>
    
    <div class="stat-card">
        <div class="icon" style="background-color: rgba(54, 162, 235, 0.2); color: #36a2eb;">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <div class="value"><?php echo $stats['assessment_passed'] ? $stats['assessment_best_score'] . '%' : 'Not Passed'; ?></div>
        <div class="label">Assessment Score</div>
    </div>
    
    <div class="stat-card">
        <div class="icon" style="background-color: rgba(153, 102, 255, 0.2); color: #9966ff;">
            <i class="fas fa-book"></i>
        </div>
        <div class="value"><?php echo $stats['quiz_passed'] ? $stats['quiz_best_score'] . '%' : 'Not Passed'; ?></div>
        <div class="label">Quiz Score</div>
    </div>
</div>

<!-- Main content grid -->
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <!-- Left column -->
    <div>
        <!-- Scheduled Training Card -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-calendar-alt"></i> My Appointments</h3>
            </div>
            <?php
            // Fetch user's upcoming appointments
            try {
                $appointments_query = "SELECT 
                    a.id,
                    a.appointment_date,
                    a.start_time,
                    a.status,
                    a.course_selection,
                    at.name as type_name,
                    u.full_name as instructor_name
                    FROM appointments a
                    LEFT JOIN appointment_types at ON a.appointment_type_id = at.id
                    LEFT JOIN instructors inst ON a.instructor_id = inst.id
                    LEFT JOIN users u ON inst.user_id = u.id
                    WHERE a.student_id = ?
                    ORDER BY a.appointment_date DESC, a.start_time DESC
                    LIMIT 3";
                
                $stmt = $conn->prepare($appointments_query);
                if (!$stmt) {
                    throw new Exception("Query preparation failed: " . $conn->error);
                }
                
                $stmt->bind_param("i", $user_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Query execution failed: " . $stmt->error);
                }
                
                $appointments_result = $stmt->get_result();
                
                if (!$appointments_result) {
                    throw new Exception("Failed to get result: " . $stmt->error);
                }
                
                if ($appointments_result->num_rows > 0) {
                    $count = 0;
                    while ($appointment = $appointments_result->fetch_assoc()) {
                        if ($count > 0) {
                            echo '<hr style="border: none; border-top: 1px solid #3a3f48; margin: 15px 0;">';
                        }
                        
                        $status_class = '';
                        $status_text = ucfirst($appointment['status']);
                        if ($appointment['status'] == 'confirmed') {
                            $status_class = 'badge-success';
                        } elseif ($appointment['status'] == 'pending') {
                            $status_class = 'badge-warning';
                        } elseif ($appointment['status'] == 'completed') {
                            $status_class = 'badge-info';
                        } else {
                            $status_class = 'badge-danger';
                        }
                        
                        $date = date('F j, Y', strtotime($appointment['appointment_date']));
                        $time = date('g:i A', strtotime($appointment['start_time']));
                        
                        // Display course name or type_name
                        $display_name = $appointment['type_name'] ? $appointment['type_name'] : $appointment['course_selection'];
                        
                        echo '<div style="margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <div>
                                    <div style="font-size: 16px; font-weight: 500;">' . htmlspecialchars($display_name) . '</div>
                                    <div style="font-size: 13px; color: #8b8d93;">' . $time . ', ' . $date . '</div>
                                    ' . ($appointment['instructor_name'] ? '<div style="font-size: 12px; color: #ffcc00;"><i class="fas fa-user"></i> ' . htmlspecialchars($appointment['instructor_name']) . '</div>' : '') . '
                                </div>
                                <div>
                                    <span class="badge ' . $status_class . '">' . $status_text . '</span>
                                </div>
                            </div>
                        </div>';
                        $count++;
                    }
                } else {
                    echo '<div style="text-align: center; padding: 20px; color: #8b8d93;">
                        <i class="fas fa-calendar-times" style="font-size: 48px; margin-bottom: 10px; opacity: 0.3;"></i>
                        <p>No appointments scheduled yet</p>
                        <a href="appointments.php" class="btn" style="display: inline-block; margin-top: 10px; padding: 8px 16px; background: #ffcc00; color: #1e2128; text-decoration: none; border-radius: 5px;">Book Appointment</a>
                    </div>';
                }
            } catch (Exception $e) {
                echo '<div style="text-align: center; padding: 20px; color: #f5576c;">';
                echo 'Error loading appointments<br>';
                echo '<small style="font-size: 11px;">Error: ' . htmlspecialchars($e->getMessage()) . '</small><br>';
                echo '<small style="font-size: 11px;">SQL Error: ' . htmlspecialchars($conn->error) . '</small>';
                echo '</div>';
                error_log("Dashboard appointments error: " . $e->getMessage());
                error_log("SQL Error: " . $conn->error);
            }
            ?>
        </div>
    </div>
    
    <!-- Right column -->
    <div>
        <!-- E-Learning Progress Card -->
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-graduation-cap"></i> E-Learning Progress</h3>
            </div>
            
            <!-- Assessment Status -->
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <div>
                        <div style="font-size: 16px; font-weight: 500;">📝 Assessment Test</div>
                        <div style="font-size: 13px; color: #8b8d93;">20 Questions • 70% to Pass</div>
                    </div>
                    <?php if ($stats['assessment_passed']): ?>
                        <span class="badge badge-success"><i class="fas fa-check"></i> Passed</span>
                    <?php elseif ($stats['assessment_attempts'] > 0): ?>
                        <span class="badge badge-warning">In Progress</span>
                    <?php else: ?>
                        <span class="badge" style="background-color: #8b8d93;">Not Started</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($stats['assessment_attempts'] > 0): ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span style="font-size: 12px; color: #8b8d93;">Best Score:</span>
                        <span style="font-size: 12px; font-weight: 600; color: <?php echo $stats['assessment_passed'] ? '#4caf50' : '#f5576c'; ?>">
                            <?php echo $stats['assessment_best_score']; ?>%
                        </span>
                    </div>
                    <div style="font-size: 11px; color: #8b8d93; margin-bottom: 8px;">
                        Attempts: <?php echo $stats['assessment_attempts']; ?>
                    </div>
                <?php endif; ?>
                
                <div class="progress" style="height: 10px; background-color: #3a3f48; border-radius: 5px;">
                    <div style="height: 100%; width: <?php echo $stats['assessment_passed'] ? '100' : ($stats['assessment_best_score'] ?? 0); ?>%; background-color: <?php echo $stats['assessment_passed'] ? '#4caf50' : '#667eea'; ?>; border-radius: 5px; transition: width 0.3s;"></div>
                </div>
            </div>
            
            <hr style="border: none; border-top: 1px solid #3a3f48; margin: 15px 0;">
            
            <!-- Quiz Status -->
            <div style="margin-bottom: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                    <div>
                        <div style="font-size: 16px; font-weight: 500;">📚 Final Quiz</div>
                        <div style="font-size: 13px; color: #8b8d93;">50 Questions • 70% to Pass</div>
                    </div>
                    <?php if ($stats['quiz_passed']): ?>
                        <span class="badge badge-success"><i class="fas fa-check"></i> Passed</span>
                    <?php elseif (!$stats['assessment_passed']): ?>
                        <span class="badge" style="background-color: #8b8d93;"><i class="fas fa-lock"></i> Locked</span>
                    <?php elseif ($stats['quiz_attempts'] > 0): ?>
                        <span class="badge badge-warning">In Progress</span>
                    <?php else: ?>
                        <span class="badge badge-info">Available</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($stats['quiz_attempts'] > 0): ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span style="font-size: 12px; color: #8b8d93;">Best Score:</span>
                        <span style="font-size: 12px; font-weight: 600; color: <?php echo $stats['quiz_passed'] ? '#4caf50' : '#f5576c'; ?>">
                            <?php echo $stats['quiz_best_score']; ?>%
                        </span>
                    </div>
                    <div style="font-size: 11px; color: #8b8d93; margin-bottom: 8px;">
                        Attempts: <?php echo $stats['quiz_attempts']; ?>
                    </div>
                <?php elseif (!$stats['assessment_passed']): ?>
                    <div style="font-size: 12px; color: #8b8d93; margin-bottom: 8px;">
                        <i class="fas fa-info-circle"></i> Complete assessment first to unlock
                    </div>
                <?php endif; ?>
                
                <div class="progress" style="height: 10px; background-color: #3a3f48; border-radius: 5px;">
                    <div style="height: 100%; width: <?php echo $stats['quiz_passed'] ? '100' : ($stats['quiz_best_score'] ?? 0); ?>%; background-color: <?php echo $stats['quiz_passed'] ? '#4caf50' : '#f5576c'; ?>; border-radius: 5px; transition: width 0.3s;"></div>
                </div>
            </div>
            
            <hr style="border: none; border-top: 1px solid #3a3f48; margin: 15px 0;">
            
            <div style="text-align: center;">
                <a href="e-learning.php" class="btn" style="display: inline-block; padding: 10px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; text-decoration: none; border-radius: 5px; transition: transform 0.2s;">
                    <i class="fas fa-book-reader"></i> Go to E-Learning
                </a>
            </div>
        </div>
        
        <!-- Quick Actions Card -->
        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <a href="appointments.php" class="btn" style="display: flex; flex-direction: column; align-items: center; padding: 15px; background: #2c3038; color: white; text-decoration: none; border-radius: 8px; transition: background 0.3s;">
                    <i class="fas fa-calendar-plus" style="font-size: 24px; margin-bottom: 8px; color: #ffcc00;"></i>
                    <span style="font-size: 13px;">Book Appointment</span>
                </a>
                
                <a href="simulation.php" class="btn" style="display: flex; flex-direction: column; align-items: center; padding: 15px; background: #2c3038; color: white; text-decoration: none; border-radius: 8px; transition: background 0.3s;">
                    <i class="fas fa-car" style="font-size: 24px; margin-bottom: 8px; color: #FF5722;"></i>
                    <span style="font-size: 13px;">Start Simulation</span>
                </a>
                
                <a href="../view_quiz_results.php" class="btn" style="display: flex; flex-direction: column; align-items: center; padding: 15px; background: #2c3038; color: white; text-decoration: none; border-radius: 8px; transition: background 0.3s;">
                    <i class="fas fa-chart-line" style="font-size: 24px; margin-bottom: 8px; color: #2196F3;"></i>
                    <span style="font-size: 13px;">View Results</span>
                </a>
                
                <a href="simulation_result.php" class="btn" style="display: flex; flex-direction: column; align-items: center; padding: 15px; background: #2c3038; color: white; text-decoration: none; border-radius: 8px; transition: background 0.3s;">
                    <i class="fas fa-trophy" style="font-size: 24px; margin-bottom: 8px; color: #9C27B0;"></i>
                    <span style="font-size: 13px;">Sim Results</span>
                </a>
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