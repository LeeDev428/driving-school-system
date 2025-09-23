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
$page_title = "Students Management";
$header_title = "Students Management";

// Get all students with their statistics
try {
    // Main students query
    $students_query = "SELECT 
                        u.id,
                        u.full_name,
                        u.email,
                        u.contact_number,
                        u.license_type,
                        u.status,
                        u.created_at,
                        u.profile_image,
                        COUNT(DISTINCT a.id) as total_appointments,
                        COUNT(DISTINCT CASE WHEN a.status = 'completed' THEN a.id END) as completed_lessons,
                        COUNT(DISTINCT qr.session_id) as simulation_sessions,
                        COALESCE(AVG(CASE WHEN qr.is_correct = 1 THEN 100 ELSE 0 END), 0) as avg_simulation_score
                      FROM users u
                      LEFT JOIN appointments a ON u.id = a.student_id
                      LEFT JOIN quiz_responses qr ON u.id = qr.user_id
                      WHERE u.user_type = 'student'
                      GROUP BY u.id, u.full_name, u.email, u.contact_number, u.license_type, u.status, u.created_at, u.profile_image
                      ORDER BY u.created_at DESC";
    
    $students_result = $conn->query($students_query);
    $students = $students_result ? $students_result->fetch_all(MYSQLI_ASSOC) : [];
    
    // Get overall statistics
    $stats_query = "SELECT 
                      COUNT(*) as total_students,
                      COUNT(CASE WHEN status = 'active' THEN 1 END) as active_students,
                      COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_students,
                      COUNT(CASE WHEN created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 END) as new_students_month
                    FROM users 
                    WHERE user_type = 'student'";
    $stats_result = $conn->query($stats_query);
    $stats = $stats_result ? $stats_result->fetch_assoc() : [];
    
} catch (Exception $e) {
    $students = [];
    $stats = ['total_students' => 0, 'active_students' => 0, 'inactive_students' => 0, 'new_students_month' => 0];
    $error_message = "Error loading student data: " . $e->getMessage();
}

// Generate content
ob_start();
?>

<div style="padding: 20px;">
    <!-- Page Header -->
    <div style="background: #2c3e50; border-radius: 10px; padding: 20px; margin-bottom: 30px;">
        <h2 style="color: #ffcc00; margin: 0; font-size: 24px;">
            <i class="fas fa-graduation-cap"></i> Students Management
        </h2>
        <p style="color: #ecf0f1; margin: 10px 0 0 0;">
            Manage and view all enrolled students in the driving school
        </p>
    </div>

    <!-- Quick Stats -->
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 30px;">
        <div style="background: #34495e; border-radius: 10px; padding: 20px; text-align: center;">
            <h3 style="color: #ffcc00; font-size: 28px; margin: 0;"><?php echo $stats['total_students'] ?? 0; ?></h3>
            <p style="color: #bdc3c7; margin: 5px 0 0 0;">Total Students</p>
        </div>
        <div style="background: #27ae60; border-radius: 10px; padding: 20px; text-align: center;">
            <h3 style="color: white; font-size: 28px; margin: 0;"><?php echo $stats['active_students'] ?? 0; ?></h3>
            <p style="color: #ecf0f1; margin: 5px 0 0 0;">Active Students</p>
        </div>
        <div style="background: #e74c3c; border-radius: 10px; padding: 20px; text-align: center;">
            <h3 style="color: white; font-size: 28px; margin: 0;"><?php echo $stats['inactive_students'] ?? 0; ?></h3>
            <p style="color: #ecf0f1; margin: 5px 0 0 0;">Inactive Students</p>
        </div>
        <div style="background: #3498db; border-radius: 10px; padding: 20px; text-align: center;">
            <h3 style="color: white; font-size: 28px; margin: 0;"><?php echo $stats['new_students_month'] ?? 0; ?></h3>
            <p style="color: #ecf0f1; margin: 5px 0 0 0;">New This Month</p>
        </div>
    </div>

    <!-- Students Table -->
    <div style="background: #2c3e50; border-radius: 10px; overflow: hidden;">
        <div style="padding: 20px; border-bottom: 2px solid #34495e;">
            <h3 style="color: #ffcc00; margin: 0; font-size: 18px;">
                <i class="fas fa-users"></i> All Students
            </h3>
        </div>
        
        <?php if (!empty($students)): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #34495e;">
                            <th style="padding: 15px; text-align: left; color: #ecf0f1; border-bottom: 1px solid #455A6B;">Student</th>
                            <th style="padding: 15px; text-align: left; color: #ecf0f1; border-bottom: 1px solid #455A6B;">Contact</th>
                            <th style="padding: 15px; text-align: center; color: #ecf0f1; border-bottom: 1px solid #455A6B;">License Type</th>
                            <th style="padding: 15px; text-align: center; color: #ecf0f1; border-bottom: 1px solid #455A6B;">Status</th>
                            <th style="padding: 15px; text-align: center; color: #ecf0f1; border-bottom: 1px solid #455A6B;">Appointments</th>
                            <th style="padding: 15px; text-align: center; color: #ecf0f1; border-bottom: 1px solid #455A6B;">Completed</th>
                            <th style="padding: 15px; text-align: center; color: #ecf0f1; border-bottom: 1px solid #455A6B;">Simulations</th>
                            <th style="padding: 15px; text-align: center; color: #ecf0f1; border-bottom: 1px solid #455A6B;">Avg Score</th>
                            <th style="padding: 15px; text-align: center; color: #ecf0f1; border-bottom: 1px solid #455A6B;">Enrolled</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $index => $student): ?>
                            <tr style="<?php echo $index % 2 == 0 ? 'background: #34495e;' : 'background: #2c3e50;'; ?>">
                                <!-- Student Name & Email -->
                                <td style="padding: 15px; color: #ecf0f1; border-bottom: 1px solid #455A6B;">
                                    <div style="display: flex; align-items: center;">
                                        <?php if ($student['profile_image']): ?>
                                            <img src="../<?php echo htmlspecialchars($student['profile_image']); ?>" 
                                                 style="width: 40px; height: 40px; border-radius: 50%; margin-right: 12px; object-fit: cover;">
                                        <?php else: ?>
                                            <div style="width: 40px; height: 40px; border-radius: 50%; background: #95a5a6; margin-right: 12px; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <div style="font-weight: bold; margin-bottom: 3px;">
                                                <?php echo htmlspecialchars($student['full_name']); ?>
                                            </div>
                                            <div style="font-size: 12px; color: #bdc3c7;">
                                                <?php echo htmlspecialchars($student['email']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                
                                <!-- Contact -->
                                <td style="padding: 15px; color: #ecf0f1; border-bottom: 1px solid #455A6B;">
                                    <?php if ($student['contact_number']): ?>
                                        <i class="fas fa-phone" style="color: #27ae60; margin-right: 5px;"></i>
                                        <?php echo htmlspecialchars($student['contact_number']); ?>
                                    <?php else: ?>
                                        <span style="color: #7f8c8d; font-style: italic;">No contact</span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- License Type -->
                                <td style="padding: 15px; text-align: center; border-bottom: 1px solid #455A6B;">
                                    <?php if ($student['license_type']): ?>
                                        <span style="background: #3498db; color: white; padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: bold;">
                                            <?php echo htmlspecialchars(strtoupper($student['license_type'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #7f8c8d; font-style: italic;">Not set</span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Status -->
                                <td style="padding: 15px; text-align: center; border-bottom: 1px solid #455A6B;">
                                    <span style="background: <?php echo $student['status'] == 'active' ? '#27ae60' : '#e74c3c'; ?>; color: white; padding: 5px 10px; border-radius: 15px; font-size: 12px; font-weight: bold;">
                                        <?php echo ucfirst($student['status']); ?>
                                    </span>
                                </td>
                                
                                <!-- Total Appointments -->
                                <td style="padding: 15px; text-align: center; color: #3498db; border-bottom: 1px solid #455A6B; font-weight: bold;">
                                    <?php echo $student['total_appointments']; ?>
                                </td>
                                
                                <!-- Completed Lessons -->
                                <td style="padding: 15px; text-align: center; color: #27ae60; border-bottom: 1px solid #455A6B; font-weight: bold;">
                                    <?php echo $student['completed_lessons']; ?>
                                </td>
                                
                                <!-- Simulation Sessions -->
                                <td style="padding: 15px; text-align: center; color: #f39c12; border-bottom: 1px solid #455A6B; font-weight: bold;">
                                    <?php echo $student['simulation_sessions']; ?>
                                </td>
                                
                                <!-- Average Score -->
                                <td style="padding: 15px; text-align: center; border-bottom: 1px solid #455A6B;">
                                    <?php if ($student['simulation_sessions'] > 0): ?>
                                        <?php $avg_score = round($student['avg_simulation_score'], 1); ?>
                                        <span style="background: <?php echo $avg_score >= 70 ? '#27ae60' : '#e74c3c'; ?>; color: white; padding: 5px 8px; border-radius: 10px; font-weight: bold; font-size: 12px;">
                                            <?php echo $avg_score; ?>%
                                        </span>
                                    <?php else: ?>
                                        <span style="color: #7f8c8d; font-style: italic;">No data</span>
                                    <?php endif; ?>
                                </td>
                                
                                <!-- Enrolled Date -->
                                <td style="padding: 15px; text-align: center; color: #bdc3c7; border-bottom: 1px solid #455A6B; font-size: 12px;">
                                    <?php echo date('M j, Y', strtotime($student['created_at'])); ?>
                                    <br>
                                    <small style="color: #7f8c8d;"><?php echo date('g:i A', strtotime($student['created_at'])); ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="padding: 60px; text-align: center; color: #7f8c8d;">
                <i class="fas fa-user-times" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                <h3 style="margin: 0 0 10px 0; color: #95a5a6;">No Students Found</h3>
                <p style="margin: 0;">No students have registered in the driving school yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Student Progress Summary -->
    <?php if (!empty($students)): ?>
    <div style="background: #34495e; border-radius: 10px; padding: 20px; margin-top: 20px;">
        <h4 style="color: #ffcc00; margin: 0 0 15px 0; font-size: 16px;">
            <i class="fas fa-chart-line"></i> Progress Summary
        </h4>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
            <div style="text-align: center;">
                <h5 style="color: #ecf0f1; margin: 0 0 5px 0;">Students with Appointments</h5>
                <p style="color: #3498db; font-size: 20px; font-weight: bold; margin: 0;">
                    <?php echo count(array_filter($students, function($s) { return $s['total_appointments'] > 0; })); ?>
                </p>
            </div>
            <div style="text-align: center;">
                <h5 style="color: #ecf0f1; margin: 0 0 5px 0;">Students with Completed Lessons</h5>
                <p style="color: #27ae60; font-size: 20px; font-weight: bold; margin: 0;">
                    <?php echo count(array_filter($students, function($s) { return $s['completed_lessons'] > 0; })); ?>
                </p>
            </div>
            <div style="text-align: center;">
                <h5 style="color: #ecf0f1; margin: 0 0 5px 0;">Students with Simulations</h5>
                <p style="color: #f39c12; font-size: 20px; font-weight: bold; margin: 0;">
                    <?php echo count(array_filter($students, function($s) { return $s['simulation_sessions'] > 0; })); ?>
                </p>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();

// Include the main layout template
include "../layouts/main_layout.php";
?>
