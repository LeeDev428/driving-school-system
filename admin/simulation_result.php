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
$page_title = "Simulation Results";
$header_title = "Student Simulation Results";

// Get all simulation results with student info
try {
    $query = "SELECT 
                qr.session_id,
                u.full_name as student_name,
                u.id as student_id,
                COUNT(*) as total_questions,
                SUM(CASE WHEN qr.is_correct = 1 THEN 1 ELSE 0 END) as correct_answers,
                SUM(qr.points_earned) as total_points,
                ROUND((SUM(CASE WHEN qr.is_correct = 1 THEN 1 ELSE 0 END) / COUNT(*)) * 100, 1) as accuracy_percentage,
                MIN(qr.answered_at) as session_date
              FROM quiz_responses qr 
              JOIN users u ON qr.user_id = u.id 
              WHERE u.user_type = 'student'
              GROUP BY qr.session_id, u.id, u.full_name
              ORDER BY qr.session_id DESC";
    
    $result = $conn->query($query);
    $simulation_results = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    
    // Get overall statistics
    $stats_query = "SELECT 
                      COUNT(DISTINCT session_id) as total_sessions,
                      COUNT(DISTINCT user_id) as total_students,
                      AVG(points_earned) as avg_points
                    FROM quiz_responses";
    $stats_result = $conn->query($stats_query);
    $stats = $stats_result ? $stats_result->fetch_assoc() : [];
    
} catch (Exception $e) {
    $simulation_results = [];
    $stats = ['total_sessions' => 0, 'total_students' => 0, 'avg_points' => 0];
    $error_message = "Error loading data: " . $e->getMessage();
}

// Generate content
ob_start();
?>

<div style="padding: 20px;">
    <!-- Page Header -->
    <div style="background: #2c3e50; border-radius: 10px; padding: 20px; margin-bottom: 30px;">
        <h2 style="color: #ffcc00; margin: 0; font-size: 24px;">
            <i class="fas fa-chart-bar"></i> Student Simulation Results
        </h2>
        <p style="color: #ecf0f1; margin: 10px 0 0 0;">
            View and analyze all student driving simulation performance
        </p>
    </div>

    <!-- Quick Stats -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 30px;">
        <div style="background: #34495e; border-radius: 10px; padding: 20px; text-align: center;">
            <h3 style="color: #ffcc00; font-size: 28px; margin: 0;"><?php echo $stats['total_sessions'] ?? 0; ?></h3>
            <p style="color: #bdc3c7; margin: 5px 0 0 0;">Total Sessions</p>
        </div>
        <div style="background: #34495e; border-radius: 10px; padding: 20px; text-align: center;">
            <h3 style="color: #ffcc00; font-size: 28px; margin: 0;"><?php echo $stats['total_students'] ?? 0; ?></h3>
            <p style="color: #bdc3c7; margin: 5px 0 0 0;">Active Students</p>
        </div>
        <div style="background: #34495e; border-radius: 10px; padding: 20px; text-align: center;">
            <h3 style="color: #ffcc00; font-size: 28px; margin: 0;"><?php echo round($stats['avg_points'] ?? 0, 1); ?></h3>
            <p style="color: #bdc3c7; margin: 5px 0 0 0;">Average Points</p>
        </div>
    </div>

    <!-- Results Table -->
    <div style="background: #2c3e50; border-radius: 10px; overflow: hidden;">
        <div style="padding: 20px; border-bottom: 2px solid #34495e;">
            <h3 style="color: #ffcc00; margin: 0; font-size: 18px;">
                <i class="fas fa-table"></i> All Simulation Results
            </h3>
        </div>
        
        <?php if (!empty($simulation_results)): ?>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #34495e;">
                            <th style="padding: 15px; text-align: left; color: #ecf0f1; border-bottom: 1px solid #455A6B;">Session ID</th>
                            <th style="padding: 15px; text-align: left; color: #ecf0f1; border-bottom: 1px solid #455A6B;">Student Name</th>
                            <th style="padding: 15px; text-align: center; color: #ecf0f1; border-bottom: 1px solid #455A6B;">Questions</th>
                            <th style="padding: 15px; text-align: center; color: #ecf0f1; border-bottom: 1px solid #455A6B;">Correct</th>
                            <th style="padding: 15px; text-align: center; color: #ecf0f1; border-bottom: 1px solid #455A6B;">Accuracy</th>
                            <th style="padding: 15px; text-align: center; color: #ecf0f1; border-bottom: 1px solid #455A6B;">Points</th>
                            <th style="padding: 15px; text-align: center; color: #ecf0f1; border-bottom: 1px solid #455A6B;">Grade</th>
                            <th style="padding: 15px; text-align: center; color: #ecf0f1; border-bottom: 1px solid #455A6B;">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($simulation_results as $index => $result): ?>
                            <?php
                            $accuracy = $result['accuracy_percentage'];
                            $grade = 'F';
                            $grade_color = '#e74c3c';
                            
                            if ($accuracy >= 90) {
                                $grade = 'A';
                                $grade_color = '#27ae60';
                            } elseif ($accuracy >= 80) {
                                $grade = 'B';
                                $grade_color = '#2ecc71';
                            } elseif ($accuracy >= 70) {
                                $grade = 'C';
                                $grade_color = '#f39c12';
                            } elseif ($accuracy >= 60) {
                                $grade = 'D';
                                $grade_color = '#e67e22';
                            }
                            ?>
                            <tr style="<?php echo $index % 2 == 0 ? 'background: #34495e;' : 'background: #2c3e50;'; ?>">
                                <td style="padding: 15px; color: #ecf0f1; border-bottom: 1px solid #455A6B;">
                                    <code style="background: #1a1d24; padding: 5px 8px; border-radius: 4px; font-size: 12px;">
                                        <?php echo htmlspecialchars(substr($result['session_id'], -8)); ?>
                                    </code>
                                </td>
                                <td style="padding: 15px; color: #ecf0f1; border-bottom: 1px solid #455A6B;">
                                    <i class="fas fa-user"></i> 
                                    <?php echo htmlspecialchars($result['student_name']); ?>
                                </td>
                                <td style="padding: 15px; text-align: center; color: #ecf0f1; border-bottom: 1px solid #455A6B;">
                                    <?php echo $result['total_questions']; ?>
                                </td>
                                <td style="padding: 15px; text-align: center; color: #27ae60; border-bottom: 1px solid #455A6B; font-weight: bold;">
                                    <?php echo $result['correct_answers']; ?>
                                </td>
                                <td style="padding: 15px; text-align: center; border-bottom: 1px solid #455A6B;">
                                    <span style="background: <?php echo $accuracy >= 70 ? '#27ae60' : '#e74c3c'; ?>; color: white; padding: 5px 10px; border-radius: 15px; font-weight: bold; font-size: 12px;">
                                        <?php echo $accuracy; ?>%
                                    </span>
                                </td>
                                <td style="padding: 15px; text-align: center; color: #ffcc00; border-bottom: 1px solid #455A6B; font-weight: bold;">
                                    <?php echo $result['total_points']; ?>
                                </td>
                                <td style="padding: 15px; text-align: center; border-bottom: 1px solid #455A6B;">
                                    <span style="background: <?php echo $grade_color; ?>; color: white; padding: 8px 12px; border-radius: 50%; font-weight: bold; font-size: 14px; display: inline-block; width: 35px; height: 35px; line-height: 19px;">
                                        <?php echo $grade; ?>
                                    </span>
                                </td>
                                <td style="padding: 15px; text-align: center; color: #bdc3c7; border-bottom: 1px solid #455A6B; font-size: 12px;">
                                    <?php echo date('M j, Y', strtotime($result['session_date'])); ?>
                                    <br>
                                    <small style="color: #7f8c8d;"><?php echo date('g:i A', strtotime($result['session_date'])); ?></small>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div style="padding: 60px; text-align: center; color: #7f8c8d;">
                <i class="fas fa-inbox" style="font-size: 48px; margin-bottom: 20px; opacity: 0.5;"></i>
                <h3 style="margin: 0 0 10px 0; color: #95a5a6;">No Simulation Results Found</h3>
                <p style="margin: 0;">No students have completed any driving simulations yet.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Legend -->
    <div style="background: #34495e; border-radius: 10px; padding: 20px; margin-top: 20px;">
        <h4 style="color: #ffcc00; margin: 0 0 15px 0; font-size: 16px;">
            <i class="fas fa-info-circle"></i> Grading Scale
        </h4>
        <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px;">
            <div style="text-align: center;">
                <span style="background: #27ae60; color: white; padding: 8px 12px; border-radius: 50%; font-weight: bold; display: inline-block; width: 35px; height: 35px; line-height: 19px;">A</span>
                <p style="margin: 10px 0 0 0; color: #ecf0f1; font-size: 12px;">90-100%</p>
            </div>
            <div style="text-align: center;">
                <span style="background: #2ecc71; color: white; padding: 8px 12px; border-radius: 50%; font-weight: bold; display: inline-block; width: 35px; height: 35px; line-height: 19px;">B</span>
                <p style="margin: 10px 0 0 0; color: #ecf0f1; font-size: 12px;">80-89%</p>
            </div>
            <div style="text-align: center;">
                <span style="background: #f39c12; color: white; padding: 8px 12px; border-radius: 50%; font-weight: bold; display: inline-block; width: 35px; height: 35px; line-height: 19px;">C</span>
                <p style="margin: 10px 0 0 0; color: #ecf0f1; font-size: 12px;">70-79%</p>
            </div>
            <div style="text-align: center;">
                <span style="background: #e67e22; color: white; padding: 8px 12px; border-radius: 50%; font-weight: bold; display: inline-block; width: 35px; height: 35px; line-height: 19px;">D</span>
                <p style="margin: 10px 0 0 0; color: #ecf0f1; font-size: 12px;">60-69%</p>
            </div>
            <div style="text-align: center;">
                <span style="background: #e74c3c; color: white; padding: 8px 12px; border-radius: 50%; font-weight: bold; display: inline-block; width: 35px; height: 35px; line-height: 19px;">F</span>
                <p style="margin: 10px 0 0 0; color: #ecf0f1; font-size: 12px;">Below 60%</p>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Include the main layout template
include "../layouts/main_layout.php";
?>
