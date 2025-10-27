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
$page_title = "E-Learning";
$header_title = "E-Learning Portal";
$notification_count = 2;
$user_id = $_SESSION["id"];

// Check if user has passed assessment (to unlock quiz)
$assessment_passed = false;
$check_sql = "SELECT passed FROM user_assessment_sessions 
              WHERE user_id = ? AND status = 'completed' AND passed = 1 
              ORDER BY time_completed DESC LIMIT 1";
if ($stmt = mysqli_prepare($conn, $check_sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($result)) {
        $assessment_passed = true;
    }
    mysqli_stmt_close($stmt);
}

// Get video tutorials
$videos_sql = "SELECT * FROM elearning_videos WHERE status = 'Active' ORDER BY created_at";
$videos = [];
if ($result = mysqli_query($conn, $videos_sql)) {
    while ($row = mysqli_fetch_assoc($result)) {
        $videos[] = $row;
    }
}

ob_start();
?>

<div class="elearning-container">
    <!-- Main Course Selection -->
    <div class="course-selection-header">
        <h1 style="text-align: center; margin-bottom: 10px; color: #2c3e50;">Choose Your Learning Path</h1>
        <p style="text-align: center; color: #7f8c8d; margin-bottom: 30px;">Select between theory-based learning or practical driving simulation</p>
    </div>

    <!-- Course Type Selection Cards -->
    <div class="course-type-grid">
        <!-- E-Learning (TDC) Card -->
        <div class="course-type-card" id="elearning-card">
            <div class="course-card-icon">
                <i class="fas fa-book" style="font-size: 60px; color: #3498db;"></i>
            </div>
            <h2>E-Learning (TDC)</h2>
            <p class="course-subtitle">Theoretical Driving Course</p>
            <p class="course-description">Learn road safety rules, traffic signs, and driving theory through interactive modules, videos, and quizzes.</p>
            <ul class="course-features">
                 <li><i class="fas fa-check-circle"></i> E-Modules</li>
                <li><i class="fas fa-check-circle"></i> Video Tutorials</li>
                <li><i class="fas fa-check-circle"></i> Quizzes & Assessments</li>
                <li><i class="fas fa-check-circle"></i> Progress Tracking</li>
            </ul>
            <button class="course-select-btn" onclick="showELearningContent()">
                <i class="fas fa-graduation-cap"></i> Start E-Learning
            </button>
        </div>

        <!-- Simulation (PDC) Card -->
        <div class="course-type-card" id="simulation-card">
            <div class="course-card-icon">
                <i class="fas fa-car" style="font-size: 60px; color: #e74c3c;"></i>
            </div>
            <h2>Simulation (PDC)</h2>
            <p class="course-subtitle">Practical Driving Course</p>
            <p class="course-description">Practice real-world driving scenarios in a safe virtual environment. Test your skills with interactive simulations.</p>
            <ul class="course-features">
                <li><i class="fas fa-check-circle"></i> Car Simulation</li>
                <li><i class="fas fa-check-circle"></i> Motorcycle Simulation</li>
                <li><i class="fas fa-check-circle"></i> Traffic Scenarios</li>
                <li><i class="fas fa-check-circle"></i> Instant Feedback</li>
            </ul>
            <button class="course-select-btn" onclick="window.location.href='simulation.php'">
                <i class="fas fa-gamepad"></i> Start Simulation
            </button>
        </div>
    </div>

    <!-- E-Learning Content (Hidden by default) -->
    <div id="elearning-content" style="display: none;">
        <div style="margin-bottom: 20px;">
            <button class="back-btn" onclick="showCourseSelection()">
                <i class="fas fa-arrow-left"></i> Back to Course Selection
            </button>
        </div>

        <!-- Tab Navigation -->
        <div class="course-tabs" style="display: flex; justify-content: center; margin-bottom: 30px; border-bottom: 2px solid #e0e0e0;">
            <button class="tab-btn" onclick="window.location.href='e-learning-module/module/index.html'" style="padding: 15px 40px; background: none; border: none; font-size: 16px; font-weight: 600; color: #2c3e50; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.3s;">
                <i class="fas fa-book-open"></i> E-Modules
            </button>
            <button class="tab-btn" onclick="switchTab('videos')" style="padding: 15px 40px; background: none; border: none; font-size: 16px; font-weight: 600; color: #7f8c8d; cursor: pointer; border-bottom: 3px solid transparent; transition: all 0.3s;">
                <i class="fas fa-video"></i> Video Tutorials
            </button>
            <button class="tab-btn active" onclick="switchTab('quizzes')" style="padding: 15px 40px; background: none; border: none; font-size: 16px; font-weight: 600; color: #2c3e50; cursor: pointer; border-bottom: 3px solid #667eea; transition: all 0.3s;">
                <i class="fas fa-clipboard-check"></i> Quizzes & Assessments
            </button>
        </div>

    <!-- Video Tutorials Tab -->
    <div id="videos-tab" class="tab-content" style="display: none;">
        <div class="videos-header" style="text-align: center; margin-bottom: 30px;">
            <h2 style="font-size: 32px; color: #2c3e50; margin-bottom: 10px;">🎥 Video Tutorials</h2>
            <p style="color: #7f8c8d; font-size: 16px;">Learn through interactive video lessons</p>
        </div>
        
        <div class="videos-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 25px;">
            <!-- Video 1: Road Marking Lines -->
            <div class="video-card" style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.1);">
                <div class="video-embed" style="position: relative; padding-top: 56.25%; background: #000;">
                    <iframe src="https://drive.google.com/file/d/1wsqPO5rErOWopnA3l1-UCluLjlzkHLmA/preview" 
                            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;" 
                            allow="autoplay"></iframe>
                </div>
                <div class="video-content" style="padding: 20px;">
                    <h3 style="font-size: 18px; margin-bottom: 10px; color: #2c3e50;">Road Marking Lines</h3>
                    <p style="color: #7f8c8d; font-size: 14px; margin-bottom: 15px;">Learn about different road marking lines and their meanings for safe driving.</p>
                </div>
            </div>
            
            <!-- Video 2: Road Safety -->
            <div class="video-card" style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.1);">
                <div class="video-embed" style="position: relative; padding-top: 56.25%; background: #000;">
                    <iframe src="https://drive.google.com/file/d/1cMrbPnRZDCL7lFMVYQOsmxVjw4aTkbsA/preview" 
                            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;" 
                            allow="autoplay"></iframe>
                </div>
                <div class="video-content" style="padding: 20px;">
                    <h3 style="font-size: 18px; margin-bottom: 10px; color: #2c3e50;">Road Safety</h3>
                    <p style="color: #7f8c8d; font-size: 14px; margin-bottom: 15px;">Essential road safety tips and practices for responsible driving.</p>
                </div>
            </div>
            
            <!-- Video 3: Road Signs -->
            <div class="video-card" style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.1);">
                <div class="video-embed" style="position: relative; padding-top: 56.25%; background: #000;">
                    <iframe src="https://drive.google.com/file/d/1qL3lGNZ5xEvTiGenZYzVwgl8jYkDO12o/preview" 
                            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;" 
                            allow="autoplay"></iframe>
                </div>
                <div class="video-content" style="padding: 20px;">
                    <h3 style="font-size: 18px; margin-bottom: 10px; color: #2c3e50;">Road Signs</h3>
                    <p style="color: #7f8c8d; font-size: 14px; margin-bottom: 15px;">Understanding traffic signs and their importance in road navigation.</p>
                </div>
            </div>
            
            <!-- Video 4: RS-Regulatory Signs -->
            <div class="video-card" style="background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.1);">
                <div class="video-embed" style="position: relative; padding-top: 56.25%; background: #000;">
                    <iframe src="https://drive.google.com/file/d/1YXwLy_U1T8G4uHyyOQ7Zu4--aJhVZxIT/preview" 
                            style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; border: none;" 
                            allow="autoplay"></iframe>
                </div>
                <div class="video-content" style="padding: 20px;">
                    <h3 style="font-size: 18px; margin-bottom: 10px; color: #2c3e50;">RS-Regulatory Signs</h3>
                    <p style="color: #7f8c8d; font-size: 14px; margin-bottom: 15px;">Comprehensive guide to regulatory signs and traffic rules compliance.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quizzes & Assessments Tab -->
    <div id="quizzes-tab" class="tab-content active" style="display: block;">

        <!-- Assessment & Quiz Cards -->
        <div class="assessments-grid" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 30px; max-width: 900px; margin: 0 auto;">
            <!-- Assessment Card -->
            <div class="assessment-quiz-card" style="background: white; border-radius: 15px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); text-align: center; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 30px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)';">
                <div style="font-size: 60px; margin-bottom: 20px;">📝</div>
                <h3 style="font-size: 24px; margin-bottom: 10px; color: #2c3e50;">Assessment</h3>
                <p style="color: #7f8c8d; margin-bottom: 20px;">20 True or False Questions</p>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: left;">
                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                        <i class="fas fa-check-circle" style="color: #28a745; margin-right: 8px;"></i>
                        <span style="font-size: 14px; color: #2c3e50; font-weight: 600;">20 Questions</span>
                    </div>
                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                        <i class="fas fa-clock" style="color: #ffc107; margin-right: 8px;"></i>
                        <span style="font-size: 14px; color: #2c3e50; font-weight: 600;">No Time Limit</span>
                    </div>
                    <div style="display: flex; align-items: center;">
                        <i class="fas fa-trophy" style="color: #667eea; margin-right: 8px;"></i>
                        <span style="font-size: 14px; color: #2c3e50; font-weight: 600;">Passing: 70%</span>
                    </div>
                </div>
                <button onclick="window.location.href='assessments.php'" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 12px 30px; border-radius: 10px; font-size: 16px; font-weight: bold; cursor: pointer; width: 100%; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                    <i class="fas fa-play"></i> Take Assessment
                </button>
                <div style="margin-top: 15px; padding: 10px; background: #e3f2fd; border-radius: 8px;">
                    <small style="color: #1976d2; font-weight: bold;">✓ Must complete first</small>
                </div>
            </div>

            <!-- Quiz Card -->
            <div class="assessment-quiz-card" style="background: white; border-radius: 15px; padding: 30px; box-shadow: 0 5px 20px rgba(0,0,0,0.1); text-align: center; transition: all 0.3s ease; <?php echo !$assessment_passed ? 'opacity: 0.7;' : ''; ?>" onmouseover="this.style.transform='translateY(-5px)'; this.style.boxShadow='0 10px 30px rgba(0,0,0,0.15)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 5px 20px rgba(0,0,0,0.1)';">
                <div style="font-size: 60px; margin-bottom: 20px;"><?php echo $assessment_passed ? '📚' : '🔒'; ?></div>
                <h3 style="font-size: 24px; margin-bottom: 10px; color: #2c3e50;">Quiz</h3>
                <p style="color: #7f8c8d; margin-bottom: 20px;">50 Multiple Choice Questions</p>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 10px; margin-bottom: 20px; text-align: left;">
                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                        <i class="fas fa-check-circle" style="color: #28a745; margin-right: 8px;"></i>
                        <span style="font-size: 14px; color: #2c3e50; font-weight: 600;">50 Questions</span>
                    </div>
                    <div style="display: flex; align-items: center; margin-bottom: 8px;">
                        <i class="fas fa-clock" style="color: #ffc107; margin-right: 8px;"></i>
                        <span style="font-size: 14px; color: #2c3e50; font-weight: 600;">No Time Limit</span>
                    </div>
                    <div style="display: flex; align-items: center;">
                        <i class="fas fa-trophy" style="color: #f5576c; margin-right: 8px;"></i>
                        <span style="font-size: 14px; color: #2c3e50; font-weight: 600;">Passing: 70%</span>
                    </div>
                </div>
                
                <?php if ($assessment_passed): ?>
                    <button onclick="window.location.href='quizzes.php'" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; border: none; padding: 12px 30px; border-radius: 10px; font-size: 16px; font-weight: bold; cursor: pointer; width: 100%; transition: all 0.3s ease;" onmouseover="this.style.transform='translateY(-2px)'" onmouseout="this.style.transform='translateY(0)'">
                        <i class="fas fa-play"></i> Take Quiz
                    </button>
                    <div style="margin-top: 15px; padding: 10px; background: #d4edda; border-radius: 8px;">
                        <small style="color: #155724; font-weight: bold;">✓ Unlocked - Assessment Passed!</small>
                    </div>
                <?php else: ?>
                    <button disabled style="background: #6c757d; color: white; border: none; padding: 12px 30px; border-radius: 10px; font-size: 16px; font-weight: bold; cursor: not-allowed; width: 100%; opacity: 0.6;">
                        <i class="fas fa-lock"></i> Quiz Locked
                    </button>
                    <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 8px;">
                        <small style="color: #856404; font-weight: bold;">⚠ Complete Assessment First</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div> <!-- End elearning-content -->
    
</div> <!-- End elearning-container -->

<?php
$content = ob_get_clean();

$extra_styles = <<<EOT
<style>
.elearning-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 20px;
}

/* Course Type Selection Grid */
.course-type-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 30px;
    margin: 40px auto;
    max-width: 1000px;
}

.course-type-card {
    background: white;
    border-radius: 15px;
    padding: 40px 30px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 2px solid transparent;
}

.course-type-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
    border-color: #ffc107;
}

.course-card-icon {
    margin-bottom: 20px;
}

.course-type-card h2 {
    color: #2c3e50;
    font-size: 28px;
    margin-bottom: 10px;
}

.course-subtitle {
    color: #7f8c8d;
    font-size: 16px;
    margin-bottom: 20px;
}

.course-description {
    color: #555;
    line-height: 1.6;
    margin-bottom: 25px;
    font-size: 15px;
}

.course-features {
    list-style: none;
    padding: 0;
    margin: 25px 0;
    text-align: left;
}

.course-features li {
    padding: 10px 0;
    color: #555;
    border-bottom: 1px solid #ecf0f1;
}

.course-features li:last-child {
    border-bottom: none;
}

.course-features i {
    color: #27ae60;
    margin-right: 10px;
}

.course-select-btn {
    width: 100%;
    padding: 15px 30px;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-top: 20px;
}

#elearning-card .course-select-btn {
    background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
    color: white;
}

#elearning-card .course-select-btn:hover {
    background: linear-gradient(135deg, #2980b9 0%, #21618c 100%);
    transform: scale(1.05);
}

#simulation-card .course-select-btn {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
    color: white;
}

#simulation-card .course-select-btn:hover {
    background: linear-gradient(135deg, #c0392b 0%, #a93226 100%);
    transform: scale(1.05);
}

.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
}

.back-btn:hover {
    background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.back-btn:active {
    transform: translateY(0);
}

.back-btn i {
    margin-right: 0;
    font-size: 16px;
}

.elearning-tabs {
    display: flex;
    background: #1e2129;
    border-radius: 8px;
    padding: 5px;
    margin-bottom: 30px;
    border: 1px solid #3a3f48;
}

.tab-btn {
    flex: 1;
    background: transparent;
    border: none;
    color: #8b8d93;
    padding: 15px 20px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-weight: 500;
}

.tab-btn.active {
    background: #ffcc00;
    color: #1a1d24;
}

.tab-btn:hover:not(.active) {
    background: #2a2f38;
    color: #ffffff;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.modules-header, .videos-header, .quizzes-header {
    text-align: center;
    margin-bottom: 30px;
}

.modules-header h2, .videos-header h2, .quizzes-header h2 {
    color: #ffffff;
    font-size: 28px;
    margin-bottom: 10px;
}

.modules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 30px;
}

.module-card {
    background: #282c34;
    border: 1px solid #3a3f48;
    border-radius: 12px;
    padding: 25px;
    transition: all 0.3s;
}

.module-card:hover {
    border-color: #ffcc00;
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.3);
}

.module-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(255, 204, 0, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: #ffcc00;
    font-size: 24px;
}

.module-card h3 {
    color: #ffffff;
    font-size: 20px;
    margin-bottom: 15px;
    text-align: center;
}

.module-status {
    text-align: center;
    margin-bottom: 15px;
}

.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
}

.status-badge.active {
    background: rgba(76, 175, 80, 0.2);
    color: #4CAF50;
}

.module-description {
    color: #8b8d93;
    line-height: 1.6;
    margin-bottom: 20px;
    text-align: center;
    font-size: 14px;
}

.module-stats {
    margin-bottom: 20px;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    color: #8b8d93;
    font-size: 13px;
}

.stat-item i {
    color: #ffcc00;
    width: 16px;
}

.progress-section {
    margin-bottom: 25px;
}

.progress-label {
    color: #8b8d93;
    font-size: 12px;
    margin-bottom: 8px;
    text-transform: uppercase;
    font-weight: 500;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #1e2129;
    border-radius: 4px;
    overflow: hidden;
    margin-bottom: 5px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #4CAF50, #8BC34A);
    border-radius: 4px;
    transition: width 0.3s;
}

.progress-text {
    text-align: right;
    color: #4CAF50;
    font-size: 12px;
    font-weight: 600;
}

.module-actions {
    text-align: center;
}

.btn-primary {
    background: #ffcc00;
    color: #1a1d24;
    border: none;
    padding: 12px 24px;
    border-radius: 6px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.btn-primary:hover {
    background: #e6b800;
    transform: translateY(-2px);
}

/* Video Tutorials Styles */
.videos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
    gap: 25px;
}

.video-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: all 0.3s;
}

.video-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.video-embed {
    position: relative;
    padding-top: 56.25%;
    background: #000;
    width: 100%;
}

.video-embed iframe {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    border: none;
}

.video-content {
    padding: 20px;
}

.video-content h3 {
    color: #2c3e50;
    margin-bottom: 10px;
    font-size: 18px;
}

.video-content p {
    color: #7f8c8d;
    font-size: 14px;
    margin-bottom: 15px;
    line-height: 1.5;
}

/* Quiz Styles */
.quizzes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 25px;
}

.quiz-card {
    background: #282c34;
    border: 1px solid #3a3f48;
    border-radius: 12px;
    padding: 25px;
    text-align: center;
    transition: all 0.3s;
}

.quiz-card:hover {
    border-color: #ffcc00;
    transform: translateY(-3px);
}

.quiz-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: rgba(33, 150, 243, 0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    color: #2196F3;
    font-size: 24px;
}

.quiz-card h3 {
    color: #ffffff;
    margin-bottom: 15px;
}

.quiz-card p {
    color: #8b8d93;
    font-size: 14px;
    margin-bottom: 20px;
    line-height: 1.5;
}

.quiz-info {
    margin-bottom: 25px;
}

.info-item {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    margin-bottom: 8px;
    color: #8b8d93;
    font-size: 13px;
}

.info-item i {
    color: #2196F3;
    width: 16px;
}

/* Responsive */
@media (max-width: 768px) {
    .course-type-grid {
        grid-template-columns: 1fr;
        gap: 20px;
        margin: 20px auto;
    }
    
    .course-type-card {
        padding: 30px 20px;
    }
    
    .elearning-tabs {
        flex-direction: column;
    }
    
    .modules-grid, .videos-grid, .quizzes-grid {
        grid-template-columns: 1fr;
    }
    
    .module-card, .video-card, .quiz-card {
        margin-bottom: 20px;
    }
}
</style>
EOT;

$extra_scripts = <<<EOT
<script>
// Show/Hide Course Selection and E-Learning Content
function showCourseSelection() {
    document.querySelector('.course-type-grid').style.display = 'grid';
    document.querySelector('.course-selection-header').style.display = 'block';
    document.getElementById('elearning-content').style.display = 'none';
}

function showELearningContent() {
    document.querySelector('.course-type-grid').style.display = 'none';
    document.querySelector('.course-selection-header').style.display = 'none';
    document.getElementById('elearning-content').style.display = 'block';
}

function switchTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.style.display = 'none';
        tab.classList.remove('active');
    });
    
    // Remove active class from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
        btn.style.color = '#7f8c8d';
        btn.style.borderBottom = '3px solid transparent';
    });
    
    // Show selected tab
    document.getElementById(tabName + '-tab').style.display = 'block';
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Add active class to clicked button
    event.target.classList.add('active');
    event.target.style.color = '#2c3e50';
    event.target.style.borderBottom = '3px solid #667eea';
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    console.log('E-learning portal loaded');
    // Show course selection by default
    showCourseSelection();
});
</script>
EOT;

// Include the main layout template
include "../layouts/main_layout.php";
?>